<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Quotation;
use App\Models\Crm\SalesOrder;
use App\Models\Sales\CustomerProfile;
use Illuminate\View\View;

class SalesDashboardController extends Controller
{
    /**
     * Quotations and Sales Orders already live in the CRM module (shared
     * records, not duplicated here) - this dashboard reads them directly
     * rather than mirroring the data into a second Sales-owned table.
     */
    public function index(): View
    {
        $companyId = Company::current()->id;

        $orders = SalesOrder::where('company_id', $companyId);
        $quotations = Quotation::where('company_id', $companyId);

        return view('admin.sales.dashboard', [
            'totalRevenue' => (clone $orders)->where('status', '!=', 'cancelled')->sum('total'),
            'pendingOrders' => (clone $orders)->where('status', 'pending')->count(),
            'confirmedOrders' => (clone $orders)->where('status', 'confirmed')->count(),
            'fulfilledOrders' => (clone $orders)->where('status', 'fulfilled')->count(),
            'funnel' => collect(Quotation::STATUSES)->map(fn ($label, $key) => [
                'label' => $label, 'count' => (clone $quotations)->where('status', $key)->count(),
            ]),
            'pendingApprovals' => (clone $quotations)->where('status', 'pending_approval')->with(['account', 'owner'])->orderByDesc('created_at')->limit(10)->get(),
            'recentOrders' => SalesOrder::where('company_id', $companyId)->with(['account', 'owner'])->orderByDesc('order_date')->limit(10)->get(),
            'topSalespeople' => SalesOrder::where('company_id', $companyId)->where('status', '!=', 'cancelled')
                ->selectRaw('owner_employee_id, SUM(total) as total_sales, COUNT(*) as order_count')
                ->whereNotNull('owner_employee_id')->groupBy('owner_employee_id')->orderByDesc('total_sales')->limit(5)
                ->with('owner')->get(),
            'customersOverLimit' => CustomerProfile::where('company_id', $companyId)->whereColumn('outstanding_balance', '>', 'credit_limit')->where('credit_limit', '>', 0)->with('account')->get(),
        ]);
    }
}
