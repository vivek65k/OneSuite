<?php

namespace App\Http\Controllers\Admin\Sales;

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

/**
 * Contracts live in crm_contracts - the Sales-native surface adds
 * recurring billing (generate the next order from an active contract)
 * on top; the CRM Contracts and Renewal Management screens remain
 * available too, reading/writing the same records.
 */
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

        return view('admin.sales.contracts.index', [
            'contracts' => $query->latest()->paginate(25)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'total' => Contract::where('company_id', $companyId)->count(),
            'activeCount' => Contract::where('company_id', $companyId)->where('status', 'active')->count(),
            'totalValue' => Contract::where('company_id', $companyId)->where('status', 'active')->sum('value'),
            'renewalCount' => Contract::where('company_id', $companyId)->where('status', 'active')
                ->whereNotNull('end_date')->where('end_date', '<=', now()->addDays(90))->count(),
            'dueForBilling' => Contract::where('company_id', $companyId)->where('status', 'active')
                ->where('billing_frequency', '!=', 'one_time')->whereNotNull('next_billing_date')
                ->where('next_billing_date', '<=', now())->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $contract = new Contract(['billing_frequency' => 'one_time']);

        if ($request->filled('sales_order_id')) {
            $order = SalesOrder::where('company_id', Company::current()->id)->find($request->query('sales_order_id'));
            if ($order) {
                $contract->sales_order_id = $order->id;
                $contract->account_id = $order->account_id;
                $contract->value = $order->total;
                $contract->title = $order->order_number.' Contract';
            }
        }

        return view('admin.sales.contracts.form', ['contract' => $contract, ...$this->formOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $this->validated($request);
        $data['contract_number'] = Contract::nextNumber($companyId);
        if (($data['billing_frequency'] ?? 'one_time') !== 'one_time' && empty($data['next_billing_date'])) {
            $data['next_billing_date'] = $data['start_date'] ?? now();
        }

        $contract = Contract::create($data + ['company_id' => $companyId]);

        return redirect()->route('admin.sales.contracts.edit', $contract)->with('status', 'Contract created.');
    }

    public function edit(Contract $contract): View
    {
        $this->ownCompany($contract);

        return view('admin.sales.contracts.form', ['contract' => $contract->load(['signatureRequests', 'renewedFrom', 'renewals']), ...$this->formOptions()]);
    }

    public function update(Request $request, Contract $contract): RedirectResponse
    {
        $this->ownCompany($contract);
        $contract->update($this->validated($request));

        return redirect()->route('admin.sales.contracts.edit', $contract)->with('status', 'Contract updated.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $this->ownCompany($contract);
        $contract->delete();

        return redirect()->route('admin.sales.contracts.index')->with('status', 'Contract deleted.');
    }

    public function renew(Contract $contract): RedirectResponse
    {
        $this->ownCompany($contract);
        abort_unless($contract->end_date, 422, 'This contract has no end date to renew from.');

        $new = $contract->renew(Contract::nextNumber($contract->company_id));

        return redirect()->route('admin.sales.contracts.edit', $new)->with('status', 'Renewal contract created as a draft - review and activate it.');
    }

    /**
     * Manually triggers the next recurring Sales Order for this contract
     * (a scheduled job would call Contract::generateRecurringOrder() the
     * same way, on next_billing_date).
     */
    public function generateOrder(Contract $contract): RedirectResponse
    {
        $this->ownCompany($contract);
        abort_unless($contract->status === 'active', 422, 'Only an active contract can generate a recurring order.');

        $order = $contract->generateRecurringOrder(SalesOrder::nextNumber($contract->company_id));

        return redirect()->route('admin.sales.orders.edit', $order)->with('status', "Recurring order {$order->order_number} created as a draft for this billing cycle.");
    }

    protected function formOptions(): array
    {
        $companyId = Company::current()->id;

        return [
            'accounts' => Account::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
            'salesOrders' => SalesOrder::where('company_id', $companyId)->orderBy('order_number')->pluck('order_number', 'id'),
            'employees' => Employee::query()->where('company_id', $companyId)->where('status', 'active')
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')->pluck('name', 'id'),
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
            'billing_frequency' => ['required', 'in:'.implode(',', array_keys(Contract::BILLING_FREQUENCIES))],
            'next_billing_date' => ['nullable', 'date'],
        ]) + ['auto_renew' => $request->boolean('auto_renew')];
    }

    protected function ownCompany(Contract $contract): void
    {
        abort_unless($contract->company_id === Company::current()->id, 404);
    }
}
