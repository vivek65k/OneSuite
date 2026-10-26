<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Opportunity;
use Illuminate\View\View;

class AiSalesForecastController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;
        $history = collect(range(5, 0))->map(function ($i) use ($companyId) {
            $month = now()->copy()->subMonths($i)->startOfMonth();

            return [
                'month' => $month,
                'amount' => (float) Opportunity::where('company_id', $companyId)->where('status', 'won')
                    ->whereYear('closed_at', $month->year)->whereMonth('closed_at', $month->month)->sum('amount'),
            ];
        });

        $values = $history->pluck('amount');
        $n = $values->count();
        $x = range(0, $n - 1);
        $meanX = array_sum($x) / $n;
        $meanY = $values->avg();

        $numerator = 0;
        $denominator = 0;
        foreach ($x as $i => $xi) {
            $numerator += ($xi - $meanX) * ($values[$i] - $meanY);
            $denominator += ($xi - $meanX) ** 2;
        }
        $slope = $denominator > 0 ? $numerator / $denominator : 0;
        $intercept = $meanY - $slope * $meanX;

        $projection = collect(range($n, $n + 2))->map(function ($i) use ($slope, $intercept, $n) {
            return [
                'month' => now()->copy()->addMonths($i - $n + 1)->startOfMonth(),
                'projected' => max(0, round($slope * $i + $intercept, 2)),
            ];
        });

        return view('admin.crm.ai-sales-forecast.index', [
            'history' => $history,
            'projection' => $projection,
            'trend' => $slope > 0 ? 'up' : ($slope < 0 ? 'down' : 'flat'),
            'avgMonthly' => round($meanY, 2),
        ]);
    }
}
