@extends('layouts.admin')

@section('title', 'Lead Scoring')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    <x-page-title title="Lead Scoring" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Open Leads" :value="$total" icon="ti-target" tone="indigo" />
        <x-kpi-card label="Average Score" :value="$avgScore" icon="ti-gauge" tone="violet" />
        <x-kpi-card label="Hot Leads (70+)" :value="$hotCount" icon="ti-flame" tone="rose" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.lead-scoring.index') }}">
            @if ($sort !== 'score')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'desc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-gauge"></i> Leads ranked by score</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name or company" aria-label="Search leads">
                    </div>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Lead" />
                        <th>Source</th>
                        <x-th-sort column="score" :sort="$sort" :direction="$direction" label="Score (0–100)" />
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr>
                            <td>
                                <div class="list-user-text">
                                    <div class="list-user-name">{{ $lead->name }}</div>
                                    <div class="list-user-meta">{{ $lead->company_name ?? $lead->email ?? '—' }}</div>
                                </div>
                            </td>
                            <td>{{ $lead->source?->name ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.crm.lead-scoring.update', $lead) }}" class="d-flex align-items-center gap-2">
                                    @csrf @method('PUT')
                                    <input type="range" min="0" max="100" name="score" value="{{ $lead->score }}" class="form-range" oninput="this.nextElementSibling.textContent = this.value">
                                    <span class="fw-semibold" style="width: 2.5rem">{{ $lead->score }}</span>
                                    <button type="submit" class="btn btn-light btn-sm">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-5"><i class="ti ti-gauge font-24 d-block mb-2"></i>No leads to score.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($leads->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $leads->links() }}</div></div>
        @endif
    </div>
@endsection
