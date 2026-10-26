@extends('layouts.admin')

@section('title', 'Attendance Dashboard')

@php
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $initialsOf = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $breakdown = collect(\App\Support\Attendance::STATUS_META)
        ->map(fn ($meta, $status) => $meta + ['status' => $status, 'count' => $todayCounts->get($status, 0)])
        ->filter(fn ($row) => $row['count'] > 0 || in_array($row['status'], ['present', 'absent', 'on_leave', 'wfh'], true));
@endphp

@section('content')
    <x-page-title title="Attendance Dashboard" :breadcrumbs="['Attendance' => route('admin.attendance.dashboard')]" />

    <div class="kpi-strip">
        <x-kpi-card label="Present today" :value="$todayCounts->get('present', 0)" icon="ti-user-check" tone="success"
                    :hint="$headcount ? 'of '.$headcount.' employees' : null" :href="route('admin.attendance.daily', ['status' => 'present'])" />
        <x-kpi-card label="Absent today" :value="$todayCounts->get('absent', 0)" icon="ti-user-x" tone="danger" :href="route('admin.attendance.daily', ['status' => 'absent'])" />
        <x-kpi-card label="On leave · WFH" :value="$todayCounts->get('on_leave', 0).' · '.$todayCounts->get('wfh', 0)" icon="ti-calendar-off" tone="violet" hint="Leave · work from home" />
        <x-kpi-card label="Not marked yet" :value="$notMarked" icon="ti-clock" tone="{{ $notMarked ? 'amber' : 'success' }}"
                    :hint="$notMarked ? 'Mark on the daily sheet' : 'Everyone is marked'" :href="route('admin.attendance.daily', ['status' => 'unmarked'])" />
    </div>

    <div class="row g-3 mb-3">
        {{-- Today at a glance --}}
        <div class="col-xl-5">
            <div class="card h-100 side-list tone-success">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-calendar-event"></i></span>
                    <span class="fw-semibold">Today · {{ now()->format('l, M j') }}</span>
                </div>
                <div class="card-body">
                    <div class="today-glance">
                        <div class="progress-ring progress-ring-lg tone-success" style="--value: {{ $attendanceRate ?? 0 }}" role="img" aria-label="Attendance rate {{ $attendanceRate ?? 0 }}%">
                            <span>{{ $attendanceRate !== null ? $attendanceRate : '—' }}<small>{{ $attendanceRate !== null ? '%' : '' }}</small></span>
                        </div>
                        <div class="today-glance-text">
                            <div class="fw-semibold">Attendance rate</div>
                            <div class="list-subtext">Present, half day or WFH, out of {{ $marked }} marked</div>
                            <a href="{{ route('admin.attendance.daily') }}" class="btn btn-de-primary btn-sm text-light mt-2"><i class="ti ti-edit font-14 me-1"></i> Open daily sheet</a>
                        </div>
                    </div>

                    <div class="stack-bar mt-3" role="img" aria-label="Today's attendance breakdown">
                        @foreach ($breakdown as $row)
                            @if ($row['count'])
                                <span class="tone-{{ $row['tone'] }}" style="flex: {{ $row['count'] }}" title="{{ \App\Support\Attendance::label($row['status']) }}: {{ $row['count'] }}"></span>
                            @endif
                        @endforeach
                        @if ($notMarked)
                            <span class="is-unmarked" style="flex: {{ $notMarked }}" title="Not marked: {{ $notMarked }}"></span>
                        @endif
                    </div>
                    <div class="stack-legend">
                        @foreach ($breakdown as $row)
                            <span><i class="legend-dot tone-{{ $row['tone'] }}"></i>{{ \App\Support\Attendance::label($row['status']) }} <strong>{{ $row['count'] }}</strong></span>
                        @endforeach
                        <span><i class="legend-dot is-unmarked"></i>Not marked <strong>{{ $notMarked }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 7-day trend --}}
        <div class="col-xl-7">
            <div class="card h-100 side-list tone-indigo">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-chart-bar"></i></span>
                    <span class="fw-semibold">Last 7 days</span>
                    <a href="{{ route('admin.attendance.monthly') }}" class="ms-auto font-12 text-decoration-none">Monthly view <i class="ti ti-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div class="trend-chart">
                        @foreach ($trend as $day)
                            <a href="{{ route('admin.attendance.daily', ['date' => $day['date']->toDateString()]) }}" class="trend-col {{ $day['date']->isToday() ? 'is-today' : '' }}"
                               title="{{ $day['date']->format('D, M j') }}: {{ $day['total'] }} marked">
                                <span class="trend-value">{{ $day['total'] ?: '' }}</span>
                                <span class="trend-bar" style="height: {{ $day['total'] ? max(6, round($day['total'] / $trendMax * 100)) : 0 }}%">
                                    @foreach (['present', 'wfh', 'half_day', 'on_leave', 'absent'] as $status)
                                        @if (! empty($day['counts'][$status]))
                                            <span class="tone-{{ \App\Support\Attendance::meta($status)['tone'] }}" style="flex: {{ $day['counts'][$status] }}"></span>
                                        @endif
                                    @endforeach
                                </span>
                                <span class="trend-label">{{ $day['date']->isToday() ? 'Today' : $day['date']->format('D') }}</span>
                            </a>
                        @endforeach
                    </div>
                    <div class="stack-legend justify-content-center mt-2">
                        @foreach (['present', 'wfh', 'half_day', 'on_leave', 'absent'] as $status)
                            <span><i class="legend-dot tone-{{ \App\Support\Attendance::meta($status)['tone'] }}"></i>{{ \App\Support\Attendance::label($status) }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending approvals --}}
    <div class="category-grid">
        @foreach ([
            ['label' => 'Regularization requests', 'count' => $navCounts['regularization'], 'tone' => 'amber', 'icon' => 'ti-edit', 'route' => 'admin.regularization-requests.index'],
            ['label' => 'Work from home requests', 'count' => $navCounts['wfh'], 'tone' => 'sky', 'icon' => 'ti-home', 'route' => 'admin.wfh-requests.index'],
            ['label' => 'Overtime requests', 'count' => $navCounts['overtime'], 'tone' => 'pink', 'icon' => 'ti-alarm', 'route' => 'admin.overtime-requests.index'],
        ] as $card)
            <a href="{{ route($card['route'], ['status' => 'pending', 'per_page' => 25]) }}" class="category-card tone-{{ $card['tone'] }}">
                <div class="category-card-top">
                    <span class="category-card-icon"><i class="ti {{ $card['icon'] }}"></i></span>
                    <span class="approval-count">{{ $card['count'] }}</span>
                </div>
                <div class="category-card-title">{{ $card['label'] }}</div>
                <div class="category-card-meta">{{ $card['count'] ? 'Waiting for review' : 'Nothing pending' }} <i class="ti ti-arrow-right"></i></div>
            </a>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card side-list tone-indigo">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-clock"></i></span>
                    <span class="fw-semibold">Latest activity today</span>
                    <a href="{{ route('admin.attendance.punch-logs', ['from' => now()->toDateString(), 'to' => now()->toDateString(), 'per_page' => 25]) }}" class="ms-auto font-12 text-decoration-none">All punch logs <i class="ti ti-arrow-right"></i></a>
                </div>
                @forelse ($recent as $record)
                    @php $meta = \App\Support\Attendance::meta($record->status); @endphp
                    <div class="side-list-item">
                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($record->employee?->employee_code.$record->employee?->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($record->employee?->name) }}</span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold font-13 text-truncate">{{ $record->employee?->name }}</div>
                            <div class="list-subtext">
                                @if ($record->check_in_time)
                                    <i class="ti ti-login"></i> {{ \App\Support\Attendance::formatTime($record->check_in_time) }}
                                @endif
                                @if ($record->check_out_time)
                                    · <i class="ti ti-logout"></i> {{ \App\Support\Attendance::formatTime($record->check_out_time) }}
                                @endif
                                @unless ($record->check_in_time || $record->check_out_time)
                                    {{ $record->employee?->department?->name ?? 'Marked '.$record->updated_at?->diffForHumans() }}
                                @endunless
                            </div>
                        </div>
                        <span class="tag tone-{{ $meta['tone'] }}">{{ \App\Support\Attendance::label($record->status) }}</span>
                    </div>
                @empty
                    <div class="side-list-empty text-muted"><i class="ti ti-clock"></i> No attendance marked today yet</div>
                @endforelse
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card side-list tone-danger">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-user-x"></i></span>
                    <span class="fw-semibold">Away today</span>
                    <span class="side-list-count">{{ $todayCounts->get('absent', 0) + $todayCounts->get('on_leave', 0) }}</span>
                </div>
                @forelse ($absentToday as $record)
                    @php $meta = \App\Support\Attendance::meta($record->status); @endphp
                    <div class="side-list-item">
                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($record->employee?->employee_code.$record->employee?->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($record->employee?->name) }}</span>
                        <div class="min-w-0 flex-grow-1 fw-semibold font-13 text-truncate">{{ $record->employee?->name }}</div>
                        <span class="tag tone-{{ $meta['tone'] }}">{{ \App\Support\Attendance::label($record->status) }}</span>
                    </div>
                @empty
                    <div class="side-list-empty"><i class="ti ti-circle-check"></i> Nobody marked absent or on leave</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
