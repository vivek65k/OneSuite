@extends('layouts.admin')

@section('title', 'Probation Tracking')

@php
    $statusMeta = \App\Support\Onboarding::PROBATION_META;
    $statusLabels = \App\Models\ProbationRecord::STATUSES;

    $filterLabels = ['search' => 'Search', 'status' => 'Status', 'employee_id' => 'Employee', 'review' => 'Review'];
    $reviewOptions = ['overdue' => 'Review overdue', 'next_30' => 'Due in 30 days'];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => match ($key) {
            'status' => $statusLabels[$state[$key]] ?? $state[$key],
            'employee_id' => $employees[$state[$key]] ?? $state[$key],
            'review' => $reviewOptions[$state[$key]] ?? $state[$key],
            default => $state[$key],
        }]);

    // Every list link is built from the full current state rather than the
    // bare route - a query-less URL makes PersistsTableState restore old state.
    $listUrl = fn (array $overrides = []) => route('admin.probation-records.index', array_filter(
        array_merge($state, ['page' => null], $overrides) + ['per_page' => 25],
        fn ($value) => $value !== null && $value !== ''
    ));

    $currentStatus = (string) ($state['status'] ?? '');
    $statusTabs = collect(['' => ['label' => 'All', 'tone' => 'indigo']])
        ->merge(collect($statusLabels)->map(fn ($label, $key) => ['label' => $label, 'tone' => $statusMeta[$key]['tone'] ?? 'slate']));

    $panelKeys = ['employee_id', 'review'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $initialsOf = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

@section('content')
    <x-page-title title="Probation Tracking" :breadcrumbs="['Onboarding' => route('admin.onboarding.overview')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="On probation" :value="$kpis['active']" icon="ti-clock" tone="sky" hint="Including extended" :href="$listUrl(['status' => null, 'review' => null])" />
        <x-kpi-card label="Reviews due in 30 days" :value="$kpis['due_30']" icon="ti-calendar-event" tone="amber" :href="$listUrl(['review' => 'next_30', 'status' => null])" />
        <x-kpi-card label="Reviews overdue" :value="$kpis['overdue']" icon="ti-alert-triangle" tone="{{ $kpis['overdue'] ? 'danger' : 'success' }}"
                    :hint="$kpis['overdue'] ? 'Decision pending' : 'All reviewed on time'" :href="$listUrl(['review' => 'overdue', 'status' => null])" />
        <x-kpi-card label="Confirmed this year" :value="$kpis['confirmed_year']" icon="ti-shield-check" tone="success" :hint="now()->format('Y')" :href="$listUrl(['status' => 'confirmed', 'review' => null])" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.probation-records.index') }}">
            @foreach (['per_page' => $perPage, 'status' => $currentStatus] as $name => $value)
                @if ((string) $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Probation status">
                    @foreach ($statusTabs as $value => $tab)
                        <a href="{{ $listUrl(['status' => $value === '' ? null : $value]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $currentStatus === (string) $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $statusCounts[$value === '' ? 'all' : $value] ?? 0 }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search employee or code" aria-label="Search probation records">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#probation-filter-panel" aria-expanded="false" aria-controls="probation-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    @can('create', \App\Models\ProbationRecord::class)
                        <a href="{{ route('admin.probation-records.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add record</a>
                    @endcan
                </div>
            </div>

            <div class="collapse" id="probation-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-4">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" class="form-select form-select-sm">
                                <option value="">All employees</option>
                                @foreach ($employees as $id => $name)
                                    <option value="{{ $id }}" @selected((string) ($state['employee_id'] ?? '') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <label class="form-label">Review</label>
                            <select name="review" class="form-select form-select-sm">
                                <option value="">Any time</option>
                                @foreach ($reviewOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(($state['review'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-4 d-flex align-items-end gap-2">
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
                        <th>Employee</th>
                        <th style="min-width: 260px">Probation period</th>
                        <th>Review</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        @include('admin.hrm.onboarding.probation-records._row', ['record' => $record])
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-shield-check font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No probation records found</div>
                                @if ($activeFilters->isNotEmpty())
                                    <span class="font-13"><a href="{{ $clearAllUrl }}">Clear filters</a></span>
                                @else
                                    <span class="font-13">Add a record when a new hire starts their probation period.</span>
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
            <div class="ms-auto">{{ $records->links() }}</div>
        </div>
    </div>
@endsection
