@extends('layouts.admin')

@section('title', 'Lead Conversion')

@section('content')
    <x-page-title title="Lead Conversion" />

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="segmented" role="group" aria-label="Range">
                <a href="{{ request()->fullUrlWithQuery(['range' => '30']) }}" class="segmented-item {{ $range === '30' ? 'active' : '' }}">30 Days</a>
                <a href="{{ request()->fullUrlWithQuery(['range' => '90']) }}" class="segmented-item {{ $range === '90' ? 'active' : '' }}">90 Days</a>
                <a href="{{ request()->fullUrlWithQuery(['range' => 'all']) }}" class="segmented-item {{ $range === 'all' ? 'active' : '' }}">All Time</a>
            </div>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Total Leads" :value="$totalLeads" icon="ti-target" tone="indigo" />
        <x-kpi-card label="Converted" :value="$converted" icon="ti-circle-check" tone="success" />
        <x-kpi-card label="Conversion Rate" value="{{ $conversionRate }}%" icon="ti-chart-pie" tone="sky" />
        <x-kpi-card label="Avg Days to Convert" :value="$avgDaysToConvert" icon="ti-calendar-time" tone="amber" />
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-chart-dots"></i> Conversion Funnel</div></div>
                <div class="p-3">
                    @foreach ($funnel as $status => $row)
                        @php $max = $funnel->max('count') ?: 1; @endphp
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="font-13" style="width: 90px;">{{ $row['label'] }}</span>
                            <div class="mini-progress flex-grow-1"><span style="width: {{ $row['count'] / $max * 100 }}%;" class="bg-primary"></span></div>
                            <span class="font-13 text-muted" style="width: 36px;">{{ $row['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-route"></i> Conversion by Source</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Source</th><th class="text-end">Leads</th><th class="text-end">Converted</th><th class="text-end">Rate</th></tr></thead>
                        <tbody>
                            @forelse ($bySource as $row)
                                <tr><td class="fw-semibold">{{ $row['name'] }}</td><td class="text-end">{{ $row['total'] }}</td><td class="text-end">{{ $row['converted'] }}</td><td class="text-end">{{ $row['rate'] }}%</td></tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No data yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
