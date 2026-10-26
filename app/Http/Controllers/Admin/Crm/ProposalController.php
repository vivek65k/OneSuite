<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Proposal;
use App\Models\Crm\Quotation;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProposalController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $query = Proposal::where('company_id', $companyId)->with(['account', 'owner']);

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where('title', 'like', "%{$search}%");
        }

        return view('admin.crm.proposals.index', [
            'proposals' => $query->latest()->paginate(25)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'total' => Proposal::where('company_id', $companyId)->count(),
            'sentCount' => Proposal::where('company_id', $companyId)->where('status', 'sent')->count(),
            'acceptedCount' => Proposal::where('company_id', $companyId)->where('status', 'accepted')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.proposals.form', ['proposal' => new Proposal, ...$this->formOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Proposal::create($this->validated($request) + ['company_id' => Company::current()->id, 'status' => 'draft']);

        return redirect()->route('admin.crm.proposals.index')->with('status', 'Proposal created.');
    }

    public function edit(Proposal $proposal): View
    {
        $this->ownCompany($proposal);

        return view('admin.crm.proposals.form', ['proposal' => $proposal, ...$this->formOptions()]);
    }

    public function update(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->ownCompany($proposal);
        $proposal->update($this->validated($request));

        return redirect()->route('admin.crm.proposals.index')->with('status', 'Proposal updated.');
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        $this->ownCompany($proposal);
        $proposal->delete();

        return back()->with('status', 'Proposal deleted.');
    }

    public function send(Proposal $proposal): RedirectResponse
    {
        $this->ownCompany($proposal);
        $proposal->update(['status' => 'sent', 'sent_at' => now()]);

        return back()->with('status', 'Proposal marked as sent.');
    }

    public function markDecision(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->ownCompany($proposal);

        $data = $request->validate(['decision' => ['required', 'in:accepted,declined']]);
        $proposal->update(['status' => $data['decision']]);

        return back()->with('status', 'Proposal marked as '.$data['decision'].'.');
    }

    protected function formOptions(): array
    {
        $companyId = Company::current()->id;

        return [
            'accounts' => Account::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
            'quotations' => Quotation::where('company_id', $companyId)->orderBy('quotation_number')->pluck('quotation_number', 'id'),
            'employees' => Employee::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ];
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'account_id' => ['nullable', 'exists:crm_accounts,id'],
            'quotation_id' => ['nullable', 'exists:crm_quotations,id'],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
            'content' => ['nullable', 'string', 'max:20000'],
        ]);
    }

    protected function ownCompany(Proposal $proposal): void
    {
        abort_unless($proposal->company_id === Company::current()->id, 404);
    }
}
