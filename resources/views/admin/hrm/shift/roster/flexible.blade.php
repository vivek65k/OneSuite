@extends('layouts.admin')

@section('title', 'Flexible Shift')

@php
    use App\Support\ShiftTime;

    $statusMeta = [
        'within' => ['label' => 'Within window', 'tone' => 'success', 'icon' => 'ti-circle-check'],
        'late' => ['label' => 'Late', 'tone' => 'danger', 'icon' => 'ti-clock'],
        'early' => ['label' => 'Early', 'tone' => 'sky', 'icon' => 'ti-sunrise'],
        'missing' => ['label' => 'No check-in', 'tone' => 'slate', 'icon' => 'ti-alert-circle'],
        'absent' => ['label' => 'Absent', 'tone' => 'rose', 'icon' => 'ti-user-x'],
        'leave' => ['label' => 'On leave', 'tone' => 'violet', 'icon' => 'ti-plane'],
        'awaiting' => ['label' => 'Today', 'tone' => 'amber', 'icon' => 'ti-player-pause'],
        'upcoming' => ['label' => 'Upcoming', 'tone' => 'indigo', 'icon' => 'ti-calendar-event'],
    ];

    $pageUrl = fn (array $overrides = []) => route('admin.shift-roster.flexible', array_filter(
        array_merge($state, ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'status' => $status ?: null], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
    $pct = fn ($minutes) => max(0, min(100, $minutes / 1440 * 100));
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $avatarTone = fn ($e) => $avatarTones[crc32($e->employee_code.$e->name) % count($avatarTones)];
    $initialsOf = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $offsetText = fn ($m) => $m === 0 ? 'right on time' : (abs($m) >= 60 ? $fmt(abs($m) / 60).' h' : abs($m).' min').($m > 0 ? ' after start' : ' before start');

    $ranges = [
        'This week' => [now()->startOfWeek(), now()->endOfWeek()],
        'Last week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
        'This month' => [now()->startOfMonth(), now()->endOfMonth()],
        'Next 7 days' => [today(), today()->addDays(6)],
    ];

    $filterLabels = ['search' => 'Search', 'department_id' => 'Department', 'shift_id' => 'Shift'];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => match ($key) {
            'department_id' => $departments[$state[$key]] ?? $state[$key],
            'shift_id' => $flexShifts->get($state[$key])?->name ?? $state[$key],
            default => $state[$key],
        }]);
    $clearAllUrl = $pageUrl(array_fill_keys(array_keys($filterLabels), null));
    $tabs = collect(['' => ['label' => 'All', 'tone' => 'indigo']])
        ->merge(collect($statusMeta)->only(['within', 'late', 'early', 'missing', 'absent', 'leave', 'upcoming'])->map(fn ($m) => ['label' => $m['label'], 'tone' => $m['tone']]));
@endphp

