@extends('layouts.admin')

@section('title', 'Sales Dashboard')

@section('content')
    <x-page-title title="Sales Dashboard" />

    <div class="kpi-strip mb-3">
        <x-kpi-card label="Total Revenue" :value="number_format($totalRevenue, 2)" icon="ti ti-currency-rupee" />
        <x-kpi-card label="Pending Orders" :value="$pendingOrders" icon="ti ti-clock" />
        <x-kpi-card label="Confirmed Orders" :value="$confirmedOrders" icon="ti ti-circle-check" />
        <x-kpi-card label="Fulfilled Orders" :value="$fulfilledOrders" icon="ti ti-truck-delivery" />
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-filter"></i> Sales Funnel (Quotations)</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Stage</th><th>Count</th></tr></thead>
                        <tbody>
                            @foreach ($funnel as $stage)
                                <tr><td class="font-13">{{ $stage['label'] }}</td><td class="text-muted font-13">{{ $stage['count'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-trophy"></i> Top Salespeople (by Order Value)</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Salesperson</th><th>Orders</th><th>Total Sales</th></tr></thead>
                        <tbody>
                            @forelse ($topSalespeople as $row)
                                <tr>
                                    <td class="font-13">{{ $row->owner?->name ?? 'Unassigned' }}</td>
                                    <td class="text-muted font-13">{{ $row->order_count }}</td>
                                    <td class="font-13 fw-semibold">{{ number_format($row->total_sales, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-clock"></i> Pending Quotation Approvals <span class="list-tab-count">{{ $pendingApprovals->count() }}</span></div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Quote #</th><th>Account</th><th>Owner</th><th>Total</th></tr></thead>
                        <tbody>
                            @forelse ($pendingApprovals as $quote)
                                <tr>
                                    <td class="font-monospace font-13">{{ $quote->quotation_number }}</td>
                                    <td class="text-muted font-13">{{ $quote->account?->name }}</td>
                                    <td class="text-muted font-13">{{ $quote->owner?->name }}</td>
                                    <td class="text-muted font-13">{{ number_format($quote->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Nothing pending approval.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-alert-triangle"></i> Customers Over Credit Limit <span class="list-tab-count">{{ $customersOverLimit->count() }}</span></div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Account</th><th>Credit Limit</th><th>Outstanding</th></tr></thead>
                        <tbody>
                            @forelse ($customersOverLimit as $profile)
                                <tr>
                                    <td class="font-13">{{ $profile->account?->name }}</td>
                                    <td class="text-muted font-13">{{ number_format($profile->credit_limit, 2) }}</td>
                                    <td class="font-13 text-danger fw-semibold">{{ number_format($profile->outstanding_balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No customers over their credit limit.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view mt-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-list"></i> Recent Sales Orders</div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Order #</th><th>Account</th><th>Owner</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td class="font-monospace font-13">{{ $order->order_number }}</td>
                            <td class="text-muted font-13">{{ $order->account?->name }}</td>
                            <td class="text-muted font-13">{{ $order->owner?->name }}</td>
                            <td class="text-muted font-13">{{ $order->order_date?->format('M j, Y') }}</td>
                            <td class="text-muted font-13">{{ number_format($order->total, 2) }}</td>
                            <td><span class="tag tone-{{ $order->status === 'fulfilled' ? 'success' : ($order->status === 'cancelled' ? 'danger' : 'amber') }} font-11">{{ \App\Models\Crm\SalesOrder::STATUSES[$order->status] ?? $order->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-shopping-cart font-24 d-block mb-2"></i>No sales orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
