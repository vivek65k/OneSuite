@extends('layouts.admin')

@section('title', 'Team Attendance')

@php
    use App\Support\Attendance;
    use App\Support\ShiftTime;
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.team-attendance.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $monthUrl = fn ($date) => $pageUrl(['month' => $date->format('Y-m') === now()->format('Y-m') ? null : $date->format('Y-m'), 'tab' => null]);
    // How each kind of day looks; the recorded statuses come from the shared attendance palette.
    $kindMeta = fn (string $kind) => match ($kind) {
        'missing' => ['tone' => 'rose', 'icon' => 'ti-alert-circle', 'label' => 'Not marked', 'letter' => '?'],
        'upcoming', 'before' => null,
        default => ['tone' => ['danger' => 'rose'][Attendance::meta($kind)['tone']] ?? Attendance::meta($kind)['tone'], 'icon' => Attendance::meta($kind)['icon'], 'label' => Attendance::label($kind), 'letter' => Attendance::meta($kind)['letter']],
    };
    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
    $time = fn (?string $t) => $t ? date('g:i A', strtotime($t)) : null;
    $dates = collect(range(1, $start->daysInMonth))->map(fn ($day) => $start->copy()->day($day));
    $tabs = [
        '' => ['label' => 'All days', 'tone' => 'indigo', 'count' => $counts['all']],
        'attention' => ['label' => 'Needs attention', 'tone' => 'rose', 'count' => $counts['attention']],
        'worked' => ['label' => 'Worked', 'tone' => 'success', 'count' => $counts['worked']],
        'off' => ['label' => 'Leave & days off', 'tone' => 'violet', 'count' => $counts['off']],
    ];
    $requestTones = ['pending' => 'amber', 'approved' => 'success', 'rejected' => 'rose'];
    $legend = ['present', 'wfh', 'half_day', 'on_leave', 'absent', 'missing', 'holiday', 'weekend'];
    $nowGroups = [
        'In today' => [['present', 'half_day'], 'success'],
        'Working from home' => [['wfh'], 'sky'],
        'On leave' => [['on_leave'], 'violet'],
        'Not in yet' => [['upcoming', 'absent'], 'rose'],
    ];
    $canApprove = \Illuminate\Support\Facades\Route::has('admin.team-approvals.index');
    // A weekly off or holiday for the whole team: nobody is expected in, so the "today" panel would only say "Nobody" four times.
    $dayOff = $now->isNotEmpty() && $now->every(fn ($r) => in_array($r['day']['kind'], ['weekend', 'holiday'], true));
@endphp

