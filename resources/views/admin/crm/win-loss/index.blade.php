@extends('layouts.admin')

@section('title', 'Win / Loss Analysis')

@php
    $reasonMax = max(1, $byReason->max());
@endphp

@section('content')
    <x-page-title title="Win / Loss Analysis" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $winRate >= 50 ? 'success' : 'amber' }}" aria-hidden="true"><i class="ti ti-trophy font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $winRate }}% win rate in {{ $year }}</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-success">{{ $wonCount }} won</span>
                <span class="status-dot status-dot-danger">{{ $lostCount }} lost</span>
            </div>
        </div>
        <div class="record-header-actions">
            <form method="GET" action="{{ route('admin.crm.win-loss-analysis.index') }}">
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Year">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Win Rate" :value="$winRate.'%'" icon="ti-trophy" tone="success" />
        <x-kpi-card label="Won Value" :value="number_format($wonValue)" icon="ti-currency-rupee" tone="success" />
        <x-kpi-card label="Lost Value" :value="number_format($lostValue)" icon="ti-currency-rupee" tone="rose" />
        <x-kpi-card label="Avg Sales Cycle" :value="$avgCycleDays.' days'" icon="ti-calendar-time" tone="indigo" hint="Create to close" />
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card list-view tone-rose mb-0 h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-flag"></i> Lost Reasons</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($byReason as $reason => $count)
                                <tr>
                                    <td class="fw-semibold">{{ $reason }}</td>
                                    <td style="min-width: 140px">
                                        <div class="mini-progress tone-rose">
                                            <div class="mini-progress-bar is-toned"><span style="width: {{ round($count / $reasonMax * 100) }}%"></span></div>
                                        </div>
                                    </td>
                                    <td class="text-end"><span class="tag tone-rose">{{ $count }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-5">No lost deals yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card list-view tone-indigo mb-0 h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-shield"></i> Competitor Record</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr><th>Competitor</th><th class="text-center">Won Against</th><th class="text-center">Lost To</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($competitors as $competitor)
                                <tr>
                                    <td class="fw-semibold">{{ $competitor->name }}</td>
                                    <td class="text-center"><span class="tag tone-success">{{ $competitor->won_against }}</span></td>
                                    <td class="text-center"><span class="tag tone-rose">{{ $competitor->lost_to }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-5">No competitor outcomes recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-users"></i> By Owner</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Owner</th><th class="text-center">Won</th><th class="text-center">Lost</th><th style="min-width: 160px">Win Rate</th></tr>
                </thead>
                <tbody>
                    @forelse ($byOwner as $owner => $row)
                        @php $ownerTotal = $row['won'] + $row['lost']; $ownerRate = $ownerTotal ? round($row['won'] / $ownerTotal * 100) : 0; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $owner }}</td>
                            <td class="text-center"><span class="tag tone-success">{{ $row['won'] }}</span></td>
                            <td class="text-center"><span class="tag tone-rose">{{ $row['lost'] }}</span></td>
                            <td>
                                <div class="mini-progress tone-success">
                                    <div class="mini-progress-bar is-toned"><span style="width: {{ $ownerRate }}%"></span></div>
                                    <span class="mini-progress-label">{{ $ownerRate }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">No closed deals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
