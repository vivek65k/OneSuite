@extends('layouts.admin')

@section('title', 'Pipeline Reports')

@section('content')
    <x-page-title title="Pipeline Reports" />

    <div class="kpi-strip">
        <x-kpi-card label="Open Deals" :value="$totalOpen" icon="ti-briefcase" tone="indigo" />
        <x-kpi-card label="Pipeline Value" value="{{ number_format($totalValue, 0) }}" icon="ti-cash" tone="violet" />
        <x-kpi-card label="Weighted Value" value="{{ number_format($totalWeighted, 0) }}" icon="ti-chart-bar" tone="sky" />
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-chart-bar"></i> By Stage</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Stage</th><th class="text-end">Deals</th><th class="text-end">Value</th><th class="text-end">Weighted</th></tr></thead>
                        <tbody>
                            @forelse ($byStage as $row)
                                <tr><td class="fw-semibold">{{ $row['name'] }}</td><td class="text-end">{{ $row['count'] }}</td><td class="text-end">{{ number_format($row['amount'], 0) }}</td><td class="text-end">{{ number_format($row['weighted'], 0) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No stages configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-clock"></i> Pipeline Aging</div></div>
                <div class="p-3">
                    @foreach ($aging as $bucket => $count)
                        @php $max = max($aging) ?: 1; @endphp
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="font-13" style="width: 90px;">{{ $bucket }}</span>
                            <div class="mini-progress flex-grow-1"><span style="width: {{ $count / $max * 100 }}%;" class="bg-warning"></span></div>
                            <span class="font-13 text-muted" style="width: 36px;">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="list-toolbar border-top"><div class="list-toolbar-title"><i class="ti ti-users"></i> By Owner</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Owner</th><th class="text-end">Deals</th><th class="text-end">Value</th></tr></thead>
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
