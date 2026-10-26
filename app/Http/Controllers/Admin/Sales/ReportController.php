<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\SalesOrder;
use App\Models\Crm\SalesOrderLine;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRM already has pipeline-side reports (Sales Reports, Sales Forecast,
 * Pipeline Reports, Win/Loss, Salesperson Performance) built on
 * Opportunities - this is the Sales-side complement built on actual
 * booked revenue (Sales Orders), plus the one report nothing else in
 * OneSuite has yet: real margin/profitability per line, using Inventory
 * cost_price against the Sales Order's unit_price.
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $months = (int) $request->query('months', 6);
        $since = now()->subMonths($months)->startOfMonth();

        $orders = SalesOrder::where('company_id', $companyId)->where('status', '!=', 'cancelled')
            ->where('order_date', '>=', $since)->with('owner')->get();

        $byMonth = $orders->groupBy(fn ($o) => $o->order_date?->format('Y-m') ?? 'Unknown')
            ->map(fn ($group) => ['count' => $group->count(), 'amount' => (float) $group->sum('total')])
            ->sortKeys();

        $byOwner = $orders->groupBy(fn ($o) => $o->owner?->name ?? 'Unassigned')
            ->map(fn ($group) => ['count' => $group->count(), 'amount' => (float) $group->sum('total')])
            ->sortByDesc('amount');

        $profitability = $this->profitability($companyId, $since);

        return view('admin.sales.reports.index', [
            'months' => $months,
            'byMonth' => $byMonth,
            'byOwner' => $byOwner,
            'totalRevenue' => (float) $orders->sum('total'),
            'totalOrders' => $orders->count(),
            'avgOrderValue' => $orders->count() > 0 ? round($orders->avg('total'), 2) : 0,
            'profitability' => $profitability,
        ]);
    }

    /**
     * Margin only computes for lines whose product is linked to an
     * Inventory item (Product::inventory_item_id) - that's where the real
     * cost_price lives; unlinked lines are excluded and reported as a
     * count so the numbers aren't silently incomplete.
     */
    protected function profitability(int $companyId, \Illuminate\Support\Carbon $since): array
    {
        $lines = SalesOrderLine::whereHas('salesOrder', fn ($q) => $q->where('company_id', $companyId)->where('status', '!=', 'cancelled')->where('order_date', '>=', $since))
            ->with(['product.inventoryItem', 'salesOrder'])->get();

        $costed = $lines->filter(fn ($line) => $line->product?->inventoryItem);
        $uncostedCount = $lines->count() - $costed->count();

        $rows = $costed->map(function ($line) {
            $cost = (float) $line->product->inventoryItem->cost_price * (float) $line->quantity;
            $revenue = (float) $line->line_total;

            return [
                'product' => $line->product->name,
                'order' => $line->salesOrder?->order_number,
                'quantity' => $line->quantity,
                'revenue' => $revenue,
                'cost' => $cost,
                'margin' => $revenue - $cost,
                'margin_pct' => $revenue > 0 ? round(($revenue - $cost) / $revenue * 100, 1) : 0,
            ];
        });

        $byProduct = $rows->groupBy('product')->map(fn ($group) => [
            'quantity' => $group->sum('quantity'),
            'revenue' => $group->sum('revenue'),
            'cost' => $group->sum('cost'),
            'margin' => $group->sum('margin'),
        ])->sortByDesc('margin');

        return [
            'rows' => $byProduct,
            'totalRevenue' => $rows->sum('revenue'),
            'totalCost' => $rows->sum('cost'),
            'totalMargin' => $rows->sum('margin'),
            'marginPct' => $rows->sum('revenue') > 0 ? round($rows->sum('margin') / $rows->sum('revenue') * 100, 1) : 0,
            'uncostedCount' => $uncostedCount,
        ];
    }
}
