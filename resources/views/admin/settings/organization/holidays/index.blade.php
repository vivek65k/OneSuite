@extends('layouts.admin')

@section('title', 'Holiday Calendar')

@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $year = $state['year'];
    $pageUrl = fn (array $overrides = []) => route('admin.holidays.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== '' && $value !== 'list'
    ));
    $tabs = [
        '' => ['label' => 'All holidays', 'tone' => 'indigo', 'count' => $counts['all']],
        'yearly' => ['label' => 'Every year', 'tone' => 'pink', 'count' => $counts['yearly']],
        'once' => ['label' => 'One-time', 'tone' => 'amber', 'count' => $counts['once']],
    ];
    $canCreate = auth()->user()->can('create', \App\Models\Holiday::class);
    $next = $kpis['next'];
    $byDate = $occurrences->groupBy(fn ($o) => $o['date']->toDateString());
    $byMonth = $occurrences->groupBy(fn ($o) => $o['date']->month);
    $monthTones = ['indigo', 'sky', 'teal', 'lime', 'success', 'amber', 'pink', 'rose', 'violet', 'indigo', 'sky', 'teal'];
@endphp

@section('content')
    <x-page-title title="Holiday Calendar" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Holidays in {{ $year }}" :value="$kpis['total']" icon="ti-calendar-event" tone="pink" :hint="$kpis['weekdays'].' on weekdays'" />
        <x-kpi-card label="Next holiday" :value="$next ? $next['holiday']->name : '—'" icon="ti-gift" tone="indigo"
                    :hint="$next ? $next['date']->format('D, M j').($next['date']->isToday() ? ' · today' : ' · in '.(int) today()->diffInDays($next['date']).' '.Str::plural('day', (int) today()->diffInDays($next['date']))) : 'Nothing scheduled'" />
        <x-kpi-card label="Still to come" :value="$kpis['remaining']" icon="ti-calendar-time" tone="teal" :hint="$year === now()->year ? 'Left in '.$year : ($year < now()->year ? 'Year has passed' : 'All ahead')" />
        <x-kpi-card label="Long weekends" :value="$kpis['long_weekends']" icon="ti-sun" tone="amber" hint="Holidays on a Monday or Friday" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="month-nav">
                <a href="{{ $pageUrl(['year' => $year - 1]) }}" class="btn-icon" title="{{ $year - 1 }}"><i class="ti ti-chevron-left font-18"></i></a>
                <div class="month-nav-label"><span class="fw-bold">{{ $year }}</span><span class="text-muted">{{ $counts['all'] }} {{ Str::plural('holiday', $counts['all']) }}</span></div>
                <a href="{{ $pageUrl(['year' => $year + 1]) }}" class="btn-icon" title="{{ $year + 1 }}"><i class="ti ti-chevron-right font-18"></i></a>
                @if ($year !== now()->year)
                    <a href="{{ $pageUrl(['year' => now()->year]) }}" class="btn btn-light btn-sm ms-1">This year</a>
                @endif
            </div>
            <div class="list-toolbar-actions">
                <form method="GET" action="{{ route('admin.holidays.index') }}" class="list-search">
                    <i class="ti ti-search"></i>
                    @foreach (['year' => $year, 'type' => $state['type'], 'view' => $state['view'] === 'calendar' ? 'calendar' : ''] as $name => $value)
                        @if ($value !== '')
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search holiday" aria-label="Search holidays">
                </form>
                <div class="segmented" role="group" aria-label="View">
                    <a href="{{ $pageUrl(['view' => null]) }}" class="segmented-item {{ $state['view'] === 'list' ? 'active' : '' }}"><i class="ti ti-list me-1"></i>List</a>
                    <a href="{{ $pageUrl(['view' => 'calendar']) }}" class="segmented-item {{ $state['view'] === 'calendar' ? 'active' : '' }}"><i class="ti ti-calendar me-1"></i>Year</a>
                </div>
                <a href="{{ route('admin.holidays.export', ['year' => $year]) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export {{ $year }}</a>
                @if ($canCreate)
                    <a href="{{ route('admin.holidays.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add holiday</a>
                @endif
            </div>
        </div>

        <div class="list-toolbar pt-0">
            <nav class="list-tabs" aria-label="Holiday type">
                @foreach ($tabs as $value => $tab)
                    <a href="{{ $pageUrl(['type' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['type'] === $value ? 'active' : '' }}">
                        {{ $tab['label'] }}
                        <span class="list-tab-count">{{ $tab['count'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        @if ($state['search'] !== '')
            <div class="list-subbar">
                <div class="list-chips">
                    <a href="{{ $pageUrl(['search' => null]) }}" class="filter-chip" title="Remove filter">Search: <strong>{{ $state['search'] }}</strong> <i class="ti ti-x"></i></a>
                </div>
            </div>
        @endif

        @if ($state['view'] === 'calendar')
            <div class="year-grid">
                @foreach (range(1, 12) as $month)
                    @php
                        $first = Carbon::create($year, $month, 1);
                        $lead = $first->dayOfWeekIso - 1;
                    @endphp
                    <div class="year-month tone-{{ $monthTones[$month - 1] }}">
                        <div class="year-month-head">
                            <span>{{ $first->format('F') }}</span>
                            @if ($byMonth->has($month))
                                <span class="tag tone-pink">{{ $byMonth[$month]->count() }}</span>
                            @endif
                        </div>
                        <div class="year-month-grid">
                            @foreach (['M', 'T', 'W', 'T', 'F', 'S', 'S'] as $dow)
                                <span class="year-dow">{{ $dow }}</span>
                            @endforeach
                            @for ($i = 0; $i < $lead; $i++)
                                <span></span>
                            @endfor
                            @for ($d = 1; $d <= $first->daysInMonth; $d++)
                                @php
                                    $day = $first->copy()->day($d);
                                    $hits = $byDate->get($day->toDateString());
                                    $classes = collect(['year-day', $day->isWeekend() ? 'is-weekend' : null, $day->isToday() ? 'is-today' : null, $hits ? 'is-holiday' : null])->filter()->implode(' ');
                                @endphp
                                @if ($hits)
                                    <span class="{{ $classes }}" title="{{ $day->format('D, M j') }}: {{ $hits->map(fn ($o) => $o['holiday']->name)->implode(', ') }}">{{ $d }}</span>
                                @elseif ($canCreate && ! $day->isPast())
                                    <a href="{{ route('admin.holidays.create', ['date' => $day->toDateString()]) }}" class="{{ $classes }}" title="Add a holiday on {{ $day->format('M j') }}">{{ $d }}</a>
                                @else
                                    <span class="{{ $classes }}">{{ $d }}</span>
                                @endif
                            @endfor
                        </div>
                        @if ($byMonth->has($month))
                            <ul class="year-month-list">
                                @foreach ($byMonth[$month] as $o)
                                    <li><strong>{{ $o['date']->format('j') }}</strong> {{ $o['holiday']->name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        @elseif ($occurrences->isEmpty())
            <div class="empty-state tone-pink">
                <span class="empty-state-icon"><i class="ti ti-calendar-event"></i></span>
                <h5>{{ $counts['all'] ? 'No holidays match' : 'No holidays in '.$year }}</h5>
                <p>{{ $counts['all'] ? 'Try another tab or clear the search.' : 'Add public and company holidays so rosters, leave and attendance skip them.' }}</p>
                @if ($canCreate && ! $counts['all'])
                    <a href="{{ route('admin.holidays.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add holiday</a>
                @endif
            </div>
        @else
            <div class="table-responsive table-responsive-sticky">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Holiday</th>
                            <th>Repeats</th>
                            <th>When</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($occurrences as $o)
                            @php
                                $holiday = $o['holiday'];
                                $date = $o['date'];
                                $isPast = $date->lt(today());
                                $days = (int) today()->diffInDays($date);
                            @endphp
                            <tr class="{{ $isPast ? 'is-past-row' : '' }} {{ $date->isToday() ? 'is-today-row' : '' }}">
                                <td class="text-nowrap">
                                    <div class="date-range-cell">
                                        <span class="when-block tone-{{ $monthTones[$date->month - 1] }}"><span class="when-block-month">{{ $date->format('M') }}</span><span class="when-block-day">{{ $date->format('j') }}</span></span>
                                        <div>
                                            <div class="fw-semibold">{{ $date->format('l') }}</div>
                                            @if ($date->isWeekend())
                                                <span class="list-subtext">Falls on a weekend</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        @can('update', $holiday)
                                            <a href="{{ route('admin.holidays.edit', $holiday) }}">{{ $holiday->name }}</a>
                                        @else
                                            {{ $holiday->name }}
                                        @endcan
                                    </div>
                                    @if ($o['long_weekend'])
                                        <span class="tag tone-amber mt-1"><i class="ti ti-sun"></i>Long weekend</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($holiday->is_recurring)
                                        <span class="tag tone-pink"><i class="ti ti-repeat"></i>Every year</span>
                                        @if ($holiday->date->year < $year)
                                            <div class="list-subtext mt-1">since {{ $holiday->date->year }}</div>
                                        @endif
                                    @else
                                        <span class="tag tag-outline tone-slate">One-time</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if ($date->isToday())
                                        <span class="countdown tone-success"><i class="ti ti-confetti"></i> Today</span>
                                    @elseif ($isPast)
                                        <span class="list-subtext">{{ $date->diffForHumans() }}</span>
                                    @else
                                        <span class="countdown tone-{{ $days <= 14 ? 'indigo' : 'slate' }}">in {{ $days }} {{ Str::plural('day', $days) }}</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="list-row-actions">
                                        @can('update', $holiday)
                                            <a href="{{ route('admin.holidays.edit', $holiday) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                        @endcan
                                        @can('delete', $holiday)
                                            <form action="{{ route('admin.holidays.destroy', $holiday) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm({{ json_encode($holiday->is_recurring ? 'Delete “'.$holiday->name.'”? It repeats every year, so it will be removed from every year.' : 'Delete “'.$holiday->name.'”?') }});">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
