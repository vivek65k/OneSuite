@extends('layouts.admin')

@section('title', 'AI Sales Forecasting')

@section('content')
    <x-page-title title="AI Sales Forecasting" />

    <div class="kpi-strip">
        <x-kpi-card label="Avg Monthly Revenue" value="{{ number_format($avgMonthly, 0) }}" icon="ti-cash" tone="indigo" />
        <x-kpi-card label="Trend" value="{{ ucfirst($trend) }}" icon="{{ $trend === 'up' ? 'ti-trending-up' : ($trend === 'down' ? 'ti-trending-down' : 'ti-minus') }}" tone="{{ $trend === 'up' ? 'success' : ($trend === 'down' ? 'rose' : 'slate') }}" />
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-chart-bar"></i> Last 6 Months (Actual)</div></div>
                <div class="p-3">
                    @foreach ($history as $row)
                        @php $max = $history->max('amount') ?: 1; @endphp
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="font-13" style="width: 64px;">{{ $row['month']->format('M Y') }}</span>
                            <div class="mini-progress flex-grow-1"><span style="width: {{ $row['amount'] / $max * 100 }}%;" class="bg-primary"></span></div>
                            <span class="font-13 text-muted" style="width: 90px;">{{ number_format($row['amount'], 0) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-trending-up"></i> Next 3 Months (Projected)</div></div>
                <div class="p-3">
                    @foreach ($projection as $row)
                        @php $max = $projection->max('projected') ?: 1; @endphp
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="font-13" style="width: 64px;">{{ $row['month']->format('M Y') }}</span>
                            <div class="mini-progress flex-grow-1"><span style="width: {{ $row['projected'] / $max * 100 }}%;" class="bg-success"></span></div>
                            <span class="font-13 text-muted" style="width: 90px;">{{ number_format($row['projected'], 0) }}</span>
                        </div>
                    @endforeach
                    <p class="text-muted font-12 mt-2 mb-0">Projection uses linear trend regression over the last 6 months of won revenue.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
