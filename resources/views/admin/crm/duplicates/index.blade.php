@extends('layouts.admin')

@section('title', 'Duplicate Management')

@php
    use Illuminate\Support\Str;

    $totalInGroups = $groups->sum->count();
@endphp

@section('content')
    <x-page-title title="Duplicate Management" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Duplicate Groups" :value="$groups->count()" icon="ti-copy" tone="amber" />
        <x-kpi-card label="Leads Involved" :value="$totalInGroups" icon="ti-target" tone="indigo" />
        <x-kpi-card label="Flagged" :value="$flaggedCount" icon="ti-flag" tone="rose" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.duplicate-management.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-search"></i> Find a group</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name or email" aria-label="Search duplicate groups">
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if ($groups->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-success"><i class="ti ti-circle-check"></i></span>
                <h5>No duplicates found</h5>
                <p>
                    @if ($search !== '')
                        No duplicate groups match "{{ $search }}". <a href="{{ route('admin.crm.duplicate-management.index') }}">Clear search</a>.
                    @else
                        No leads currently share the same email or phone number.
                    @endif
                </p>
            </div>
        </div>
    @else
        @foreach ($groups as $group)
            <div class="card list-view mb-3">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-copy"></i> Possible duplicate group <span class="list-tab-count">{{ $group->count() }}</span></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Lead</th>
                                <th>Email / Phone</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group as $lead)
                                <tr>
                                    <td class="fw-semibold">{{ $lead->name }}</td>
                                    <td>{{ collect([$lead->email, $lead->phone])->filter()->implode(' · ') }}</td>
                                    <td>
                                        @if ($lead->is_duplicate_of_lead_id)
                                            <span class="tag tone-amber">Marked duplicate</span>
                                        @else
                                            <span class="tag tone-slate">{{ \App\Models\Crm\Lead::STATUSES[$lead->status] ?? $lead->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($lead->is_duplicate_of_lead_id)
                                            <form action="{{ route('admin.crm.duplicate-management.unmark', $lead) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-light btn-sm">Unmark</button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.crm.duplicate-management.mark', $lead) }}" method="POST" class="d-inline-flex gap-1">
                                                @csrf
                                                <select name="is_duplicate_of_lead_id" class="form-select form-select-sm" required>
                                                    <option value="">Duplicate of…</option>
                                                    @foreach ($group as $other)
                                                        @continue($other->id === $lead->id)
                                                        <option value="{{ $other->id }}">{{ $other->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-light btn-sm">Mark</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif
@endsection
