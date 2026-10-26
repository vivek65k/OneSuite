@extends('layouts.admin')

@section('title', 'Interview Feedback')

@php
    $recommendationMeta = [
        'hire' => ['label' => 'Hire', 'tone' => 'success', 'icon' => 'ti-thumb-up'],
        'maybe' => ['label' => 'Maybe', 'tone' => 'amber', 'icon' => 'ti-help'],
        'no_hire' => ['label' => 'No hire', 'tone' => 'danger', 'icon' => 'ti-thumb-down'],
    ];

    $filterLabels = [
        'search' => 'Search',
        'recommendation' => 'Recommendation',
        'candidate_id' => 'Candidate',
        'interviewer' => 'Interviewer',
        'min_rating' => 'Rating',
    ];

    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(function ($label, $key) use ($state, $candidates, $recommendationMeta) {
            $value = $state[$key];

            if ($key === 'recommendation') {
                $value = $recommendationMeta[$value]['label'] ?? Str::headline($value);
            } elseif ($key === 'candidate_id') {
                $value = $candidates[$value] ?? $value;
            } elseif ($key === 'min_rating') {
                $value = $value.'★ & up';
            }

            return ['key' => $key, 'label' => $label, 'value' => $value];
        });

    // Every list link is built from the full current state (sort, per page,
    // other filters) rather than the bare route - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route('admin.interview-feedback.index', array_filter(
        array_merge($state, ['page' => null], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));

    $currentRecommendation = (string) ($state['recommendation'] ?? '');
    $tabs = collect(['' => ['label' => 'All feedback', 'tone' => 'indigo']])
        ->merge(collect($recommendationMeta)->map(fn ($meta) => ['label' => $meta['label'], 'tone' => $meta['tone']]))
        ->map(fn ($tab, $key) => $tab + [
            'count' => $recommendationCounts[$key === '' ? 'all' : $key] ?? 0,
            'current' => $currentRecommendation === (string) $key,
            'url' => $listUrl(['recommendation' => $key === '' ? null : $key]),
        ]);

    $hireRate = $kpis['total'] ? round($kpis['hire'] / $kpis['total'] * 100) : null;
    $avgRating = $kpis['avg_rating'] !== null ? round((float) $kpis['avg_rating'], 1) : null;

    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $tagTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky', 'rose', 'lime'];
    $toneFor = fn (?string $text) => $tagTones[crc32(Str::slug((string) $text, '_')) % count($tagTones)];
    $initialsOf = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $panelKeys = ['candidate_id', 'interviewer', 'min_rating'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
    $canCreate = auth()->user()->can('create', \App\Models\InterviewFeedback::class);
@endphp

@section('content')
    <x-page-title title="Interview Feedback" :breadcrumbs="['Interviews' => route('admin.interviews.index')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Feedback received" :value="$kpis['total']" icon="ti-message-circle" tone="indigo" :href="$listUrl(['recommendation' => null])" />
        <x-kpi-card label="Recommended to hire" :value="$kpis['hire']" icon="ti-thumb-up" tone="success"
                    :hint="$hireRate !== null ? $hireRate.'% of all feedback' : null" :href="$listUrl(['recommendation' => 'hire'])" />
        <x-kpi-card label="Average rating" :value="$avgRating !== null ? $avgRating.' / 5' : '—'" icon="ti-star" tone="amber"
                    :hint="$kpis['rated'] ? 'From '.$kpis['rated'].' rated '.Str::plural('interview', $kpis['rated']) : 'No ratings yet'" />
        <x-kpi-card label="Awaiting feedback" :value="$kpis['awaiting']" icon="ti-clock" tone="pink" hint="Completed, not yet reviewed"
                    :href="route('admin.interviews.index', ['status' => 'completed', 'sort' => 'scheduled_at', 'direction' => 'desc', 'per_page' => 25])" />
    </div>

    @if ($awaitingInterviews->isNotEmpty() && $canCreate)
        <div class="nudge-card tone-pink">
            <div class="nudge-card-head">
                <span class="nudge-card-icon"><i class="ti ti-clock"></i></span>
                <div>
                    <div class="fw-semibold">{{ $kpis['awaiting'] }} completed {{ Str::plural('interview', $kpis['awaiting']) }} still {{ $kpis['awaiting'] === 1 ? 'needs' : 'need' }} feedback</div>
                    <div class="text-muted font-12">Capture it while it's fresh - it drives the hiring decision.</div>
                </div>
            </div>
            <div class="nudge-list">
                @foreach ($awaitingInterviews as $pending)
                    <div class="nudge-item">
                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32((string) $pending->candidate?->email.$pending->candidate?->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($pending->candidate?->name) }}</span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold text-truncate">{{ $pending->candidate?->name ?? 'Candidate' }}</div>
                            <div class="list-subtext text-truncate">{{ $pending->round }} · {{ $pending->scheduled_at?->diffForHumans() }}</div>
                        </div>
                        <a href="{{ route('admin.interview-feedback.create', ['interview_id' => $pending->id]) }}" class="btn btn-sm nudge-btn"><i class="ti ti-plus font-14 me-1"></i> Add feedback</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.interview-feedback.index') }}">
            @foreach (['sort' => $sort, 'direction' => $direction, 'per_page' => $perPage, 'recommendation' => $currentRecommendation] as $name => $value)
                @if ((string) $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Recommendation">
                    @foreach ($tabs as $tab)
                        <a href="{{ $tab['url'] }}" class="list-tab tone-{{ $tab['tone'] }} {{ $tab['current'] ? 'active' : '' }}" @if ($tab['current']) aria-current="page" @endif>
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ number_format($tab['count']) }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Candidate, interviewer or comment" aria-label="Search feedback">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#feedback-filter-panel" aria-expanded="false" aria-controls="feedback-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    <x-export-csv-button route="admin.interview-feedback.export" />
                    @if ($canCreate)
                        <a href="{{ route('admin.interview-feedback.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-message-plus font-14 me-1"></i> Add feedback</a>
                    @endif
                </div>
            </div>

            <div class="collapse" id="feedback-filter-panel">
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
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Interviewer</label>
                            <select name="interviewer" class="form-select form-select-sm">
                                <option value="">Anyone</option>
                                @foreach ($interviewers as $name)
                                    <option value="{{ $name }}" @selected(($state['interviewer'] ?? '') === $name)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Minimum rating</label>
                            <select name="min_rating" class="form-select form-select-sm">
                                <option value="">Any rating</option>
                                @foreach ([5, 4, 3, 2] as $stars)
                                    <option value="{{ $stars }}" @selected((string) ($state['min_rating'] ?? '') === (string) $stars)>{{ str_repeat('★', $stars) }} {{ $stars === 5 ? 'only' : '& up' }}</option>
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
                        <th>Candidate</th>
                        <th>Round</th>
                        <x-th-sort column="interviewer_name" :sort="$sort" :direction="$direction" label="Interviewer" />
                        <x-th-sort column="rating" :sort="$sort" :direction="$direction" label="Rating" />
                        <x-th-sort column="recommendation" :sort="$sort" :direction="$direction" label="Verdict" />
                        <th>Comments</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feedbackEntries as $entry)
                        @php
                            $interview = $entry->interview;
                            $candidate = $interview?->candidate;
                            $meta = $recommendationMeta[$entry->recommendation] ?? ['label' => Str::headline($entry->recommendation), 'tone' => 'slate', 'icon' => 'ti-point'];
                        @endphp
                        <tr>
                            <td>
                                @if ($candidate)
                                    <div class="list-user">
                                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($candidate->email.$candidate->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($candidate->name) }}</span>
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
                                @if ($interview)
                                    <span class="tag tone-{{ $toneFor($interview->round) }}">{{ $interview->round }}</span>
                                    <div class="list-subtext mt-1"><i class="ti ti-calendar"></i> {{ $interview->scheduled_at?->format('M j, Y') }}</div>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td><span class="list-icon-text"><i class="ti ti-user-check"></i>{{ $entry->interviewer_name }}</span></td>
                            <td class="text-nowrap">
                                @if ($entry->rating)
                                    <span class="star-rating" title="{{ $entry->rating }} out of 5" aria-label="{{ $entry->rating }} out of 5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="ti {{ $i <= $entry->rating ? 'ti-star is-on' : 'ti-star' }}"></i>
                                        @endfor
                                    </span>
                                @else
                                    <span class="text-muted font-12">Not rated</span>
                                @endif
                            </td>
                            <td>
                                <span class="verdict-pill tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i> {{ $meta['label'] }}</span>
                            </td>
                            <td class="comment-cell">
                                @if ($entry->comments)
                                    <span class="comment-excerpt" title="{{ $entry->comments }}">{{ Str::limit($entry->comments, 90) }}</span>
                                @else
                                    <span class="text-muted font-12">No comments</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $entry)
                                        <a href="{{ route('admin.interview-feedback.edit', $entry) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $entry)
                                        <form action="{{ route('admin.interview-feedback.destroy', $entry) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this feedback?');">
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
                                <i class="ti ti-message-2 font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No feedback found</div>
                                @if ($activeFilters->isNotEmpty())
                                    <span class="font-13">Try adjusting your filters, or <a href="{{ $clearAllUrl }}">clear them all</a>.</span>
                                @else
                                    <span class="font-13">Feedback appears here once interviewers submit their scorecards.</span>
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
            <div class="ms-auto">{{ $feedbackEntries->links() }}</div>
        </div>
    </div>
@endsection
