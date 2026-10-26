@extends('layouts.admin')

@section('title', 'My Attendance')

@php
    use App\Support\Attendance;
    use App\Support\ShiftTime;
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.my-attendance.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $monthUrl = fn ($date) => $pageUrl(['month' => $date->format('Y-m') === now()->format('Y-m') ? null : $date->format('Y-m'), 'tab' => null]);
    $tabs = [
        '' => ['label' => 'All days', 'tone' => 'indigo', 'count' => $counts['all']],
        'attention' => ['label' => 'Needs attention', 'tone' => 'rose', 'count' => $counts['attention']],
        'worked' => ['label' => 'Worked', 'tone' => 'success', 'count' => $counts['worked']],
        'off' => ['label' => 'Leave & days off', 'tone' => 'violet', 'count' => $counts['off']],
    ];
    // How each kind of day looks; the recorded statuses come from the shared attendance palette.
    $kindMeta = fn (string $kind) => match ($kind) {
        'missing' => ['tone' => 'rose', 'icon' => 'ti-alert-circle', 'label' => 'Not marked'],
        'upcoming', 'before' => null,
        default => ['tone' => ['danger' => 'rose'][Attendance::meta($kind)['tone']] ?? Attendance::meta($kind)['tone'], 'icon' => Attendance::meta($kind)['icon'], 'label' => Attendance::label($kind)],
    };
    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
    $time = fn (?string $t) => $t ? date('g:i A', strtotime($t)) : null;
    $requestTones = ['pending' => 'amber', 'approved' => 'success', 'rejected' => 'rose'];
    $canRequest = \Illuminate\Support\Facades\Route::has('admin.my-requests.index');
    $gridStart = $start->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
    $gridEnd = $start->copy()->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);
    $byDate = $days->keyBy(fn ($d) => $d['date']->toDateString());
    $attendanceRate = $kpis['working'] ? (int) round($kpis['worked'] / $kpis['working'] * 100) : null;
@endphp

