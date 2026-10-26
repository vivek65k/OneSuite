<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Batch;
use App\Models\Inventory\ExpectedReceipt;
use App\Models\Inventory\Grn;
use App\Models\Inventory\GrnLine;
use App\Models\Inventory\Item;
use App\Models\Inventory\PutawayRule;
use App\Models\Inventory\PutawayTask;
use App\Models\Inventory\QualityInspection;
use App\Models\Inventory\QuarantineStock;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\Supplier;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GrnController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.grns.index', [
            'grns' => Grn::where('company_id', $companyId)->with(['warehouse', 'supplier', 'lines.item'])->orderByDesc('created_at')->paginate(20),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'suppliers' => Supplier::where('company_id', $companyId)->orderBy('name')->get(),
            'items' => Item::where('company_id', $companyId)->where('is_variant', false)->orderBy('name')->get(['id', 'name', 'track_batch', 'track_expiry']),
            'expectedReceipts' => ExpectedReceipt::where('company_id', $companyId)->where('status', 'pending')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'supplier_id' => ['nullable', 'exists:inv_suppliers,id'],
            'expected_receipt_id' => ['nullable', 'exists:inv_expected_receipts,id'],
            'item_id' => ['required', 'array', 'min:1'],
            'item_id.*' => ['exists:inv_items,id'],
            'received_qty' => ['required', 'array'],
            'damaged_qty' => ['nullable', 'array'],
            'batch_number' => ['nullable', 'array'],
            'expiry_date' => ['nullable', 'array'],
            'inspection_required' => ['nullable', 'array'],
        ]);

        $grn = Grn::create([
            'company_id' => $companyId,
            'grn_number' => Grn::nextNumber($companyId),
            'warehouse_id' => $data['warehouse_id'],
            'supplier_id' => $data['supplier_id'] ?? null,
            'expected_receipt_id' => $data['expected_receipt_id'] ?? null,
            'status' => 'draft',
        ]);

        foreach ($data['item_id'] as $i => $itemId) {
            $required = ! empty($data['inspection_required'][$i]);
            GrnLine::create([
                'grn_id' => $grn->id,
                'item_id' => $itemId,
                'received_qty' => $data['received_qty'][$i] ?? 0,
                'damaged_qty' => $data['damaged_qty'][$i] ?? 0,
                'batch_number' => $data['batch_number'][$i] ?? null,
                'expiry_date' => $data['expiry_date'][$i] ?? null,
                'inspection_required' => $required,
                'inspection_status' => $required ? 'pending' : 'not_required',
            ]);
        }

        return redirect()->route('admin.inventory.grns.index')->with('status', "GRN {$grn->grn_number} saved as draft - stock is not yet available.");
    }

    /**
     * Posting is the only point a GRN moves stock. A draft GRN never
     * touches inv_stock; items needing inspection go to quarantine instead
     * of straight to on-hand, per the inbound design rules.
     */
    public function post(Grn $grn): RedirectResponse
    {
        abort_unless($grn->company_id === Company::current()->id, 404);
        abort_unless($grn->status === 'draft', 422, 'This GRN is already posted.');

        foreach ($grn->lines as $line) {
            $goodQty = $line->good_qty;
            if ($goodQty <= 0) {
                continue;
            }

            if ($line->batch_number) {
                Batch::updateOrCreate(
                    ['item_id' => $line->item_id, 'warehouse_id' => $grn->warehouse_id, 'batch_number' => $line->batch_number],
                    ['company_id' => $grn->company_id, 'expiry_date' => $line->expiry_date, 'quantity' => $goodQty]
                );
            }

            if ($line->inspection_required) {
                QuarantineStock::create([
                    'company_id' => $grn->company_id, 'warehouse_id' => $grn->warehouse_id,
                    'item_id' => $line->item_id, 'grn_line_id' => $line->id, 'quantity' => $goodQty,
                ]);
                QualityInspection::create([
                    'company_id' => $grn->company_id, 'grn_line_id' => $line->id,
                    'warehouse_id' => $grn->warehouse_id, 'item_id' => $line->item_id, 'status' => 'pending',
                ]);
            } else {
                $this->addStockAndPutaway($grn->company_id, $grn->warehouse_id, $line->item_id, $goodQty, $grn->grn_number);
                $line->update(['inspection_status' => 'not_required']);
            }
        }

        $grn->update(['status' => 'posted', 'posted_at' => now(), 'posted_by_employee_id' => $this->currentEmployee()?->id]);

        if ($grn->expectedReceipt) {
            $grn->expectedReceipt->update(['status' => 'received']);
        }

        return back()->with('status', "GRN {$grn->grn_number} posted - stock updated (quality-hold items moved to quarantine).");
    }

    public static function addStockAndPutaway(int $companyId, int $warehouseId, int $itemId, float $qty, string $sourceRef, string $sourceType = 'Grn'): void
    {
        $stock = Stock::firstOrCreate(['warehouse_id' => $warehouseId, 'item_id' => $itemId], ['company_id' => $companyId, 'quantity' => 0]);
        $before = (float) $stock->quantity;
        $stock->update(['quantity' => $stock->quantity + $qty]);

        StockLedger::record([
            'company_id' => $companyId, 'warehouse_id' => $warehouseId, 'item_id' => $itemId,
            'transaction_type' => 'receipt', 'quantity_before' => $before, 'quantity_change' => $qty, 'quantity_after' => $before + $qty,
            'source_type' => $sourceType, 'source_reference' => $sourceRef, 'employee_id' => Auth::user()?->employee?->id,
        ]);

        $item = Item::find($itemId);
        $rule = PutawayRule::where('company_id', $companyId)->where('warehouse_id', $warehouseId)->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('category_id')->orWhere('category_id', $item->category_id))
            ->orderByRaw('category_id IS NULL')->first();

        PutawayTask::create([
            'company_id' => $companyId, 'warehouse_id' => $warehouseId, 'item_id' => $itemId, 'quantity' => $qty,
            'suggested_location_id' => $rule?->fixed_location_id, 'status' => 'pending', 'source_reference' => $sourceRef,
        ]);
    }
}
