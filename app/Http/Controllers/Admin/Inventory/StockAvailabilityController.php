<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\ExpectedReceiptLine;
use App\Models\Inventory\QuarantineStock;
use App\Models\Inventory\Stock;
use Illuminate\View\View;

class StockAvailabilityController extends Controller
{
    /**
     * Expected availability = on-hand (already excludes quality-hold, which
     * lives in its own quarantine table) + incoming not yet received -
     * reserved/outgoing commitments not yet dispatched.
     */
    public function index(): View
    {
        $companyId = Company::current()->id;

        $rows = Stock::where('company_id', $companyId)->with(['item', 'warehouse'])->get()
            ->map(function (Stock $stock) {
                $qualityHold = QuarantineStock::where('warehouse_id', $stock->warehouse_id)->where('item_id', $stock->item_id)->sum('quantity');
                $incoming = ExpectedReceiptLine::whereHas('receipt', fn ($q) => $q->where('warehouse_id', $stock->warehouse_id)->where('status', 'pending'))
                    ->where('item_id', $stock->item_id)->sum('expected_qty');

                $availableNow = (float) $stock->quantity - (float) $stock->reserved_quantity;

                return [
                    'stock' => $stock,
                    'quality_hold' => (float) $qualityHold,
                    'incoming' => (float) $incoming,
                    'available_now' => $availableNow,
                    'expected_availability' => $availableNow + (float) $incoming,
                ];
            })
            ->filter(fn ($row) => $row['stock']->quantity > 0 || $row['incoming'] > 0 || $row['quality_hold'] > 0)
            ->sortBy(fn ($row) => $row['stock']->item?->name)->values();

        return view('admin.inventory.stock-availability.index', ['rows' => $rows]);
    }
}
