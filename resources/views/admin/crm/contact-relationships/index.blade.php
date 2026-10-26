@extends('layouts.admin')

@section('title', 'Contact Relationships')

@section('content')
    <x-page-title title="Contact Relationships" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Relationships" :value="$total" icon="ti-link" tone="indigo" />
        <x-kpi-card label="Contacts Available" :value="$contacts->count()" icon="ti-users" tone="violet" :href="route('admin.crm.contacts.index')" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-link"></i> Add a relationship</div>
        </div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.contact-relationships.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Contact</label>
                    <select name="contact_id" class="form-select form-select-sm" required>
                        <option value="">Select contact</option>
                        @foreach ($contacts as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Relationship</label>
                    <select name="relationship_type" class="form-select form-select-sm" required>
                        @foreach (\App\Models\Crm\ContactRelationship::TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Related contact</label>
                    <select name="related_contact_id" class="form-select form-select-sm" required>
                        <option value="">Select contact</option>
                        @foreach ($contacts as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.contact-relationships.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-filter"></i> Filter relationships</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search contact name" aria-label="Search relationships">
                    </div>
                    <select name="relationship_type" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Relationship type">
                        <option value="">Any type</option>
                        @foreach (\App\Models\Crm\ContactRelationship::TYPES as $key => $label)
                            <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Contact</th>
                        <th>Relationship</th>
                        <th>Related Contact</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($relationships as $relationship)
                        <tr>
                            <td class="fw-semibold">{{ $relationship->contact?->name ?? '—' }}</td>
                            <td><span class="tag tone-violet">{{ \App\Models\Crm\ContactRelationship::TYPES[$relationship->relationship_type] ?? $relationship->relationship_type }}</span></td>
                            <td>{{ $relationship->relatedContact?->name ?? '—' }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.contact-relationships.destroy', $relationship) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this relationship?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="ti ti-link font-24 d-block mb-2"></i>
                                No relationships recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($relationships->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $relationships->links() }}</div></div>
        @endif
    </div>
@endsection
