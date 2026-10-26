<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\CaseModel;
use App\Models\Crm\Contact;
use App\Models\Crm\SlaPolicy;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaseController extends Controller
{
    protected const SORTABLE = ['case_number', 'subject', 'status', 'priority', 'created_at'];

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', '');
        $priorityFilter = (string) $request->query('priority', '');
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $query = CaseModel::where('company_id', $companyId)->where('type', 'case')
            ->with(['account', 'contact', 'assignedTo']);

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }
        if ($priorityFilter !== '') {
            $query->where('priority', $priorityFilter);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")->orWhere('case_number', 'like', "%{$search}%");
            });
        }

        $cases = $query->orderBy($sort, $direction)->paginate(20)->withQueryString();
        $all = CaseModel::where('company_id', $companyId)->where('type', 'case')->get();

        return view('admin.crm.cases.index', [
            'cases' => $cases,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'priorityFilter' => $priorityFilter,
            'sort' => $sort,
            'direction' => $direction,
            'statuses' => CaseModel::STATUSES,
            'priorities' => CaseModel::PRIORITIES,
            'openCount' => $all->whereNotIn('status', ['resolved', 'closed'])->count(),
            'overdueCount' => $all->filter(fn ($c) => $c->is_overdue)->count(),
            'resolvedCount' => $all->where('status', 'resolved')->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.cases.form', $this->formData($companyId, new CaseModel(['type' => $request->query('type', 'case')])));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $this->validated($request);
        $data['company_id'] = $companyId;
        $data['case_number'] = CaseModel::nextCaseNumber($companyId);
        $this->applySla($data, $companyId);

        $case = CaseModel::create($data);

        return redirect()->route('admin.crm.cases.edit', $case)->with('status', 'Case created.');
    }

    public function edit(CaseModel $case): View
    {
        abort_unless($case->company_id === Company::current()->id, 404);

        return view('admin.crm.cases.form', $this->formData($case->company_id, $case));
    }

    public function update(Request $request, CaseModel $case): RedirectResponse
    {
        abort_unless($case->company_id === Company::current()->id, 404);
        $data = $this->validated($request);

        if ($data['status'] === 'resolved' && $case->status !== 'resolved') {
            $data['resolved_at'] = now();
        }

        $case->update($data);

        return redirect()->route('admin.crm.cases.edit', $case)->with('status', 'Case updated.');
    }

    public function destroy(CaseModel $case): RedirectResponse
    {
        abort_unless($case->company_id === Company::current()->id, 404);
        $routeName = 'admin.crm.cases.index';
        if ($case->type === 'complaint') {
            $routeName = 'admin.crm.complaints.index';
        } elseif ($case->type === 'service_request') {
            $routeName = 'admin.crm.service-requests.index';
        }
        $case->delete();

        return redirect()->route($routeName)->with('status', 'Case deleted.');
    }

    public function escalate(Request $request, CaseModel $case): RedirectResponse
    {
        abort_unless($case->company_id === Company::current()->id, 404);

        $data = $request->validate([
            'escalated_to_employee_id' => ['required', 'exists:employees,id'],
            'escalation_reason' => ['nullable', 'string'],
        ]);

        $case->update($data + ['is_escalated' => true, 'escalated_at' => now()]);

        return back()->with('status', 'Case escalated.');
    }

    protected function formData(int $companyId, CaseModel $case): array
    {
        return [
            'case' => $case,
            'accounts' => Account::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'contacts' => Contact::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'employees' => Employee::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'slaPolicies' => SlaPolicy::where('company_id', $companyId)->where('is_active', true)->get(),
            'branches' => \App\Models\Branch::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
        ];
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(CaseModel::TYPES))],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', array_keys(CaseModel::STATUSES))],
            'priority' => ['required', 'in:'.implode(',', array_keys(CaseModel::PRIORITIES))],
            'source' => ['nullable', 'string', 'max:100'],
            'account_id' => ['nullable', 'exists:crm_accounts,id'],
            'contact_id' => ['nullable', 'exists:crm_contacts,id'],
            'assigned_to_employee_id' => ['nullable', 'exists:employees,id'],
            'sla_policy_id' => ['nullable', 'exists:crm_sla_policies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);
    }

    protected function applySla(array &$data, int $companyId): void
    {
        if (empty($data['sla_policy_id'])) {
            return;
        }

        $policy = SlaPolicy::where('company_id', $companyId)->find($data['sla_policy_id']);
        if ($policy) {
            $data['response_due_at'] = now()->addHours($policy->response_hours);
            $data['resolution_due_at'] = now()->addHours($policy->resolution_hours);
        }
    }
}
