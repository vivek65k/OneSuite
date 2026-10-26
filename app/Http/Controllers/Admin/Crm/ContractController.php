<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Contract;
use App\Models\Crm\SalesOrder;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $query = Contract::where('company_id', $companyId)->with(['account', 'owner']);

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('contract_number', 'like', "%{$search}%"));
        }

        return view('admin.crm.contracts.index', [
            'contracts' => $query->latest()->paginate(25)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'total' => Contract::where('company_id', $companyId)->count(),
            'activeCount' => Contract::where('company_id', $companyId)->where('status', 'active')->count(),
            'totalValue' => Contract::where('company_id', $companyId)->where('status', 'active')->sum('value'),
            'renewalCount' => Contract::where('company_id', $companyId)->where('status', 'active')
                ->whereNotNull('end_date')->where('end_date', '<=', now()->addDays(90))->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $contract = new Contract;

        if ($request->filled('sales_order_id')) {
            $order = SalesOrder::where('company_id', Company::current()->id)->find($request->query('sales_order_id'));

            if ($order) {
                $contract->sales_order_id = $order->id;
                $contract->account_id = $order->account_id;
                $contract->value = $order->total;
                $contract->title = $order->order_number.' Contract';
            }
        }

        return view('admin.crm.contracts.form', ['contract' => $contract, ...$this->formOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $this->validated($request);
        $data['contract_number'] = Contract::nextNumber($companyId);

        Contract::create($data + ['company_id' => $companyId]);

        return redirect()->route('admin.crm.contracts.index')->with('status', 'Contract created.');
    }

    public function edit(Contract $contract): View
    {
        $this->ownCompany($contract);

        return view('admin.crm.contracts.form', ['contract' => $contract->load(['signatureRequests', 'renewedFrom', 'renewals']), ...$this->formOptions()]);
    }

    public function update(Request $request, Contract $contract): RedirectResponse
    {
        $this->ownCompany($contract);
        $contract->update($this->validated($request));

        return redirect()->route('admin.crm.contracts.edit', $contract)->with('status', 'Contract updated.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $this->ownCompany($contract);
        $contract->delete();

        return redirect()->route('admin.crm.contracts.index')->with('status', 'Contract deleted.');
    }

    protected function formOptions(): array
    {
        $companyId = Company::current()->id;

        return [
            'accounts' => Account::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
            'salesOrders' => SalesOrder::where('company_id', $companyId)->orderBy('order_number')->pluck('order_number', 'id'),
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
            'sales_order_id' => ['nullable', 'exists:crm_sales_orders,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(Contract::STATUSES))],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'value' => ['required', 'numeric', 'min:0'],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
            'auto_renew' => ['nullable', 'boolean'],
        ]) + ['auto_renew' => $request->boolean('auto_renew')];
    }

    protected function ownCompany(Contract $contract): void
    {
        abort_unless($contract->company_id === Company::current()->id, 404);
    }
}
