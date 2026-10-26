@extends('layouts.admin')

@section('title', 'Daily Attendance')

@php
    use App\Support\Attendance;

    $canMark = auth()->user()->can('create', \App\Models\AttendanceRecord::class);
    $dayUrl = fn (array $overrides = []) => route('admin.attendance.daily', array_filter(
        array_merge($state, ['date' => $date], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));

    $currentStatus = (string) ($state['status'] ?? '');
    $chips = collect(['' => ['label' => 'Everyone', 'tone' => 'indigo', 'count' => $rosterTotal]])
        ->merge(collect(Attendance::STATUS_META)->map(fn ($meta, $status) => [
            'label' => Attendance::label($status), 'tone' => $meta['tone'], 'count' => $counts->get($status, 0),
        ]))
        ->put('unmarked', ['label' => 'Not marked', 'tone' => 'amber', 'count' => $counts->get('unmarked', 0)])
        ->filter(fn ($chip, $key) => $key === '' || $key === 'unmarked' || $chip['count'] > 0 || in_array($key, Attendance::QUICK_STATUSES, true));

    $marked = $rosterTotal - $counts->get('unmarked', 0);
    $markedPercent = $rosterTotal ? (int) round($marked / $rosterTotal * 100) : 0;
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
@endphp

@section('content')
    <x-page-title title="Daily Attendance" :breadcrumbs="['Attendance' => route('admin.attendance.dashboard')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    {{-- Date navigator + completion --}}
    <div class="section-hero tone-success">
        <div class="date-nav">
            <a href="{{ $dayUrl(['date' => $day->copy()->subDay()->toDateString()]) }}" class="btn-icon" title="Previous day"><i class="ti ti-chevron-left font-18"></i></a>
            <div class="date-nav-block">
                <span class="date-nav-month">{{ $day->format('M') }}</span>
                <span class="date-nav-day">{{ $day->format('j') }}</span>
            </div>
            <a href="{{ $dayUrl(['date' => $day->copy()->addDay()->toDateString()]) }}" class="btn-icon" title="Next day"><i class="ti ti-chevron-right font-18"></i></a>
        </div>
        <div class="section-hero-text">
            <h4>{{ $day->isToday() ? 'Today' : ($day->isYesterday() ? 'Yesterday' : $day->format('l')) }}, {{ $day->format('F j, Y') }}</h4>
            <p>{{ $marked }} of {{ $rosterTotal }} employees marked{{ $counts->get('unmarked', 0) ? ' · '.$counts->get('unmarked').' still to mark' : ' - all done' }}.</p>
            <form method="GET" action="{{ route('admin.attendance.daily') }}" class="d-flex gap-2 mt-2 flex-wrap">
                @foreach (array_filter($state) as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <input type="date" name="date" class="form-control form-control-sm" style="max-width: 170px" value="{{ $date }}" onchange="this.form.submit()" aria-label="Pick a date">
                @unless ($day->isToday())
                    <a href="{{ $dayUrl(['date' => now()->toDateString()]) }}" class="btn btn-light btn-sm">Jump to today</a>
                @endunless
            </form>
        </div>
        <div class="progress-ring" style="--value: {{ $markedPercent }}" role="img" aria-label="{{ $markedPercent }}% marked">
            <span>{{ $markedPercent }}<small>%</small></span>
        </div>
    </div>

    <div class="card list-view">
        {{-- Status chips + search/department --}}
        <form method="GET" action="{{ route('admin.attendance.daily') }}">
            <input type="hidden" name="date" value="{{ $date }}">
            @if ($currentStatus !== '')
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Attendance status">
                    @foreach ($chips as $key => $chip)
                        <a href="{{ $dayUrl(['status' => $key === '' ? null : $key]) }}" class="list-tab tone-{{ $chip['tone'] }} {{ $currentStatus === (string) $key ? 'active' : '' }}">
                            {{ $chip['label'] }}
                            <span class="list-tab-count">{{ $chip['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
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
                </div>
            </div>
        </form>

        @if ($canMark && $employees->isNotEmpty())
            <form method="POST" action="{{ route('admin.attendance.daily.bulk') }}" id="bulk-form" class="bulk-bar">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <label class="bulk-select">
                    <input type="checkbox" class="form-check-input" id="select-all" aria-label="Select all">
                    <span id="bulk-summary">Select employees</span>
                </label>
                <button type="button" class="btn btn-link btn-sm p-0" id="select-unmarked">Select unmarked</button>
                <div class="bulk-actions">
                    <span class="list-subtext">Mark selected as</span>
                    @foreach (Attendance::QUICK_STATUSES as $status)
                        @php $meta = Attendance::meta($status); @endphp
                        <button type="submit" name="status" value="{{ $status }}" class="quick-chip tone-{{ $meta['tone'] }}" disabled data-bulk-action>
                            <i class="ti {{ $meta['icon'] }}"></i> {{ Attendance::label($status) }}
                        </button>
                    @endforeach
                </div>
            </form>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        @if ($canMark)
                            <th style="width: 40px"><span class="visually-hidden">Select</span></th>
                        @endif
                        <th>Employee</th>
                        <th>{{ $canMark ? 'Mark attendance' : 'Status' }}</th>
                        <th>Check in / out</th>
                        <th>Hours</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php
                            $record = $employee->attendanceRecords->first();
                            $current = $record?->status;
                            $hours = $record?->work_hours ?? Attendance::hoursBetween($record?->check_in_time, $record?->check_out_time);
                            $initials = collect(preg_split('/\s+/', trim($employee->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                        @endphp
                        <tr class="{{ $current ? '' : 'is-unmarked-row' }}">
                            @if ($canMark)
                                <td><input type="checkbox" class="form-check-input" name="employee_ids[]" value="{{ $employee->id }}" form="bulk-form" data-row-select data-unmarked="{{ $current ? 0 : 1 }}" aria-label="Select {{ $employee->name }}"></td>
                            @endif
                            <td>
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></div>
                                        <div class="list-user-meta">{{ $employee->employee_code }}@if ($employee->department) <span class="list-meta-sep">&middot;</span> {{ $employee->department->name }}@endif</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($canMark)
                                    <form method="POST" action="{{ route('admin.attendance.daily.mark') }}" class="mark-segment" role="group" aria-label="Mark {{ $employee->name }}">
                                        @csrf
                                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                        <input type="hidden" name="date" value="{{ $date }}">
                                        <input type="hidden" name="check_in_time" value="{{ $record?->check_in_time ? substr($record->check_in_time, 0, 5) : '' }}">
                                        <input type="hidden" name="check_out_time" value="{{ $record?->check_out_time ? substr($record->check_out_time, 0, 5) : '' }}">
                                        @foreach (Attendance::QUICK_STATUSES as $status)
                                            @php $meta = Attendance::meta($status); @endphp
                                            <button type="submit" name="status" value="{{ $status }}" class="mark-segment-btn tone-{{ $meta['tone'] }} {{ $current === $status ? 'active' : '' }}"
                                                    title="{{ Attendance::label($status) }}" aria-pressed="{{ $current === $status ? 'true' : 'false' }}">{{ $meta['letter'] }}</button>
                                        @endforeach
                                    </form>
                                    @if ($current && ! in_array($current, Attendance::QUICK_STATUSES, true))
                                        <span class="tag tone-{{ Attendance::meta($current)['tone'] }} ms-1">{{ Attendance::label($current) }}</span>
                                    @endif
                                @elseif ($current)
                                    <span class="tag tone-{{ Attendance::meta($current)['tone'] }}"><i class="ti {{ Attendance::meta($current)['icon'] }}"></i>{{ Attendance::label($current) }}</span>
                                @else
                                    <span class="countdown tone-amber">Not marked</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($record?->check_in_time || $record?->check_out_time)
                                    <span class="time-pair">
                                        <span><i class="ti ti-login text-success"></i> {{ Attendance::formatTime($record->check_in_time) ?? '—' }}</span>
                                        <span><i class="ti ti-logout text-danger"></i> {{ Attendance::formatTime($record->check_out_time) ?? '—' }}</span>
                                    </span>
                                @else
                                    <span class="text-muted font-12">No punches</span>
                                @endif
                                @if ($record?->source && $record->source !== 'manual')
                                    <div class="list-subtext mt-1"><i class="ti {{ Attendance::SOURCE_META[$record->source]['icon'] ?? 'ti-point' }}"></i> {{ \App\Models\AttendanceRecord::SOURCES[$record->source] ?? $record->source }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($hours)
                                    <span class="hours-figure">{{ rtrim(rtrim(number_format((float) $hours, 2), '0'), '.') }}<small>h</small></span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($canMark)
                                    <button type="button" class="btn-icon btn-icon-accent" data-bs-toggle="collapse" data-bs-target="#times-{{ $employee->id }}" aria-expanded="false" title="Edit times"><i class="ti ti-clock font-16"></i></button>
                                @endif
                            </td>
                        </tr>
                        @if ($canMark)
                            <tr class="collapse times-row" id="times-{{ $employee->id }}">
                                <td colspan="6">
                                    <form method="POST" action="{{ route('admin.attendance.daily.mark') }}" class="inline-add-form row g-2 align-items-end tone-indigo">
                                        @csrf
                                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                        <input type="hidden" name="date" value="{{ $date }}">
                                        <div class="col-md-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select form-select-sm" required>
                                                @foreach (\App\Models\AttendanceRecord::STATUSES as $value => $label)
                                                    <option value="{{ $value }}" @selected(($current ?? 'present') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Check in</label>
                                            <input type="time" name="check_in_time" class="form-control form-control-sm" value="{{ $record?->check_in_time ? substr($record->check_in_time, 0, 5) : '' }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Check out</label>
                                            <input type="time" name="check_out_time" class="form-control form-control-sm" value="{{ $record?->check_out_time ? substr($record->check_out_time, 0, 5) : '' }}">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-de-primary btn-sm text-light w-100"><i class="ti ti-check font-14 me-1"></i> Save</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-users font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No employees to show</div>
                                @if ($currentStatus !== '' || filled($state['search'] ?? null) || filled($state['department_id'] ?? null))
                                    <span class="font-13"><a href="{{ route('admin.attendance.daily', ['date' => $date]) }}">Clear filters</a></span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Bulk selection bar.
        (function () {
            var all = document.getElementById('select-all');
            if (!all) return;
            var rows = Array.prototype.slice.call(document.querySelectorAll('[data-row-select]'));
            var actions = document.querySelectorAll('[data-bulk-action]');
            var summary = document.getElementById('bulk-summary');

            function refresh() {
                var picked = rows.filter(function (box) { return box.checked; }).length;
                actions.forEach(function (btn) { btn.disabled = picked === 0; });
                summary.textContent = picked ? picked + ' selected' : 'Select employees';
                all.checked = picked > 0 && picked === rows.length;
                all.indeterminate = picked > 0 && picked < rows.length;
            }

            all.addEventListener('change', function () {
                rows.forEach(function (box) { box.checked = all.checked; });
                refresh();
            });
            rows.forEach(function (box) { box.addEventListener('change', refresh); });
            document.getElementById('select-unmarked').addEventListener('click', function () {
                rows.forEach(function (box) { box.checked = box.dataset.unmarked === '1'; });
                refresh();
            });
            refresh();
        })();
    </script>
@endpush
