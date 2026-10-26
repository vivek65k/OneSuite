@extends('layouts.admin')

@section('title', 'Night Shift')

@php
    use App\Http\Controllers\Admin\Hrm\ShiftRosterController as Roster;
    use App\Support\ShiftTime;

    $pageUrl = fn (array $overrides = []) => route('admin.shift-roster.night', array_filter(
        array_merge($state, ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'view' => $view === 'people' ? 'people' : null], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $avatarTone = fn ($e) => $avatarTones[crc32($e->employee_code.$e->name) % count($avatarTones)];
    $initialsOf = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

    $ranges = [
        'This week' => [now()->startOfWeek(), now()->endOfWeek()],
        'Next 7 days' => [today(), today()->addDays(6)],
        'This month' => [now()->startOfMonth(), now()->endOfMonth()],
        'Last 30 days' => [today()->subDays(29), today()],
    ];

    $filterLabels = ['search' => 'Search', 'department_id' => 'Department', 'shift_id' => 'Shift'];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => match ($key) {
            'department_id' => $departments[$state[$key]] ?? $state[$key],
            'shift_id' => $nightShifts->get($state[$key])?->name ?? $state[$key],
            default => $state[$key],
        }]);
    $clearAllUrl = $pageUrl(array_fill_keys(array_keys($filterLabels), null));
@endphp

