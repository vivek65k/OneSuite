@extends('layouts.admin')

@section('title', 'AI Lead Scoring')

@section('content')
    <x-page-title title="AI Lead Scoring" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Hot Leads (70+)" :value="$hotCount" icon="ti-bulb" tone="success" />
        <x-kpi-card label="Average Score" :value="$avgScore" icon="ti-target" tone="indigo" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-bulb"></i> Computed Scores <span class="list-tab-count">{{ $rows->count() }}</span></div>
            <div class="list-toolbar-actions">
                <form method="POST" action="{{ route('admin.crm.ai-lead-scoring.recalculate') }}">
                    @csrf
                    <button type="submit" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-refresh font-14 me-1"></i> Recalculate All</button>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Lead</th><th>Score</th><th>Why</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.leads.edit', $row['lead']) }}">{{ $row['lead']->name }}</a></td>
                            <td><span class="tag tone-{{ $row['score'] >= 70 ? 'success' : ($row['score'] >= 40 ? 'amber' : 'slate') }}">{{ $row['score'] }}</span></td>
                            <td class="font-12 text-muted">
                                @foreach ($row['factors'] as $factor)
                                    <span class="tag tone-indigo me-1">{{ $factor['label'] }} ({{ $factor['points'] > 0 ? '+' : '' }}{{ $factor['points'] }})</span>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-5">No leads to score.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
