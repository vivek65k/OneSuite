@extends('layouts.admin')

@section('title', 'Leave Calendar')

@php
    use App\Support\LeaveTypeStyle;

    $pageUrl = fn (array $overrides = []) => route('admin.leave-requests.calendar', array_filter(
        array_merge($state, ['month' => $month], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $isTimeline = ($state['view'] ?? '') === 'timeline';
    $withPending = (bool) ($state['pending'] ?? false);
    $gridStart = $start->copy()->startOfWeek();
    $gridEnd = $end->copy()->endOfWeek();
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $styleOf = fn ($type) => LeaveTypeStyle::for($type?->name, $type?->code);
    $initialsOf = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $maxChips = 3;
@endphp

@section('content')
    <x-page-title title="Leave Calendar" />

    <div class="kpi-strip">
        <x-kpi-card label="Off today" :value="$kpis['today']" icon="ti-plane" tone="violet" :hint="now()->format('l, M j')" />
        <x-kpi-card label="People on leave" :value="$kpis['people']" icon="ti-users" tone="indigo" :hint="'in '.$start->format('F')" />
        <x-kpi-card label="Leave days" :value="$fmt($kpis['days'])" icon="ti-calendar-stats" tone="teal" hint="Approved, this month" />
        <x-kpi-card label="Busiest day" :value="$kpis['busiest_day'] ? \Illuminate\Support\Carbon::parse($kpis['busiest_day'])->format('M j') : '—'" icon="ti-chart-bar" tone="amber"
                    :hint="$kpis['busiest_count'] ? $kpis['busiest_count'].' '.Str::plural('person', $kpis['busiest_count']).' off' : 'No leave this month'" />
    </div>

    <div class="row g-3">
        <div class="col-xl-9">
            <div class="card list-view mb-0">
                <div class="list-toolbar">
                    <div class="month-nav">
                        <a href="{{ $pageUrl(['month' => $start->copy()->subMonth()->format('Y-m')]) }}" class="btn-icon" title="Previous month"><i class="ti ti-chevron-left font-18"></i></a>
                        <div class="month-nav-label"><span class="fw-bold">{{ $start->format('F') }}</span><span class="text-muted">{{ $start->format('Y') }}</span></div>
                        <a href="{{ $pageUrl(['month' => $start->copy()->addMonth()->format('Y-m')]) }}" class="btn-icon" title="Next month"><i class="ti ti-chevron-right font-18"></i></a>
                        @if ($month !== now()->format('Y-m'))
                            <a href="{{ $pageUrl(['month' => now()->format('Y-m')]) }}" class="btn btn-light btn-sm ms-1">Today</a>
                        @endif
                    </div>
                    <div class="list-toolbar-actions">
                        <div class="segmented" role="group" aria-label="View">
                            <a href="{{ $pageUrl(['view' => null]) }}" class="segmented-item {{ $isTimeline ? '' : 'active' }}"><i class="ti ti-calendar me-1"></i>Month</a>
                            <a href="{{ $pageUrl(['view' => 'timeline']) }}" class="segmented-item {{ $isTimeline ? 'active' : '' }}"><i class="ti ti-layout-grid me-1"></i>Timeline</a>
                        </div>
                        <a href="{{ $pageUrl(['pending' => $withPending ? null : 1]) }}" class="btn btn-sm {{ $withPending ? 'btn-de-primary text-light' : 'btn-light' }}" title="Show pending requests as dashed chips">
                            <i class="ti {{ $withPending ? 'ti-square-check' : 'ti-square' }} font-14 me-1"></i> Include pending{{ $kpis['pending'] ? ' ('.$kpis['pending'].')' : '' }}
                        </a>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.leave-requests.calendar') }}" class="list-segment-bar">
                    @foreach (['month' => $month, 'view' => $state['view'] ?? null, 'pending' => $state['pending'] ?? null] as $name => $value)
                        @if (filled($value))
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <div class="d-flex flex-wrap gap-2 align-items-center">
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
                        <select name="leave_type_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Leave type">
                            <option value="">All leave types</option>
                            @foreach ($leaveTypes as $type)
                                <option value="{{ $type->id }}" @selected((string) ($state['leave_type_id'] ?? '') === (string) $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @if (filled($state['search'] ?? null) || filled($state['department_id'] ?? null) || filled($state['leave_type_id'] ?? null))
                            <a href="{{ $pageUrl(['search' => null, 'department_id' => null, 'leave_type_id' => null]) }}" class="list-clear-all">Clear filters</a>
                        @endif
                    </div>
                </form>

                @if (! $isTimeline)
                    {{-- Month grid --}}
                    <div class="cal-grid" role="grid" aria-label="{{ $start->format('F Y') }}">
                        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dow)
                            <div class="cal-dow {{ in_array($dow, ['Sat', 'Sun'], true) ? 'is-weekend' : '' }}" role="columnheader">{{ $dow }}</div>
                        @endforeach
                        @for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay())
                            @php
                                $key = $day->toDateString();
                                $inMonth = $day->month === $start->month;
                                $entries = collect($byDay[$key] ?? []);
                                $holiday = $holidays[$key] ?? null;
                            @endphp
                            <div class="cal-day {{ $inMonth ? '' : 'is-outside' }} {{ $day->isWeekend() ? 'is-weekend' : '' }} {{ $day->isToday() ? 'is-today' : '' }} {{ $holiday ? 'is-holiday' : '' }}" role="gridcell">
                                <div class="cal-day-head">
                                    <span class="cal-day-num">{{ $day->format('j') }}</span>
                                    @if ($inMonth && $entries->isNotEmpty())
                                        <span class="cal-day-count" title="{{ $entries->count() }} on leave">{{ $entries->count() }}</span>
                                    @endif
                                </div>
                                @if ($holiday && $inMonth)
                                    <div class="cal-holiday" title="{{ $holiday }}"><i class="ti ti-confetti"></i> {{ $holiday }}</div>
                                @endif
                                @if ($inMonth)
                                    @foreach ($entries->take($maxChips) as $entry)
                                        @php $style = $styleOf($entry->leaveType); @endphp
                                        <div class="cal-chip tone-{{ $style['tone'] }} {{ $entry->status === 'pending' ? 'is-pending' : '' }}"
                                             title="{{ $entry->employee?->name }} · {{ $entry->leaveType?->name }} · {{ $entry->start_date->format('M j') }}{{ $entry->start_date->isSameDay($entry->end_date) ? '' : ' – '.$entry->end_date->format('M j') }}{{ $entry->status === 'pending' ? ' (pending)' : '' }}">
                                            <span class="cal-chip-avatar">{{ $initialsOf($entry->employee?->name) }}</span>
                                            <span class="cal-chip-name">{{ \Illuminate\Support\Str::of($entry->employee?->name)->before(' ') }}</span>
                                        </div>
                                    @endforeach
                                    @if ($entries->count() > $maxChips)
                                        <div class="dropdown">
                                            <button type="button" class="cal-more" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">+{{ $entries->count() - $maxChips }} more</button>
                                            <div class="dropdown-menu p-2 cal-more-menu">
                                                <div class="fw-semibold font-12 px-1 mb-1">{{ $day->format('l, M j') }}</div>
                                                @foreach ($entries as $entry)
                                                    @php $style = $styleOf($entry->leaveType); @endphp
                                                    <div class="cal-chip tone-{{ $style['tone'] }} {{ $entry->status === 'pending' ? 'is-pending' : '' }} mb-1">
                                                        <span class="cal-chip-avatar">{{ $initialsOf($entry->employee?->name) }}</span>
                                                        <span class="cal-chip-name">{{ $entry->employee?->name }} · {{ $entry->leaveType?->code }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endfor
                    </div>
                @else
                    {{-- Timeline: one row per person on leave, bars across the days --}}
                    @php
                        $people = $requests->groupBy('employee_id');
                        $days = collect(range(1, $start->daysInMonth));
                    @endphp
                    <div class="table-responsive heatmap-wrap">
                        <table class="table list-table heatmap timeline mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th class="heatmap-name">Employee</th>
                                    @foreach ($days as $d)
                                        @php $date = $start->copy()->day($d); @endphp
                                        <th class="heatmap-day {{ $date->isWeekend() ? 'is-weekend' : '' }} {{ $date->isToday() ? 'is-today' : '' }} {{ isset($holidays[$date->toDateString()]) ? 'is-holiday' : '' }}" title="{{ $date->format('l, M j') }}{{ isset($holidays[$date->toDateString()]) ? ' · '.$holidays[$date->toDateString()] : '' }}">
                                            <span>{{ $date->format('D')[0] }}</span>{{ $d }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($people as $employeeId => $entries)
                                    @php $employee = $entries->first()->employee; @endphp
                                    <tr>
                                        <td class="heatmap-name">
                                            <div class="list-user">
                                                <span class="list-avatar list-avatar-{{ $avatarTones[crc32((string) $employee?->employee_code.$employee?->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($employee?->name) }}</span>
                                                <div class="list-user-text">
                                                    <div class="list-user-name">{{ $employee?->name }}</div>
                                                    <div class="list-user-meta">{{ $employee?->department?->name ?? $employee?->employee_code }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        @foreach ($days as $d)
                                            @php
                                                $date = $start->copy()->day($d);
                                                $entry = $entries->first(fn ($e) => $e->start_date->lte($date) && $e->end_date->gte($date));
                                                $style = $entry ? $styleOf($entry->leaveType) : null;
                                                $isStart = $entry && ($entry->start_date->isSameDay($date) || $d === 1);
                                                $isEnd = $entry && ($entry->end_date->isSameDay($date) || $d === $start->daysInMonth);
                                            @endphp
                                            <td class="heatmap-day {{ $date->isWeekend() ? 'is-weekend' : '' }} {{ $date->isToday() ? 'is-today' : '' }}">
                                                @if ($entry)
                                                    <span class="timeline-bar tone-{{ $style['tone'] }} {{ $entry->status === 'pending' ? 'is-pending' : '' }} {{ $isStart ? 'is-start' : '' }} {{ $isEnd ? 'is-end' : '' }}"
                                                          title="{{ $entry->leaveType?->name }} · {{ $entry->start_date->format('M j') }}–{{ $entry->end_date->format('M j') }}{{ $entry->status === 'pending' ? ' (pending)' : '' }}">{{ $isStart ? $entry->leaveType?->code : '' }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $days->count() + 1 }}" class="text-center text-muted py-5">
                                            <i class="ti ti-calendar font-24 d-block mb-2"></i>
                                            <div class="fw-semibold text-body mb-1">Nobody is on leave in {{ $start->format('F') }}</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-3 d-flex flex-column gap-3">
            <div class="card side-list tone-violet mb-0">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-plane"></i></span>
                    <span class="fw-semibold">Off today</span>
                    <span class="side-list-count">{{ $offToday->count() }}</span>
                </div>
                @forelse ($offToday as $entry)
                    @php $style = $styleOf($entry->leaveType); @endphp
                    <div class="side-list-item">
                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32((string) $entry->employee?->employee_code.$entry->employee?->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($entry->employee?->name) }}</span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold font-13 text-truncate">{{ $entry->employee?->name }}</div>
                            <div class="list-subtext">back {{ $entry->end_date->copy()->addDay()->format('D, M j') }}</div>
                        </div>
                        <span class="tag tone-{{ $style['tone'] }} {{ $entry->status === 'pending' ? 'tag-outline' : '' }}">{{ $entry->leaveType?->code }}</span>
                    </div>
                @empty
                    <div class="side-list-empty"><i class="ti ti-circle-check"></i> Everyone's in today</div>
                @endforelse
            </div>

            <div class="card side-list tone-indigo mb-0">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-palette"></i></span>
                    <span class="fw-semibold">Legend</span>
                </div>
                <div class="p-3 d-flex flex-column gap-2 font-13">
                    @foreach ($leaveTypes->unique(fn ($t) => mb_strtolower($t->name)) as $type)
                        @php $style = $styleOf($type); @endphp
                        <span class="d-flex align-items-center gap-2"><span class="cal-chip tone-{{ $style['tone'] }} cal-chip-swatch"><i class="ti {{ $style['icon'] }}"></i></span> {{ $type->name }}</span>
                    @endforeach
                    <span class="d-flex align-items-center gap-2"><span class="cal-chip is-pending cal-chip-swatch tone-slate"></span> Pending request</span>
                    <span class="d-flex align-items-center gap-2"><span class="cal-holiday cal-chip-swatch m-0"><i class="ti ti-confetti"></i></span> Company holiday</span>
                </div>
            </div>
        </div>
    </div>
@endsection
