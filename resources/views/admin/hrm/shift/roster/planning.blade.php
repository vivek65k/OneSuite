@extends('layouts.admin')

@section('title', 'Roster Planning')

@php
    use App\Support\ShiftTime;

    $end = $start->copy()->addDays(6);
    $weekUrl = fn (array $overrides = []) => route('admin.shift-roster.planning', array_filter(
        array_merge($state, ['start' => $start->toDateString()], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $isThisWeek = $start->isSameDay(now()->startOfWeek());
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $shiftStyles = $allShifts->union($shifts->keyBy('id'))->map(fn ($s) => ShiftTime::style($s));
    $shiftById = $allShifts->union($shifts->keyBy('id'));
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $weekDates = collect($dates)->map->toDateString()->all();
    $workDates = collect($dates)->reject->isWeekend()->map->toDateString()->values()->all();
    $allIds = $employees->pluck('id')->all();

    // Coverage per day: shift_id => count, for the footer row.
    $coverage = collect($dates)->mapWithKeys(fn ($d) => [$d->toDateString() => $employees->flatMap->shiftRosters
        ->filter(fn ($r) => $r->date->isSameDay($d))->countBy('shift_id')]);
@endphp

@section('content')
    <x-page-title title="Roster Planning" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Scheduled" :value="$kpis['scheduled'].' / '.$kpis['employees']" icon="ti-users" tone="indigo" hint="Employees with at least one shift" />
        <x-kpi-card label="Open slots" :value="$kpis['open']" icon="ti-calendar-plus" tone="{{ $kpis['open'] ? 'amber' : 'success' }}" :hint="$kpis['open'] ? 'Unassigned days (excl. leave)' : 'Every day is covered'" />
        <x-kpi-card label="Scheduled hours" :value="$fmt($kpis['hours']).' h'" icon="ti-clock" tone="teal" hint="Working hours this week" />
        <x-kpi-card label="On leave" :value="$kpis['leave_days']" icon="ti-plane" tone="violet" hint="Approved leave days this week" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="month-nav">
                <a href="{{ $weekUrl(['start' => $start->copy()->subWeek()->toDateString()]) }}" class="btn-icon" title="Previous week"><i class="ti ti-chevron-left font-18"></i></a>
                <div class="month-nav-label"><span class="fw-bold">{{ $start->format('M j') }} – {{ $end->format($start->month === $end->month ? 'j' : 'M j') }}</span><span class="text-muted">{{ $end->format('Y') }}</span></div>
                <a href="{{ $weekUrl(['start' => $start->copy()->addWeek()->toDateString()]) }}" class="btn-icon" title="Next week"><i class="ti ti-chevron-right font-18"></i></a>
                @unless ($isThisWeek)
                    <a href="{{ $weekUrl(['start' => now()->startOfWeek()->toDateString()]) }}" class="btn btn-light btn-sm ms-1">This week</a>
                @endunless
            </div>
            <div class="list-toolbar-actions">
                <form method="GET" action="{{ route('admin.shift-roster.planning') }}" class="d-flex gap-2 flex-wrap">
                    <input type="hidden" name="start" value="{{ $start->toDateString() }}">
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
                </form>
                @if ($canEdit)
                    <form method="POST" action="{{ route('admin.shift-roster.copy-week') }}" onsubmit="return confirm('Copy last week\'s shifts into the empty slots of this week?');">
                        @csrf
                        <input type="hidden" name="start" value="{{ $start->toDateString() }}">
                        <button type="submit" class="btn btn-light btn-sm"><i class="ti ti-copy font-14 me-1"></i> Copy last week</button>
                    </form>
                    <a href="{{ route('admin.shift-roster.assign') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-user-plus font-14 me-1"></i> Assign range</a>
                @endif
            </div>
        </div>

        <div class="list-segment-bar">
            <div class="stack-legend m-0">
                @foreach ($shifts as $shift)
                    <span><span class="legend-dot tone-{{ $shiftStyles[$shift->id]['tone'] }}"></span><strong>{{ $shift->code }}</strong> {{ ShiftTime::format($shift->start_time) }}–{{ ShiftTime::format($shift->end_time) }}</span>
                @endforeach
                <span><span class="legend-dot is-unmarked"></span> Off</span>
                <span><span class="legend-dot tone-violet"></span> Leave / holiday marked on cell</span>
            </div>
            @if ($canEdit)
                <span class="list-segment-hint"><i class="ti ti-pencil"></i> Click a day to change it - saves instantly</span>
            @endif
        </div>

        <div class="table-responsive roster-wrap">
            <table class="table list-table roster-board mb-0" id="roster-board" data-csrf="{{ csrf_token() }}" data-url="{{ route('admin.shift-roster.update') }}">
                <thead class="thead-light">
                    <tr>
                        <th class="roster-name">Employee</th>
                        @foreach ($dates as $date)
                            @php $holiday = $holidays[$date->toDateString()] ?? null; @endphp
                            <th class="roster-day {{ $date->isToday() ? 'is-today' : '' }} {{ $date->isWeekend() ? 'is-weekend' : '' }}">
                                <div class="roster-day-head">
                                    <span class="roster-dow">{{ $date->format('D') }}</span>
                                    <span class="roster-date">{{ $date->format('j') }}</span>
                                    @if ($canEdit && $shifts->isNotEmpty() && $employees->isNotEmpty())
                                        <div class="dropdown ms-auto">
                                            <button type="button" class="btn-icon roster-menu-btn" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Fill {{ $date->format('l') }} for everyone"><i class="ti ti-dots-vertical"></i></button>
                                            <div class="dropdown-menu dropdown-menu-end p-2">
                                                <h6 class="dropdown-header">Everyone on {{ $date->format('D, M j') }}</h6>
                                                @include('admin.hrm.shift.roster._bulk-options', ['employeeIds' => $allIds, 'bulkDates' => [$date->toDateString()]])
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @if ($holiday)
                                    <div class="roster-holiday" title="{{ $holiday }}"><i class="ti ti-confetti"></i> {{ $holiday }}</div>
                                @endif
                            </th>
                        @endforeach
                        <th class="roster-total">Hours</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php
                            $initials = collect(preg_split('/\s+/', trim($employee->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                            $rowHours = $employee->shiftRosters->sum(fn ($r) => ($s = $shiftById->get($r->shift_id)) ? ShiftTime::netHours($s) : 0);
                            $rowShifts = $shifts->filter(fn ($s) => ! $s->branch_id || (int) $s->branch_id === (int) $employee->branch_id);
                        @endphp
                        <tr>
                            <td class="roster-name">
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                    <div class="list-user-text min-w-0 flex-grow-1">
                                        <div class="list-user-name text-truncate">{{ $employee->name }}</div>
                                        <div class="list-user-meta text-truncate">{{ $employee->department?->name ?? $employee->employee_code }}</div>
                                    </div>
                                    @if ($canEdit && $rowShifts->isNotEmpty())
                                        <div class="dropdown">
                                            <button type="button" class="btn-icon roster-menu-btn" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Fill {{ $employee->name }}'s week"><i class="ti ti-dots-vertical"></i></button>
                                            <div class="dropdown-menu dropdown-menu-end p-2">
                                                <h6 class="dropdown-header">{{ $employee->name }} · Mon–Fri</h6>
                                                @include('admin.hrm.shift.roster._bulk-options', ['employeeIds' => [$employee->id], 'bulkDates' => $workDates, 'bulkShifts' => $rowShifts])
                                                <div class="dropdown-divider"></div>
                                                <h6 class="dropdown-header">Whole week</h6>
                                                @include('admin.hrm.shift.roster._bulk-options', ['employeeIds' => [$employee->id], 'bulkDates' => $weekDates, 'bulkShifts' => $rowShifts])
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            @foreach ($dates as $date)
                                @php
                                    $key = $date->toDateString();
                                    $roster = $employee->shiftRosters->first(fn ($r) => $r->date->isSameDay($date));
                                    $current = $roster ? $shiftById->get($roster->shift_id) : null;
                                    $leaveCode = $onLeave[$employee->id][$key] ?? null;
                                    $tone = $current ? ($shiftStyles[$current->id]['tone'] ?? 'slate') : null;
                                @endphp
                                <td class="roster-day {{ $date->isToday() ? 'is-today' : '' }} {{ $date->isWeekend() ? 'is-weekend' : '' }}">
                                    <div class="dropdown">
                                        <button type="button" class="roster-cell {{ $current ? 'tone-'.$tone.' has-shift' : 'is-off' }} {{ $leaveCode ? 'has-leave' : '' }}"
                                                @if ($canEdit) data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" @else disabled @endif
                                                title="{{ $employee->name }} · {{ $date->format('D, M j') }}{{ $current ? ' · '.$current->name.' '.ShiftTime::format($current->start_time).'–'.ShiftTime::format($current->end_time) : ' · Off' }}{{ $leaveCode ? ' · On leave ('.$leaveCode.')' : '' }}">
                                            <span class="roster-cell-code" data-code>{{ $current?->code ?? ($leaveCode ? '' : 'Off') }}</span>
                                            <span class="roster-cell-time" data-time>{{ $current ? ShiftTime::format($current->start_time).'–'.ShiftTime::format($current->end_time) : '' }}</span>
                                            @if ($leaveCode)
                                                <span class="roster-cell-leave"><i class="ti ti-plane"></i> {{ $leaveCode }}</span>
                                            @endif
                                        </button>
                                        @if ($canEdit)
                                            <div class="dropdown-menu roster-picker p-2">
                                                <form method="POST" action="{{ route('admin.shift-roster.update') }}" data-roster-form>
                                                    @csrf
                                                    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                                    <input type="hidden" name="date" value="{{ $key }}">
                                                    <input type="hidden" name="start" value="{{ $start->toDateString() }}">
                                                    @if ($leaveCode)
                                                        <div class="leave-alert is-warning mt-0 mb-2"><i class="ti ti-plane"></i> <span>On approved leave ({{ $leaveCode }})</span></div>
                                                    @endif
                                                    @foreach ($rowShifts as $shift)
                                                        <button type="submit" name="shift_id" value="{{ $shift->id }}" class="roster-option tone-{{ $shiftStyles[$shift->id]['tone'] }} {{ $current?->id === $shift->id ? 'is-current' : '' }}">
                                                            <span class="roster-option-code">{{ $shift->code }}</span>
                                                            <span class="roster-option-name">{{ $shift->name }}<small>{{ ShiftTime::format($shift->start_time) }}–{{ ShiftTime::format($shift->end_time) }}</small></span>
                                                            @if ($current?->id === $shift->id)<i class="ti ti-check ms-auto"></i>@endif
                                                        </button>
                                                    @endforeach
                                                    <button type="submit" name="shift_id" value="" class="roster-option is-off-option {{ $current ? '' : 'is-current' }}">
                                                        <span class="roster-option-code">Off</span>
                                                        <span class="roster-option-name">Day off<small>No shift</small></span>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                            <td class="roster-total"><span class="hours-figure" data-row-hours>{{ $fmt($rowHours) }}<small>h</small></span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="ti ti-users font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No employees to schedule</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($employees->isNotEmpty())
                    <tfoot>
                        <tr class="roster-coverage">
                            <td class="roster-name"><span class="fw-semibold font-12">Coverage</span></td>
                            @foreach ($dates as $date)
                                @php $counts = $coverage[$date->toDateString()]; $open = $employees->count() - $counts->sum(); @endphp
                                <td class="roster-day {{ $date->isToday() ? 'is-today' : '' }} {{ $date->isWeekend() ? 'is-weekend' : '' }}">
                                    <div class="roster-coverage-cell">
                                        @foreach ($counts as $shiftId => $count)
                                            @if ($shiftById->has($shiftId))
                                                <span class="tag tone-{{ $shiftStyles[$shiftId]['tone'] ?? 'slate' }}">{{ $shiftById[$shiftId]->code }} {{ $count }}</span>
                                            @endif
                                        @endforeach
                                        @if ($open > 0)
                                            <span class="tag tag-outline tone-slate">Off {{ $open }}</span>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                            <td class="roster-total"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="kanban-toast" id="roster-toast" role="status" aria-live="polite" hidden></div>
@endsection

@if ($canEdit)
    @push('scripts')
        <script>
            // Instant save: intercept the picker forms, PATCH via fetch, update the cell in place.
            (function () {
                var board = document.getElementById('roster-board');
                if (!board) return;
                var toast = document.getElementById('roster-toast'), timer;
                function say(msg, bad) {
                    toast.textContent = msg; toast.classList.toggle('is-error', !!bad); toast.hidden = false;
                    clearTimeout(timer); timer = setTimeout(function () { toast.hidden = true; }, 2400);
                }
                board.querySelectorAll('[data-roster-form]').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        var choice = event.submitter;
                        var body = new FormData(form);
                        body.set('shift_id', choice ? choice.value : '');
                        var cell = form.closest('.dropdown').querySelector('.roster-cell');
                        cell.classList.add('is-saving');
                        fetch(board.dataset.url, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': board.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                            body: body
                        }).then(function (r) {
                            if (!r.ok) throw new Error(r.status);
                            return r.json();
                        }).then(function (data) {
                            var s = data.shift;
                            cell.className = cell.className.replace(/\btone-\S+|\bhas-shift\b|\bis-off\b|\bis-saving\b/g, '').trim() + (s ? ' has-shift tone-' + s.tone : ' is-off');
                            cell.querySelector('[data-code]').textContent = s ? s.code : (cell.classList.contains('has-leave') ? '' : 'Off');
                            cell.querySelector('[data-time]').textContent = s ? s.time : '';
                            form.querySelectorAll('.roster-option').forEach(function (opt) {
                                var on = String(opt.value) === String(s ? s.id : '');
                                opt.classList.toggle('is-current', on);
                            });
                            var dd = bootstrap.Dropdown.getInstance(cell);
                            if (dd) dd.hide();
                            cell.classList.add('is-moved');
                            setTimeout(function () { cell.classList.remove('is-moved'); }, 700);
                            say(s ? 'Set to ' + s.name + ' (' + s.time + ')' : 'Marked as day off');
                        }).catch(function () {
                            cell.classList.remove('is-saving');
                            say('Could not save - please try again.', true);
                        });
                    });
                });
            })();
        </script>
    @endpush
@endif
