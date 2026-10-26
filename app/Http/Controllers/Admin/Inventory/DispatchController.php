<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\SalesOrderLine;
use App\Models\Inventory\Dispatch;
use App\Models\Inventory\InventoryPackage;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\StockReservation;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DispatchController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.dispatches.index', [
            'dispatches' => Dispatch::where('company_id', $companyId)->where('status', 'dispatched')->with(['warehouse', 'package.lines.item'])->orderByDesc('created_at')->paginate(20),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'packedPackages' => InventoryPackage::where('company_id', $companyId)->where('status', 'packed')->with('lines.item')->get(),
        ]);
    }

    /**
     * This is the configured dispatch/issue transaction: the ONLY place that
     * reduces inv_stock.quantity on the outbound side, and the point where a
     * matching reservation is fulfilled (its reserved_quantity is released
     * together with the on-hand reduction). Reservation and physical
     * reduction remain two distinct events up to this step.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'package_id' => ['required', 'exists:inv_packages,id'],
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);

        $package = InventoryPackage::where('company_id', $companyId)->where('status', 'packed')->findOrFail($data['package_id']);
        $dispatchNumber = Dispatch::nextNumber($companyId);

        foreach ($package->lines as $line) {
            $stock = Stock::firstOrCreate(
                ['warehouse_id' => $data['warehouse_id'], 'item_id' => $line->item_id],
                ['company_id' => $companyId, 'quantity' => 0]
            );
            $before = (float) $stock->quantity;
            $after = max(0, $stock->quantity - $line->quantity);
            $stock->update(['quantity' => $after]);

            StockLedger::record([
                'company_id' => $companyId, 'warehouse_id' => $data['warehouse_id'], 'item_id' => $line->item_id,
                'transaction_type' => 'issue', 'quantity_before' => $before, 'quantity_change' => -(float) $line->quantity, 'quantity_after' => $after,
                'source_type' => 'Dispatch', 'source_reference' => $dispatchNumber, 'employee_id' => Auth::user()?->employee?->id,
            ]);

            $reservation = StockReservation::where('company_id', $companyId)->where('warehouse_id', $data['warehouse_id'])
                ->where('item_id', $line->item_id)->where('status', 'reserved')->first();
            if ($reservation) {
                $stock->update(['reserved_quantity' => max(0, $stock->reserved_quantity - min($reservation->quantity, $line->quantity))]);
                $reservation->update(['status' => 'fulfilled']);

                // If this reservation was created for a Sales Order line
                // (order confirm -> reserveStock()), dispatching it is what
                // actually ships that line - roll the fulfilled qty and the
                // order's own status forward from here.
                $orderLine = SalesOrderLine::where('reservation_id', $reservation->id)->first();
                if ($orderLine) {
                    $fulfilled = min($orderLine->quantity, (float) $orderLine->fulfilled_qty + (float) $line->quantity);
                    $orderLine->update(['fulfilled_qty' => $fulfilled]);
                    $orderLine->salesOrder->refreshFulfilmentStatus();
                }
            }
        }

        $dispatch = Dispatch::create([
            'company_id' => $companyId,
            'warehouse_id' => $data['warehouse_id'],
            'package_id' => $package->id,
            'dispatch_number' => $dispatchNumber,
            'carrier' => $data['carrier'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
            'status' => 'dispatched',
            'dispatched_at' => now(),
        ]);

        $package->update(['status' => 'dispatched']);

        return redirect()->route('admin.inventory.dispatches.index')->with('status', "Dispatch {$dispatch->dispatch_number} created - stock reduced accordingly.");
    }
}
