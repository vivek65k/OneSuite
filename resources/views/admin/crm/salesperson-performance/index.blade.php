@extends('layouts.admin')

@section('title', 'Salesperson Performance')

@section('content')
    <x-page-title title="Salesperson Performance" />

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="segmented" role="group" aria-label="Range">
                <a href="{{ request()->fullUrlWithQuery(['range' => '30']) }}" class="segmented-item {{ $range === '30' ? 'active' : '' }}">30 Days</a>
                <a href="{{ request()->fullUrlWithQuery(['range' => '90']) }}" class="segmented-item {{ $range === '90' ? 'active' : '' }}">90 Days</a>
                <a href="{{ request()->fullUrlWithQuery(['range' => '365']) }}" class="segmented-item {{ $range === '365' ? 'active' : '' }}">12 Months</a>
            </div>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Team Revenue Won" value="{{ number_format($totalWonAmount, 0) }}" icon="ti-cash" tone="success" />
        <x-kpi-card label="Deals Won" :value="$totalWonCount" icon="ti-trophy" tone="indigo" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-trophy"></i> Leaderboard</div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Rank</th><th>Salesperson</th><th class="text-end">Open Deals</th><th class="text-end">Won</th><th class="text-end">Revenue</th><th class="text-end">Win Rate</th><th class="text-end">Activities</th></tr></thead>
                <tbody>
                    @forelse ($rows as $i => $row)
                        <tr>
                            <td>#{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $row['employee']->name }}</td>
                            <td class="text-end">{{ $row['open_count'] }}</td>
                            <td class="text-end">{{ $row['won_count'] }}</td>
                            <td class="text-end">{{ number_format($row['won_amount'], 0) }}</td>
                            <td class="text-end">{{ $row['win_rate'] }}%</td>
                            <td class="text-end">{{ $row['activities_count'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-trophy font-24 d-block mb-2"></i>No activity in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
