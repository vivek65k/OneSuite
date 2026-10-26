<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Activity;
use App\Models\Crm\Contact;
use App\Models\Crm\DealApproval;
use App\Models\Crm\DealApprovalRule;
use App\Models\Crm\Opportunity;
use App\Models\Crm\SalesStage;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OpportunityController extends Controller
{
    use ExportsCsv, ResolvesCurrentEmployee;

    protected const SORTABLE = ['name', 'amount', 'expected_close_date', 'created_at'];

    public function index(Request $request, string $scope = 'all'): View
    {
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'expected_close_date';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = $this->filtered($request, $scope);

        return view('admin.crm.opportunities.index', [
            'scope' => $scope,
            'opportunities' => $query->with(['account', 'stage', 'owner'])->orderBy($sort, $direction)->paginate(25)->withQueryString(),
            'search' => trim((string) $request->query('search', '')),
            'stageId' => $request->query('sales_stage_id'),
            'status' => (string) $request->query('status', ''),
            'sort' => $sort,
            'direction' => $direction,
            'stages' => $this->stageOptions(),
            'counts' => [
                'all' => $this->companyQuery()->count(),
                'mine' => (clone $this->companyQuery())->where('owner_employee_id', optional(auth()->user()->employee)->id)->count(),
            ],
            'totalValue' => (clone $this->companyQuery())->where('status', 'open')->sum('amount'),
            'wonValue' => (clone $this->companyQuery())->where('status', 'won')->sum('amount'),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.opportunities.form', [
            'opportunity' => new Opportunity,
            'accounts' => $this->accountOptions(),
            'contacts' => $this->contactOptions(),
            'stages' => $this->stageOptions(),
            'employees' => $this->employeeOptions(),
            'branches' => $this->branchOptions(),
            'teams' => \App\Models\Crm\Team::where('company_id', Company::current()->id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Opportunity::create($this->validated($request) + ['company_id' => Company::current()->id, 'status' => 'open']);

        return redirect()->route('admin.crm.opportunities.index')->with('status', 'Opportunity created.');
    }

    public function edit(Opportunity $opportunity): View
    {
        $this->ownCompany($opportunity);

        return view('admin.crm.opportunities.form', [
            'opportunity' => $opportunity->load('products', 'competitors'),
            'accounts' => $this->accountOptions(),
            'contacts' => $this->contactOptions(),
            'stages' => $this->stageOptions(),
            'employees' => $this->employeeOptions(),
            'branches' => $this->branchOptions(),
            'teams' => \App\Models\Crm\Team::where('company_id', $opportunity->company_id)->orderBy('name')->get(['id', 'name']),
            'timeline' => Activity::where('company_id', $opportunity->company_id)->where('related_type', Opportunity::class)
                ->where('related_id', $opportunity->id)->with('owner')->orderByDesc('created_at')->get(),
            'dealApprovals' => DealApproval::where('opportunity_id', $opportunity->id)->with(['rule', 'approver'])->get(),
        ]);
    }

    public function update(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $this->ownCompany($opportunity);

        $data = $this->validated($request);
        $data['status'] = $request->input('status', $opportunity->status);

        if ($data['status'] === 'won' && $opportunity->status !== 'won') {
            $blocked = $this->requireApprovalIfNeeded($opportunity, (float) $data['amount']);
            if ($blocked) {
                return back()->withInput()->with('status', 'This deal needs sign-off before it can be marked Won - approval request(s) sent. See Deal Approvals.');
            }
        }

        if (in_array($data['status'], ['won', 'lost'], true) && $opportunity->status === 'open') {
            $data['closed_at'] = now();
        } elseif ($data['status'] === 'open') {
            $data['closed_at'] = null;
            $data['lost_reason'] = null;
        }

        $opportunity->update($data);

        return redirect()->route('admin.crm.opportunities.index')->with('status', 'Opportunity updated.');
    }

    /**
     * Returns true (and creates pending approval requests) if this deal's
     * amount matches any active matrix rule that hasn't been approved yet.
     * A deal is blocked from closing Won until every matching rule's
     * approval is granted.
     */
    protected function requireApprovalIfNeeded(Opportunity $opportunity, float $amount): bool
    {
        $companyId = $opportunity->company_id;
        $rules = DealApprovalRule::where('company_id', $companyId)->where('is_active', true)->get()
            ->filter(fn ($rule) => $rule->matches($amount));

        if ($rules->isEmpty()) {
            return false;
        }

        $existing = DealApproval::where('opportunity_id', $opportunity->id)->get();
        $blocked = false;

        foreach ($rules as $rule) {
            $approval = $existing->firstWhere('rule_id', $rule->id);

            if (! $approval) {
                DealApproval::create([
                    'company_id' => $companyId,
                    'opportunity_id' => $opportunity->id,
                    'rule_id' => $rule->id,
                    'approver_employee_id' => $rule->approver_employee_id,
                    'requested_by_employee_id' => $this->currentEmployee()?->id,
                    'status' => 'pending',
                    'requested_at' => now(),
                ]);
                $blocked = true;
            } elseif ($approval->status !== 'approved') {
                $blocked = true;
            }
        }

        return $blocked;
    }

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        $this->ownCompany($opportunity);
        $opportunity->delete();

        return back()->with('status', 'Opportunity deleted.');
    }

    public function export(Request $request): StreamedResponse
    {
        $opportunities = $this->filtered($request, (string) $request->query('scope', 'all'))->with(['account', 'stage', 'owner'])->get();

        return $this->streamCsv(
            'opportunities.csv',
            ['Name', 'Account', 'Stage', 'Amount', 'Probability %', 'Expected Close', 'Owner', 'Status'],
            (function () use ($opportunities) {
                foreach ($opportunities as $opportunity) {
                    yield [
                        $opportunity->name, $opportunity->account?->name, $opportunity->stage?->name,
                        $opportunity->amount, $opportunity->effective_probability,
                        optional($opportunity->expected_close_date)->format('Y-m-d'),
                        $opportunity->owner?->name, Opportunity::STATUSES[$opportunity->status] ?? $opportunity->status,
                    ];
                }
            })()
        );
    }

    protected function filtered(Request $request, string $scope)
    {
        $search = trim((string) $request->query('search', ''));
        $stageId = $request->query('sales_stage_id');
        $status = (string) $request->query('status', '');

        $query = $this->companyQuery();

        if ($scope === 'mine') {
            $query->where('owner_employee_id', $this->currentEmployee()->id);
        }

        if ($stageId) {
            $query->where('sales_stage_id', $stageId);
        }

        if (in_array($status, array_keys(Opportunity::STATUSES), true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query;
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_id' => ['nullable', 'exists:crm_accounts,id'],
            'contact_id' => ['nullable', 'exists:crm_contacts,id'],
            'sales_stage_id' => ['required', 'exists:crm_sales_stages,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
            'lost_reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'risks_blockers' => ['nullable', 'string', 'max:2000'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'team_id' => ['nullable', 'exists:crm_teams,id'],
        ]);
    }

    protected function branchOptions()
    {
        return \App\Models\Branch::where('company_id', Company::current()->id)->orderBy('name')->pluck('name', 'id');
    }

    protected function accountOptions()
    {
        return Account::where('company_id', Company::current()->id)->orderBy('name')->pluck('name', 'id');
    }

    protected function contactOptions()
    {
        return Contact::where('company_id', Company::current()->id)->orderBy('name')->pluck('name', 'id');
    }

    protected function stageOptions()
    {
        return SalesStage::where('company_id', Company::current()->id)->orderBy('sort_order')->pluck('name', 'id');
    }

    protected function employeeOptions()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->where('status', 'active')
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function companyQuery()
    {
        return Opportunity::query()->where('company_id', Company::current()->id);
    }

    protected function ownCompany(Opportunity $opportunity): void
    {
        abort_unless($opportunity->company_id === Company::current()->id, 404);
    }
}