@section('content')
    <x-page-title title="My Attendance" />

    <div class="kpi-strip">
        <x-kpi-card label="Days worked" :value="$num($kpis['worked']).($kpis['worked'] == 1 ? ' day' : ' days')" icon="ti-user-check" :tone="$attendanceRate !== null && $attendanceRate < 75 ? 'amber' : 'success'" :hint="$kpis['working'] ? 'of '.$kpis['working'].' working '.Str::plural('day', $kpis['working']).' so far'.($kpis['wfh'] ? ' · '.$kpis['wfh'].' from home' : '').($kpis['half'] ? ' · '.$kpis['half'].' half' : '') : 'No working days yet this month'" />
        <x-kpi-card label="Absent or not marked" :value="$kpis['absent'] + $kpis['missing']" icon="ti-alert-circle" :tone="$kpis['absent'] + $kpis['missing'] ? 'rose' : 'teal'" :hint="$kpis['absent'] + $kpis['missing'] ? collect([$kpis['absent'] ? $kpis['absent'].' absent' : null, $kpis['missing'] ? $kpis['missing'].' with nothing marked' : null])->filter()->implode(' · ') : 'Every working day is accounted for'" />
        <x-kpi-card label="Late arrivals" :value="$kpis['late']" icon="ti-clock" :tone="$kpis['late'] ? 'amber' : 'teal'" :hint="$kpis['late'] ? $kpis['late_minutes'].' minutes in total, after grace' : 'On time every day with a shift'" />
        <x-kpi-card label="Hours worked" :value="$num($kpis['hours']).' h'" icon="ti-history" tone="indigo" :hint="$kpis['average'] !== null ? 'about '.$num($kpis['average']).' h a day'.($kpis['leave'] ? ' · '.$kpis['leave'].' on leave' : '') : ($kpis['leave'] ? $kpis['leave'].' '.Str::plural('day', $kpis['leave']).' on leave' : 'No check-in and check-out times yet')" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="month-nav pb-0">
                <a href="{{ $monthUrl($start->copy()->subMonth()) }}" class="btn-icon" title="Previous month"><i class="ti ti-chevron-left font-18"></i></a>
                <div class="month-nav-label"><span class="fw-bold">{{ $start->format('F') }}</span><span class="text-muted">{{ $start->format('Y') }}</span></div>
                @if ($isCurrent)
                    <span class="btn-icon disabled opacity-50" title="There is no attendance in the future" aria-disabled="true"><i class="ti ti-chevron-right font-18"></i></span>
                @else
                    <a href="{{ $monthUrl($start->copy()->addMonth()) }}" class="btn-icon" title="Next month"><i class="ti ti-chevron-right font-18"></i></a>
                    <a href="{{ route('admin.my-attendance.index') }}" class="btn btn-light btn-sm ms-1">This month</a>
                @endif
            </div>
            <div class="list-toolbar-actions">
                <form method="GET" action="{{ route('admin.my-attendance.index') }}" class="d-flex align-items-center gap-2">
                    <input type="month" name="month" class="form-control form-control-sm" style="width: 160px" value="{{ $month }}" max="{{ now()->format('Y-m') }}" onchange="this.form.submit()" aria-label="Go to month">
                </form>
                @if ($canRequest)
                    <a href="{{ route('admin.my-requests.index') }}" class="btn btn-sm btn-de-primary text-light"><i class="ti ti-edit font-14 me-1"></i> Request a correction</a>
                @endif
            </div>
        </div>

        <div class="cal-grid" role="grid" aria-label="{{ $start->format('F Y') }}">
            @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dow)
                <div class="cal-dow {{ in_array($dow, ['Sat', 'Sun'], true) ? 'is-weekend' : '' }}" role="columnheader">{{ $dow }}</div>
            @endforeach
            @for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay())
                @php
                    $d = $byDate[$day->toDateString()] ?? null;
                    $meta = $d ? $kindMeta($d['kind']) : null;
                    $times = $d ? collect([$time($d['in']), $time($d['out'])])->filter()->implode(' – ') : '';
                @endphp
                <div class="cal-day {{ $d ? '' : 'is-outside' }} {{ ($d ? $d['weekly_off'] : $day->isWeekend()) ? 'is-weekend' : '' }} {{ $day->isToday() ? 'is-today' : '' }} {{ $d && $d['holiday'] ? 'is-holiday' : '' }}" style="min-height: 92px" role="gridcell">
                    <div class="cal-day-head">
                        <span class="cal-day-num">{{ $day->format('j') }}</span>
                        @if ($d && $d['attention'])
                            <span class="cal-day-count" style="background-color: rgba(229, 72, 77, 0.14); color: #d13438" title="{{ $d['attention'] }}">!</span>
                        @endif
                    </div>
                    @if ($d)
                        @if ($d['holiday'])
                            <div class="cal-holiday" title="{{ $d['holiday'] }}"><i class="ti ti-confetti"></i> {{ $d['holiday'] }}</div>
                        @endif
                        @if ($meta && ! ($d['kind'] === 'holiday' && $d['holiday']) && $d['kind'] !== 'weekend')
                            <a href="#day-{{ $day->toDateString() }}" class="cal-event tone-{{ $meta['tone'] }}" title="{{ $meta['label'] }}{{ $times ? ' · '.$times : '' }}{{ $d['kind'] === 'on_leave' && $d['leave'] ? ' · '.$d['leave']->leaveType?->name : '' }}">
                                {{ $d['kind'] === 'on_leave' && $d['leave']?->leaveType ? $d['leave']->leaveType->name : $meta['label'] }}
                            </a>
                            @if ($times)
                                <span class="font-11 text-muted text-truncate">{{ $times }}</span>
                            @endif
                        @elseif ($d['kind'] === 'upcoming' && $d['leave'])
                            <span class="cal-event tone-{{ $d['leave']->status === 'approved' ? 'violet' : 'amber' }}" title="{{ $d['leave']->leaveType?->name }} · {{ $d['leave']->status }}">{{ $d['leave']->status === 'approved' ? 'Leave' : 'Leave requested' }}</span>
                        @elseif ($d['kind'] === 'upcoming' && $d['shift'])
                            <span class="font-11 text-muted text-truncate" title="{{ $d['shift']->name }}">{{ $d['shift']->code ?: $d['shift']->name }} · {{ ShiftTime::format($d['shift']->start_time) }}</span>
                        @endif
                    @endif
                </div>
            @endfor
        </div>
        <div class="list-subbar">
            <div class="d-flex flex-wrap gap-3 font-12 text-muted">
                @foreach (['present', 'wfh', 'half_day', 'on_leave', 'absent', 'missing'] as $kind)
                    <span class="d-flex align-items-center gap-1"><span class="tag tone-{{ $kindMeta($kind)['tone'] }}" style="padding: 0 6px">&nbsp;</span> {{ $kindMeta($kind)['label'] }}</span>
                @endforeach
                <span class="d-flex align-items-center gap-1"><span class="cal-holiday m-0"><i class="ti ti-confetti"></i></span> Holiday</span>
            </div>
        </div>
    </div>

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
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
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
                        <tr id="day-{{ $d['date']->toDateString() }}" class="{{ $d['attention'] ? 'is-pending-row' : '' }}">
                            <td class="text-nowrap">
                                <span class="fw-semibold">{{ $d['date']->format('M j') }}</span>
                                <span class="text-muted font-12">{{ $d['date']->isToday() ? 'Today' : $d['date']->format('D') }}</span>
                            </td>
                            <td>
                                @if ($meta)
                                    <span class="tag tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span>
                                @else
                                    <span class="text-muted font-12">{{ $d['kind'] === 'before' ? 'Before you joined' : ($d['date']->isToday() ? 'Not marked yet' : '—') }}</span>
                                @endif
                                @if ($d['holiday'] && $d['kind'] !== 'holiday')
                                    <div class="payslip-parts">{{ $d['holiday'] }}</div>
                                @elseif ($d['kind'] === 'holiday')
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
                                    <div><span class="tag tone-{{ $requestTones[$request->status] ?? 'slate' }}" title="{{ $request->remarks ?: $request->reason }}"><i class="ti ti-edit"></i>Correction {{ $request->status }}</span></div>
                                @endif
                                @if ($d['record']?->notes)
                                    <div class="payslip-parts" title="{{ $d['record']->notes }}">{{ Str::limit($d['record']->notes, 60) }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($canRequest && $d['attention'] && ! Str::contains($d['attention'], 'late'))
                                    <a href="{{ route('admin.my-requests.index', ['date' => $d['date']->toDateString()]) }}" class="btn btn-sm review-btn" title="Ask for {{ $d['date']->format('M j') }} to be corrected"><i class="ti ti-edit"></i> Regularize</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-clock font-24 d-block mb-2"></i>
                                @if ($state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">{{ $state['tab'] === 'attention' ? 'Nothing needs your attention' : 'No days like this in '.$start->format('F') }}</div>
                                    <span class="font-13"><a href="{{ $pageUrl(['tab' => null]) }}">Show all days</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">Nothing to show for {{ $start->format('F Y') }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