@section('content')
    <x-page-title title="Night Shift" />

    @if ($rangeNote)
        <div class="alert alert-info d-flex align-items-center gap-2"><i class="ti ti-info-circle font-18"></i> {{ $rangeNote }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Night shifts" :value="$kpis['nights']" icon="ti-moon-stars" tone="violet" :hint="$kpis['people'].' '.\Illuminate\Support\Str::plural('person', $kpis['people']).' · '.$fmt($kpis['hours']).' h'" />
        <x-kpi-card label="Tonight" :value="$kpis['tonight']" icon="ti-stars" tone="indigo" :hint="today()->format('l, M j')" />
        <x-kpi-card label="Long streaks" :value="$kpis['long_streaks']" icon="ti-flame" :tone="$kpis['long_streaks'] ? 'amber' : 'success'" :hint="Roster::LONG_NIGHT_STREAK.'+ nights in a row'" />
        <x-kpi-card label="Short rest" :value="$kpis['short_rest']" icon="ti-bed" :tone="$kpis['short_rest'] ? 'rose' : 'success'" :hint="'Under '.Roster::MIN_REST_HOURS.' h before the next shift'" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.shift-roster.night') }}">
            @if ($view === 'people')
                <input type="hidden" name="view" value="people">
            @endif
            <div class="list-toolbar">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="list-date-range">
                        <input type="date" name="start" class="form-control form-control-sm" value="{{ $start->toDateString() }}" aria-label="From">
                        <span>to</span>
                        <input type="date" name="end" class="form-control form-control-sm" value="{{ $end->toDateString() }}" aria-label="To">
                    </div>
                    <button type="submit" class="btn btn-de-primary btn-sm text-light">Apply</button>
                </div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search employee" aria-label="Search employee">
                    </div>
                    <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                        <option value="">All departments</option>
                        @foreach ($departments as $id => $name)
                            <option value="{{ $id }}" @selected((string) ($state['department_id'] ?? '') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <select name="shift_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Night shift">
                        <option value="">All night shifts</option>
                        @foreach ($nightShifts as $shift)
                            <option value="{{ $shift->id }}" @selected((string) ($state['shift_id'] ?? '') === (string) $shift->id)>{{ $shift->name }} ({{ $shift->code }})</option>
                        @endforeach
                    </select>
                    <div class="segmented" role="group" aria-label="View">
                        <a href="{{ $pageUrl(['view' => null]) }}" class="segmented-item {{ $view === 'list' ? 'active' : '' }}"><i class="ti ti-list me-1"></i>List</a>
                        <a href="{{ $pageUrl(['view' => 'people']) }}" class="segmented-item {{ $view === 'people' ? 'active' : '' }}"><i class="ti ti-layout-grid me-1"></i>By employee</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="list-subbar">
            <div class="list-chips">
                @foreach ($ranges as $label => [$from, $to])
                    @php $isActive = $start->isSameDay($from) && $end->isSameDay($to); @endphp
                    <a href="{{ $pageUrl(['start' => $from->toDateString(), 'end' => $to->toDateString()]) }}" class="quick-chip {{ $isActive ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
                @foreach ($activeFilters as $filter)
                    <a href="{{ $pageUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">
                        {{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong>
                        <i class="ti ti-x"></i>
                    </a>
                @endforeach
                @if ($activeFilters->isNotEmpty())
                    <a href="{{ $clearAllUrl }}" class="list-clear-all">Clear all</a>
                @endif
            </div>
        </div>

        <div class="list-segment-bar">
            <div class="stack-legend m-0">
                @forelse ($nightShifts as $shift)
                    <span title="{{ $shift->name }}">
                        <span class="legend-dot tone-violet"></span><strong>{{ $shift->code }}</strong> {{ ShiftTime::format($shift->start_time) }}–{{ ShiftTime::format($shift->end_time) }}
                        @unless ($shift->is_night_shift)
                            <span class="tag tag-outline tone-amber" title="Crosses midnight but isn't flagged as a night shift">not flagged</span>
                        @endunless
                        @unless ($shift->is_active)
                            <span class="tag tag-outline tone-slate">inactive</span>
                        @endunless
                    </span>
                @empty
                    <span class="text-muted">No night shifts are set up. A shift counts as night when it is flagged as one or runs past midnight.</span>
                @endforelse
            </div>
            <span class="list-segment-hint"><i class="ti ti-info-circle"></i> {{ $start->format('M j') }} – {{ $end->format('M j, Y') }} · {{ $start->diffInDays($end) + 1 }} days</span>
        </div>

        @if ($rosters->isEmpty())
            <div class="empty-state tone-violet">
                <span class="empty-state-icon"><i class="ti ti-moon"></i></span>
                <h5>No night shifts in this range</h5>
                <p>{{ $activeFilters->isNotEmpty() ? 'Nothing matches these filters - try clearing them.' : 'Nobody is rostered on a night shift between these dates.' }}</p>
                @if ($activeFilters->isNotEmpty())
                    <a href="{{ $clearAllUrl }}" class="btn btn-light btn-sm">Clear filters</a>
                @elseif (auth()->user()->can('update_shift_roster') && $nightShifts->where('is_active', true)->isNotEmpty())
                    <a href="{{ route('admin.shift-roster.assign') }}" class="btn btn-de-primary btn-sm text-light"><i class="ti ti-calendar-plus"></i> Assign a night shift</a>
                @endif
            </div>
        @elseif ($view === 'list')
            <div class="table-responsive table-responsive-sticky">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Night of</th>
                            <th>Employee</th>
                            <th>Shift</th>
                            <th class="night-track-col">Timeline <span class="text-muted fw-normal">(24 h)</span></th>
                            <th class="text-center">Hours</th>
                            <th>Checks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rosters as $roster)
                            @php
                                $employee = $roster->employee;
                                $shift = $roster->shift;
                                $flag = $flags[$roster->id];
                                $crosses = ShiftTime::crossesMidnight($shift->start_time, $shift->end_time);
                            @endphp
                            <tr class="{{ $roster->date->isToday() ? 'is-today-row' : '' }}">
                                <td class="text-nowrap">
                                    <div class="date-range-cell">
                                        <span class="when-block tone-violet"><span class="when-block-month">{{ $roster->date->format('M') }}</span><span class="when-block-day">{{ $roster->date->format('j') }}</span></span>
                                        <div>
                                            <div class="fw-semibold">{{ $roster->date->format('l') }}</div>
                                            @if ($roster->date->isToday())
                                                <span class="countdown tone-indigo"><i class="ti ti-stars"></i> Tonight</span>
                                            @elseif ($crosses)
                                                <div class="list-subtext">ends {{ $roster->date->copy()->addDay()->format('D') }} {{ ShiftTime::format($shift->end_time) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="list-user">
                                        <span class="list-avatar list-avatar-{{ $avatarTone($employee) }}" aria-hidden="true">{{ $initialsOf($employee->name) }}</span>
                                        <div class="list-user-text">
                                            <div class="list-user-name"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></div>
                                            <div class="list-user-meta">{{ $employee->department?->name ?? 'No department' }} · {{ $employee->employee_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="tag tone-violet"><i class="ti ti-moon"></i>{{ $shift->name }}</span>
                                    <div class="list-subtext mt-1">{{ $shift->code }} · {{ ShiftTime::format($shift->start_time) }} – {{ ShiftTime::format($shift->end_time) }}</div>
                                </td>
                                <td class="night-track-col">
                                    <div class="shift-track">
                                        @foreach (ShiftTime::segments($shift->start_time, $shift->end_time) as $seg)
                                            <span class="shift-track-bar tone-violet" style="left: {{ $seg['left'] }}%; width: {{ $seg['width'] }}%"></span>
                                        @endforeach
                                    </div>
                                    <div class="shift-track-scale"><span>12a</span><span>6a</span><span>12p</span><span>6p</span><span>12a</span></div>
                                </td>
                                <td class="text-center"><span class="hours-figure">{{ $fmt(ShiftTime::netHours($shift)) }}<small>h</small></span></td>
                                <td>
                                    <div class="night-checks">
                                        @if ($flag['leave'])
                                            <span class="tag tone-pink" title="Rostered on a day with approved leave"><i class="ti ti-plane"></i> On leave ({{ $flag['leave'] }})</span>
                                        @endif
                                        @if ($flag['short_rest'])
                                            <span class="tag tone-rose" title="Next shift starts {{ $fmt(max(0, $flag['rest'])) }} h after this one ends"><i class="ti ti-bed"></i> {{ $flag['rest'] <= 0 ? 'No rest' : 'Only '.$fmt($flag['rest']).' h rest' }}</span>
                                        @endif
                                        @if ($flag['streak'] >= Roster::LONG_NIGHT_STREAK)
                                            <span class="tag tone-amber"><i class="ti ti-flame"></i> Night {{ $flag['streak'] }} in a row</span>
                                        @elseif ($flag['streak'] > 1)
                                            <span class="tag tag-outline tone-slate">Night {{ $flag['streak'] }} in a row</span>
                                        @endif
                                        @if (! $flag['leave'] && ! $flag['short_rest'] && $flag['streak'] < Roster::LONG_NIGHT_STREAK)
                                            <span class="tag tone-success"><i class="ti ti-circle-check"></i> OK</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            @php
                $days = collect(range(0, $start->diffInDays($end)))->map(fn ($i) => $start->copy()->addDays($i));
                $byPerson = $rosters->groupBy('employee_id')->sortBy(fn ($rows) => $rows->first()->employee->name);
            @endphp
            <div class="px-3 pt-3 pb-1">
                <div class="stack-legend m-0">
                    <span><span class="heat-cell heat-cell-sm tone-violet">N</span> Night shift</span>
                    <span><span class="heat-cell heat-cell-sm tone-danger">N</span> Short rest after</span>
                    <span><span class="heat-cell heat-cell-sm tone-pink is-clash">N</span> Rostered while on leave</span>
                    <span><span class="heat-cell heat-cell-sm tone-pink">L</span> On leave</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table heatmap mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="heatmap-name">Employee</th>
                            @foreach ($days as $day)
                                <th class="heatmap-day {{ $day->isWeekend() ? 'is-weekend' : '' }} {{ $day->isToday() ? 'is-today' : '' }}">{{ $day->format('j') }}<span>{{ $day->format('D')[0] }}</span></th>
                            @endforeach
                            <th class="heatmap-total" title="Nights">Nights</th>
                            <th class="heatmap-total" title="Hours">Hours</th>
                            <th class="heatmap-total" title="Longest run of consecutive nights">Streak</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($byPerson as $employeeId => $rows)
                            @php
                                $employee = $rows->first()->employee;
                                $byDate = $rows->keyBy(fn ($r) => $r->date->toDateString());
                                $streak = $streaks[$employeeId] ?? 0;
                            @endphp
                            <tr>
                                <td class="heatmap-name">
                                    <div class="list-user">
                                        <span class="list-avatar list-avatar-{{ $avatarTone($employee) }}" aria-hidden="true">{{ $initialsOf($employee->name) }}</span>
                                        <div class="list-user-text min-w-0">
                                            <div class="list-user-name text-truncate">{{ $employee->name }}</div>
                                            <div class="list-user-meta text-truncate">{{ $employee->department?->name ?? $employee->employee_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                @foreach ($days as $day)
                                    @php
                                        $key = $day->toDateString();
                                        $roster = $byDate->get($key);
                                        $leaveCode = $onLeave[$employeeId][$key] ?? null;
                                    @endphp
                                    <td class="heatmap-day {{ $day->isWeekend() ? 'is-weekend' : '' }} {{ $day->isToday() ? 'is-today' : '' }}">
                                        @if ($roster)
                                            @php $flag = $flags[$roster->id]; @endphp
                                            <span class="heat-cell tone-{{ $flag['leave'] ? 'pink is-clash' : ($flag['short_rest'] ? 'danger' : 'violet') }}"
                                                  title="{{ $day->format('D, M j') }}: {{ $roster->shift->name }}{{ $flag['short_rest'] ? ' · only '.$fmt(max(0, $flag['rest'])).' h rest after' : '' }}{{ $flag['leave'] ? ' · but on approved leave ('.$flag['leave'].')' : '' }}">N</span>
                                        @elseif ($leaveCode)
                                            <span class="heat-cell tone-pink" title="{{ $day->format('D, M j') }}: on leave ({{ $leaveCode }})">L</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="heatmap-total"><strong>{{ $rows->count() }}</strong></td>
                                <td class="heatmap-total">{{ $fmt($rows->sum(fn ($r) => ShiftTime::netHours($r->shift))) }}</td>
                                <td class="heatmap-total">
                                    <span class="{{ $streak >= Roster::LONG_NIGHT_STREAK ? 'text-warning fw-bold' : '' }}">{{ $streak }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
