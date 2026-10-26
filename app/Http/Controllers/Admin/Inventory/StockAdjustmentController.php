<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\ApprovalRule;
use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.stock-adjustments.index', [
            'adjustments' => StockAdjustment::where('company_id', $companyId)->with(['item', 'warehouse'])->orderByDesc('created_at')->paginate(20),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'items' => Item::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'reasonCodes' => StockAdjustment::REASON_CODES,
        ]);
    }

    /**
     * Checks the Approval Rules matrix for 'adjustment': if an active rule
     * exists and this adjustment's magnitude is at or under its qty
     * threshold, it auto-posts immediately: otherwise it falls back to the
     * baseline rule of always requiring manual approval.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'item_id' => ['required', 'exists:inv_items,id'],
            'physical_qty' => ['required', 'numeric', 'min:0'],
            'reason_code' => ['required', 'in:'.implode(',', array_keys(StockAdjustment::REASON_CODES))],
            'reason_notes' => ['nullable', 'string', 'max:1000'],
            'supporting_document' => ['nullable', 'string', 'max:255'],
        ]);

        $stock = Stock::firstOrCreate(
            ['warehouse_id' => $data['warehouse_id'], 'item_id' => $data['item_id']],
            ['company_id' => $companyId, 'quantity' => 0]
        );
        $adjustmentQty = $data['physical_qty'] - $stock->quantity;

        $rule = ApprovalRule::where('company_id', $companyId)->where('action_type', 'adjustment')->where('is_active', true)->whereNotNull('threshold_qty')->first();
        $autoApprove = $rule && abs($adjustmentQty) <= $rule->threshold_qty;

        $adjustment = StockAdjustment::create([
            'company_id' => $companyId,
            'warehouse_id' => $data['warehouse_id'],
            'item_id' => $data['item_id'],
            'adjustment_number' => StockAdjustment::nextNumber($companyId),
            'system_qty' => $stock->quantity,
            'physical_qty' => $data['physical_qty'],
            'adjustment_qty' => $adjustmentQty,
            'reason_code' => $data['reason_code'],
            'reason_notes' => $data['reason_notes'] ?? null,
            'supporting_document' => $data['supporting_document'] ?? null,
            'status' => 'pending_approval',
            'requested_by_employee_id' => $this->currentEmployee()?->id,
        ]);

        if ($autoApprove) {
            $this->postAdjustment($adjustment);

            return redirect()->route('admin.inventory.stock-adjustments.index')->with('status', "Adjustment {$adjustment->adjustment_number} is within the auto-approval threshold ({$rule->threshold_qty}) and was posted immediately.");
        }

        return redirect()->route('admin.inventory.stock-adjustments.index')->with('status', "Adjustment {$adjustment->adjustment_number} submitted for approval.");
    }

    /**
     * Approval is the only point the adjustment quantity is posted to
     * inv_stock and written to the permanent stock ledger.
     */
    public function approve(StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->authorizeAdjustment($stockAdjustment);
        abort_unless($stockAdjustment->status === 'pending_approval', 422, 'This adjustment has already been actioned.');

        $this->postAdjustment($stockAdjustment);

        return back()->with('status', "Adjustment {$stockAdjustment->adjustment_number} approved and posted to stock.");
    }

    protected function postAdjustment(StockAdjustment $stockAdjustment): void
    {
        $stock = Stock::firstOrCreate(
            ['warehouse_id' => $stockAdjustment->warehouse_id, 'item_id' => $stockAdjustment->item_id],
            ['company_id' => $stockAdjustment->company_id, 'quantity' => 0]
        );
        $before = (float) $stock->quantity;
        $after = max(0, $before + $stockAdjustment->adjustment_qty);
        $stock->update(['quantity' => $after]);

        StockLedger::record([
            'company_id' => $stockAdjustment->company_id, 'warehouse_id' => $stockAdjustment->warehouse_id, 'item_id' => $stockAdjustment->item_id,
            'transaction_type' => 'adjustment', 'quantity_before' => $before, 'quantity_change' => $stockAdjustment->adjustment_qty, 'quantity_after' => $after,
            'source_type' => 'StockAdjustment', 'source_reference' => $stockAdjustment->adjustment_number,
            'employee_id' => $this->currentEmployee()?->id, 'reason' => $stockAdjustment->reason_code,
        ]);

        $stockAdjustment->update(['status' => 'approved', 'approved_by_employee_id' => $this->currentEmployee()?->id, 'approved_at' => now()]);
    }

    public function reject(Request $request, StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->authorizeAdjustment($stockAdjustment);
        abort_unless($stockAdjustment->status === 'pending_approval', 422, 'This adjustment has already been actioned.');

        $data = $request->validate(['rejected_reason' => ['nullable', 'string', 'max:255']]);
        $stockAdjustment->update($data + ['status' => 'rejected', 'approved_by_employee_id' => $this->currentEmployee()?->id, 'approved_at' => now()]);

        return back()->with('status', "Adjustment {$stockAdjustment->adjustment_number} rejected - stock unchanged.");
    }

    protected function authorizeAdjustment(StockAdjustment $adjustment): void
    {
        abort_unless($adjustment->company_id === Company::current()->id, 404);
    }
}
