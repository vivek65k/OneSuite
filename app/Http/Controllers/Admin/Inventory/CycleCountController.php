<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\CountSchedule;
use App\Models\Inventory\CountSheet;
use App\Models\Inventory\CountSheetLine;
use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CycleCountController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.cycle-counting.index', [
            'schedules' => CountSchedule::where('company_id', $companyId)->with('warehouse')->orderByDesc('created_at')->get(),
            'sheets' => CountSheet::where('company_id', $companyId)->with(['warehouse', 'lines.item'])->orderByDesc('created_at')->paginate(15),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'items' => Item::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:cycle,physical'],
            'frequency' => ['nullable', 'in:daily,weekly,monthly'],
            'abc_class' => ['nullable', 'in:A,B,C'],
        ]);

        CountSchedule::create($data + ['company_id' => $companyId, 'is_active' => true]);

        return redirect()->route('admin.inventory.cycle-counting.index')->with('status', 'Count schedule saved.');
    }

    /**
     * The count sheet is seeded with the current system quantity, but
     * blind_count hides it from the counter in the view until a count is
     * submitted - the counter only ever sees the physical bins.
     */
    public function storeSheet(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'schedule_id' => ['nullable', 'exists:inv_count_schedules,id'],
            'type' => ['required', 'in:cycle,physical'],
            'blind_count' => ['nullable', 'boolean'],
            'item_id' => ['required', 'array', 'min:1'],
            'item_id.*' => ['exists:inv_items,id'],
        ]);

        $sheet = CountSheet::create([
            'company_id' => $companyId,
            'warehouse_id' => $data['warehouse_id'],
            'schedule_id' => $data['schedule_id'] ?? null,
            'count_number' => CountSheet::nextNumber($companyId),
            'type' => $data['type'],
            'blind_count' => $request->boolean('blind_count', true),
            'status' => 'counting',
        ]);

        foreach ($data['item_id'] as $itemId) {
            $stock = Stock::where('warehouse_id', $data['warehouse_id'])->where('item_id', $itemId)->first();
            CountSheetLine::create([
                'sheet_id' => $sheet->id, 'item_id' => $itemId,
                'system_qty' => $stock->quantity ?? 0, 'status' => 'pending',
            ]);
        }

        return redirect()->route('admin.inventory.cycle-counting.index')->with('status', "Count sheet {$sheet->count_number} created - ready for counting.");
    }

    public function countLine(Request $request, CountSheetLine $countSheetLine): RedirectResponse
    {
        $this->authorizeSheet($countSheetLine->sheet);
        $data = $request->validate(['counted_qty' => ['required', 'numeric', 'min:0']]);

        $variance = $data['counted_qty'] - $countSheetLine->system_qty;
        $countSheetLine->update(['counted_qty' => $data['counted_qty'], 'variance' => $variance, 'status' => 'counted']);

        $sheet = $countSheetLine->sheet;
        if ($sheet->lines()->where('status', 'pending')->doesntExist()) {
            $sheet->update(['status' => 'variance_review']);
        }

        return back()->with('status', 'Count recorded.');
    }

    public function recountLine(CountSheetLine $countSheetLine): RedirectResponse
    {
        $this->authorizeSheet($countSheetLine->sheet);
        $countSheetLine->update(['status' => 'recount_requested', 'counted_qty' => null, 'variance' => null]);
        $countSheetLine->sheet->update(['status' => 'counting']);

        return back()->with('status', 'Recount requested for this line.');
    }

    /**
     * Approving posts every counted line's variance straight to inv_stock
     * and the stock ledger as a count_adjustment - the same posting point
     * used for manual adjustments, just sourced from a count sheet.
     */
    public function approveSheet(CountSheet $countSheet): RedirectResponse
    {
        $this->authorizeSheet($countSheet);
        abort_unless($countSheet->status === 'variance_review', 422, 'All lines must be counted before this sheet can be approved.');

        foreach ($countSheet->lines as $line) {
            if ($line->status !== 'counted' || abs((float) $line->variance) < 0.0001) {
                $line->update(['status' => 'approved']);

                continue;
            }

            $stock = Stock::firstOrCreate(
                ['warehouse_id' => $countSheet->warehouse_id, 'item_id' => $line->item_id],
                ['company_id' => $countSheet->company_id, 'quantity' => 0]
            );
            $before = (float) $stock->quantity;
            $after = max(0, $line->counted_qty);
            $stock->update(['quantity' => $after]);

            StockLedger::record([
                'company_id' => $countSheet->company_id, 'warehouse_id' => $countSheet->warehouse_id, 'item_id' => $line->item_id,
                'location_id' => $line->location_id,
                'transaction_type' => 'count_adjustment', 'quantity_before' => $before, 'quantity_change' => $after - $before, 'quantity_after' => $after,
                'source_type' => 'CountSheet', 'source_reference' => $countSheet->count_number,
                'employee_id' => $this->currentEmployee()?->id, 'reason' => ucfirst($countSheet->type).' count variance',
            ]);

            $line->update(['status' => 'approved']);
        }

        $countSheet->update(['status' => 'approved']);

        return back()->with('status', "Count sheet {$countSheet->count_number} approved - variances posted to stock.");
    }

    protected function authorizeSheet(CountSheet $sheet): void
    {
        abort_unless($sheet->company_id === Company::current()->id, 404);
    }
}
