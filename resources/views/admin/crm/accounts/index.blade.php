@extends('layouts.admin')

@section('title', 'Accounts')

@php
    $panelFilterCount = collect([$industry, $ownerId, $branchId])->filter(fn ($v) => filled($v))->count();
    $listUrl = fn (array $overrides = []) => route('admin.crm.accounts.index', array_filter(
        array_merge(['search' => $search, 'customer_segment_id' => $segmentId, 'industry' => $industry, 'owner_employee_id' => $ownerId, 'branch_id' => $branchId], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
@endphp

@section('content')
    <x-page-title title="Accounts" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Accounts" :value="$total" icon="ti-building" tone="indigo" />
        <x-kpi-card label="With Owner" :value="$withOwner" icon="ti-user-check" tone="success" />
        <x-kpi-card label="Total Contacts" :value="$totalContacts" icon="ti-users" tone="violet" :href="route('admin.crm.contacts.index')" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.accounts.index') }}">
            @if ($sort !== 'name')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'asc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-building"></i> Accounts</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name or industry" aria-label="Search accounts">
                    </div>
                    <select name="customer_segment_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Segment">
                        <option value="">Any segment</option>
                        @foreach ($segments as $id => $name)
                            <option value="{{ $id }}" @selected((string) $segmentId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#accounts-filter-panel" aria-expanded="false" aria-controls="accounts-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    <a href="{{ route('admin.crm.accounts.export', request()->query()) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export</a>
                    <a href="{{ route('admin.crm.accounts.merge') }}" class="btn btn-light btn-sm"><i class="ti ti-arrows-join font-14 me-1"></i> Merge</a>
                    <a href="{{ route('admin.crm.accounts.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Account</a>
                </div>
            </div>

            <div class="collapse" id="accounts-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Industry</label>
                            <select name="industry" class="form-select form-select-sm">
                                <option value="">Any industry</option>
                                @foreach ($industries as $value)
                                    <option value="{{ $value }}" @selected($industry === $value)>{{ $value }}</option>
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
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select form-select-sm">
                                <option value="">Any branch</option>
                                @foreach ($branches as $id => $name)
                                    <option value="{{ $id }}" @selected((string) $branchId === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-de-primary btn-sm text-light flex-fill">Apply</button>
                            <a href="{{ $listUrl(['industry' => null, 'owner_employee_id' => null, 'branch_id' => null]) }}" class="btn btn-light btn-sm">Clear</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Account" />
                        <x-th-sort column="industry" :sort="$sort" :direction="$direction" label="Industry" />
                        <th>Parent</th>
                        <th class="text-center">Contacts</th>
                        <th>Owner</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td>
                                <a href="{{ route('admin.crm.accounts.edit', $account) }}" class="fw-semibold">{{ $account->name }}</a>
                                @if ($account->segment)
                                    <div><span class="tag tone-violet">{{ $account->segment->name }}</span></div>
                                @endif
                            </td>
                            <td>{{ $account->industry ?? '—' }}</td>
                            <td>{{ $account->parent?->name ?? '—' }}</td>
                            <td class="text-center"><span class="tag tone-indigo">{{ $account->contacts_count }}</span></td>
                            <td>{{ $account->owner?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.customer-360.index', ['account_id' => $account->id]) }}" class="btn-icon btn-icon-info" title="360° view"><i class="ti ti-eye font-16"></i></a>
                                <a href="{{ route('admin.crm.accounts.edit', $account) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.accounts.destroy', $account) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this account?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-building font-24 d-block mb-2"></i>
                                No accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($accounts->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $accounts->links() }}</div></div>
        @endif
    </div>
@endsection
