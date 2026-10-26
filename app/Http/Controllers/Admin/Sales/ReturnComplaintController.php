<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\CaseModel;
use App\Models\Crm\SalesOrder;
use App\Models\Inventory\CustomerReturn;
use App\Models\Inventory\CustomerReturnLine;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Returns stay owned by Inventory (inv_customer_returns - the stock-side
 * draft/approve/receive lifecycle, unchanged) and complaints stay owned
 * by CRM (crm_cases, type=complaint - SLA/escalation, unchanged). This
 * is the Sales-side view that ties both back to the originating Sales
 * Order, plus the one piece neither owned yet: creating a replacement
 * order for a return.
 */
class ReturnComplaintController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.sales.returns-complaints.index', [
            'returns' => CustomerReturn::where('company_id', $companyId)->whereNotNull('sales_order_id')
                ->with(['salesOrder.account', 'replacementOrder', 'lines.item'])->orderByDesc('created_at')->get(),
            'complaints' => CaseModel::where('company_id', $companyId)->where('type', 'complaint')
                ->with(['salesOrder', 'account', 'assignedTo'])->orderByDesc('created_at')->get(),
            'returnableOrders' => SalesOrder::where('company_id', $companyId)
                ->whereIn('status', ['fulfilled', 'partially_fulfilled', 'closed'])
                ->with('lines.product')->get(),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
        ]);
    }

    /**
     * Only lines whose product is linked to an Inventory item
     * (Product::inventory_item_id) can actually be returned into stock -
     * others are skipped, same bridge used for fulfilment reservations.
     */
    public function storeReturn(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'sales_order_id' => ['required', 'exists:crm_sales_orders,id'],
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'reason' => ['required', 'string', 'max:255'],
            'order_line_id' => ['required', 'array', 'min:1'],
            'order_line_id.*' => ['exists:crm_sales_order_lines,id'],
            'quantity' => ['required', 'array', 'min:1'],
            'quantity.*' => ['numeric', 'min:0.01'],
        ]);

        $order = SalesOrder::where('company_id', $companyId)->with('lines.product')->findOrFail($data['sales_order_id']);

        $return = CustomerReturn::create([
            'company_id' => $companyId, 'warehouse_id' => $data['warehouse_id'], 'sales_order_id' => $order->id,
            'return_number' => CustomerReturn::nextNumber($companyId), 'reason' => $data['reason'], 'status' => 'draft',
        ]);

        $linesAdded = 0;
        foreach ($data['order_line_id'] as $i => $orderLineId) {
            $orderLine = $order->lines->firstWhere('id', (int) $orderLineId);
            $itemId = $orderLine?->product?->inventory_item_id;
            if (! $itemId) {
                continue;
            }

            CustomerReturnLine::create(['return_id' => $return->id, 'item_id' => $itemId, 'quantity' => $data['quantity'][$i]]);
            $linesAdded++;
        }

        if ($linesAdded === 0) {
            $return->delete();

            return back()->withErrors(['order_line_id' => 'None of the selected lines are linked to an Inventory item - nothing to return.']);
        }

        return redirect()->route('admin.sales.returns-complaints.index')->with('status', "Return {$return->return_number} created as a draft.");
    }

    public function createReplacement(CustomerReturn $customerReturn): RedirectResponse
    {
        abort_unless($customerReturn->company_id === Company::current()->id, 404);
        abort_unless($customerReturn->status === 'received', 422, 'Only a received return can generate a replacement order.');

        $order = $customerReturn->createReplacementOrder(SalesOrder::nextNumber($customerReturn->company_id));

        return redirect()->route('admin.sales.orders.edit', $order)->with('status', "Replacement order {$order->order_number} created as a draft.");
    }

    public function storeComplaint(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'sales_order_id' => ['required', 'exists:crm_sales_orders,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', 'in:'.implode(',', array_keys(CaseModel::PRIORITIES))],
        ]);

        $order = SalesOrder::where('company_id', $companyId)->findOrFail($data['sales_order_id']);

        $case = CaseModel::create($data + [
            'company_id' => $companyId, 'account_id' => $order->account_id, 'type' => 'complaint',
            'status' => 'open', 'case_number' => CaseModel::nextCaseNumber($companyId),
        ]);

        return redirect()->route('admin.crm.cases.edit', $case)->with('status', "Complaint {$case->case_number} logged against {$order->order_number}.");
    }

}
