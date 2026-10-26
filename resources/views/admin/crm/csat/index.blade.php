@extends('layouts.admin')

@section('title', 'Customer Satisfaction (CSAT)')

@section('content')
    <x-page-title title="Customer Satisfaction (CSAT)" />

    <div class="kpi-strip">
        <x-kpi-card label="Avg CSAT" value="{{ $avgRating }} / 5" icon="ti-mood-smile" tone="success" />
        <x-kpi-card label="Responses" :value="$totalResponses" icon="ti-message-report" tone="indigo" />
        <x-kpi-card label="Promoters (4-5★)" :value="$promoters" icon="ti-thumb-up" tone="success" />
        <x-kpi-card label="Detractors (1-2★)" :value="$detractors" icon="ti-mood-sad" tone="rose" />
        <x-kpi-card label="Case CSAT" value="{{ $avgCaseCsat }} / 5" icon="ti-ticket" tone="violet" />
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-chart-bar"></i> Rating Distribution</div></div>
                <div class="p-3">
                    @foreach ($distribution->reverse() as $star => $count)
                        @php $pct = $totalResponses > 0 ? round($count / $totalResponses * 100) : 0; @endphp
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="font-13" style="width: 48px;">{{ $star }}★</span>
                            <div class="mini-progress flex-grow-1"><span style="width: {{ $pct }}%;" class="bg-{{ $star >= 4 ? 'success' : ($star <= 2 ? 'danger' : 'warning') }}"></span></div>
                            <span class="font-13 text-muted" style="width: 36px;">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-chart-line"></i> Monthly Trend</div></div>
                <div class="p-3">
                    @forelse ($monthly as $month => $avg)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="font-13" style="width: 64px;">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y') }}</span>
                            <div class="mini-progress flex-grow-1"><span style="width: {{ $avg / 5 * 100 }}%;" class="bg-success"></span></div>
                            <span class="font-13 text-muted" style="width: 36px;">{{ $avg }}</span>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4">No feedback data yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
