<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\Supplier;
use App\Models\Inventory\SupplierReturn;
use App\Models\Inventory\SupplierReturnLine;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupplierReturnController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.supplier-returns.index', [
            'returns' => SupplierReturn::where('company_id', $companyId)->with(['supplier', 'warehouse', 'lines.item'])->orderByDesc('created_at')->paginate(20),
            'suppliers' => Supplier::where('company_id', $companyId)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'items' => Item::where('company_id', $companyId)->where('is_variant', false)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:inv_suppliers,id'],
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'item_id' => ['required', 'array', 'min:1'],
            'item_id.*' => ['exists:inv_items,id'],
            'quantity' => ['required', 'array', 'min:1'],
            'quantity.*' => ['numeric', 'min:0.01'],
            'batch_number' => ['nullable', 'array'],
        ]);

        $return = SupplierReturn::create([
            'company_id' => $companyId,
            'return_number' => SupplierReturn::nextNumber($companyId),
            'supplier_id' => $data['supplier_id'],
            'warehouse_id' => $data['warehouse_id'],
            'reason' => $data['reason'] ?? null,
            'status' => 'draft',
        ]);

        foreach ($data['item_id'] as $i => $itemId) {
            SupplierReturnLine::create([
                'return_id' => $return->id, 'item_id' => $itemId,
                'quantity' => $data['quantity'][$i], 'batch_number' => $data['batch_number'][$i] ?? null,
            ]);
        }

        return redirect()->route('admin.inventory.supplier-returns.index')->with('status', "Return {$return->return_number} created as draft.");
    }

    public function approve(SupplierReturn $supplierReturn): RedirectResponse
    {
        $this->authorizeReturn($supplierReturn);
        abort_unless($supplierReturn->status === 'draft', 422, 'Only a draft return can be approved.');
        $supplierReturn->update(['status' => 'approved']);

        return back()->with('status', 'Return approved - ready to dispatch.');
    }

    /**
     * Stock only reduces here, at dispatch - never at request or approval,
     * matching the supplier-returns rule.
     */
    public function dispatch(Request $request, SupplierReturn $supplierReturn): RedirectResponse
    {
        $this->authorizeReturn($supplierReturn);
        abort_unless($supplierReturn->status === 'approved', 422, 'Only an approved return can be dispatched.');

        $data = $request->validate([
            'credit_note_reference' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);

        foreach ($supplierReturn->lines as $line) {
            $stock = Stock::firstOrCreate(
                ['warehouse_id' => $supplierReturn->warehouse_id, 'item_id' => $line->item_id],
                ['company_id' => $supplierReturn->company_id, 'quantity' => 0]
            );
            $before = (float) $stock->quantity;
            $after = max(0, $stock->quantity - $line->quantity);
            $stock->update(['quantity' => $after]);

            StockLedger::record([
                'company_id' => $supplierReturn->company_id, 'warehouse_id' => $supplierReturn->warehouse_id, 'item_id' => $line->item_id,
                'batch_number' => $line->batch_number, 'serial_number' => $line->serial_number,
                'transaction_type' => 'return_out', 'quantity_before' => $before, 'quantity_change' => -(float) $line->quantity, 'quantity_after' => $after,
                'source_type' => 'SupplierReturn', 'source_reference' => $supplierReturn->return_number, 'employee_id' => Auth::user()?->employee?->id,
                'reason' => $supplierReturn->reason,
            ]);
        }

        $supplierReturn->update($data + ['status' => 'dispatched', 'dispatched_at' => now()]);

        return back()->with('status', "Return {$supplierReturn->return_number} dispatched - stock reduced accordingly.");
    }

    protected function authorizeReturn(SupplierReturn $return): void
    {
        abort_unless($return->company_id === Company::current()->id, 404);
    }
}
