<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Quotation;
use App\Models\Crm\SalesOrder;
use App\Models\Crm\SalesOrderEvent;
use App\Models\Crm\SalesOrderLine;
use App\Models\Employee;
use App\Models\Inventory\Warehouse;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sales orders live in crm_sales_orders - this is the Sales-native
 * surface for the full order lifecycle (confirm, amend, fulfil,
 * cancel, close) with a timeline; the simpler CRM screens remain
 * available too, reading/writing the same records.
 */
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

        return view('admin.sales.orders.index', [
            'orders' => $query->latest()->paginate(25)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'total' => SalesOrder::where('company_id', $companyId)->count(),
            'totalValue' => SalesOrder::where('company_id', $companyId)->whereIn('status', ['confirmed', 'partially_fulfilled', 'fulfilled'])->sum('total'),
            'backorderCount' => SalesOrderLine::whereHas('salesOrder', fn ($q) => $q->where('company_id', $companyId)->whereIn('status', ['confirmed', 'partially_fulfilled']))
                ->whereColumn('fulfilled_qty', '<', 'quantity')->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $quotation = null;
        if ($request->filled('quotation_id')) {
            $quotation = Quotation::where('company_id', Company::current()->id)->with('lines')->find($request->query('quotation_id'));
        }

        return view('admin.sales.orders.form', ['order' => new SalesOrder, 'fromQuotation' => $quotation, ...$this->formOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $this->validated($request);
        $data['order_number'] = SalesOrder::nextNumber($companyId);

        $order = SalesOrder::create($data + ['company_id' => $companyId]);

        if ($request->filled('quotation_id')) {
            $quotation = Quotation::where('company_id', $companyId)->with('lines')->find($request->input('quotation_id'));
            foreach ($quotation?->lines ?? [] as $line) {
                $order->lines()->create([
                    'product_id' => $line->product_id, 'description' => $line->description,
                    'quantity' => $line->quantity, 'unit_price' => $line->unit_price, 'line_total' => $line->line_total,
                ]);
            }
            if ($quotation) {
                $order->update(['total' => $quotation->total]);
            }
        }

        return redirect()->route('admin.sales.orders.edit', $order)->with('status', 'Sales order created.');
    }

    public function edit(SalesOrder $salesOrder): View
    {
        $this->ownCompany($salesOrder);

        return view('admin.sales.orders.form', [
            'order' => $salesOrder->load(['lines.product', 'lines.reservation', 'events.employee', 'quotation']),
            'fromQuotation' => null,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->ownCompany($salesOrder);
        abort_if(in_array($salesOrder->status, ['cancelled', 'closed'], true), 422, 'A cancelled or closed order cannot be edited.');

        $salesOrder->update($this->validated($request));

        return redirect()->route('admin.sales.orders.edit', $salesOrder)->with('status', 'Sales order updated.');
    }

    public function destroy(SalesOrder $salesOrder): RedirectResponse
    {
        $this->ownCompany($salesOrder);
        $salesOrder->delete();

        return redirect()->route('admin.sales.orders.index')->with('status', 'Sales order deleted.');
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
        SalesOrderEvent::log($salesOrder->id, 'amendment', "Line added: {$data['description']} (qty {$data['quantity']}).", auth()->user()?->employee?->id);

        return back()->with('status', 'Line added.');
    }

    /**
     * Confirming moves the order out of draft/pending and immediately
     * reserves Inventory stock for every line whose product is linked to
     * an Inventory item - this is the Sales/Inventory integration point:
     * reservation here never touches on-hand quantity, matching the
     * reservation != physical-reduction rule used throughout Inventory.
     */
    /**
     * Credit control (Sales → Automation & AI's over-limit recommendation
     * is the read-only view of this; this is the point it can actually
     * block, per Settings & Configuration's block_order_over_credit_limit).
     */
    public function confirm(SalesOrder $salesOrder): RedirectResponse
    {
        $this->ownCompany($salesOrder);
        abort_unless(in_array($salesOrder->status, ['draft', 'pending'], true), 422, 'Only a draft or pending order can be confirmed.');

        $settings = \App\Models\Sales\SalesSetting::forCompany($salesOrder->company_id);
        $creditWarning = null;

        if ($settings->require_credit_check && $salesOrder->account_id) {
            $profile = \App\Models\Sales\CustomerProfile::forAccount($salesOrder->company_id, $salesOrder->account_id);
            $projectedBalance = (float) $profile->outstanding_balance + (float) $salesOrder->total;

            if ($profile->credit_limit > 0 && $projectedBalance > $profile->credit_limit) {
                if ($settings->block_order_over_credit_limit) {
                    return back()->withErrors(['credit' => "Confirming this order would put {$profile->account->name} at ".number_format($projectedBalance, 2)." against a credit limit of ".number_format($profile->credit_limit, 2).'. Raise the limit or collect payment first.']);
                }
                $creditWarning = "Note: this order puts the customer over their credit limit (projected ".number_format($projectedBalance, 2)." vs limit ".number_format($profile->credit_limit, 2).').';
            }
        }

        $salesOrder->update(['status' => 'confirmed']);
        $result = $salesOrder->reserveStock();

        $message = "Order confirmed - {$result['reserved']} line(s) reserved in Inventory.";
        if (! empty($result['shortages'])) {
            $shortageDesc = collect($result['shortages'])->map(fn ($s) => "{$s['line']->description} (only {$s['available']} available)")->implode(', ');
            $message .= " Shortage on: {$shortageDesc} - a purchase suggestion may be needed.";
        }
        if ($creditWarning) {
            $message .= ' '.$creditWarning;
        }

        return back()->with('status', $message);
    }

    /**
     * Records partial or full shipment per line. The order's own status
     * then rolls up automatically (confirmed -> partially_fulfilled ->
     * fulfilled) via SalesOrder::refreshFulfilmentStatus().
     */
    public function fulfillLine(Request $request, SalesOrderLine $salesOrderLine): RedirectResponse
    {
        $order = $salesOrderLine->salesOrder;
        $this->ownCompany($order);
        abort_unless(in_array($order->status, ['confirmed', 'partially_fulfilled'], true), 422, 'Only a confirmed order can record fulfilment.');
        abort_if($salesOrderLine->reservation?->status === 'reserved', 422, 'This line is reserved in Inventory - dispatch it from Inventory & Warehouse so the two stay in sync, rather than recording fulfilment here manually.');

        $data = $request->validate(['fulfilled_qty' => ['required', 'numeric', 'min:0', 'max:'.$salesOrderLine->quantity]]);
        $salesOrderLine->update($data);

        SalesOrderEvent::log($order->id, 'fulfilment', "{$salesOrderLine->description}: {$data['fulfilled_qty']} of {$salesOrderLine->quantity} fulfilled.", auth()->user()?->employee?->id);
        $order->refreshFulfilmentStatus();

        return back()->with('status', 'Fulfilment recorded.');
    }

    public function close(SalesOrder $salesOrder): RedirectResponse
    {
        $this->ownCompany($salesOrder);
        abort_unless($salesOrder->status === 'fulfilled', 422, 'Only a fully fulfilled order can be closed.');

        $salesOrder->update(['status' => 'closed', 'closed_at' => now()]);

        return back()->with('status', 'Order closed.');
    }

    /**
     * Cancellation is blocked once anything has shipped - a partially or
     * fully fulfilled order must be handled through returns instead of a
     * blanket cancel, per the module's controlled-cancellation rule.
     */
    public function cancel(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->ownCompany($salesOrder);
        abort_if(in_array($salesOrder->status, ['partially_fulfilled', 'fulfilled', 'closed', 'cancelled'], true), 422, 'An order that has shipped, or is already closed/cancelled, cannot be cancelled here.');

        $data = $request->validate(['cancelled_reason' => ['required', 'string', 'max:255']]);
        $salesOrder->update($data + ['status' => 'cancelled', 'cancelled_at' => now()]);
        $salesOrder->releaseReservations();
        SalesOrderEvent::log($salesOrder->id, 'cancellation', "Order cancelled: {$data['cancelled_reason']}", auth()->user()?->employee?->id);

        return back()->with('status', 'Order cancelled - any reserved stock has been released.');
    }

    protected function formOptions(): array
    {
        $companyId = Company::current()->id;

        return [
            'accounts' => Account::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
            'employees' => Employee::query()->where('company_id', $companyId)->where('status', 'active')
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')->pluck('name', 'id'),
        ];
    }


    protected function validated(Request $request): array
    {
        return $request->validate([
            'account_id' => ['nullable', 'exists:crm_accounts,id'],
            'quotation_id' => ['nullable', 'exists:crm_quotations,id'],
            'warehouse_id' => ['nullable', 'exists:inv_warehouses,id'],
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
