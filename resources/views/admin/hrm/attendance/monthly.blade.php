@extends('layouts.admin')

@section('title', 'Monthly Attendance')

@php
    use App\Support\Attendance;

    $monthUrl = fn (array $overrides = []) => route('admin.attendance.monthly', array_filter(
        array_merge($state, ['month' => $month], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $prev = $start->copy()->subMonth()->format('Y-m');
    $next = $start->copy()->addMonth()->format('Y-m');
    $totalsFor = ['present', 'absent', 'half_day', 'on_leave', 'wfh'];

    // Month-wide totals per status, for the summary strip.
    $monthTotals = $employees->flatMap->attendanceRecords->countBy('status');
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
@endphp

@section('content')
    <x-page-title title="Monthly Attendance" :breadcrumbs="['Attendance' => route('admin.attendance.dashboard')]" />

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="month-nav">
                <a href="{{ $monthUrl(['month' => $prev]) }}" class="btn-icon" title="Previous month"><i class="ti ti-chevron-left font-18"></i></a>
                <div class="month-nav-label">
                    <span class="fw-bold">{{ $start->format('F') }}</span>
                    <span class="text-muted">{{ $start->format('Y') }}</span>
                </div>
                <a href="{{ $monthUrl(['month' => $next]) }}" class="btn-icon" title="Next month"><i class="ti ti-chevron-right font-18"></i></a>
                @if ($month !== now()->format('Y-m'))
                    <a href="{{ $monthUrl(['month' => now()->format('Y-m')]) }}" class="btn btn-light btn-sm ms-1">This month</a>
                @endif
            </div>

            <form method="GET" action="{{ route('admin.attendance.monthly') }}" class="list-toolbar-actions">
                <input type="hidden" name="month" value="{{ $month }}">
                <div class="list-search">
                    <i class="ti ti-search"></i>
                    <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search name or code" aria-label="Search employees">
                </div>
                <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                    <option value="">All departments</option>
                    @foreach ($departments as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($state['department_id'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <input type="month" name="month" class="form-control form-control-sm" style="max-width: 150px" value="{{ $month }}" onchange="this.form.submit()" aria-label="Pick a month">
            </form>
        </div>

        {{-- Legend + month totals --}}
        <div class="list-segment-bar">
            <div class="stack-legend m-0">
                @foreach (Attendance::STATUS_META as $status => $meta)
                    <span><span class="heat-cell heat-cell-sm tone-{{ $meta['tone'] }}">{{ $meta['letter'] }}</span> {{ Attendance::label($status) }} <strong>{{ $monthTotals->get($status, 0) }}</strong></span>
                @endforeach
            </div>
        </div>

        <div class="table-responsive heatmap-wrap">
            <table class="table list-table heatmap mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="heatmap-name">Employee</th>
                        @foreach ($days as $day)
                            @php $date = $start->copy()->day($day); @endphp
                            <th class="heatmap-day {{ $date->isWeekend() ? 'is-weekend' : '' }} {{ $date->isToday() ? 'is-today' : '' }}" title="{{ $date->format('l, M j') }}">
                                <span>{{ $date->format('D')[0] }}</span>{{ $day }}
                            </th>
                        @endforeach
                        @foreach ($totalsFor as $status)
                            <th class="heatmap-total" title="{{ Attendance::label($status) }}"><span class="heat-cell heat-cell-sm tone-{{ Attendance::meta($status)['tone'] }}">{{ Attendance::meta($status)['letter'] }}</span></th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php
                            $byDay = $employee->attendanceRecords->keyBy(fn ($r) => (int) $r->date->format('j'));
                            $employeeCounts = $employee->attendanceRecords->countBy('status');
                            $initials = collect(preg_split('/\s+/', trim($employee->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                        @endphp
                        <tr>
                            <td class="heatmap-name">
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $employee->name }}</div>
                                        <div class="list-user-meta">{{ $employee->department?->name ?? $employee->employee_code }}</div>
                                    </div>
                                </div>
                            </td>
                            @foreach ($days as $day)
                                @php
                                    $date = $start->copy()->day($day);
                                    $record = $byDay->get($day);
                                    $meta = Attendance::meta($record?->status);
                                @endphp
                                <td class="heatmap-day {{ $date->isWeekend() ? 'is-weekend' : '' }} {{ $date->isToday() ? 'is-today' : '' }}">
                                    @if ($record)
                                        <a href="{{ route('admin.attendance.daily', ['date' => $date->toDateString()]) }}" class="heat-cell tone-{{ $meta['tone'] }}"
                                           title="{{ $employee->name }} · {{ $date->format('D, M j') }}: {{ Attendance::label($record->status) }}{{ $record->check_in_time ? ' ('.Attendance::formatTime($record->check_in_time).'–'.(Attendance::formatTime($record->check_out_time) ?? '…').')' : '' }}">{{ $meta['letter'] }}</a>
                                    @elseif ($date->isFuture())
                                        <span class="heat-cell is-future"></span>
                                    @else
                                        <a href="{{ route('admin.attendance.daily', ['date' => $date->toDateString(), 'status' => 'unmarked']) }}" class="heat-cell is-empty" title="{{ $date->format('D, M j') }}: not marked"></a>
                                    @endif
                                </td>
                            @endforeach
                            @foreach ($totalsFor as $status)
                                <td class="heatmap-total"><span class="heatmap-count {{ $employeeCounts->get($status, 0) ? 'tone-'.Attendance::meta($status)['tone'] : '' }}">{{ $employeeCounts->get($status, 0) }}</span></td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $days->count() + 1 + count($totalsFor) }}" class="text-center text-muted py-5">
                                <i class="ti ti-users font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No employees found</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
