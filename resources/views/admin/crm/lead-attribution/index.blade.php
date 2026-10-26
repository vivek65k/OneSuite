@extends('layouts.admin')

@section('title', 'Lead Attribution')

@section('content')
    <x-page-title title="Lead Attribution" />

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="segmented" role="group" aria-label="Range">
                <a href="{{ request()->fullUrlWithQuery(['range' => '30']) }}" class="segmented-item {{ $range === '30' ? 'active' : '' }}">30 Days</a>
                <a href="{{ request()->fullUrlWithQuery(['range' => '90']) }}" class="segmented-item {{ $range === '90' ? 'active' : '' }}">90 Days</a>
                <a href="{{ request()->fullUrlWithQuery(['range' => '365']) }}" class="segmented-item {{ $range === '365' ? 'active' : '' }}">12 Months</a>
                <a href="{{ request()->fullUrlWithQuery(['range' => 'all']) }}" class="segmented-item {{ $range === 'all' ? 'active' : '' }}">All Time</a>
            </div>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Total Leads" :value="$totalLeads" icon="ti-target" tone="indigo" />
        <x-kpi-card label="Converted" :value="$convertedTotal" icon="ti-circle-check" tone="success" />
        <x-kpi-card label="Unattributed" :value="$unattributed" icon="ti-question-mark" tone="amber" />
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-route"></i> By Lead Source</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Source</th><th class="text-end">Leads</th><th class="text-end">Qualified</th><th class="text-end">Converted</th></tr></thead>
                        <tbody>
                            @forelse ($bySource as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['name'] }}</td>
                                    <td class="text-end">{{ $row['total'] }}</td>
                                    <td class="text-end">{{ $row['qualified'] }}</td>
                                    <td class="text-end"><span class="tag tone-success">{{ $row['converted'] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No attributed leads in this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-speakerphone"></i> By Campaign</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Campaign</th><th class="text-end">Leads</th><th class="text-end">Converted</th><th class="text-end">Cost / Lead</th></tr></thead>
                        <tbody>
                            @forelse ($byCampaign as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['name'] }} <span class="tag tone-indigo font-11">{{ $row['type'] }}</span></td>
                                    <td class="text-end">{{ $row['total'] }}</td>
                                    <td class="text-end"><span class="tag tone-success">{{ $row['converted'] }}</span></td>
                                    <td class="text-end">{{ number_format($row['cost_per_lead'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No campaign-attributed leads in this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
