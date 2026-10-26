@extends('layouts.admin')

@section('title', 'Open Jobs')

@php
    $filterLabels = [
        'search' => 'Search',
        'department_id' => 'Department',
        'branch_id' => 'Branch',
        'employment_type' => 'Type',
    ];

    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(function ($label, $key) use ($state, $departments, $branches, $employmentTypes) {
            $value = $state[$key];

            if ($key === 'department_id') {
                $value = $departments[$value] ?? $value;
            } elseif ($key === 'branch_id') {
                $value = $branches[$value] ?? $value;
            } elseif ($key === 'employment_type') {
                $value = $employmentTypes[$value] ?? Str::headline($value);
            }

            return ['key' => $key, 'label' => $label, 'value' => $value];
        });

    // Every link is built from the full current state rather than the bare
    // route - a query-less URL makes PersistsTableState restore old state.
    $listUrl = fn (array $overrides = []) => route('admin.job-positions.open', array_filter(
        array_merge($state, ['page' => null], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));

    $sortOptions = [
        'Newest first' => ['sort' => 'created_at', 'direction' => 'desc'],
        'Oldest first' => ['sort' => 'created_at', 'direction' => 'asc'],
        'Title A-Z' => ['sort' => 'title', 'direction' => 'asc'],
        'Most openings' => ['sort' => 'headcount', 'direction' => 'desc'],
    ];
    $currentSortLabel = collect($sortOptions)->search(fn ($option) => $option['sort'] === $sort && $option['direction'] === $direction) ?: 'Title A-Z';

    $tagTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky', 'rose', 'lime'];
    $toneFor = fn (?string $text) => $tagTones[crc32(Str::slug((string) $text, '_')) % count($tagTones)];
    $money = fn ($amount) => $amount === null ? null : rtrim(rtrim(number_format((float) $amount, 2), '0'), '.');

    $panelKeys = ['department_id', 'branch_id', 'employment_type'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
@endphp

@section('content')
    <x-page-title title="Open Jobs" :breadcrumbs="['Job Positions' => route('admin.job-positions.index')]" />

    <div class="kpi-strip">
        <x-kpi-card label="Open positions" :value="$kpis['open']" icon="ti-speakerphone" tone="success" hint="Accepting applications" />
        <x-kpi-card label="Seats to fill" :value="$kpis['openings']" icon="ti-user-plus" tone="violet"
                    :hint="$kpis['hired'] ? $kpis['hired'].' hired so far' : 'None hired yet'" />
        <x-kpi-card label="Applicants" :value="$kpis['applicants']" icon="ti-users" tone="pink"
                    :hint="$kpis['openings'] ? round($kpis['applicants'] / $kpis['openings'], 1).' per seat' : null" :href="route('admin.candidates.index')" />
        <x-kpi-card label="In pipeline" :value="$kpis['in_pipeline']" icon="ti-chart-arrows" tone="sky" hint="Screening to offer" :href="route('admin.candidates.pipeline')" />
    </div>

    <div class="card list-view mb-3">
        <form method="GET" action="{{ route('admin.job-positions.open') }}">
            @foreach (['sort' => $sort, 'direction' => $direction, 'per_page' => $perPage] as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach

            <div class="list-toolbar">
                <div class="list-toolbar-title">
                    <span class="list-toolbar-title-icon tone-success"><i class="ti ti-speakerphone"></i></span>
                    <div>
                        <div class="fw-semibold">Job board</div>
                        <div class="text-muted font-12">{{ $positions->total() }} open {{ Str::plural('position', $positions->total()) }}</div>
                    </div>
                </div>

                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search job title" aria-label="Search open jobs">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#open-jobs-filter-panel" aria-expanded="false" aria-controls="open-jobs-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-arrows-sort font-14 me-1"></i> {{ $currentSortLabel }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @foreach ($sortOptions as $label => $option)
                                <li><a class="dropdown-item {{ $label === $currentSortLabel ? 'active' : '' }}" href="{{ $listUrl($option) }}">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    @can('create', \App\Models\JobPosition::class)
                        <a href="{{ route('admin.job-positions.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New position</a>
                    @endcan
                </div>
            </div>

            <div class="collapse" id="open-jobs-filter-panel">
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
            <div class="list-subbar border-bottom-0">
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
    </div>

    @if ($positions->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-success"><i class="ti ti-speakerphone"></i></span>
                <h5>{{ $activeFilters->isNotEmpty() ? 'No open jobs match your filters' : 'No open positions right now' }}</h5>
                <p>
                    @if ($activeFilters->isNotEmpty())
                        Try adjusting your filters, or <a href="{{ $clearAllUrl }}">clear them all</a>.
                    @else
                        Positions set to <strong>Open</strong> appear here as a job board.
                    @endif
                </p>
                @can('create', \App\Models\JobPosition::class)
                    <a href="{{ route('admin.job-positions.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New position</a>
                @endcan
            </div>
        </div>
    @else
        <div class="job-grid">
            @foreach ($positions as $position)
                @php
                    $tone = $toneFor($position->department?->name ?? $position->title);
                    $filledPercent = min(100, (int) round($position->hired_count / max(1, $position->headcount) * 100));
                    $seatsLeft = max(0, $position->headcount - $position->hired_count);
                @endphp
                <article class="job-card tone-{{ $tone }}">
                    <div class="job-card-head">
                        <span class="job-card-icon"><i class="ti ti-briefcase"></i></span>
                        <div class="job-card-heading">
                            <h5 class="job-card-title">
                                @can('update', $position)
                                    <a href="{{ route('admin.job-positions.edit', $position) }}">{{ $position->title }}</a>
                                @else
                                    {{ $position->title }}
                                @endcan
                            </h5>
                            <div class="job-card-posted">Posted {{ $position->created_at?->diffForHumans() }}</div>
                        </div>
                        <span class="status-dot status-dot-success">Open</span>
                    </div>

                    <div class="job-card-tags">
                        @if ($position->department)
                            <span class="tag tone-{{ $toneFor($position->department->name) }}"><i class="ti ti-sitemap"></i>{{ $position->department->name }}</span>
                        @endif
                        @if ($position->branch)
                            <span class="tag tone-sky"><i class="ti ti-map-pin"></i>{{ $position->branch->name }}</span>
                        @endif
                        @if ($position->employment_type)
                            <span class="tag tag-outline tone-{{ $toneFor($position->employment_type) }}"><i class="ti ti-clock"></i>{{ Str::headline($position->employment_type) }}</span>
                        @endif
                    </div>

                    <div class="job-card-salary">
                        <i class="ti ti-cash"></i>
                        @if ($position->min_salary !== null || $position->max_salary !== null)
                            <strong>{{ $currency }} {{ $money($position->min_salary) ?? '—' }} &ndash; {{ $money($position->max_salary) ?? '—' }}</strong>
                        @else
                            <span class="text-muted">Salary not disclosed</span>
                        @endif
                    </div>

                    <div class="job-card-stats">
                        <div class="job-card-stat tone-violet">
                            <span class="job-card-stat-value">{{ $position->headcount }}</span>
                            <span class="job-card-stat-label">{{ Str::plural('Opening', $position->headcount) }}</span>
                        </div>
                        <div class="job-card-stat tone-pink">
                            <span class="job-card-stat-value">{{ $position->candidates_count }}</span>
                            <span class="job-card-stat-label">{{ Str::plural('Applicant', $position->candidates_count) }}</span>
                        </div>
                        <div class="job-card-stat tone-sky">
                            <span class="job-card-stat-value">{{ $position->in_pipeline_count }}</span>
                            <span class="job-card-stat-label">In pipeline</span>
                        </div>
                    </div>

                    <div class="job-card-progress" title="{{ $position->hired_count }} of {{ $position->headcount }} seats filled">
                        <div class="job-card-progress-label">
                            <span>Seats filled</span>
                            <span><strong>{{ $position->hired_count }}</strong> / {{ $position->headcount }} &middot; {{ $seatsLeft }} left</span>
                        </div>
                        <div class="job-card-progress-bar"><span style="width: {{ $filledPercent }}%"></span></div>
                    </div>

                    <div class="job-card-actions">
                        <a href="{{ route('admin.candidates.index', ['job_position_id' => $position->id, 'sort' => 'applied_date', 'direction' => 'desc', 'per_page' => 25]) }}" class="btn btn-light btn-sm flex-fill">
                            <i class="ti ti-users font-14 me-1"></i> View applicants
                        </a>
                        @can('update', $position)
                            <a href="{{ route('admin.job-positions.edit', $position) }}" class="btn-icon btn-icon-accent" title="Edit position"><i class="ti ti-pencil font-16"></i></a>
                        @endcan
                    </div>
                </article>
            @endforeach
        </div>

        <div class="list-footer job-grid-footer">
            <div class="list-per-page dropdown">
                <span class="text-muted font-13">Per page</span>
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">{{ $perPage }}</button>
                <ul class="dropdown-menu">
                    @foreach ([10, 25, 50, 100] as $option)
                        <li><a class="dropdown-item {{ $perPage === $option ? 'active' : '' }}" href="{{ $listUrl(['per_page' => $option]) }}">{{ $option }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="ms-auto">{{ $positions->links() }}</div>
        </div>
    @endif
@endsection
