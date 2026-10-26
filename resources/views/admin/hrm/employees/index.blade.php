@extends('layouts.admin')

@section('title', 'Employees')

@php
    $filterLabels = [
        'search' => 'Search',
        'status' => 'Status',
        'department_id' => 'Department',
        'branch_id' => 'Branch',
        'employment_type' => 'Type',
        'joined_from' => 'Joined from',
        'joined_until' => 'Joined until',
    ];

    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(function ($label, $key) use ($state, $departments, $branches, $employmentTypes) {
            $value = $state[$key];

            if ($key === 'employment_type') {
                $value = $employmentTypes[$value] ?? Str::headline($value);
            } elseif ($key === 'status') {
                $value = Str::headline($value);
            } elseif ($key === 'department_id') {
                $value = $departments[$value] ?? $value;
            } elseif ($key === 'branch_id') {
                $value = $branches[$value] ?? $value;
            }

            return ['key' => $key, 'label' => $label, 'value' => $value];
        });

    // Every list link is built from the full current state (sort, per page,
    // other filters) rather than the bare route - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route('admin.employees.index', array_filter(
        array_merge($state, ['page' => null], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));

    $currentStatus = (string) ($state['status'] ?? '');
    $statusTabs = collect(['' => 'All employees'])
        ->merge(collect(\App\Models\Employee::STATUSES)->mapWithKeys(fn ($status) => [$status => Str::headline($status)]))
        ->map(fn ($label, $status) => [
            'label' => $label,
            'count' => $statusCounts[$status === '' ? 'all' : $status] ?? 0,
            'current' => $currentStatus === (string) $status,
            'url' => $listUrl(['status' => $status === '' ? null : $status]),
        ]);

    $statusTones = ['active' => 'success', 'on_leave' => 'warning', 'inactive' => 'muted', 'terminated' => 'danger'];
    $tabTones = ['' => 'indigo', 'active' => 'success', 'on_leave' => 'warning', 'inactive' => 'slate', 'terminated' => 'danger'];
    $statusTabs = $statusTabs->map(fn ($tab, $status) => $tab + ['tone' => $tabTones[$status] ?? 'indigo']);

    // Stable colour per department / type name, so the same department is
    // always the same colour on every row and every page.
    $tagTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky', 'rose', 'lime'];
    $toneFor = fn (?string $text) => $tagTones[crc32(Str::slug((string) $text, '_')) % count($tagTones)];

    $panelKeys = ['department_id', 'branch_id', 'employment_type', 'joined_from', 'joined_until'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
@endphp

@section('content')
    <x-page-title title="Employees" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total employees" :value="$kpis['total']" icon="ti-users" tone="indigo" :href="$listUrl(['status' => null])" />
        <x-kpi-card label="Active" :value="$kpis['active']" icon="ti-user-check" tone="success"
                    :hint="$kpis['total'] ? round($kpis['active'] / $kpis['total'] * 100).'% of workforce' : null" :href="$listUrl(['status' => 'active'])" />
        <x-kpi-card label="On leave" :value="$kpis['on_leave']" icon="ti-plane" tone="warning" :href="$listUrl(['status' => 'on_leave'])" />
        <x-kpi-card label="Joined this month" :value="$kpis['joined_this_month']" icon="ti-confetti" tone="pink" :hint="now()->format('F Y')" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.employees.index') }}">
            @foreach (['sort' => $sort, 'direction' => $direction, 'per_page' => $perPage, 'status' => $currentStatus] as $name => $value)
                @if ((string) $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            {{-- Toolbar: saved-view style status tabs, then search / filters / actions --}}
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Employee status">
                    @foreach ($statusTabs as $tab)
                        <a href="{{ $tab['url'] }}" class="list-tab tone-{{ $tab['tone'] }} {{ $tab['current'] ? 'active' : '' }}" @if ($tab['current']) aria-current="page" @endif>
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ number_format($tab['count']) }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search name, code or email" aria-label="Search employees">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#employees-filter-panel" aria-expanded="false" aria-controls="employees-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    <x-export-csv-button route="admin.employees.export" />
                    @can('create', \App\Models\Employee::class)
                        <a href="{{ route('admin.employees.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-user-plus font-14 me-1"></i> Add employee</a>
                    @endcan
                </div>
            </div>

            {{-- Advanced filters: a collapsed drawer, opened from the Filters button --}}
            <div class="collapse" id="employees-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select form-select-sm">
                                <option value="">Any department</option>
                                @foreach ($departments as $id => $name)
                                    <option value="{{ $id }}" @selected((string) ($state['department_id'] ?? '') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select form-select-sm">
                                <option value="">Any branch</option>
                                @foreach ($branches as $id => $name)
                                    <option value="{{ $id }}" @selected((string) ($state['branch_id'] ?? '') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label">Employment type</label>
                            <select name="employment_type" class="form-select form-select-sm">
                                <option value="">Any type</option>
                                @foreach ($employmentTypes as $slug => $label)
                                    <option value="{{ $slug }}" @selected(($state['employment_type'] ?? '') === $slug)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <label class="form-label">Joined between</label>
                            <div class="list-date-range">
                                <input type="date" name="joined_from" class="form-control form-control-sm" value="{{ $state['joined_from'] ?? '' }}" aria-label="Joined from">
                                <span>to</span>
                                <input type="date" name="joined_until" class="form-control form-control-sm" value="{{ $state['joined_until'] ?? '' }}" aria-label="Joined until">
                            </div>
                        </div>
                        <div class="col-lg-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-de-primary btn-sm text-light flex-fill">Apply</button>
                            <a href="{{ $listUrl(array_fill_keys($panelKeys, null)) }}" class="btn btn-light btn-sm">Clear</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $listUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">
                            {{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong>
                            <i class="ti ti-x"></i>
                        </a>
                    @endforeach
                    <a href="{{ $clearAllUrl }}" class="list-clear-all">Clear all</a>
                </div>
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Employee" />
                        <x-th-sort column="employee_code" :sort="$sort" :direction="$direction" label="Code" />
                        <th>Department</th>
                        <th>Branch</th>
                        <th>Type</th>
                        <x-th-sort column="status" :sort="$sort" :direction="$direction" label="Status" />
                        <x-th-sort column="joining_date" :sort="$sort" :direction="$direction" label="Joined" />
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php
                            $initials = collect(preg_split('/\s+/', trim($employee->name)))
                                ->filter()
                                ->take(2)
                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');
                        @endphp
                        <tr>
                            <td>
                                <div class="list-user">
                                    @if ($employee->avatar)
                                        <img src="{{ asset('storage/'.$employee->avatar) }}" alt="" class="list-avatar">
                                    @else
                                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                    @endif
                                    <div class="list-user-text">
                                        <div class="list-user-name">
                                            <a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a>
                                        </div>
                                        <div class="list-user-meta">
                                            {{ $employee->designation ?: ($employee->email ?: '—') }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap"><span class="list-code">{{ $employee->employee_code }}</span></td>
                            <td>
                                @if ($employee->department)
                                    <span class="tag tone-{{ $toneFor($employee->department->name) }}">{{ $employee->department->name }}</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                @if ($employee->branch)
                                    <span class="list-icon-text"><i class="ti ti-building-store"></i>{{ $employee->branch->name }}</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($employee->employment_type)
                                    <span class="tag tag-outline tone-{{ $toneFor($employee->employment_type) }}">{{ Str::headline($employee->employment_type) }}</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                <span class="status-dot status-dot-{{ $statusTones[$employee->status] ?? 'muted' }}">
                                    {{ Str::headline($employee->status) }}
                                </span>
                            </td>
                            <td class="text-nowrap text-muted">
                                {{ optional($employee->joining_date)->format('M j, Y') ?: '—' }}
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    <a href="{{ route('admin.employees.show', $employee) }}" class="btn-icon btn-icon-info" title="View"><i class="ti ti-eye font-16"></i></a>
                                    @can('update', $employee)
                                        <a href="{{ route('admin.employees.edit', $employee) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $employee)
                                        <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this employee?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="ti ti-users font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No employees found</div>
                                @if ($activeFilters->isNotEmpty())
                                    <span class="font-13">Try adjusting your filters, or <a href="{{ $clearAllUrl }}">clear them all</a>.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="list-footer">
            <div class="list-per-page dropdown">
                <span class="text-muted font-13">Rows per page</span>
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">{{ $perPage }}</button>
                <ul class="dropdown-menu">
                    @foreach ([10, 25, 50, 100] as $option)
                        <li><a class="dropdown-item {{ $perPage === $option ? 'active' : '' }}" href="{{ $listUrl(['per_page' => $option]) }}">{{ $option }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="ms-auto">{{ $employees->links() }}</div>
        </div>
    </div>
@endsection
