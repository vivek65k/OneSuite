<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\WarehouseTransfer;
use App\Models\Inventory\WarehouseTransferLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WarehouseTransferController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.warehouse-transfers.index', [
            'transfers' => WarehouseTransfer::where('company_id', $companyId)
                ->with(['sourceWarehouse', 'destWarehouse', 'lines.item'])->orderByDesc('created_at')->paginate(20),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'items' => Item::where('company_id', $companyId)->where('is_variant', false)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'source_warehouse_id' => ['required', 'exists:inv_warehouses,id', 'different:dest_warehouse_id'],
            'dest_warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'item_id' => ['required', 'array', 'min:1'],
            'item_id.*' => ['exists:inv_items,id'],
            'quantity' => ['required', 'array', 'min:1'],
            'quantity.*' => ['numeric', 'min:0.01'],
        ]);

        $transfer = WarehouseTransfer::create([
            'company_id' => $companyId,
            'transfer_number' => WarehouseTransfer::nextNumber($companyId),
            'source_warehouse_id' => $data['source_warehouse_id'],
            'dest_warehouse_id' => $data['dest_warehouse_id'],
            'status' => 'requested',
            'requested_by_employee_id' => $this->currentEmployee()?->id,
            'notes' => $data['notes'] ?? null,
            'requested_at' => now(),
        ]);

        foreach ($data['item_id'] as $i => $itemId) {
            WarehouseTransferLine::create([
                'transfer_id' => $transfer->id,
                'item_id' => $itemId,
                'requested_qty' => $data['quantity'][$i],
            ]);
        }

        return redirect()->route('admin.inventory.warehouse-transfers.index')->with('status', "Transfer {$transfer->transfer_number} requested.");
    }

    public function approve(WarehouseTransfer $warehouseTransfer): RedirectResponse
    {
        $this->authorizeTransfer($warehouseTransfer);
        abort_unless($warehouseTransfer->status === 'requested', 422, 'Only a requested transfer can be approved.');

        $warehouseTransfer->update(['status' => 'approved', 'approved_by_employee_id' => $this->currentEmployee()?->id]);

        return back()->with('status', 'Transfer approved.');
    }

    public function pick(WarehouseTransfer $warehouseTransfer): RedirectResponse
    {
        $this->authorizeTransfer($warehouseTransfer);
        abort_unless($warehouseTransfer->status === 'approved', 422, 'Only an approved transfer can be picked.');

        $warehouseTransfer->update(['status' => 'picked']);

        return back()->with('status', 'Transfer marked as picked.');
    }

    /**
     * Dispatch is the point actual on-hand stock leaves the source warehouse
     * - not the request or approval. Reservation vs on-hand reduction stay
     * two separate events, per the outbound design rule.
     */
    public function dispatch(Request $request, WarehouseTransfer $warehouseTransfer): RedirectResponse
    {
        $this->authorizeTransfer($warehouseTransfer);
        abort_unless($warehouseTransfer->status === 'picked', 422, 'Only a picked transfer can be dispatched.');

        $data = $request->validate(['dispatched_qty' => ['required', 'array']]);

        foreach ($warehouseTransfer->lines as $line) {
            $qty = (float) ($data['dispatched_qty'][$line->id] ?? $line->requested_qty);
            $line->update(['dispatched_qty' => $qty]);

            $stock = Stock::firstOrCreate(
                ['warehouse_id' => $warehouseTransfer->source_warehouse_id, 'item_id' => $line->item_id],
                ['company_id' => $warehouseTransfer->company_id, 'quantity' => 0]
            );
            $before = (float) $stock->quantity;
            $after = max(0, $stock->quantity - $qty);
            $stock->update(['quantity' => $after]);

            StockLedger::record([
                'company_id' => $warehouseTransfer->company_id, 'warehouse_id' => $warehouseTransfer->source_warehouse_id, 'item_id' => $line->item_id,
                'transaction_type' => 'transfer_out', 'quantity_before' => $before, 'quantity_change' => -$qty, 'quantity_after' => $after,
                'source_type' => 'WarehouseTransfer', 'source_reference' => $warehouseTransfer->transfer_number,
                'employee_id' => Auth::user()?->employee?->id,
            ]);
        }

        $warehouseTransfer->update(['status' => 'in_transit', 'dispatched_at' => now()]);

        return back()->with('status', 'Transfer dispatched - stock moved out of the source warehouse.');
    }

    public function receive(Request $request, WarehouseTransfer $warehouseTransfer): RedirectResponse
    {
        $this->authorizeTransfer($warehouseTransfer);
        abort_unless($warehouseTransfer->status === 'in_transit', 422, 'Only an in-transit transfer can be received.');

        $data = $request->validate([
            'received_qty' => ['required', 'array'],
            'discrepancy_notes' => ['nullable', 'array'],
        ]);

        $hasDiscrepancy = false;

        foreach ($warehouseTransfer->lines as $line) {
            $qty = (float) ($data['received_qty'][$line->id] ?? $line->dispatched_qty);
            $note = $data['discrepancy_notes'][$line->id] ?? null;

            if ($line->dispatched_qty !== null && abs($qty - $line->dispatched_qty) > 0.0001) {
                $hasDiscrepancy = true;
            }

            $line->update(['received_qty' => $qty, 'discrepancy_notes' => $note]);

            $stock = Stock::firstOrCreate(
                ['warehouse_id' => $warehouseTransfer->dest_warehouse_id, 'item_id' => $line->item_id],
                ['company_id' => $warehouseTransfer->company_id, 'quantity' => 0]
            );
            $before = (float) $stock->quantity;
            $stock->update(['quantity' => $before + $qty]);

            StockLedger::record([
                'company_id' => $warehouseTransfer->company_id, 'warehouse_id' => $warehouseTransfer->dest_warehouse_id, 'item_id' => $line->item_id,
                'transaction_type' => 'transfer_in', 'quantity_before' => $before, 'quantity_change' => $qty, 'quantity_after' => $before + $qty,
                'source_type' => 'WarehouseTransfer', 'source_reference' => $warehouseTransfer->transfer_number,
                'employee_id' => Auth::user()?->employee?->id, 'reason' => $note,
            ]);
        }

        $warehouseTransfer->update(['status' => 'received', 'received_at' => now()]);

        return back()->with('status', $hasDiscrepancy
            ? 'Transfer received with a quantity discrepancy - see line notes.'
            : 'Transfer received in full - stock is now available at the destination.');
    }

    public function cancel(WarehouseTransfer $warehouseTransfer): RedirectResponse
    {
        $this->authorizeTransfer($warehouseTransfer);
        abort_if(in_array($warehouseTransfer->status, ['in_transit', 'received'], true), 422, 'A dispatched transfer cannot be cancelled.');

        $warehouseTransfer->update(['status' => 'cancelled']);

        return back()->with('status', 'Transfer cancelled.');
    }

    protected function authorizeTransfer(WarehouseTransfer $transfer): void
    {
        abort_unless($transfer->company_id === Company::current()->id, 404);
    }
}
