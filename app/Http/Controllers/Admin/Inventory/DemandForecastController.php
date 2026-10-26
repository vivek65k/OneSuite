<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\DemandForecast;
use App\Models\Inventory\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DemandForecastController extends Controller
{
    protected const OUTFLOW_TYPES = ['issue', 'transfer_out', 'return_out'];

    protected const LOOKBACK_MONTHS = 3;

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.demand-forecast.index', [
            'forecasts' => DemandForecast::where('company_id', $companyId)->with(['item', 'warehouse'])->orderByDesc('forecast_qty')->get(),
        ]);
    }

    /**
     * Simple moving-average forecast: historical monthly outflow (issue,
     * transfer-out, return-out) over the lookback window, averaged and
     * projected forward as next month's expected demand. Known seasonality
     * or demand would adjust this average in a fuller implementation.
     */
    public function generate(): RedirectResponse
    {
        $companyId = Company::current()->id;
        $since = now()->subMonths(self::LOOKBACK_MONTHS);

        $rows = StockLedger::where('company_id', $companyId)->whereIn('transaction_type', self::OUTFLOW_TYPES)
            ->where('created_at', '>=', $since)
            ->selectRaw('warehouse_id, item_id, SUM(ABS(quantity_change)) as total_qty')
            ->groupBy('warehouse_id', 'item_id')->get();

        DemandForecast::where('company_id', $companyId)->delete();

        foreach ($rows as $row) {
            $monthlyAvg = (float) $row->total_qty / self::LOOKBACK_MONTHS;

            DemandForecast::create([
                'company_id' => $companyId, 'warehouse_id' => $row->warehouse_id, 'item_id' => $row->item_id,
                'period_label' => 'Next 30 days', 'historical_avg_qty' => $monthlyAvg, 'forecast_qty' => $monthlyAvg,
                'method' => 'moving_average', 'generated_at' => now(),
            ]);
        }

        return redirect()->route('admin.inventory.demand-forecast.index')->with('status', $rows->isEmpty()
            ? 'No outbound movement found in the lookback window yet - record some issues or transfers first.'
            : "Forecast regenerated for {$rows->count()} item/warehouse combination(s).");
    }
}
