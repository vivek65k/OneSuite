@extends('layouts.admin')

@section('title', 'Sales Reports')

@section('content')
    <x-page-title title="Sales Reports" />

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="segmented" role="group" aria-label="Range">
                <a href="{{ request()->fullUrlWithQuery(['months' => 3]) }}" class="segmented-item {{ $months == 3 ? 'active' : '' }}">3 Months</a>
                <a href="{{ request()->fullUrlWithQuery(['months' => 6]) }}" class="segmented-item {{ $months == 6 ? 'active' : '' }}">6 Months</a>
                <a href="{{ request()->fullUrlWithQuery(['months' => 12]) }}" class="segmented-item {{ $months == 12 ? 'active' : '' }}">12 Months</a>
            </div>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Deals Won" :value="$totalWon" icon="ti-trophy" tone="success" />
        <x-kpi-card label="Won Revenue" value="{{ number_format($totalWonAmount, 0) }}" icon="ti-cash" tone="indigo" />
        <x-kpi-card label="Win Rate" value="{{ $winRate }}%" icon="ti-chart-pie" tone="sky" />
        <x-kpi-card label="Avg Deal Size" value="{{ number_format($avgDealSize, 0) }}" icon="ti-coin" tone="amber" />
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-chart-bar"></i> Won Revenue by Month</div></div>
                <div class="p-3">
                    @forelse ($byMonth as $month => $row)
                        @php $max = $byMonth->max('amount') ?: 1; @endphp
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="font-13" style="width: 64px;">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y') }}</span>
                            <div class="mini-progress flex-grow-1"><span style="width: {{ $row['amount'] / $max * 100 }}%;" class="bg-success"></span></div>
                            <span class="font-13 text-muted" style="width: 90px;">{{ number_format($row['amount'], 0) }} ({{ $row['count'] }})</span>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4">No won deals in this range.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-trophy"></i> Top Performers</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Owner</th><th class="text-end">Deals</th><th class="text-end">Revenue</th></tr></thead>
                        <tbody>
                            @forelse ($byOwner as $owner => $row)
                                <tr><td class="fw-semibold">{{ $owner }}</td><td class="text-end">{{ $row['count'] }}</td><td class="text-end">{{ number_format($row['amount'], 0) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No data yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
