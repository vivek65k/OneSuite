@extends('layouts.admin')

@section('title', 'Contacts')

@php
    $panelFilterCount = collect([$segmentId, $ownerId])->filter(fn ($v) => filled($v))->count();
    $listUrl = fn (array $overrides = []) => route('admin.crm.contacts.index', array_filter(
        array_merge(['search' => $search, 'account_id' => $accountId, 'customer_segment_id' => $segmentId, 'owner_employee_id' => $ownerId], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
@endphp

@section('content')
    <x-page-title title="Contacts" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Contacts" :value="$total" icon="ti-users" tone="indigo" />
        <x-kpi-card label="Linked to an Account" :value="$withAccount" icon="ti-building" tone="success" />
        <x-kpi-card label="Unlinked" :value="$total - $withAccount" icon="ti-user-off" tone="amber" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.contacts.index') }}">
            @if ($sort !== 'name')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'asc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-users"></i> Contacts</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name, email, company" aria-label="Search contacts">
                    </div>
                    <select name="account_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Account">
                        <option value="">Any account</option>
                        @foreach ($accounts as $id => $name)
                            <option value="{{ $id }}" @selected((string) $accountId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#contacts-filter-panel" aria-expanded="false" aria-controls="contacts-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    <a href="{{ route('admin.crm.contacts.export', request()->query()) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export</a>
                    <a href="{{ route('admin.crm.contacts.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Contact</a>
                </div>
            </div>

            <div class="collapse" id="contacts-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Segment</label>
                            <select name="customer_segment_id" class="form-select form-select-sm">
                                <option value="">Any segment</option>
                                @foreach ($segments as $id => $name)
                                    <option value="{{ $id }}" @selected((string) $segmentId === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Owner</label>
                            <select name="owner_employee_id" class="form-select form-select-sm">
                                <option value="">Any owner</option>
                                @foreach ($owners as $id => $name)
                                    <option value="{{ $id }}" @selected((string) $ownerId === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-de-primary btn-sm text-light flex-fill">Apply</button>
                            <a href="{{ $listUrl(['customer_segment_id' => null, 'owner_employee_id' => null]) }}" class="btn btn-light btn-sm">Clear</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Contact" />
                        <th>Account</th>
                        <th>Segment</th>
                        <th>Owner</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $contact)
                        <tr>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$contact" />
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ route('admin.crm.contacts.show', $contact) }}">{{ $contact->name }}</a></div>
                                        <div class="list-user-meta">{{ collect([$contact->job_title, $contact->email])->filter()->implode(' · ') ?: '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $contact->account?->name ?? '—' }}</td>
                            <td>{{ $contact->segment?->name ?? '—' }}</td>
                            <td>{{ $contact->owner?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.contacts.show', $contact) }}" class="btn-icon" title="View details"><i class="ti ti-eye font-16"></i></a>
                                <a href="{{ route('admin.crm.contacts.edit', $contact) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.contacts.destroy', $contact) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this contact?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-users font-24 d-block mb-2"></i>
                                No contacts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($contacts->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $contacts->links() }}</div></div>
        @endif
    </div>
@endsection
