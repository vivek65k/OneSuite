@extends('layouts.admin')

@section('title', 'Lead Qualification')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    <x-page-title title="Lead Qualification" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting" :value="$total" icon="ti-list-check" tone="amber" />
        <x-kpi-card label="New" :value="$newCount" icon="ti-bulb" tone="sky" />
        <x-kpi-card label="Contacted" :value="$contactedCount" icon="ti-phone" tone="violet" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.lead-qualification.index') }}">
            @if ($sort !== 'score')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'desc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="segmented" role="group" aria-label="Qualification status">
                    <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="segmented-item {{ $statusFilter === '' ? 'active' : '' }}">All</a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'new']) }}" class="segmented-item {{ $statusFilter === 'new' ? 'active' : '' }}">New</a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'contacted']) }}" class="segmented-item {{ $statusFilter === 'contacted' ? 'active' : '' }}">Contacted</a>
                </div>
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
                        <x-th-sort column="score" :sort="$sort" :direction="$direction" label="Score" align="start" />
                        <th>Assigned To</th>
                        <th>BANT (Budget / Need / Authority / Timeline)</th>
                        <th class="text-end">Decision</th>
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
                            <td class="text-center">{{ $lead->score }}</td>
                            <td>{{ $lead->assignedTo?->name ?? '—' }}</td>
                            <td>
                                <form action="{{ route('admin.crm.lead-qualification.bant', $lead) }}" method="POST" class="d-flex gap-1 flex-wrap align-items-center">
                                    @csrf
                                    <input type="text" name="budget" value="{{ $lead->budget }}" class="form-control form-control-sm" placeholder="Budget" style="width:90px;">
                                    <div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="has_need" value="1" @checked($lead->has_need)><label class="form-check-label font-11">Need</label></div>
                                    <div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="has_authority" value="1" @checked($lead->has_authority)><label class="form-check-label font-11">Authority</label></div>
                                    <select name="timeline" class="form-select form-select-sm" style="width:120px;">
                                        <option value="">Timeline</option>
                                        @foreach (\App\Models\Crm\Lead::TIMELINES as $key => $label)
                                            <option value="{{ $key }}" @selected($lead->timeline === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn-icon" title="Save"><i class="ti ti-check font-16"></i></button>
                                </form>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.lead-qualification.qualify', $lead) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-check"></i> Qualify</button>
                                </form>
                                <form action="{{ route('admin.crm.lead-qualification.disqualify', $lead) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm review-btn is-reject"><i class="ti ti-x"></i> Disqualify</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-list-check font-24 d-block mb-2"></i>No leads waiting on a qualification decision.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($leads->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $leads->links() }}</div></div>
        @endif
    </div>
@endsection
