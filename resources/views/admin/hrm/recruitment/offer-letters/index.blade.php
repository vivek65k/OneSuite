@extends('layouts.admin')

@section('title', 'Offer Letters')

@php
    // "rejected" on an offer means the candidate turned it down.
    $statusMeta = [
        'draft' => ['label' => 'Draft', 'tone' => 'violet', 'dot' => 'muted', 'icon' => 'ti-pencil'],
        'sent' => ['label' => 'Sent', 'tone' => 'sky', 'dot' => 'info', 'icon' => 'ti-send'],
        'accepted' => ['label' => 'Accepted', 'tone' => 'success', 'dot' => 'success', 'icon' => 'ti-circle-check'],
        'rejected' => ['label' => 'Declined', 'tone' => 'danger', 'dot' => 'danger', 'icon' => 'ti-circle-x'],
        'withdrawn' => ['label' => 'Withdrawn', 'tone' => 'slate', 'dot' => 'muted', 'icon' => 'ti-arrow-back-up'],
    ];
    $lifecycle = ['draft', 'sent', 'accepted'];

    $filterLabels = [
        'search' => 'Search',
        'status' => 'Status',
        'job_position_id' => 'Position',
        'joining' => 'Joining',
        'offer_from' => 'Offered from',
        'offer_until' => 'Offered until',
    ];
    $joiningOptions = ['next_30' => 'In the next 30 days', 'overdue' => 'Accepted, date passed'];

    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(function ($label, $key) use ($state, $jobPositions, $statusMeta, $joiningOptions) {
            $value = $state[$key];

            if ($key === 'status') {
                $value = $statusMeta[$value]['label'] ?? Str::headline($value);
            } elseif ($key === 'job_position_id') {
                $value = $jobPositions[$value] ?? $value;
            } elseif ($key === 'joining') {
                $value = $joiningOptions[$value] ?? $value;
            }

            return ['key' => $key, 'label' => $label, 'value' => $value];
        });

    // Every list link is built from the full current state (sort, per page,
    // other filters) rather than the bare route - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route('admin.offer-letters.index', array_filter(
        array_merge($state, ['page' => null], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));

    $currentStatus = (string) ($state['status'] ?? '');
    $statusTabs = collect(['' => ['label' => 'All offers', 'tone' => 'indigo']])
        ->merge(collect($statusMeta)->map(fn ($meta) => ['label' => $meta['label'], 'tone' => $meta['tone']]))
        ->map(fn ($tab, $status) => $tab + [
            'count' => $statusCounts[$status === '' ? 'all' : $status] ?? 0,
            'current' => $currentStatus === (string) $status,
            'url' => $listUrl(['status' => $status === '' ? null : $status]),
        ]);

    // Row-menu quick actions per current status.
    $quickActions = [
        'draft' => ['sent' => 'Mark as sent', 'withdrawn' => 'Withdraw'],
        'sent' => ['accepted' => 'Mark accepted', 'rejected' => 'Mark declined', 'withdrawn' => 'Withdraw'],
        'accepted' => ['withdrawn' => 'Withdraw'],
        'rejected' => ['draft' => 'Reopen as draft'],
        'withdrawn' => ['draft' => 'Reopen as draft'],
    ];

    $money = fn ($amount) => $amount === null ? null : rtrim(rtrim(number_format((float) $amount, 2), '0'), '.');
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $initialsOf = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $panelKeys = ['job_position_id', 'joining', 'offer_from', 'offer_until'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
@endphp

@section('content')
    <x-page-title title="Offer Letters" :breadcrumbs="['Candidates' => route('admin.candidates.index')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Awaiting response" :value="$kpis['sent']" icon="ti-send" tone="sky" hint="Sent, no answer yet" :href="$listUrl(['status' => 'sent', 'joining' => null])" />
        <x-kpi-card label="Accepted" :value="$kpis['accepted']" icon="ti-circle-check" tone="success"
                    :hint="$kpis['acceptance_rate'] !== null ? $kpis['acceptance_rate'].'% acceptance rate' : 'No responses yet'" :href="$listUrl(['status' => 'accepted', 'joining' => null])" />
        <x-kpi-card label="Joining in 30 days" :value="$kpis['joining_soon']" icon="ti-calendar-event" tone="pink" hint="Accepted offers" :href="$listUrl(['status' => 'accepted', 'joining' => 'next_30'])" />
        <x-kpi-card label="Drafts" :value="$kpis['drafts']" icon="ti-pencil" tone="violet" hint="Not sent yet" :href="$listUrl(['status' => 'draft', 'joining' => null])" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.offer-letters.index') }}">
            @foreach (['sort' => $sort, 'direction' => $direction, 'per_page' => $perPage, 'status' => $currentStatus] as $name => $value)
                @if ((string) $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Offer status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search candidate" aria-label="Search offers">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#offers-filter-panel" aria-expanded="false" aria-controls="offers-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    <x-export-csv-button route="admin.offer-letters.export" />
                    @can('create', \App\Models\OfferLetter::class)
                        <a href="{{ route('admin.offer-letters.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-file-certificate font-14 me-1"></i> Create offer</a>
                    @endcan
                </div>
            </div>

            <div class="collapse" id="offers-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Position</label>
                            <select name="job_position_id" class="form-select form-select-sm">
                                <option value="">Any position</option>
                                @foreach ($jobPositions as $id => $title)
                                    <option value="{{ $id }}" @selected((string) ($state['job_position_id'] ?? '') === (string) $id)>{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label">Joining</label>
                            <select name="joining" class="form-select form-select-sm">
                                <option value="">Any time</option>
                                @foreach ($joiningOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(($state['joining'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-12 col-lg-5">
                            <label class="form-label">Offered between</label>
                            <div class="list-date-range">
                                <input type="date" name="offer_from" class="form-control form-control-sm" value="{{ $state['offer_from'] ?? '' }}" aria-label="Offered from">
                                <span>to</span>
                                <input type="date" name="offer_until" class="form-control form-control-sm" value="{{ $state['offer_until'] ?? '' }}" aria-label="Offered until">
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
                        <th>Candidate</th>
                        <th>Position</th>
                        <x-th-sort column="salary_offered" :sort="$sort" :direction="$direction" label="Salary" />
                        <x-th-sort column="offer_date" :sort="$sort" :direction="$direction" label="Offered" />
                        <th>Joining</th>
                        <x-th-sort column="status" :sort="$sort" :direction="$direction" label="Status" />
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($offers as $offer)
                        @php
                            $candidate = $offer->candidate;
                            $meta = $statusMeta[$offer->status] ?? ['label' => Str::headline($offer->status), 'tone' => 'slate', 'dot' => 'muted', 'icon' => 'ti-point'];
                            $step = array_search($offer->status, $lifecycle, true);
                            $isClosed = in_array($offer->status, ['rejected', 'withdrawn'], true);
                            $joining = $offer->joining_date;
                        @endphp
                        <tr>
                            <td>
                                @if ($candidate)
                                    <div class="list-user">
                                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($candidate->email.$candidate->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($candidate->name) }}</span>
                                        <div class="list-user-text">
                                            <div class="list-user-name">
                                                @can('update', $offer)
                                                    <a href="{{ route('admin.offer-letters.edit', $offer) }}">{{ $candidate->name }}</a>
                                                @else
                                                    <span>{{ $candidate->name }}</span>
                                                @endcan
                                            </div>
                                            <div class="list-user-meta">{{ $candidate->email ?: 'No email' }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                @if ($offer->jobPosition)
                                    <span class="list-icon-text"><i class="ti ti-briefcase"></i>{{ $offer->jobPosition->title }}</span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($offer->salary_offered !== null)
                                    <span class="salary-figure">
                                        <span class="salary-currency">{{ $offer->currency ?: '' }}</span>
                                        {{ $money($offer->salary_offered) }}
                                    </span>
                                @else
                                    <span class="text-muted font-12">Not set</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($offer->offer_date)
                                    <div>{{ $offer->offer_date->format('M j, Y') }}</div>
                                    <div class="list-subtext">{{ $offer->offer_date->diffForHumans() }}</div>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($joining)
                                    <div>{{ $joining->format('M j, Y') }}</div>
                                    @if ($offer->status === 'accepted')
                                        @if ($joining->isToday())
                                            <span class="countdown tone-pink"><i class="ti ti-confetti"></i> Joins today</span>
                                        @elseif ($joining->isFuture())
                                            <span class="countdown tone-teal"><i class="ti ti-clock"></i> in {{ (int) now()->startOfDay()->diffInDays($joining) }} {{ Str::plural('day', (int) now()->startOfDay()->diffInDays($joining)) }}</span>
                                        @else
                                            <span class="countdown tone-amber" title="Joining date has passed - convert to an employee?"><i class="ti ti-alert-circle"></i> Date passed</span>
                                        @endif
                                    @endif
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                <div class="stage-cell">
                                    <span class="tag tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span>
                                    <div class="stage-track stage-track-3 {{ $isClosed ? 'is-rejected' : '' }}">
                                        @foreach ($lifecycle as $index => $phase)
                                            <span class="{{ $isClosed || ($step !== false && $index <= $step) ? 'is-done tone-'.$meta['tone'] : '' }}"></span>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $offer)
                                        @if (! empty($quickActions[$offer->status]))
                                            <div class="dropdown d-inline-block">
                                                <button class="btn-icon btn-icon-info" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Change status">
                                                    <i class="ti ti-arrows-right-left font-16"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><h6 class="dropdown-header">Update status</h6></li>
                                                    @foreach ($quickActions[$offer->status] as $target => $label)
                                                        <li>
                                                            <form method="POST" action="{{ route('admin.offer-letters.status', $offer) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="status" value="{{ $target }}">
                                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2">
                                                                    <span class="kanban-dot tone-{{ $statusMeta[$target]['tone'] ?? 'slate' }}"></span> {{ $label }}
                                                                </button>
                                                            </form>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        <a href="{{ route('admin.offer-letters.edit', $offer) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @can('delete', $offer)
                                        <form action="{{ route('admin.offer-letters.destroy', $offer) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this offer?');">
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
                                <i class="ti ti-file-certificate font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No offers found</div>
                                @if ($activeFilters->isNotEmpty())
                                    <span class="font-13">Try adjusting your filters, or <a href="{{ $clearAllUrl }}">clear them all</a>.</span>
                                @else
                                    <span class="font-13">Create an offer when a candidate reaches the offer stage.</span>
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
            <div class="ms-auto">{{ $offers->links() }}</div>
        </div>
    </div>
@endsection
