@extends('layouts.admin')

@section('title', 'Weekly Schedule')

@php
    use App\Support\ShiftTime;

    $end = $start->copy()->addDays(6);
    $weekUrl = fn (array $overrides = []) => route('admin.shift-roster.weekly', array_filter(
        array_merge($state, ['start' => $start->toDateString(), 'view' => $view === 'day' ? 'day' : null], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $isThisWeek = $start->isSameDay(now()->startOfWeek());
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $avatarTone = fn ($e) => $avatarTones[crc32($e->employee_code.$e->name) % count($avatarTones)];
    $initialsOf = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $styles = $shiftFilter->union($weekShifts->keyBy('id'))->map(fn ($s) => ShiftTime::style($s));
    $weeklyLimit = 48; // hours; above this a row is flagged as overtime

    $filterLabels = ['search' => 'Search', 'department_id' => 'Department', 'shift_id' => 'Shift'];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => match ($key) {
            'department_id' => $departments[$state[$key]] ?? $state[$key],
            'shift_id' => $shiftFilter->get($state[$key])?->name ?? $state[$key],
            default => $state[$key],
        }]);

    // Per-day lookups shared by both views.
    $byDay = collect($dates)->mapWithKeys(fn ($d) => [$d->toDateString() => $employees
        ->mapWithKeys(fn ($e) => [$e->id => ['employee' => $e, 'roster' => $e->shiftRosters->first(fn ($r) => $r->date->isSameDay($d) && $r->shift)]])
        ->filter(fn ($row) => $row['roster'])]);
@endphp

@section('content')
    <x-page-title title="Weekly Schedule" />

    <div class="kpi-strip">
        <x-kpi-card label="Scheduled" :value="$kpis['scheduled'].' / '.$kpis['employees']" icon="ti-users" tone="indigo" hint="Employees with at least one shift" />
        <x-kpi-card label="Scheduled hours" :value="$fmt($kpis['hours']).' h'" icon="ti-clock" tone="teal" :hint="$kpis['scheduled'] ? 'Avg '.$fmt($kpis['hours'] / $kpis['scheduled']).' h per person' : 'Nothing scheduled yet'" />
        <x-kpi-card label="Night shifts" :value="$kpis['night']" icon="ti-moon" tone="violet" hint="Shifts crossing midnight" />
        <x-kpi-card label="Open days" :value="$kpis['open']" icon="ti-calendar-plus" :tone="$kpis['open'] ? 'amber' : 'success'" :hint="$kpis['leave_days'] ? $kpis['leave_days'].' leave days excluded' : 'Days with no shift'"
                    :href="$canEdit ? route('admin.shift-roster.planning', ['start' => $start->toDateString()]) : null" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="month-nav">
                <a href="{{ $weekUrl(['start' => $start->copy()->subWeek()->toDateString()]) }}" class="btn-icon" title="Previous week"><i class="ti ti-chevron-left font-18"></i></a>
                <div class="month-nav-label"><span class="fw-bold">{{ $start->format('M j') }} – {{ $end->format($start->month === $end->month ? 'j' : 'M j') }}</span><span class="text-muted">{{ $end->format('Y') }} · Week {{ $start->isoWeek() }}</span></div>
                <a href="{{ $weekUrl(['start' => $start->copy()->addWeek()->toDateString()]) }}" class="btn-icon" title="Next week"><i class="ti ti-chevron-right font-18"></i></a>
                @unless ($isThisWeek)
                    <a href="{{ $weekUrl(['start' => now()->startOfWeek()->toDateString()]) }}" class="btn btn-light btn-sm ms-1">This week</a>
                @endunless
            </div>
            <div class="list-toolbar-actions">
                <form method="GET" action="{{ route('admin.shift-roster.weekly') }}" class="d-flex gap-2 flex-wrap">
                    <input type="hidden" name="start" value="{{ $start->toDateString() }}">
                    @if ($view === 'day')
                        <input type="hidden" name="view" value="day">
                    @endif
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
                    <select name="shift_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Shift">
                        <option value="">All shifts</option>
                        @foreach ($shiftFilter as $shift)
                            <option value="{{ $shift->id }}" @selected((string) ($state['shift_id'] ?? '') === (string) $shift->id)>{{ $shift->name }} ({{ $shift->code }})</option>
                        @endforeach
                    </select>
                </form>
                <div class="segmented" role="group" aria-label="View">
                    <a href="{{ $weekUrl(['view' => null]) }}" class="segmented-item {{ $view === 'grid' ? 'active' : '' }}"><i class="ti ti-table me-1"></i>Grid</a>
                    <a href="{{ $weekUrl(['view' => 'day']) }}" class="segmented-item {{ $view === 'day' ? 'active' : '' }}"><i class="ti ti-layout-columns me-1"></i>By day</a>
                </div>
                @if ($canEdit)
                    <a href="{{ route('admin.shift-roster.planning', ['start' => $start->toDateString()]) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-pencil font-14 me-1"></i> Edit in planner</a>
                @endif
            </div>
        </div>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $weekUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">
                            {{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong>
                            <i class="ti ti-x"></i>
                        </a>
                    @endforeach
                    <a href="{{ $weekUrl(array_fill_keys(array_keys($filterLabels), null)) }}" class="list-clear-all">Clear all</a>
                </div>
            </div>
        @endif

        <div class="list-segment-bar">
            <div class="stack-legend m-0">
                @forelse ($weekShifts as $shift)
                    <span><span class="legend-dot tone-{{ $styles[$shift->id]['tone'] }}"></span><strong>{{ $shift->code }}</strong> {{ ShiftTime::format($shift->start_time) }}–{{ ShiftTime::format($shift->end_time) }}</span>
                @empty
                    <span class="text-muted">No shifts scheduled this week</span>
                @endforelse
                <span><span class="legend-dot is-unmarked"></span> Off</span>
                <span><span class="legend-dot tone-violet"></span> On leave</span>
            </div>
        </div>

        @if ($employees->isEmpty())
            <div class="empty-state tone-indigo">
                <span class="empty-state-icon"><i class="ti ti-users"></i></span>
                <h5>No employees to show</h5>
                <p>{{ $activeFilters->isNotEmpty() ? 'Nobody matches these filters this week.' : 'Add active employees to build a schedule.' }}</p>
                @if ($activeFilters->isNotEmpty())
                    <a href="{{ $weekUrl(array_fill_keys(array_keys($filterLabels), null)) }}" class="btn btn-light btn-sm">Clear filters</a>
                @endif
            </div>
        @elseif ($view === 'grid')
            <div class="table-responsive roster-wrap">
                <table class="table list-table roster-board week-grid mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="roster-name">Employee</th>
                            @foreach ($dates as $date)
                                @php $holiday = $holidays[$date->toDateString()] ?? null; @endphp
                                <th class="roster-day {{ $date->isToday() ? 'is-today' : '' }} {{ $date->isWeekend() ? 'is-weekend' : '' }}">
                                    <div class="roster-day-head">
                                        <span class="roster-dow">{{ $date->format('D') }}</span>
                                        <span class="roster-date">{{ $date->format('j') }}</span>
                                    </div>
                                    @if ($holiday)
                                        <div class="roster-holiday" title="{{ $holiday }}"><i class="ti ti-confetti"></i> {{ $holiday }}</div>
                                    @endif
                                </th>
                            @endforeach
                            <th class="week-hours-col">Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            @php
                                $rowHours = $employee->shiftRosters->filter->shift->sum(fn ($r) => ShiftTime::netHours($r->shift));
                                $isOver = $rowHours > $weeklyLimit;
                            @endphp
                            <tr>
                                <td class="roster-name">
                                    <div class="list-user">
                                        <span class="list-avatar list-avatar-{{ $avatarTone($employee) }}" aria-hidden="true">{{ $initialsOf($employee->name) }}</span>
                                        <div class="list-user-text min-w-0">
                                            <div class="list-user-name text-truncate"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></div>
                                            <div class="list-user-meta text-truncate">{{ $employee->department?->name ?? 'No department' }} · {{ $employee->employee_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                @foreach ($dates as $date)
                                    @php
                                        $key = $date->toDateString();
                                        $roster = $employee->shiftRosters->first(fn ($r) => $r->date->isSameDay($date) && $r->shift);
                                        $shift = $roster?->shift;
                                        $leaveCode = $onLeave[$employee->id][$key] ?? null;
                                    @endphp
                                    <td class="roster-day {{ $date->isToday() ? 'is-today' : '' }} {{ $date->isWeekend() ? 'is-weekend' : '' }}">
                                        <div class="roster-cell {{ $shift ? 'has-shift tone-'.($styles[$shift->id]['tone'] ?? 'slate') : 'is-off' }} {{ $leaveCode ? 'has-leave' : '' }}"
                                             title="{{ $employee->name }} · {{ $date->format('D, M j') }}{{ $shift ? ' · '.$shift->name : ' · Off' }}{{ $leaveCode ? ' · On leave ('.$leaveCode.')' : '' }}">
                                            @if ($shift)
                                                <span class="roster-cell-code">{{ $shift->code }}@if ($styles[$shift->id]['tone'] === 'violet') <i class="ti ti-moon"></i>@endif</span>
                                                <span class="roster-cell-time">{{ ShiftTime::format($shift->start_time) }}–{{ ShiftTime::format($shift->end_time) }}</span>
                                            @elseif (! $leaveCode)
                                                <span class="roster-cell-code">Off</span>
                                            @endif
                                            @if ($leaveCode)
                                                <span class="roster-cell-leave"><i class="ti ti-plane"></i> {{ $leaveCode }}</span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                                <td class="week-hours-col">
                                    <div class="week-hours {{ $isOver ? 'is-over' : '' }}" title="{{ $fmt($rowHours) }} of {{ $weeklyLimit }} h">
                                        <span class="hours-figure">{{ $fmt($rowHours) }}<small>h</small></span>
                                        <span class="week-hours-bar"><span style="width: {{ min(100, $rowHours / $weeklyLimit * 100) }}%"></span></span>
                                        @if ($isOver)
                                            <span class="week-hours-flag">+{{ $fmt($rowHours - $weeklyLimit) }}h OT</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="roster-coverage">
                            <td class="roster-name"><span class="fw-semibold font-12">On shift</span></td>
                            @foreach ($dates as $date)
                                @php
                                    $rows = $byDay[$date->toDateString()];
                                    $dayHours = $rows->sum(fn ($row) => ShiftTime::netHours($row['roster']->shift));
                                @endphp
                                <td class="roster-day {{ $date->isToday() ? 'is-today' : '' }} {{ $date->isWeekend() ? 'is-weekend' : '' }}">
                                    <div class="week-foot"><strong>{{ $rows->count() }}</strong> <span>/ {{ $employees->count() }}</span></div>
                                    <div class="list-subtext">{{ $fmt($dayHours) }} h</div>
                                </td>
                            @endforeach
                            <td class="week-hours-col"><span class="hours-figure">{{ $fmt($kpis['hours']) }}<small>h</small></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="week-days">
                @foreach ($dates as $date)
                    @php
                        $key = $date->toDateString();
                        $groups = $byDay[$key]->groupBy(fn ($row) => $row['roster']->shift_id)
                            ->sortBy(fn ($rows) => $rows->first()['roster']->shift->start_time);
                        $leaving = $employees->filter(fn ($e) => isset($onLeave[$e->id][$key]));
                        $off = $employees->count() - $byDay[$key]->count() - $leaving->reject(fn ($e) => $byDay[$key]->has($e->getKey()))->count();
                        $holiday = $holidays[$key] ?? null;
                    @endphp
                    <div class="week-day {{ $date->isToday() ? 'is-today' : '' }} {{ $date->isWeekend() ? 'is-weekend' : '' }}">
                        <div class="week-day-head">
                            <span class="week-day-dow">{{ $date->format('l') }}</span>
                            <span class="week-day-date">{{ $date->format('M j') }}</span>
                            @if ($date->isToday())
                                <span class="tag tone-indigo ms-auto">Today</span>
                            @endif
                        </div>
                        @if ($holiday)
                            <div class="roster-holiday mb-2" title="{{ $holiday }}"><i class="ti ti-confetti"></i> {{ $holiday }}</div>
                        @endif

                        @forelse ($groups as $rows)
                            @php $shift = $rows->first()['roster']->shift; $tone = $styles[$shift->id]['tone'] ?? 'slate'; @endphp
                            <div class="week-shift tone-{{ $tone }}">
                                <div class="week-shift-head">
                                    <span class="roster-option-code">{{ $shift->code }}</span>
                                    <span class="week-shift-time">{{ ShiftTime::format($shift->start_time) }}–{{ ShiftTime::format($shift->end_time) }}</span>
                                    <span class="week-shift-count" title="{{ $rows->count() }} on this shift"><i class="ti ti-users"></i> {{ $rows->count() }}</span>
                                </div>
                                <ul class="week-people">
                                    @foreach ($rows as $row)
                                        <li>
                                            <span class="list-avatar list-avatar-{{ $avatarTone($row['employee']) }}" aria-hidden="true">{{ $initialsOf($row['employee']->name) }}</span>
                                            <span class="text-truncate">{{ $row['employee']->name }}</span>
                                            @if (isset($onLeave[$row['employee']->id][$key]))
                                                <span class="roster-cell-leave ms-auto" title="Scheduled but on approved leave"><i class="ti ti-plane"></i> {{ $onLeave[$row['employee']->id][$key] }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @empty
                            <div class="week-day-empty"><i class="ti ti-calendar-off"></i> No one scheduled</div>
                        @endforelse

                        <div class="week-day-foot">
                            @if ($leaving->isNotEmpty())
                                <span class="tag tone-violet" title="{{ $leaving->pluck('name')->implode(', ') }}"><i class="ti ti-plane"></i> {{ $leaving->count() }} on leave</span>
                            @endif
                            @if ($off > 0)
                                <span class="tag tag-outline tone-slate">{{ $off }} off</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
