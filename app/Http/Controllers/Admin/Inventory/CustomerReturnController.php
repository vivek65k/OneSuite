<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\CustomerReturn;
use App\Models\Inventory\CustomerReturnLine;
use App\Models\Inventory\Dispatch;
use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerReturnController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.customer-returns.index', [
            'returns' => CustomerReturn::where('company_id', $companyId)->with(['warehouse', 'dispatch', 'lines.item'])->orderByDesc('created_at')->paginate(20),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'dispatches' => Dispatch::where('company_id', $companyId)->where('status', 'delivered')->get(),
            'items' => Item::where('company_id', $companyId)->where('is_variant', false)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'dispatch_id' => ['nullable', 'exists:inv_dispatches,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'item_id' => ['required', 'array', 'min:1'],
            'item_id.*' => ['exists:inv_items,id'],
            'quantity' => ['required', 'array', 'min:1'],
            'quantity.*' => ['numeric', 'min:0.01'],
            'batch_number' => ['nullable', 'array'],
        ]);

        $return = CustomerReturn::create([
            'company_id' => $companyId,
            'warehouse_id' => $data['warehouse_id'],
            'dispatch_id' => $data['dispatch_id'] ?? null,
            'return_number' => CustomerReturn::nextNumber($companyId),
            'reason' => $data['reason'] ?? null,
            'status' => 'draft',
        ]);

        foreach ($data['item_id'] as $i => $itemId) {
            CustomerReturnLine::create([
                'return_id' => $return->id, 'item_id' => $itemId,
                'quantity' => $data['quantity'][$i], 'batch_number' => $data['batch_number'][$i] ?? null,
            ]);
        }

        return redirect()->route('admin.inventory.customer-returns.index')->with('status', "Return {$return->return_number} created as draft.");
    }

    public function approve(CustomerReturn $customerReturn): RedirectResponse
    {
        $this->authorizeReturn($customerReturn);
        abort_unless($customerReturn->status === 'draft', 422, 'Only a draft return can be approved.');
        $customerReturn->update(['status' => 'approved']);

        return back()->with('status', 'Return approved - ready to receive back into stock.');
    }

    /**
     * Stock only increases here, once the physical goods are received back
     * and approved - never at draft or approval time.
     */
    public function receive(CustomerReturn $customerReturn): RedirectResponse
    {
        $this->authorizeReturn($customerReturn);
        abort_unless($customerReturn->status === 'approved', 422, 'Only an approved return can be received.');

        foreach ($customerReturn->lines as $line) {
            $stock = Stock::firstOrCreate(
                ['warehouse_id' => $customerReturn->warehouse_id, 'item_id' => $line->item_id],
                ['company_id' => $customerReturn->company_id, 'quantity' => 0]
            );
            $before = (float) $stock->quantity;
            $stock->update(['quantity' => $before + $line->quantity]);

            StockLedger::record([
                'company_id' => $customerReturn->company_id, 'warehouse_id' => $customerReturn->warehouse_id, 'item_id' => $line->item_id,
                'batch_number' => $line->batch_number,
                'transaction_type' => 'return_in', 'quantity_before' => $before, 'quantity_change' => (float) $line->quantity, 'quantity_after' => $before + $line->quantity,
                'source_type' => 'CustomerReturn', 'source_reference' => $customerReturn->return_number, 'employee_id' => Auth::user()?->employee?->id,
                'reason' => $customerReturn->reason,
            ]);
        }

        $customerReturn->update(['status' => 'received', 'received_at' => now()]);

        return back()->with('status', "Return {$customerReturn->return_number} received - stock increased accordingly.");
    }

    protected function authorizeReturn(CustomerReturn $return): void
    {
        abort_unless($return->company_id === Company::current()->id, 404);
    }
}