@section('content')
    <x-page-title title="Flexible Shift" />

    @if ($rangeNote)
        <div class="alert alert-info d-flex align-items-center gap-2"><i class="ti ti-info-circle font-18"></i> {{ $rangeNote }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Flexible shifts" :value="$kpis['shifts']" icon="ti-arrows-right-left" tone="teal" :hint="$kpis['people'].' '.\Illuminate\Support\Str::plural('person', $kpis['people']).' in this range'" />
        <x-kpi-card label="Within window" :value="$kpis['within_rate'] === null ? '—' : $kpis['within_rate'].'%'" icon="ti-target" :tone="$kpis['within_rate'] === null || $kpis['within_rate'] >= 90 ? 'success' : 'amber'" hint="Of days with a check-in" :href="$pageUrl(['status' => 'within'])" />
        <x-kpi-card label="Late arrivals" :value="$kpis['late']" icon="ti-clock" :tone="$kpis['late'] ? 'danger' : 'success'" hint="Checked in after the window" :href="$pageUrl(['status' => 'late'])" />
        <x-kpi-card label="Short hours" :value="$kpis['short']" icon="ti-battery-2" :tone="$kpis['short'] ? 'amber' : 'success'" :hint="$kpis['missing'] ? $kpis['missing'].' days with no check-in' : 'Worked less than the shift'" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.shift-roster.flexible') }}">
            @if ($status !== '')
                <input type="hidden" name="status" value="{{ $status }}">
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
                    <select name="shift_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Flexible shift">
                        <option value="">All flexible shifts</option>
                        @foreach ($flexShifts as $shift)
                            <option value="{{ $shift->id }}" @selected((string) ($state['shift_id'] ?? '') === (string) $shift->id)>{{ $shift->name }} ({{ $shift->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <div class="list-toolbar pt-0">
            <nav class="list-tabs" aria-label="Check-in status">
                @foreach ($tabs as $value => $tab)
                    <a href="{{ $pageUrl(['status' => $value === '' ? null : $value]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $status === (string) $value ? 'active' : '' }}">
                        {{ $tab['label'] }}
                        <span class="list-tab-count">{{ $counts[$value === '' ? 'all' : $value] ?? 0 }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="list-subbar">
            <div class="list-chips">
                @foreach ($ranges as $label => [$from, $to])
                    <a href="{{ $pageUrl(['start' => $from->toDateString(), 'end' => $to->toDateString()]) }}" class="quick-chip {{ $start->isSameDay($from) && $end->isSameDay($to) ? 'is-active' : '' }}">{{ $label }}</a>
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

        @if ($flexShifts->isNotEmpty())
            <div class="flex-shift-strip">
                @foreach ($flexShifts as $shift)
                    @php
                        $window = (int) ($shift->flexible_window_minutes ?? 0);
                        $s = ShiftTime::toMinutes($shift->start_time);
                    @endphp
                    <div class="flex-shift-card tone-teal {{ $shift->is_active ? '' : 'is-inactive' }}">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="roster-option-code">{{ $shift->code }}</span>
                            <span class="fw-semibold text-truncate">{{ $shift->name }}</span>
                            @unless ($shift->is_active)
                                <span class="tag tag-outline tone-slate ms-auto">inactive</span>
                            @endunless
                        </div>
                        <div class="list-subtext mb-2">
                            Arrive {{ ShiftTime::format(sprintf('%02d:%02d', intdiv(($s - $window + 1440) % 1440, 60), ($s - $window + 1440) % 60)) }}
                            – {{ ShiftTime::format(sprintf('%02d:%02d', intdiv(($s + $window) % 1440, 60), ($s + $window) % 60)) }}
                            · {{ $fmt(ShiftTime::netHours($shift)) }} h work
                        </div>
                        <div class="shift-track">
                            @foreach (ShiftTime::segments($shift->start_time, $shift->end_time) as $seg)
                                <span class="shift-track-bar tone-teal is-soft" style="left: {{ $seg['left'] }}%; width: {{ $seg['width'] }}%"></span>
                            @endforeach
                            <span class="flex-window" style="left: {{ $pct($s - $window) }}%; width: {{ $pct($s + $window) - $pct($s - $window) }}%" title="Check-in window ±{{ $window }} min"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($rows->isEmpty())
            <div class="empty-state tone-teal">
                <span class="empty-state-icon"><i class="ti ti-arrows-right-left"></i></span>
                @if ($flexShifts->isEmpty())
                    <h5>No flexible shifts set up</h5>
                    <p>Turn on “Flexible start” for a shift and give it a check-in window to track it here.</p>
                    <a href="{{ route('admin.shifts.index') }}" class="btn btn-de-primary btn-sm text-light"><i class="ti ti-settings"></i> Manage shifts</a>
                @else
                    <h5>{{ $status !== '' ? 'Nothing '.mb_strtolower($statusMeta[$status]['label'] ?? $status).' here' : 'No flexible shifts in this range' }}</h5>
                    <p>{{ $activeFilters->isNotEmpty() ? 'Nothing matches these filters - try clearing them.' : 'Nobody is rostered on a flexible shift between these dates.' }}</p>
                    @if ($activeFilters->isNotEmpty() || $status !== '')
                        <a href="{{ $pageUrl(array_fill_keys(array_keys($filterLabels), null) + ['status' => null]) }}" class="btn btn-light btn-sm">Show everything</a>
                    @endif
                @endif
            </div>
        @else
            <div class="table-responsive table-responsive-sticky">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Shift</th>
                            <th class="flex-track-col">Check-in vs window <span class="text-muted fw-normal">(24 h)</span></th>
                            <th>In / Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $roster = $row['roster'];
                                $employee = $roster->employee;
                                $shift = $roster->shift;
                                $record = $row['record'];
                                $meta = $statusMeta[$row['status']];
                                $s = ShiftTime::toMinutes($shift->start_time);
                            @endphp
                            <tr class="{{ $roster->date->isToday() ? 'is-today-row' : '' }}">
                                <td class="text-nowrap">
                                    <div class="date-range-cell">
                                        <span class="when-block tone-teal"><span class="when-block-month">{{ $roster->date->format('M') }}</span><span class="when-block-day">{{ $roster->date->format('j') }}</span></span>
                                        <span class="fw-semibold">{{ $roster->date->format('D') }}</span>
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
                                    <span class="tag tone-teal"><i class="ti ti-arrows-right-left"></i>{{ $shift->code }} ±{{ $row['window'] }}m</span>
                                    <div class="list-subtext mt-1">{{ ShiftTime::format($shift->start_time) }} – {{ ShiftTime::format($shift->end_time) }}</div>
                                </td>
                                <td class="flex-track-col">
                                    <div class="shift-track">
                                        @foreach (ShiftTime::segments($shift->start_time, $shift->end_time) as $seg)
                                            <span class="shift-track-bar tone-teal is-soft" style="left: {{ $seg['left'] }}%; width: {{ $seg['width'] }}%"></span>
                                        @endforeach
                                        <span class="flex-window" style="left: {{ $pct($s - $row['window']) }}%; width: {{ $pct($s + $row['window']) - $pct($s - $row['window']) }}%"></span>
                                        @if ($row['offset'] !== null)
                                            <span class="flex-marker tone-{{ $meta['tone'] }}" style="left: {{ $pct(($s + $row['offset'] + 1440) % 1440) }}%" title="Checked in {{ \App\Support\Attendance::formatTime($record->check_in_time) }}"></span>
                                        @endif
                                    </div>
                                    <div class="shift-track-scale"><span>12a</span><span>6a</span><span>12p</span><span>6p</span><span>12a</span></div>
                                </td>
                                <td class="text-nowrap">
                                    @if ($record?->check_in_time)
                                        <div><i class="ti ti-login text-muted"></i> {{ \App\Support\Attendance::formatTime($record->check_in_time) }}</div>
                                        <div class="list-subtext"><i class="ti ti-logout"></i> {{ \App\Support\Attendance::formatTime($record->check_out_time) ?? 'no check-out' }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="flex-hours-col">
                                    @if ($row['worked'] !== null)
                                        <div class="week-hours {{ $row['short'] ? 'is-over' : '' }}">
                                            <span class="hours-figure">{{ $fmt($row['worked']) }}<small> / {{ $fmt($row['required']) }} h</small></span>
                                            <span class="week-hours-bar"><span style="width: {{ $row['required'] > 0 ? min(100, $row['worked'] / $row['required'] * 100) : 0 }}%"></span></span>
                                            @if ($row['short'])
                                                <span class="week-hours-flag">{{ $fmt($row['required'] - $row['worked']) }} h short</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="list-subtext">{{ $fmt($row['required']) }} h due</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="tag tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i>{{ $meta['label'] }}{{ $row['status'] === 'leave' && $row['leave'] ? ' ('.$row['leave'].')' : '' }}</span>
                                    @if ($row['offset'] !== null)
                                        <div class="list-subtext mt-1">
                                            @if ($row['status'] === 'late')
                                                {{ $row['offset'] - $row['window'] }} min past the window
                                            @elseif ($row['status'] === 'early')
                                                {{ -$row['offset'] - $row['window'] }} min before the window
                                            @else
                                                {{ $offsetText($row['offset']) }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
