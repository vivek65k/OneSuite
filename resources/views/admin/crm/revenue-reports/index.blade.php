@extends('layouts.admin')

@section('title', 'Revenue Reports')

@section('content')
    <x-page-title title="Revenue Reports" />

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="segmented" role="group" aria-label="Range">
                <a href="{{ request()->fullUrlWithQuery(['months' => 6]) }}" class="segmented-item {{ $months == 6 ? 'active' : '' }}">6 Months</a>
                <a href="{{ request()->fullUrlWithQuery(['months' => 12]) }}" class="segmented-item {{ $months == 12 ? 'active' : '' }}">12 Months</a>
            </div>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Won Opportunity Revenue" value="{{ number_format($totalOpportunityRevenue, 0) }}" icon="ti-cash" tone="indigo" />
        <x-kpi-card label="Sales Order Revenue" value="{{ number_format($totalOrderRevenue, 0) }}" icon="ti-receipt" tone="success" />
        <x-kpi-card label="Orders" :value="$orderCount" icon="ti-shopping-cart" tone="sky" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-chart-bar"></i> Revenue by Month</div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Month</th><th class="text-end">Opportunity Revenue</th><th class="text-end">Order Revenue</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    @forelse ($byMonth as $month => $row)
                        <tr>
                            <td class="fw-semibold">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y') }}</td>
                            <td class="text-end">{{ number_format($row['opportunity_revenue'], 0) }}</td>
                            <td class="text-end">{{ number_format($row['order_revenue'], 0) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($row['opportunity_revenue'] + $row['order_revenue'], 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No revenue data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
