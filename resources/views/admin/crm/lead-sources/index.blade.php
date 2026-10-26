@extends('layouts.admin')

@section('title', 'Lead Sources')

@section('content')
    <x-page-title title="Lead Sources" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Sources" :value="$totalSources" icon="ti-route" tone="indigo" />
        <x-kpi-card label="Active" :value="$activeCount" icon="ti-circle-check" tone="success" />
        <x-kpi-card label="Leads Attributed" :value="$totalLeads" icon="ti-target" tone="violet" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.lead-sources.index') }}">
            @if ($sort !== 'name')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'asc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="segmented" role="group" aria-label="Source status">
                    <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="segmented-item {{ $statusFilter === '' ? 'active' : '' }}">All</a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'active']) }}" class="segmented-item {{ $statusFilter === 'active' ? 'active' : '' }}">Active</a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'inactive']) }}" class="segmented-item {{ $statusFilter === 'inactive' ? 'active' : '' }}">Inactive</a>
                </div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name" aria-label="Search sources">
                    </div>
                    <a href="{{ route('admin.crm.lead-sources.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Source</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Name" />
                        <x-th-sort column="leads_count" :sort="$sort" :direction="$direction" label="Leads" align="end" />
                        <x-th-sort column="is_active" :sort="$sort" :direction="$direction" label="Status" />
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sources as $source)
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('admin.crm.lead-sources.edit', $source) }}">{{ $source->name }}</a>
                            </td>
                            <td class="text-center"><span class="tag tone-indigo">{{ $source->leads_count }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('admin.crm.lead-sources.toggle', $source) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="switch-btn {{ $source->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $source->is_active ? 'true' : 'false' }}"
                                            title="{{ $source->is_active ? 'Active - click to deactivate' : 'Inactive - click to activate' }}">
                                        <span class="visually-hidden">{{ $source->is_active ? 'Deactivate' : 'Activate' }} {{ $source->name }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.lead-sources.edit', $source) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.lead-sources.destroy', $source) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this source?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="ti ti-route font-24 d-block mb-2"></i>
                                No lead sources yet.
                                <div><a href="{{ route('admin.crm.lead-sources.create') }}">Add your first one</a>.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
