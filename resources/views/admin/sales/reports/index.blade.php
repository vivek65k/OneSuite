@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('content')
    <x-page-title title="Reports & Analytics" />

    <p class="text-muted font-13 mb-3"><i class="ti ti-info-circle"></i> This is booked-revenue reporting (actual Sales Orders) and real margin from Inventory cost prices - CRM's pipeline-side reports (forecast, win/loss, pipeline value) are linked below rather than duplicated here.</p>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-link"></i> Pipeline Reports (CRM)</div></div>
        <div class="list-filter-panel">
            <div class="row g-2">
                <div class="col-md-3"><a href="{{ route('admin.crm.sales-forecast.index') }}" class="btn btn-light w-100"><i class="ti ti-chart-line font-14 me-1"></i> Sales Forecast</a></div>
                <div class="col-md-3"><a href="{{ route('admin.crm.sales-reports.index') }}" class="btn btn-light w-100"><i class="ti ti-report-analytics font-14 me-1"></i> Pipeline Won/Lost</a></div>
                <div class="col-md-3"><a href="{{ route('admin.crm.pipeline-reports.index') }}" class="btn btn-light w-100"><i class="ti ti-filter font-14 me-1"></i> Pipeline Reports</a></div>
                <div class="col-md-3"><a href="{{ route('admin.crm.salesperson-performance.index') }}" class="btn btn-light w-100"><i class="ti ti-trophy font-14 me-1"></i> Salesperson Performance</a></div>
            </div>
        </div>
    </div>

    <form method="GET" class="mb-3">
        <div class="d-flex align-items-end gap-2">
            <div>
                <label class="form-label font-11 mb-0">Period</label>
                <select name="months" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach ([3 => '3 months', 6 => '6 months', 12 => '12 months'] as $value => $label)
                        <option value="{{ $value }}" @selected($months == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="kpi-strip mb-3">
        <x-kpi-card label="Booked Revenue" :value="number_format($totalRevenue, 2)" icon="ti-currency-rupee" tone="success" />
        <x-kpi-card label="Orders" :value="$totalOrders" icon="ti-shopping-cart" />
        <x-kpi-card label="Avg Order Value" :value="number_format($avgOrderValue, 2)" icon="ti-receipt" />
        <x-kpi-card label="Overall Margin" :value="number_format($profitability['marginPct'], 1).'%'" icon="ti-trending-up" tone="amber" />
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-calendar"></i> Revenue by Month</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Month</th><th>Orders</th><th>Revenue</th></tr></thead>
                        <tbody>
                            @forelse ($byMonth as $month => $row)
                                <tr><td class="font-13">{{ $month }}</td><td class="text-muted font-13">{{ $row['count'] }}</td><td class="font-13 fw-semibold">{{ number_format($row['amount'], 2) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No booked revenue in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-trophy"></i> Revenue by Salesperson</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Salesperson</th><th>Orders</th><th>Revenue</th></tr></thead>
                        <tbody>
                            @forelse ($byOwner as $owner => $row)
                                <tr><td class="font-13">{{ $owner }}</td><td class="text-muted font-13">{{ $row['count'] }}</td><td class="font-13 fw-semibold">{{ number_format($row['amount'], 2) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No booked revenue in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-trending-up"></i> Profitability by Product <span class="list-tab-count">{{ $profitability['rows']->count() }}</span></div></div>
        @if ($profitability['uncostedCount'] > 0)
            <div class="alert alert-warning m-3 mb-0 font-13">{{ $profitability['uncostedCount'] }} order line(s) are excluded - their product isn't linked to an Inventory item yet, so no cost price is available.</div>
        @endif
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Product</th><th>Qty Sold</th><th>Revenue</th><th>Cost</th><th>Margin</th><th>Margin %</th></tr></thead>
                <tbody>
                    @forelse ($profitability['rows'] as $product => $row)
                        <tr>
                            <td class="font-13">{{ $product }}</td>
                            <td class="text-muted font-13">{{ $row['quantity'] }}</td>
                            <td class="text-muted font-13">{{ number_format($row['revenue'], 2) }}</td>
                            <td class="text-muted font-13">{{ number_format($row['cost'], 2) }}</td>
                            <td class="font-13 fw-semibold {{ $row['margin'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($row['margin'], 2) }}</td>
                            <td class="text-muted font-13">{{ $row['revenue'] > 0 ? round($row['margin'] / $row['revenue'] * 100, 1) : 0 }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-trending-up font-24 d-block mb-2"></i>No costed sales lines in this period.</td></tr>
                    @endforelse
                </tbody>
                @if ($profitability['rows']->isNotEmpty())
                    <tfoot>
                        <tr class="fw-semibold">
                            <td class="font-13">Total</td><td></td>
                            <td class="font-13">{{ number_format($profitability['totalRevenue'], 2) }}</td>
                            <td class="font-13">{{ number_format($profitability['totalCost'], 2) }}</td>
                            <td class="font-13 {{ $profitability['totalMargin'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($profitability['totalMargin'], 2) }}</td>
                            <td class="font-13">{{ $profitability['marginPct'] }}%</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
