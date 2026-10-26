<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Opportunity;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesForecastController extends Controller
{
    /**
     * Opportunities at or above this probability count as "committed" -
     * likely enough to close that finance can plan around them. Everything
     * open below it is "best case": possible, but not safe to count on.
     */
    protected const COMMITTED_THRESHOLD = 70;

    /**
     * Weighted revenue forecast for the next 6 months, built from open
     * opportunities grouped by their expected close month. "Weighted"
     * means amount x probability - the standard way to forecast without
     * pretending every open deal will close at full value.
     */
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $ownerId = $request->query('owner_employee_id');

        $query = Opportunity::where('company_id', $companyId)
            ->where('status', 'open')
            ->whereNotNull('expected_close_date')
            ->with('stage');

        if ($ownerId) {
            $query->where('owner_employee_id', $ownerId);
        }

        $open = $query->get();

        $months = collect(range(0, 5))->map(fn ($i) => now()->copy()->startOfMonth()->addMonths($i));

        $forecast = $months->map(function ($month) use ($open) {
            $inMonth = $open->filter(fn ($o) => $o->expected_close_date->isSameMonth($month));
            $committed = $inMonth->filter(fn ($o) => $o->effective_probability >= self::COMMITTED_THRESHOLD);
            $bestCase = $inMonth->filter(fn ($o) => $o->effective_probability < self::COMMITTED_THRESHOLD);

            return [
                'month' => $month,
                'count' => $inMonth->count(),
                'amount' => $inMonth->sum('amount'),
                'weighted' => $inMonth->sum(fn ($o) => $o->weighted_amount),
                'committed' => $committed->sum('amount'),
                'bestCase' => $bestCase->sum(fn ($o) => $o->weighted_amount),
            ];
        });

        $noDateCountQuery = Opportunity::where('company_id', $companyId)->where('status', 'open')->whereNull('expected_close_date');

        if ($ownerId) {
            $noDateCountQuery->where('owner_employee_id', $ownerId);
        }

        return view('admin.crm.sales-forecast.index', [
            'forecast' => $forecast,
            'ownerId' => $ownerId,
            'owners' => Employee::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'totalWeighted' => $forecast->sum('weighted'),
            'totalAmount' => $forecast->sum('amount'),
            'totalCommitted' => $forecast->sum('committed'),
            'totalCount' => $open->count(),
            'noDateCount' => $noDateCountQuery->count(),
        ]);
    }
}
