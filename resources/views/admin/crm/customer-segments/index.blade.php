@extends('layouts.admin')

@section('title', 'Customer Segments')

@section('content')
    <x-page-title title="Customer Segments" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Segments" :value="$totalSegments" icon="ti-tag" tone="indigo" />
        <x-kpi-card label="Accounts Segmented" :value="$totalAccounts" icon="ti-building" tone="success" />
        <x-kpi-card label="Contacts Segmented" :value="$totalContacts" icon="ti-users" tone="violet" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.customer-segments.index') }}">
            @if ($sort !== 'name')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'asc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-tag"></i> Segments</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name" aria-label="Search segments">
                    </div>
                    <a href="{{ route('admin.crm.customer-segments.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Segment</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Name" />
                        <th>Description</th>
                        <x-th-sort column="accounts_count" :sort="$sort" :direction="$direction" label="Accounts" />
                        <x-th-sort column="contacts_count" :sort="$sort" :direction="$direction" label="Contacts" />
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($segments as $segment)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.customer-segments.edit', $segment) }}">{{ $segment->name }}</a></td>
                            <td class="text-muted">{{ \Illuminate\Support\Str::limit($segment->description, 60) ?: '—' }}</td>
                            <td class="text-center"><span class="tag tone-success">{{ $segment->accounts_count }}</span></td>
                            <td class="text-center"><span class="tag tone-violet">{{ $segment->contacts_count }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.customer-segments.edit', $segment) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.customer-segments.destroy', $segment) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this segment?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-tag font-24 d-block mb-2"></i>
                                No segments yet.
                                <div><a href="{{ route('admin.crm.customer-segments.create') }}">Add your first one</a>.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
