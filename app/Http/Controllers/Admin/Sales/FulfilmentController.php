<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\SalesOrder;
use Illuminate\View\View;

/**
 * A Sales-side view over fulfilment status - the actual picking, packing
 * and dispatch work happens in Inventory & Warehouse (reused, not
 * duplicated here); this screen just shows where each confirmed order
 * stands and links out to the right Inventory screen for the next step.
 */
class FulfilmentController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        $orders = SalesOrder::where('company_id', $companyId)
            ->whereIn('status', ['confirmed', 'partially_fulfilled'])
            ->with(['account', 'warehouse', 'lines.product.inventoryItem', 'lines.reservation'])
            ->orderBy('order_date')->get();

        return view('admin.sales.fulfilment.index', [
            'orders' => $orders,
            'unlinkedCount' => $orders->sum(fn ($order) => $order->lines->filter(fn ($line) => ! $line->product?->inventory_item_id)->count()),
            'noWarehouseCount' => $orders->where('warehouse_id', null)->count(),
        ]);
    }
}
