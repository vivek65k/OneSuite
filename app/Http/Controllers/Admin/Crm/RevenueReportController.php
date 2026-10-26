<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Opportunity;
use App\Models\Crm\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevenueReportController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $months = (int) $request->query('months', 12);
        $since = now()->subMonths($months)->startOfMonth();

        $won = Opportunity::where('company_id', $companyId)->where('status', 'won')->where('closed_at', '>=', $since)->get();
        $orders = SalesOrder::where('company_id', $companyId)->where('order_date', '>=', $since)->whereIn('status', ['confirmed', 'fulfilled'])->get();

        $byMonth = collect();
        for ($i = $months - 1; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $byMonth[$key] = [
                'opportunity_revenue' => (float) $won->filter(fn ($o) => $o->closed_at->format('Y-m') === $key)->sum('amount'),
                'order_revenue' => (float) $orders->filter(fn ($o) => $o->order_date->format('Y-m') === $key)->sum('total'),
            ];
        }

        return view('admin.crm.revenue-reports.index', [
            'months' => $months,
            'byMonth' => $byMonth,
            'totalOpportunityRevenue' => (float) $won->sum('amount'),
            'totalOrderRevenue' => (float) $orders->sum('total'),
            'orderCount' => $orders->count(),
        ]);
    }
}
