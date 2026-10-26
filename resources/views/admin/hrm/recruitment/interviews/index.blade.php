@extends('layouts.admin')

@section('title', 'Interview Schedule')

@php
    $filterLabels = [
        'search' => 'Search',
        'when' => 'When',
        'status' => 'Status',
        'candidate_id' => 'Candidate',
        'job_position_id' => 'Position',
        'interviewer' => 'Interviewer',
        'date_from' => 'From',
        'date_until' => 'Until',
    ];
    $whenOptions = ['' => 'All time', 'upcoming' => 'Upcoming', 'today' => 'Today', 'week' => 'This week', 'past' => 'Past'];

    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(function ($label, $key) use ($state, $candidates, $jobPositions, $whenOptions) {
            $value = $state[$key];

            if ($key === 'status') {
                $value = Str::headline($value);
            } elseif ($key === 'when') {
                $value = $whenOptions[$value] ?? $value;
            } elseif ($key === 'candidate_id') {
                $value = $candidates[$value] ?? $value;
            } elseif ($key === 'job_position_id') {
                $value = $jobPositions[$value] ?? $value;
            }

            return ['key' => $key, 'label' => $label, 'value' => $value];
        });

    // Every list link is built from the full current state (sort, per page,
    // other filters) rather than the bare route - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route('admin.interviews.index', array_filter(
        array_merge($state, ['page' => null], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));

    $statusTones = ['scheduled' => 'sky', 'rescheduled' => 'amber', 'completed' => 'success', 'cancelled' => 'danger'];
    $statusDots = ['scheduled' => 'info', 'rescheduled' => 'warning', 'completed' => 'success', 'cancelled' => 'muted'];

    $currentStatus = (string) ($state['status'] ?? '');
    $currentWhen = (string) ($state['when'] ?? '');
    $statusTabs = collect(['' => 'All interviews'])
        ->merge(collect(\App\Models\Interview::STATUSES)->mapWithKeys(fn ($status) => [$status => Str::headline($status)]))
        ->map(fn ($label, $status) => [
            'label' => $label,
            'tone' => $status === '' ? 'indigo' : ($statusTones[$status] ?? 'slate'),
            'count' => $statusCounts[$status === '' ? 'all' : $status] ?? 0,
            'current' => $currentStatus === (string) $status,
            'url' => $listUrl(['status' => $status === '' ? null : $status]),
        ]);

    // Agenda grouping only makes sense when rows are in date order.
    $groupByDay = $sort === 'scheduled_at';
    $dayLabel = function ($date) {
        if ($date->isToday()) return 'Today';
        if ($date->isTomorrow()) return 'Tomorrow';
        if ($date->isYesterday()) return 'Yesterday';
        return $date->format($date->isSameYear(now()) ? 'l, M j' : 'l, M j, Y');
    };

    $tagTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky', 'rose', 'lime'];
    $toneFor = fn (?string $text) => $tagTones[crc32(Str::slug((string) $text, '_')) % count($tagTones)];
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];

    $panelKeys = ['candidate_id', 'job_position_id', 'interviewer', 'date_from', 'date_until'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
    $previousDay = null;
@endphp

@section('content')
    <x-page-title title="Interview Schedule" :breadcrumbs="['Candidates' => route('admin.candidates.index')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Today" :value="$kpis['today']" icon="ti-calendar-time" tone="pink" :hint="now()->format('l, M j')" :href="$listUrl(['when' => 'today', 'status' => null])" />
        <x-kpi-card label="Rest of this week" :value="$kpis['week']" icon="ti-calendar-stats" tone="violet" :href="$listUrl(['when' => 'week', 'status' => null])" />
        <x-kpi-card label="Upcoming" :value="$kpis['upcoming']" icon="ti-calendar-plus" tone="sky" hint="Scheduled or rescheduled" :href="$listUrl(['when' => 'upcoming', 'status' => null])" />
        <x-kpi-card label="Completed" :value="$kpis['completed']" icon="ti-circle-check" tone="success"
                    :hint="$kpis['awaiting_feedback'] ? $kpis['awaiting_feedback'].' awaiting feedback' : 'All have feedback'" :href="$listUrl(['status' => 'completed', 'when' => null])" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.interviews.index') }}">
            @foreach (['sort' => $sort, 'direction' => $direction, 'per_page' => $perPage, 'status' => $currentStatus, 'when' => $currentWhen] as $name => $value)
                @if ((string) $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Interview status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Candidate, round or interviewer" aria-label="Search interviews">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#interviews-filter-panel" aria-expanded="false" aria-controls="interviews-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    <x-export-csv-button route="admin.interviews.export" />
                    @can('create', \App\Models\Interview::class)
                        <a href="{{ route('admin.interviews.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-calendar-plus font-14 me-1"></i> Schedule interview</a>
                    @endcan
                </div>
            </div>

            {{-- Time-window switch --}}
            <div class="list-segment-bar">
                <div class="segmented" role="group" aria-label="When">
                    @foreach ($whenOptions as $value => $label)
                        <a href="{{ $listUrl(['when' => $value === '' ? null : $value]) }}" class="segmented-item {{ $currentWhen === (string) $value ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <div class="list-segment-hint">
                    <i class="ti ti-clock"></i> Times shown in {{ config('app.timezone') }}
                </div>
            </div>

            <div class="collapse" id="interviews-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Candidate</label>
                            <select name="candidate_id" class="form-select form-select-sm">
                                <option value="">Any candidate</option>
                                @foreach ($candidates as $id => $name)
                                    <option value="{{ $id }}" @selected((string) ($state['candidate_id'] ?? '') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label">Position</label>
                            <select name="job_position_id" class="form-select form-select-sm">
                                <option value="">Any position</option>
                                @foreach ($jobPositions as $id => $title)
                                    <option value="{{ $id }}" @selected((string) ($state['job_position_id'] ?? '') === (string) $id)>{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label">Interviewer</label>
                            <select name="interviewer" class="form-select form-select-sm">
                                <option value="">Anyone</option>
                                @foreach ($interviewers as $name)
                                    <option value="{{ $name }}" @selected(($state['interviewer'] ?? '') === $name)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Between</label>
                            <div class="list-date-range">
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $state['date_from'] ?? '' }}" aria-label="From date">
                                <span>to</span>
                                <input type="date" name="date_until" class="form-control form-control-sm" value="{{ $state['date_until'] ?? '' }}" aria-label="Until date">
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
                        <x-th-sort column="scheduled_at" :sort="$sort" :direction="$direction" label="When" />
                        <th>Candidate</th>
                        <x-th-sort column="round" :sort="$sort" :direction="$direction" label="Round" />
                        <th>Interviewer</th>
                        <th>Location</th>
                        <x-th-sort column="status" :sort="$sort" :direction="$direction" label="Status" />
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($interviews as $interview)
                        @php
                            $at = $interview->scheduled_at;
                            $dayKey = $at->toDateString();
                            $isActive = in_array($interview->status, ['scheduled', 'rescheduled'], true);
                            $isOverdue = $isActive && $at->isPast();
                            $isLink = Str::startsWith((string) $interview->location, ['http://', 'https://']);
                            $candidate = $interview->candidate;
                            $initials = collect(preg_split('/\s+/', trim((string) $candidate?->name)))
                                ->filter()->take(2)
                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');
                        @endphp

                        @if ($groupByDay && $dayKey !== $previousDay)
                            @php $previousDay = $dayKey; @endphp
                            <tr class="agenda-day {{ $at->isToday() ? 'is-today' : '' }} {{ $at->isPast() && ! $at->isToday() ? 'is-past' : '' }}">
                                <td colspan="7">
                                    <span class="agenda-day-label"><i class="ti {{ $at->isToday() ? 'ti-sun' : 'ti-calendar' }}"></i> {{ $dayLabel($at) }}</span>
                                    <span class="agenda-day-date">{{ $at->format('M j, Y') }}</span>
                                </td>
                            </tr>
                        @endif

                        <tr class="{{ $interview->status === 'cancelled' ? 'is-muted-row' : '' }}">
                            <td class="text-nowrap">
                                <div class="when-cell">
                                    <span class="when-block tone-{{ $statusTones[$interview->status] ?? 'slate' }}">
                                        <span class="when-block-month">{{ $at->format('M') }}</span>
                                        <span class="when-block-day">{{ $at->format('j') }}</span>
                                    </span>
                                    <div>
                                        <div class="when-time">{{ $at->format('g:i A') }}</div>
                                        <div class="list-subtext {{ $isOverdue ? 'text-danger fw-semibold' : '' }}">
                                            {{ $isOverdue ? 'Overdue · ' : '' }}{{ $at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($candidate)
                                    <div class="list-user">
                                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($candidate->email.$candidate->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                        <div class="list-user-text">
                                            <div class="list-user-name">
                                                @can('update', $candidate)
                                                    <a href="{{ route('admin.candidates.edit', $candidate) }}">{{ $candidate->name }}</a>
                                                @else
                                                    <span>{{ $candidate->name }}</span>
                                                @endcan
                                            </div>
                                            <div class="list-user-meta"><i class="ti ti-briefcase"></i> {{ $candidate->jobPosition?->title ?: 'General application' }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $toneFor($interview->round) }}">{{ $interview->round }}</span>
                                @if ($interview->feedback_count)
                                    <div class="list-subtext mt-1"><i class="ti ti-message-circle"></i> {{ $interview->feedback_count }} {{ Str::plural('feedback', $interview->feedback_count) }}</div>
                                @elseif ($interview->status === 'completed')
                                    <div class="list-subtext mt-1 text-warning"><i class="ti ti-alert-circle"></i> Feedback pending</div>
                                @endif
                            </td>
                            <td>
                                @if ($interview->interviewer_name)
                                    <span class="list-icon-text"><i class="ti ti-user-check"></i>{{ $interview->interviewer_name }}</span>
                                @else
                                    <span class="text-muted font-12">Not assigned</span>
                                @endif
                            </td>
                            <td>
                                @if ($isLink)
                                    <a href="{{ $interview->location }}" target="_blank" rel="noopener" class="btn btn-sm join-btn {{ $isActive ? '' : 'is-disabled' }}">
                                        <i class="ti ti-video font-14 me-1"></i> {{ $isActive ? 'Join' : 'Link' }}
                                    </a>
                                @elseif ($interview->location)
                                    <span class="list-icon-text"><i class="ti ti-map-pin"></i>{{ Str::limit($interview->location, 28) }}</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                <span class="status-dot status-dot-{{ $statusDots[$interview->status] ?? 'muted' }}">{{ Str::headline($interview->status) }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $interview)
                                        <a href="{{ route('admin.interviews.edit', $interview) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $interview)
                                        <form action="{{ route('admin.interviews.destroy', $interview) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this interview?');">
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
                                <i class="ti ti-calendar-event font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No interviews found</div>
                                @if ($activeFilters->isNotEmpty())
                                    <span class="font-13">Try adjusting your filters, or <a href="{{ $clearAllUrl }}">clear them all</a>.</span>
                                @else
                                    <span class="font-13">Schedule an interview to see it on the agenda.</span>
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
            <div class="ms-auto">{{ $interviews->links() }}</div>
        </div>
    </div>
@endsection
