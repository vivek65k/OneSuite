@extends('layouts.admin')

@section('title', 'Lead Assignment')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    <x-page-title title="Lead Assignment" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Open Leads" :value="$total" icon="ti-target" tone="indigo" />
        <x-kpi-card label="Assigned" :value="$assignedCount" icon="ti-user-check" tone="success" />
        <x-kpi-card label="Unassigned" :value="$unassignedCount" icon="ti-user-off" tone="amber" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.lead-assignment.index') }}">
            @if ($sort !== 'created_at')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'desc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="segmented" role="group" aria-label="Assignment filter">
                    <a href="{{ request()->fullUrlWithQuery(['filter' => null]) }}" class="segmented-item {{ $filter === '' ? 'active' : '' }}">All</a>
                    <a href="{{ request()->fullUrlWithQuery(['filter' => 'assigned']) }}" class="segmented-item {{ $filter === 'assigned' ? 'active' : '' }}">Assigned</a>
                    <a href="{{ request()->fullUrlWithQuery(['filter' => 'unassigned']) }}" class="segmented-item {{ $filter === 'unassigned' ? 'active' : '' }}">Unassigned</a>
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
                        <th>Status</th>
                        <x-th-sort column="created_at" :sort="$sort" :direction="$direction" label="Added" />
                        <th style="width: 320px;">Assigned To</th>
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
                            <td><span class="tag tone-slate">{{ \App\Models\Crm\Lead::STATUSES[$lead->status] ?? $lead->status }}</span></td>
                            <td class="text-muted font-13">{{ $lead->created_at?->diffForHumans() }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.crm.lead-assignment.update', $lead) }}" class="d-flex gap-1">
                                    @csrf @method('PUT')
                                    <select name="assigned_to_employee_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="">— Unassigned —</option>
                                        @foreach ($employees as $id => $name)
                                            <option value="{{ $id }}" @selected($lead->assigned_to_employee_id === $id)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5"><i class="ti ti-user-check font-24 d-block mb-2"></i>No leads to assign.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($leads->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $leads->links() }}</div></div>
        @endif
    </div>
@endsection
