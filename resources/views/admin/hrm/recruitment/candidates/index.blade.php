@extends('layouts.admin')

@section('title', 'Candidates')

@php
    $filterLabels = [
        'search' => 'Search',
        'stage' => 'Stage',
        'job_position_id' => 'Applied for',
        'source' => 'Source',
        'applied_from' => 'Applied from',
        'applied_until' => 'Applied until',
    ];

    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(function ($label, $key) use ($state, $jobPositions) {
            $value = $state[$key];

            if ($key === 'stage') {
                $value = Str::headline($value);
            } elseif ($key === 'job_position_id') {
                $value = $jobPositions[$value] ?? $value;
            }

            return ['key' => $key, 'label' => $label, 'value' => $value];
        });

    // Every list link is built from the full current state (sort, per page,
    // other filters) rather than the bare route - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route('admin.candidates.index', array_filter(
        array_merge($state, ['page' => null], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));

    // Pipeline order + colour per stage; "rejected" sits outside the funnel.
    $stageTones = ['applied' => 'slate', 'screening' => 'sky', 'interview' => 'violet', 'offer' => 'amber', 'hired' => 'success', 'rejected' => 'danger'];
    $funnel = ['applied', 'screening', 'interview', 'offer', 'hired'];

    $currentStage = (string) ($state['stage'] ?? '');
    $stageTabs = collect(['' => 'All candidates'])
        ->merge(collect(\App\Models\Candidate::STAGES)->mapWithKeys(fn ($stage) => [$stage => Str::headline($stage)]))
        ->map(fn ($label, $stage) => [
            'label' => $label,
            'tone' => $stage === '' ? 'indigo' : ($stageTones[$stage] ?? 'slate'),
            'count' => $stageCounts[$stage === '' ? 'all' : $stage] ?? 0,
            'current' => $currentStage === (string) $stage,
            'url' => $listUrl(['stage' => $stage === '' ? null : $stage]),
        ]);

    $tagTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky', 'rose', 'lime'];
    $toneFor = fn (?string $text) => $tagTones[crc32(Str::slug((string) $text, '_')) % count($tagTones)];
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];

    $panelKeys = ['job_position_id', 'source', 'applied_from', 'applied_until'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
@endphp

@section('content')
    <x-page-title title="Candidates" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total candidates" :value="$kpis['total']" icon="ti-users" tone="indigo" :href="$listUrl(['stage' => null])" />
        <x-kpi-card label="In pipeline" :value="$kpis['in_pipeline']" icon="ti-chart-arrows" tone="sky" hint="Applied to offer" :href="route('admin.candidates.pipeline')" />
        <x-kpi-card label="Interviewing" :value="$kpis['interviewing']" icon="ti-message-circle-2" tone="violet" :href="$listUrl(['stage' => 'interview'])" />
        <x-kpi-card label="Hired" :value="$kpis['hired']" icon="ti-award" tone="success"
                    :hint="$kpis['this_month'].' applied this month'" :href="$listUrl(['stage' => 'hired'])" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.candidates.index') }}">
            @foreach (['sort' => $sort, 'direction' => $direction, 'per_page' => $perPage, 'stage' => $currentStage] as $name => $value)
                @if ((string) $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Candidate stage">
                    @foreach ($stageTabs as $tab)
                        <a href="{{ $tab['url'] }}" class="list-tab tone-{{ $tab['tone'] }} {{ $tab['current'] ? 'active' : '' }}" @if ($tab['current']) aria-current="page" @endif>
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ number_format($tab['count']) }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search name or email" aria-label="Search candidates">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#candidates-filter-panel" aria-expanded="false" aria-controls="candidates-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    <a href="{{ route('admin.candidates.pipeline') }}" class="btn btn-light btn-sm" title="Kanban pipeline view"><i class="ti ti-layout-kanban font-14 me-1"></i> Pipeline</a>
                    <x-export-csv-button route="admin.candidates.export" />
                    @can('create', \App\Models\Candidate::class)
                        <a href="{{ route('admin.candidates.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-user-plus font-14 me-1"></i> Add candidate</a>
                    @endcan
                </div>
            </div>

            <div class="collapse" id="candidates-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Applied for</label>
                            <select name="job_position_id" class="form-select form-select-sm">
                                <option value="">Any position</option>
                                @foreach ($jobPositions as $id => $title)
                                    <option value="{{ $id }}" @selected((string) ($state['job_position_id'] ?? '') === (string) $id)>{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label">Source</label>
                            <select name="source" class="form-select form-select-sm">
                                <option value="">Any source</option>
                                @foreach ($sources as $source)
                                    <option value="{{ $source }}" @selected(($state['source'] ?? '') === $source)>{{ $source }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-12 col-lg-5">
                            <label class="form-label">Applied between</label>
                            <div class="list-date-range">
                                <input type="date" name="applied_from" class="form-control form-control-sm" value="{{ $state['applied_from'] ?? '' }}" aria-label="Applied from">
                                <span>to</span>
                                <input type="date" name="applied_until" class="form-control form-control-sm" value="{{ $state['applied_until'] ?? '' }}" aria-label="Applied until">
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
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Candidate" />
                        <th>Applied for</th>
                        <x-th-sort column="stage" :sort="$sort" :direction="$direction" label="Stage" />
                        <th>Source</th>
                        <x-th-sort column="applied_date" :sort="$sort" :direction="$direction" label="Applied" />
                        <th class="text-center">Resume</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($candidates as $candidate)
                        @php
                            $initials = collect(preg_split('/\s+/', trim($candidate->name)))
                                ->filter()
                                ->take(2)
                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');
                            $stageIndex = array_search($candidate->stage, $funnel, true);
                            $isRejected = $candidate->stage === 'rejected';
                        @endphp
                        <tr>
                            <td>
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-{{ $avatarTones[crc32($candidate->email.$candidate->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                    <div class="list-user-text">
                                        <div class="list-user-name">
                                            @can('update', $candidate)
                                                <a href="{{ route('admin.candidates.edit', $candidate) }}">{{ $candidate->name }}</a>
                                            @else
                                                <span>{{ $candidate->name }}</span>
                                            @endcan
                                            @if ($candidate->interviews_count)
                                                <span class="list-flag tone-violet" title="Interviews scheduled"><i class="ti ti-calendar-event"></i> {{ $candidate->interviews_count }}</span>
                                            @endif
                                        </div>
                                        <div class="list-user-meta">
                                            {{ $candidate->email ?: 'No email' }}
                                            @if ($candidate->phone)
                                                <span class="list-meta-sep">&middot;</span> {{ $candidate->phone }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($candidate->jobPosition)
                                    <span class="list-icon-text"><i class="ti ti-briefcase"></i>{{ $candidate->jobPosition->title }}</span>
                                @else
                                    <span class="text-muted font-12">General application</span>
                                @endif
                            </td>
                            <td>
                                <div class="stage-cell">
                                    <span class="tag tone-{{ $stageTones[$candidate->stage] ?? 'slate' }}">{{ Str::headline($candidate->stage) }}</span>
                                    <div class="stage-track {{ $isRejected ? 'is-rejected' : '' }}" title="{{ $isRejected ? 'Rejected' : 'Step '.($stageIndex + 1).' of '.count($funnel) }}">
                                        @foreach ($funnel as $index => $step)
                                            <span class="{{ $isRejected || ($stageIndex !== false && $index <= $stageIndex) ? 'is-done tone-'.($isRejected ? 'danger' : $stageTones[$candidate->stage]) : '' }}"></span>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($candidate->source)
                                    <span class="tag tag-outline tone-{{ $toneFor($candidate->source) }}">{{ $candidate->source }}</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($candidate->applied_date)
                                    <div>{{ $candidate->applied_date->format('M j, Y') }}</div>
                                    <div class="list-subtext">{{ $candidate->applied_date->diffForHumans() }}</div>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($candidate->resume_path)
                                    <a href="{{ asset('storage/'.$candidate->resume_path) }}" target="_blank" rel="noopener" class="btn-icon btn-icon-info" title="Open resume"><i class="ti ti-file-text font-18"></i></a>
                                @else
                                    <span class="text-muted font-12">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($candidate->email)
                                        <a href="mailto:{{ $candidate->email }}" class="btn-icon btn-icon-success" title="Email {{ $candidate->email }}"><i class="ti ti-mail font-16"></i></a>
                                    @endif
                                    @can('update', $candidate)
                                        <a href="{{ route('admin.candidates.edit', $candidate) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $candidate)
                                        <form action="{{ route('admin.candidates.destroy', $candidate) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this candidate?');">
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
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-users font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No candidates found</div>
                                @if ($activeFilters->isNotEmpty())
                                    <span class="font-13">Try adjusting your filters, or <a href="{{ $clearAllUrl }}">clear them all</a>.</span>
                                @else
                                    <span class="font-13">Add candidates as applications come in to track them through hiring.</span>
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
            <div class="ms-auto">{{ $candidates->links() }}</div>
        </div>
    </div>
@endsection