@section('content')
    <x-page-title title="Team Attendance" />

    @if ($everyone->isEmpty())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-users font-24 d-block mb-2"></i>
                <div class="fw-semibold text-body mb-1">Nobody reports to you</div>
                <span class="font-13">This page shows the attendance of the people below you in the reporting line. HR sets who reports to whom on each employee's profile.</span>
            </div>
        </div>
    @else
        <div class="kpi-strip">
            @if ($isCurrent)
                <x-kpi-card label="In today" :value="$kpis['in'] + $kpis['wfh']" icon="ti-user-check" tone="success" :hint="collect(['of '.$kpis['people'], $kpis['wfh'] ? $kpis['wfh'].' from home' : null, $kpis['on_leave'] ? $kpis['on_leave'].' on leave' : null, $kpis['not_in'] ? $kpis['not_in'].' not in yet' : null, $dayOff ? 'today is a day off' : null])->filter()->implode(' · ')" />
            @else
                <x-kpi-card label="People" :value="$kpis['people']" icon="ti-users" tone="indigo" :hint="$focus ? 'Showing one person' : 'On this board'" />
            @endif
            <x-kpi-card label="Attendance" :value="$kpis['rate'] !== null ? $kpis['rate'].'%' : '—'" icon="ti-calendar-event" :tone="$kpis['rate'] !== null && $kpis['rate'] < 75 ? 'amber' : 'teal'" :hint="$kpis['working'] ? $num($kpis['worked']).' of '.$kpis['working'].' working days so far' : 'No working days yet'" />
            <x-kpi-card label="Absent or not marked" :value="$kpis['unaccounted']" icon="ti-alert-circle" :tone="$kpis['unaccounted'] ? 'rose' : 'success'" :hint="$kpis['unaccounted'] ? 'Days with no attendance or leave' : 'Every day is accounted for'" />
            <x-kpi-card label="Late arrivals" :value="$kpis['late']" icon="ti-clock" :tone="$kpis['late'] ? 'amber' : 'teal'" :hint="$kpis['late'] ? 'by '.$kpis['late_people'].' '.Str::plural('person', $kpis['late_people']).', after grace' : 'Nobody was late on a shift day'" />
        </div>

        @if ($isCurrent && ! $focus && $now->isNotEmpty() && ! $dayOff)
            <div class="card form-section tone-teal">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-user-check"></i></span>
                    <h5 class="form-section-title">Today, {{ today()->format('D, M j') }}</h5>
                </div>
                <div class="form-section-body">
                    <div class="row g-3">
                        @foreach ($nowGroups as $label => [$kinds, $tone])
                            @php
                                $group = $now->filter(fn ($r) => in_array($r['day']['kind'], $kinds, true) && ($label !== 'Not in yet' || $r['day']['working'] || $r['day']['kind'] === 'absent'));
                            @endphp
                            <div class="col-sm-6 col-xl-3">
                                <div class="font-12 text-muted mb-2">{{ $label }} <span class="count-pill">{{ $group->count() }}</span></div>
                                @forelse ($group as $r)
                                    <a href="{{ $pageUrl(['employee_id' => $r['employee']->id, 'scope' => null]) }}" class="d-flex align-items-center gap-2 py-1 text-body">
                                        <x-avatar :person="$r['employee']" />
                                        <span class="font-13" style="min-width: 0">
                                            <span class="d-block text-truncate">{{ $r['employee']->name }}</span>
                                            <span class="font-12 text-{{ $r['day']['late'] ? 'warning' : 'muted' }}">
                                                {{ $r['day']['in'] ? 'in at '.$time($r['day']['in']).($r['day']['late'] ? ' · '.$r['day']['late'].' min late' : '') : ($r['day']['kind'] === 'on_leave' ? ($r['day']['leave']?->leaveType?->name ?? 'On leave') : ($r['day']['kind'] === 'absent' ? 'marked absent' : ($r['day']['shift'] ? 'shift starts '.ShiftTime::format($r['day']['shift']->start_time) : 'nothing marked'))) }}
                                            </span>
                                        </span>
                                    </a>
                                @empty
                                    <span class="font-13 text-muted">Nobody</span>
                                @endforelse
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="card list-view">
            <div class="list-toolbar">
                <div class="month-nav pb-0">
                    <a href="{{ $monthUrl($start->copy()->subMonth()) }}" class="btn-icon" title="Previous month"><i class="ti ti-chevron-left font-18"></i></a>
                    <div class="month-nav-label"><span class="fw-bold">{{ $start->format('F') }}</span><span class="text-muted">{{ $start->format('Y') }}</span></div>
                    @if ($isCurrent)
                        <span class="btn-icon disabled opacity-50" title="There is no attendance in the future" aria-disabled="true"><i class="ti ti-chevron-right font-18"></i></span>
                    @else
                        <a href="{{ $monthUrl($start->copy()->addMonth()) }}" class="btn-icon" title="Next month"><i class="ti ti-chevron-right font-18"></i></a>
                        <a href="{{ $pageUrl(['month' => null, 'tab' => null]) }}" class="btn btn-light btn-sm ms-1">This month</a>
                    @endif
                </div>
                <form method="GET" action="{{ route('admin.team-attendance.index') }}" class="list-toolbar-actions">
                    <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Team member">
                        <option value="">Whole team ({{ $everyone->count() }})</option>
                        @foreach ($everyone as $person)
                            <option value="{{ $person->id }}" @selected($focus?->id === $person->id)>{{ $person->name }}{{ in_array($person->status, ['terminated', 'inactive'], true) ? ' (left)' : '' }}</option>
                        @endforeach
                    </select>
                    @if ($hasIndirect && ! $focus)
                        <select name="scope" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Who to show">
                            <option value="">Everyone below me</option>
                            <option value="direct" @selected($state['scope'] === 'direct')>Direct reports only</option>
                        </select>
                    @endif
                    <input type="month" name="month" class="form-control form-control-sm" style="width: 160px" value="{{ $month }}" max="{{ now()->format('Y-m') }}" onchange="this.form.submit()" aria-label="Go to month">
                    @if ($board->isNotEmpty())
                        <a href="{{ route('admin.team-attendance.export', array_filter(['month' => $state['month'], 'employee_id' => $state['employee_id'], 'scope' => $state['scope']])) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export</a>
                    @endif
                </form>
            </div>
            @if ($focus || $state['scope'] !== '')
                <div class="list-subbar">
                    <div class="list-chips">
                        @if ($focus)
                            <a href="{{ $pageUrl(['employee_id' => null, 'tab' => null]) }}" class="filter-chip" title="Show the whole team">Person: <strong>{{ $focus->name }}</strong> <i class="ti ti-x"></i></a>
                        @else
                            <a href="{{ $pageUrl(['scope' => null]) }}" class="filter-chip" title="Remove filter">Showing: <strong>Direct reports only</strong> <i class="ti ti-x"></i></a>
                        @endif
                    </div>
                </div>
            @endif

            <div class="list-segment-bar">
                <div class="stack-legend m-0">
                    @foreach ($legend as $kind)
                        <span><span class="heat-cell heat-cell-sm tone-{{ $kindMeta($kind)['tone'] }}">{{ $kindMeta($kind)['letter'] }}</span> {{ $kindMeta($kind)['label'] }}</span>
                    @endforeach
                </div>
            </div>

            <div class="table-responsive heatmap-wrap">
                <table class="table list-table heatmap mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="heatmap-name">Team member</th>
                            @foreach ($dates as $date)
                                <th class="heatmap-day {{ $date->isWeekend() ? 'is-weekend' : '' }} {{ $date->isToday() ? 'is-today' : '' }}" title="{{ $date->format('l, M j') }}"><span>{{ $date->format('D')[0] }}</span>{{ $date->day }}</th>
                            @endforeach
                            <th class="heatmap-total" title="Days worked of working days so far">Worked</th>
                            <th class="heatmap-total" title="Absent or not marked">Gaps</th>
                            <th class="heatmap-total" title="Late arrivals">Late</th>
                            <th class="heatmap-total" title="Hours worked">Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($board as $row)
                            @php
                                $person = $row['employee'];
                                $s = $row['summary'];
                                $gaps = $s['absent'] + $s['missing'];
                            @endphp
                            <tr>
                                <td class="heatmap-name">
                                    <a href="{{ $pageUrl(['employee_id' => $person->id, 'scope' => null, 'tab' => null]) }}" class="list-user text-body" title="See {{ $person->name }}'s days">
                                        <x-avatar :person="$person" />
                                        <div class="list-user-text">
                                            <div class="list-user-name">{{ $person->name }}</div>
                                            <div class="list-user-meta">{{ collect([$person->designation ?: $person->department?->name, $person->is_direct ? null : 'indirect'])->filter()->implode(' · ') ?: $person->employee_code }}</div>
                                        </div>
                                    </a>
                                </td>
                                @foreach ($dates as $date)
                                    @php
                                        $d = $row['days'][$date->toDateString()] ?? null;
                                        $meta = $d ? $kindMeta($d['kind']) : null;
                                        $times = $d ? collect([$time($d['in']), $time($d['out'])])->filter()->implode(' – ') : '';
                                    @endphp
                                    <td class="heatmap-day {{ $d && $d['weekly_off'] ? 'is-weekend' : '' }} {{ $date->isToday() ? 'is-today' : '' }}">
                                        @if ($meta)
                                            <span class="heat-cell tone-{{ $meta['tone'] }}" @if ($d['attention'] && ! in_array($d['kind'], ['missing', 'absent'], true)) style="box-shadow: inset 0 0 0 2px #e5484d" @endif
                                                  title="{{ $person->name }} · {{ $date->format('D, M j') }}: {{ $d['kind'] === 'on_leave' && $d['leave']?->leaveType ? $d['leave']->leaveType->name : ($d['kind'] === 'holiday' && $d['holiday'] ? $d['holiday'] : $meta['label']) }}{{ $times ? ' ('.$times.')' : '' }}{{ $d['attention'] ? ' · '.$d['attention'] : '' }}{{ $d['request'] ? ' · correction '.$d['request']->status : '' }}">{{ $meta['letter'] }}</span>
                                        @elseif ($d && $d['kind'] === 'upcoming' && $d['leave'])
                                            <span class="heat-cell is-future" title="{{ $date->format('D, M j') }}: {{ $d['leave']->leaveType?->name ?? 'Leave' }} {{ $d['leave']->status }}" style="border-style: dashed">L</span>
                                        @else
                                            <span class="heat-cell is-future" @if ($d && $d['kind'] === 'before') title="Before {{ $person->name }} joined" @endif></span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="heatmap-total text-nowrap" title="{{ $num($s['worked']) }} of {{ $s['working'] }} working days{{ $s['wfh'] ? ', '.$s['wfh'].' from home' : '' }}{{ $s['leave'] ? ', '.$s['leave'].' on leave' : '' }}"><span class="heatmap-count {{ $s['worked'] ? 'tone-success' : '' }}">{{ $num($s['worked']) }}</span><span class="font-11 text-muted">/{{ $s['working'] }}</span></td>
                                <td class="heatmap-total" title="{{ $s['absent'] }} absent, {{ $s['missing'] }} not marked"><span class="heatmap-count {{ $gaps ? 'tone-rose' : '' }}">{{ $gaps }}</span></td>
                                <td class="heatmap-total" title="{{ $s['late_minutes'] }} minutes in total, after grace"><span class="heatmap-count {{ $s['late'] ? 'tone-amber' : '' }}">{{ $s['late'] }}</span></td>
                                <td class="heatmap-total text-nowrap">{{ $num($s['hours']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $dates->count() + 5 }}" class="text-center text-muted py-5">
                                    <i class="ti ti-users font-24 d-block mb-2"></i>
                                    <div class="fw-semibold text-body mb-1">Nobody matches</div>
                                    <span class="font-13"><a href="{{ route('admin.team-attendance.index') }}">Show the whole team</a></span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @unless ($focus)
                <div class="list-subbar"><span class="font-12 text-muted"><i class="ti ti-info-circle"></i> Click a name to see that person's days, times and anything that needs attention. A red ring marks a day with a missing punch or a late arrival.</span></div>
            @endunless
        </div>

        @if ($focus)
            <div class="card list-view">
                <div class="list-toolbar">
                    <nav class="list-tabs" aria-label="Day type">
                        @foreach ($tabs as $value => $tab)
                            @if ($value === '' || $tab['count'] || $state['tab'] === $value)
                                <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                                    {{ $tab['label'] }}
                                    <span class="list-tab-count">{{ $tab['count'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </nav>
                    @if ($canApprove)
                        <div class="list-toolbar-actions"><a href="{{ route('admin.team-approvals.index') }}" class="btn btn-light btn-sm"><i class="ti ti-checks font-14 me-1"></i> Team approvals</a></div>
                    @endif
                </div>
                <div class="table-responsive table-responsive-sticky">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Shift</th>
                                <th>In – Out</th>
                                <th>Hours</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $d)
                                @php
                                    $meta = $kindMeta($d['kind']);
                                    $shift = $d['shift'];
                                    $full = (float) ($shift?->full_day_hours ?: 8);
                                    $request = $d['request'];
                                @endphp
                                <tr class="{{ $d['attention'] ? 'is-pending-row' : '' }}">
                                    <td class="text-nowrap">
                                        <span class="fw-semibold">{{ $d['date']->format('M j') }}</span>
                                        <span class="text-muted font-12">{{ $d['date']->isToday() ? 'Today' : $d['date']->format('D') }}</span>
                                    </td>
                                    <td>
                                        @if ($meta)
                                            <span class="tag tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span>
                                        @else
                                            <span class="text-muted font-12">{{ $d['kind'] === 'before' ? 'Before they joined' : ($d['date']->isToday() ? 'Not marked yet' : '—') }}</span>
                                        @endif
                                        @if ($d['holiday'])
                                            <div class="payslip-parts">{{ $d['holiday'] }}</div>
                                        @elseif ($d['kind'] === 'on_leave' && $d['leave'])
                                            <div class="payslip-parts">{{ $d['leave']->leaveType?->name }}{{ $d['leave']->half_day ? ' · half day' : '' }}</div>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($shift)
                                            {{ $shift->name }}
                                            <div class="payslip-parts">{{ ShiftTime::format($shift->start_time) }} – {{ ShiftTime::format($shift->end_time) }}</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($d['in'] || $d['out'])
                                            {{ $time($d['in']) ?? '—' }} – {{ $time($d['out']) ?? '—' }}
                                            @if ($d['overnight'])
                                                <div class="payslip-parts">out the next day</div>
                                            @endif
                                            @if ($d['record']?->source && $d['record']->source !== 'manual')
                                                <div class="payslip-parts"><i class="ti {{ Attendance::SOURCE_META[$d['record']->source]['icon'] ?? 'ti-point' }}"></i> {{ \App\Models\AttendanceRecord::SOURCES[$d['record']->source] ?? $d['record']->source }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="min-width: 120px">
                                        @if ($d['hours'] !== null)
                                            <div class="mini-progress tone-{{ $d['hours'] >= $full ? 'success' : ($d['hours'] >= $full / 2 ? 'amber' : 'rose') }}">
                                                <div class="mini-progress-bar is-toned"><span style="width: {{ min(100, round($d['hours'] / max(1, $full) * 100)) }}%"></span></div>
                                                <span class="mini-progress-label">{{ $num($d['hours']) }} h</span>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($d['attention'])
                                            <span class="font-13 text-danger"><i class="ti ti-alert-circle"></i> {{ $d['attention'] }}</span>
                                        @endif
                                        @if ($request)
                                            <div>
                                                <span class="tag tone-{{ $requestTones[$request->status] ?? 'slate' }}" title="{{ $request->reason }}"><i class="ti ti-edit"></i>Correction {{ $request->status }}</span>
                                                @if ($request->status === 'pending' && $canApprove)
                                                    <a href="{{ route('admin.team-approvals.index') }}" class="font-12 ms-1">Review</a>
                                                @endif
                                            </div>
                                        @endif
                                        @if ($d['record']?->notes)
                                            <div class="payslip-parts" title="{{ $d['record']->notes }}">{{ Str::limit($d['record']->notes, 60) }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="ti ti-clock font-24 d-block mb-2"></i>
                                        <div class="fw-semibold text-body mb-1">{{ $state['tab'] === 'attention' ? 'Nothing needs attention' : 'No days like this in '.$start->format('F') }}</div>
                                        @if ($state['tab'] !== '')
                                            <span class="font-13"><a href="{{ $pageUrl(['tab' => null]) }}">Show all days</a></span>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
@endsection
