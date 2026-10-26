@extends('layouts.admin')

@section('title', 'Job Positions')

@php
    $filterLabels = [
        'search' => 'Search',
        'status' => 'Status',
        'department_id' => 'Department',
        'branch_id' => 'Branch',
        'employment_type' => 'Type',
    ];

    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(function ($label, $key) use ($state, $departments, $branches, $employmentTypes) {
            $value = $state[$key];

            if ($key === 'status') {
                $value = Str::headline($value);
            } elseif ($key === 'department_id') {
                $value = $departments[$value] ?? $value;
            } elseif ($key === 'branch_id') {
                $value = $branches[$value] ?? $value;
            } elseif ($key === 'employment_type') {
                $value = $employmentTypes[$value] ?? Str::headline($value);
            }

            return ['key' => $key, 'label' => $label, 'value' => $value];
        });

    // Every list link is built from the full current state (sort, per page,
    // other filters) rather than the bare route - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route('admin.job-positions.index', array_filter(
        array_merge($state, ['page' => null], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));

    $currentStatus = (string) ($state['status'] ?? '');
    $statusTones = ['draft' => 'muted', 'open' => 'success', 'on_hold' => 'warning', 'closed' => 'danger'];
    $tabTones = ['' => 'indigo', 'draft' => 'slate', 'open' => 'success', 'on_hold' => 'warning', 'closed' => 'danger'];
    $statusTabs = collect(['' => 'All positions'])
        ->merge(collect(\App\Models\JobPosition::STATUSES)->mapWithKeys(fn ($status) => [$status => Str::headline($status)]))
        ->map(fn ($label, $status) => [
            'label' => $label,
            'tone' => $tabTones[$status] ?? 'indigo',
            'count' => $statusCounts[$status === '' ? 'all' : $status] ?? 0,
            'current' => $currentStatus === (string) $status,
            'url' => $listUrl(['status' => $status === '' ? null : $status]),
        ]);

    $tagTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky', 'rose', 'lime'];
    $toneFor = fn (?string $text) => $tagTones[crc32(Str::slug((string) $text, '_')) % count($tagTones)];

    $money = fn ($amount) => $amount === null ? null : rtrim(rtrim(number_format((float) $amount, 2), '0'), '.');

    $panelKeys = ['department_id', 'branch_id', 'employment_type'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
@endphp

@section('content')
    <x-page-title title="Job Positions" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total positions" :value="$kpis['total']" icon="ti-briefcase" tone="indigo" :href="$listUrl(['status' => null])" />
        <x-kpi-card label="Open positions" :value="$kpis['open']" icon="ti-speakerphone" tone="success" hint="Accepting applications" :href="$listUrl(['status' => 'open'])" />
        <x-kpi-card label="Open headcount" :value="$kpis['openings']" icon="ti-user-plus" tone="violet" hint="Seats to fill" />
        <x-kpi-card label="Applicants" :value="$kpis['candidates']" icon="ti-users" tone="pink" hint="Across all positions" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.job-positions.index') }}">
            @foreach (['sort' => $sort, 'direction' => $direction, 'per_page' => $perPage, 'status' => $currentStatus] as $name => $value)
                @if ((string) $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Position status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search job title" aria-label="Search job positions">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#positions-filter-panel" aria-expanded="false" aria-controls="positions-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    <x-export-csv-button route="admin.job-positions.export" />
                    @can('create', \App\Models\JobPosition::class)
                        <a href="{{ route('admin.job-positions.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New position</a>
                    @endcan
                </div>
            </div>

            <div class="collapse" id="positions-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select form-select-sm">
                                <option value="">Any department</option>
                                @foreach ($departments as $id => $name)
                                    <option value="{{ $id }}" @selected((string) ($state['department_id'] ?? '') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select form-select-sm">
                                <option value="">Any branch</option>
                                @foreach ($branches as $id => $name)
                                    <option value="{{ $id }}" @selected((string) ($state['branch_id'] ?? '') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Employment type</label>
                            <select name="employment_type" class="form-select form-select-sm">
                                <option value="">Any type</option>
                                @foreach ($employmentTypes as $slug => $label)
                                    <option value="{{ $slug }}" @selected(($state['employment_type'] ?? '') === $slug)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3 d-flex align-items-end gap-2">
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
                        <x-th-sort column="title" :sort="$sort" :direction="$direction" label="Position" />
                        <th>Department</th>
                        <th>Type</th>
                        <th>Salary range</th>
                        <x-th-sort column="headcount" :sort="$sort" :direction="$direction" label="Openings" />
                        <th>Applicants</th>
                        <x-th-sort column="status" :sort="$sort" :direction="$direction" label="Status" />
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($positions as $position)
                        <tr>
                            <td>
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-square tone-{{ $toneFor($position->department?->name ?? $position->title) }}" aria-hidden="true">
                                        <i class="ti ti-briefcase font-18"></i>
                                    </span>
                                    <div class="list-user-text">
                                        <div class="list-user-name">
                                            @can('update', $position)
                                                <a href="{{ route('admin.job-positions.edit', $position) }}">{{ $position->title }}</a>
                                            @else
                                                <span>{{ $position->title }}</span>
                                            @endcan
                                        </div>
                                        <div class="list-user-meta">
                                            @if ($position->branch)
                                                <i class="ti ti-map-pin"></i> {{ $position->branch->name }}
                                                <span class="list-meta-sep">&middot;</span>
                                            @endif
                                            Posted {{ $position->created_at?->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($position->department)
                                    <span class="tag tone-{{ $toneFor($position->department->name) }}">{{ $position->department->name }}</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($position->employment_type)
                                    <span class="tag tag-outline tone-{{ $toneFor($position->employment_type) }}">{{ Str::headline($position->employment_type) }}</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($position->min_salary !== null || $position->max_salary !== null)
                                    <span class="salary-range">
                                        {{ $currency }}
                                        {{ $money($position->min_salary) ?? '—' }} &ndash; {{ $money($position->max_salary) ?? '—' }}
                                    </span>
                                @else
                                    <span class="text-muted font-12">Not disclosed</span>
                                @endif
                            </td>
                            <td>
                                <span class="count-pill tone-violet"><i class="ti ti-users"></i> {{ $position->headcount }}</span>
                            </td>
                            <td>
                                @if ($position->candidates_count)
                                    <span class="count-pill tone-pink"><i class="ti ti-users"></i> {{ $position->candidates_count }}</span>
                                @else
                                    <span class="text-muted font-12">None yet</span>
                                @endif
                            </td>
                            <td>
                                <span class="status-dot status-dot-{{ $statusTones[$position->status] ?? 'muted' }}">{{ Str::headline($position->status) }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $position)
                                        <a href="{{ route('admin.job-positions.edit', $position) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $position)
                                        <form action="{{ route('admin.job-positions.destroy', $position) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this job position?');">
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
                                <i class="ti ti-briefcase font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No job positions found</div>
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
            <div class="ms-auto">{{ $positions->links() }}</div>
        </div>
    </div>
@endsection
