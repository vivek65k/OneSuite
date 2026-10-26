@extends('layouts.admin')

@section('title', 'Deal Win Probability')

@section('content')
    <x-page-title title="Deal Win Probability" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Avg Probability" value="{{ $avgProbability }}%" icon="ti-target" tone="indigo" />
        <x-kpi-card label="Likely to Win" :value="$likelyToWin" icon="ti-trophy" tone="success" />
        <x-kpi-card label="At Risk" :value="$atRisk" icon="ti-alert-triangle" tone="rose" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-target"></i> Computed Probabilities <span class="list-tab-count">{{ $rows->count() }}</span></div>
            <div class="list-toolbar-actions">
                <form method="POST" action="{{ route('admin.crm.ai-win-probability.recalculate') }}">
                    @csrf
                    <button type="submit" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-refresh font-14 me-1"></i> Recalculate All</button>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Opportunity</th><th>Amount</th><th>Probability</th><th>Why</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.opportunities.edit', $row['opportunity']) }}">{{ $row['opportunity']->name }}</a></td>
                            <td>{{ number_format($row['opportunity']->amount, 0) }}</td>
                            <td><span class="tag tone-{{ $row['probability'] >= 70 ? 'success' : ($row['probability'] >= 30 ? 'amber' : 'rose') }}">{{ $row['probability'] }}%</span></td>
                            <td class="font-12 text-muted">
                                @foreach ($row['factors'] as $factor)
                                    <span class="tag tone-indigo me-1">{{ $factor['label'] }} ({{ $factor['points'] > 0 ? '+' : '' }}{{ $factor['points'] }})</span>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">No open opportunities.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
