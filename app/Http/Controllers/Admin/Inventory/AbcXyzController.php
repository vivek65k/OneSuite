<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Item;
use App\Models\Inventory\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AbcXyzController extends Controller
{
    protected const OUTFLOW_TYPES = ['issue', 'transfer_out', 'return_out'];

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.abc-xyz.index', [
            'items' => Item::where('company_id', $companyId)->where('is_variant', false)->orderByRaw("FIELD(abc_class, 'A','B','C') IS NULL, FIELD(abc_class, 'A','B','C')")->get(),
        ]);
    }

    /**
     * ABC classifies by value contribution (stock qty x cost), using the
     * classic 80/95/100 cumulative-percentage cutoffs. XYZ classifies by
     * demand variability - the coefficient of variation of monthly
     * outflow quantity over the last 6 months (low CV = stable = X).
     */
    public function recalculate(): RedirectResponse
    {
        $companyId = Company::current()->id;
        $items = Item::where('company_id', $companyId)->where('is_variant', false)->with('stock')->get();

        $valued = $items->map(fn ($item) => ['item' => $item, 'value' => $item->total_quantity * (float) $item->cost_price])
            ->sortByDesc('value')->values();

        $totalValue = $valued->sum('value');
        $cumulative = 0;

        foreach ($valued as $row) {
            $cumulative += $row['value'];
            $pct = $totalValue > 0 ? $cumulative / $totalValue : 0;
            $abcClass = $pct <= 0.8 ? 'A' : ($pct <= 0.95 ? 'B' : 'C');
            $row['item']->update(['abc_class' => $abcClass]);
        }

        $since = now()->subMonths(6);

        foreach ($items as $item) {
            $monthly = StockLedger::where('company_id', $companyId)->where('item_id', $item->id)
                ->whereIn('transaction_type', self::OUTFLOW_TYPES)->where('created_at', '>=', $since)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(ABS(quantity_change)) as qty")
                ->groupBy('ym')->pluck('qty');

            if ($monthly->count() < 2 || $monthly->sum() == 0) {
                $item->update(['xyz_class' => 'Z']);

                continue;
            }

            $mean = $monthly->avg();
            $variance = $monthly->map(fn ($v) => ($v - $mean) ** 2)->avg();
            $stdDev = sqrt($variance);
            $cv = $mean > 0 ? $stdDev / $mean : 1;

            $xyzClass = $cv <= 0.5 ? 'X' : ($cv <= 1.0 ? 'Y' : 'Z');
            $item->update(['xyz_class' => $xyzClass]);
        }

        return redirect()->route('admin.inventory.abc-xyz.index')->with('status', 'ABC/XYZ classification recalculated.');
    }
}
