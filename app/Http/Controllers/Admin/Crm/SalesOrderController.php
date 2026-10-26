<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Quotation;
use App\Models\Crm\SalesOrder;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesOrderController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $query = SalesOrder::where('company_id', $companyId)->with(['account', 'owner']);

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where('order_number', 'like', "%{$search}%");
        }

        return view('admin.crm.sales-orders.index', [
            'orders' => $query->latest()->paginate(25)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'total' => SalesOrder::where('company_id', $companyId)->count(),
            'totalValue' => SalesOrder::where('company_id', $companyId)->whereIn('status', ['confirmed', 'fulfilled'])->sum('total'),
        ]);
    }

    public function create(Request $request): View
    {
        $quotation = null;

        if ($request->filled('quotation_id')) {
            $quotation = Quotation::where('company_id', Company::current()->id)->with('lines')->find($request->query('quotation_id'));
        }

        return view('admin.crm.sales-orders.form', ['order' => new SalesOrder, 'fromQuotation' => $quotation, ...$this->formOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $this->validated($request);
        $data['order_number'] = SalesOrder::nextNumber($companyId);

        $order = SalesOrder::create($data + ['company_id' => $companyId]);

        if ($request->filled('quotation_id')) {
            $quotation = Quotation::where('company_id', $companyId)->with('lines')->find($request->input('quotation_id'));

            if ($quotation) {
                foreach ($quotation->lines as $line) {
                    $order->lines()->create([
                        'product_id' => $line->product_id,
                        'description' => $line->description,
                        'quantity' => $line->quantity,
                        'unit_price' => $line->unit_price,
                        'line_total' => $line->line_total,
                    ]);
                }
                $order->update(['total' => $quotation->total]);
            }
        }

        return redirect()->route('admin.crm.sales-orders.edit', $order)->with('status', 'Sales order created.');
    }

    public function edit(SalesOrder $salesOrder): View
    {
        $this->ownCompany($salesOrder);

        return view('admin.crm.sales-orders.form', [
            'order' => $salesOrder->load('lines.product'),
            'fromQuotation' => null,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->ownCompany($salesOrder);
        $salesOrder->update($this->validated($request));

        return redirect()->route('admin.crm.sales-orders.edit', $salesOrder)->with('status', 'Sales order updated.');
    }

    public function destroy(SalesOrder $salesOrder): RedirectResponse
    {
        $this->ownCompany($salesOrder);
        $salesOrder->delete();

        return redirect()->route('admin.crm.sales-orders.index')->with('status', 'Sales order deleted.');
    }

    public function addLine(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->ownCompany($salesOrder);

        $data = $request->validate([
            'product_id' => ['nullable', 'exists:crm_products,id'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);
        $data['line_total'] = $data['quantity'] * $data['unit_price'];

        $salesOrder->lines()->create($data);
        $salesOrder->update(['total' => $salesOrder->lines()->get()->sum('line_total')]);

        return back()->with('status', 'Line added.');
    }

    protected function formOptions(): array
    {
        $companyId = Company::current()->id;

        return [
            'accounts' => Account::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
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
            'account_id' => ['nullable', 'exists:crm_accounts,id'],
            'quotation_id' => ['nullable', 'exists:crm_quotations,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(SalesOrder::STATUSES))],
            'order_date' => ['nullable', 'date'],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
        ]);
    }

    protected function ownCompany(SalesOrder $salesOrder): void
    {
        abort_unless($salesOrder->company_id === Company::current()->id, 404);
    }
}
