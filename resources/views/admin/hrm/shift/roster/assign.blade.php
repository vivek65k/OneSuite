@extends('layouts.admin')

@section('title', 'Assign Shift')

@php
    use App\Support\ShiftTime;

    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $initialsOf = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');

    $oldEmployees = collect(old('employee_ids', []))->map(fn ($id) => (string) $id)->all();
    $oldDays = collect(old('days', [1, 2, 3, 4, 5]))->map(fn ($d) => (int) $d)->all();
    $mode = old('mode', 'empty');
    $weekdays = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    $employeePayload = $employees->mapWithKeys(fn ($e) => [$e->id => ['name' => $e->name, 'branch_id' => $e->branch_id]]);
    $shiftPayload = $shifts->mapWithKeys(fn ($s) => [$s->id => [
        'name' => $s->name,
        'code' => $s->code,
        'branch_id' => $s->branch_id,
        'time' => ShiftTime::format($s->start_time).' – '.ShiftTime::format($s->end_time),
        'hours' => ShiftTime::netHours($s),
        'tone' => ShiftTime::style($s)['tone'],
    ]]);
@endphp

@section('content')
    <x-page-title title="Assign Shift" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="ti ti-circle-check font-18"></i>
            <span class="flex-grow-1">{{ session('status') }}</span>
            @if (session('assign_week'))
                <a href="{{ route('admin.shift-roster.planning', ['start' => session('assign_week')]) }}" class="btn btn-sm btn-light">View in planner <i class="ti ti-arrow-right"></i></a>
            @endif
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning d-flex align-items-center gap-2"><i class="ti ti-alert-triangle font-18"></i> {{ session('warning') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Active employees" :value="$kpis['employees']" icon="ti-users" tone="indigo" hint="Available to schedule" />
        <x-kpi-card label="Scheduled this week" :value="$kpis['this_week']" icon="ti-calendar-stats" tone="teal" :hint="'of '.$kpis['employees'].' employees'" :href="route('admin.shift-roster.planning')" />
        <x-kpi-card label="Unscheduled next week" :value="$kpis['next_week_open']" icon="ti-calendar-off" tone="amber" hint="No shift planned yet" :href="route('admin.shift-roster.planning', ['start' => now()->addWeek()->startOfWeek()->toDateString()])" />
        <x-kpi-card label="Active shifts" :value="$kpis['shifts']" icon="ti-clock" tone="violet" hint="Ready to assign" :href="route('admin.shifts.index')" />
    </div>

    @if ($shifts->isEmpty() || $employees->isEmpty())
        <div class="card">
            <div class="empty-state tone-teal">
                <span class="empty-state-icon"><i class="ti {{ $shifts->isEmpty() ? 'ti-clock' : 'ti-users' }}"></i></span>
                <h5>{{ $shifts->isEmpty() ? 'No active shifts yet' : 'No active employees to schedule' }}</h5>
                <p>{{ $shifts->isEmpty() ? 'Create a shift first, then come back to assign it.' : 'Add employees before assigning shifts.' }}</p>
                @if ($shifts->isEmpty())
                    <a href="{{ route('admin.shifts.index') }}" class="btn btn-de-primary btn-sm text-light"><i class="ti ti-plus"></i> Manage shifts</a>
                @endif
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('admin.shift-roster.assign.store') }}" id="assign-form" class="row g-3">
            @csrf
            <div class="col-lg-8">
                @if ($errors->any())
                    <div class="alert alert-danger py-2 font-13">
                        @foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach
                    </div>
                @endif

                {{-- 1. Employees --}}
                <div class="card form-section tone-indigo">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-users"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Employees</h5>
                            <p class="form-section-sub">Who is working this shift? Pick one or many.</p>
                        </div>
                        <span class="tag tone-indigo" id="picked-count">0 selected</span>
                    </div>
                    <div class="form-section-body">
                        <div class="assign-toolbar">
                            <div class="list-search flex-grow-1">
                                <i class="ti ti-search"></i>
                                <input type="search" id="emp-search" class="form-control form-control-sm" placeholder="Search name or code" aria-label="Search employees">
                            </div>
                            @if ($departments->count() > 1)
                                <select id="emp-dept" class="form-select form-select-sm w-auto" aria-label="Department">
                                    <option value="">All departments</option>
                                    @foreach ($departments as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <button type="button" class="btn btn-light btn-sm" id="emp-all"><i class="ti ti-checks"></i> Select shown</button>
                            <button type="button" class="btn btn-light btn-sm" id="emp-none">Clear</button>
                        </div>

                        <div class="assign-people" id="people">
                            @foreach ($employees as $employee)
                                <label class="assign-person" data-name="{{ mb_strtolower($employee->name.' '.$employee->employee_code) }}" data-dept="{{ $employee->department_id }}">
                                    <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" @checked(in_array((string) $employee->id, $oldEmployees, true))>
                                    <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($employee->name) }}</span>
                                    <span class="assign-person-body">
                                        <span class="assign-person-name">{{ $employee->name }}</span>
                                        <span class="assign-person-meta">{{ $employee->department?->name ?? 'No department' }} · {{ $employee->employee_code }}</span>
                                    </span>
                                    <i class="ti ti-circle-check assign-person-check"></i>
                                </label>
                            @endforeach
                        </div>
                        <div class="form-hint" id="people-empty" hidden><i class="ti ti-info-circle"></i> <span>No employees match this search.</span></div>
                    </div>
                </div>

                {{-- 2. Shift --}}
                <div class="card form-section tone-teal">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-clock"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Shift</h5>
                            <p class="form-section-sub">Only active shifts are listed.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="assign-shifts" role="radiogroup" aria-label="Shift">
                            @foreach ($shifts as $shift)
                                @php $style = ShiftTime::style($shift); @endphp
                                <label class="assign-shift tone-{{ $style['tone'] }}">
                                    <input type="radio" name="shift_id" value="{{ $shift->id }}" @checked((string) old('shift_id') === (string) $shift->id) required>
                                    <span class="assign-shift-top">
                                        <span class="roster-option-code">{{ $shift->code }}</span>
                                        <span class="assign-shift-name">{{ $shift->name }}</span>
                                        <i class="ti {{ $style['icon'] }} assign-shift-icon" title="{{ $style['label'] }}"></i>
                                    </span>
                                    <span class="assign-shift-time">{{ ShiftTime::format($shift->start_time) }} – {{ ShiftTime::format($shift->end_time) }}</span>
                                    <span class="shift-track">
                                        @foreach (ShiftTime::segments($shift->start_time, $shift->end_time) as $seg)
                                            <span class="shift-track-bar tone-{{ $style['tone'] }}" style="left: {{ $seg['left'] }}%; width: {{ $seg['width'] }}%"></span>
                                        @endforeach
                                    </span>
                                    <span class="assign-shift-facts">
                                        <span><i class="ti ti-briefcase"></i> {{ $fmt(ShiftTime::netHours($shift)) }}h work</span>
                                        <span><i class="ti ti-coffee"></i> {{ (int) $shift->break_minutes }}m break</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 3. Dates --}}
                <div class="card form-section tone-amber">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-calendar-event"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Dates</h5>
                            <p class="form-section-sub">Up to {{ $maxDays }} days at a time, on the weekdays you choose.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="assign-start">From <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="assign-start" class="form-control" value="{{ old('start_date') }}" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="assign-end">To <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="assign-end" class="form-control" value="{{ old('end_date') }}" required>
                            </div>
                            <div class="col-12">
                                <div class="quick-chips mt-0" id="date-chips">
                                    <button type="button" class="quick-chip" data-range="this-week">This week</button>
                                    <button type="button" class="quick-chip" data-range="next-week">Next week</button>
                                    <button type="button" class="quick-chip" data-range="next-2-weeks">Next 2 weeks</button>
                                    <button type="button" class="quick-chip" data-range="this-month">Rest of this month</button>
                                    <button type="button" class="quick-chip" data-range="next-month">Next month</button>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Working days <span class="text-danger">*</span></label>
                                <div class="weekday-picker">
                                    @foreach ($weekdays as $num => $label)
                                        <label class="weekday-pill {{ $num >= 6 ? 'is-weekend' : '' }}">
                                            <input type="checkbox" name="days[]" value="{{ $num }}" @checked(in_array($num, $oldDays, true))>
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                    <span class="weekday-presets">
                                        <button type="button" class="quick-chip" data-days="1,2,3,4,5">Mon–Fri</button>
                                        <button type="button" class="quick-chip" data-days="1,2,3,4,5,6">Mon–Sat</button>
                                        <button type="button" class="quick-chip" data-days="1,2,3,4,5,6,7">Every day</button>
                                        <button type="button" class="quick-chip" data-days="6,7">Weekends</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. Existing shifts --}}
                <div class="card form-section tone-violet mb-0">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-layers-intersect"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Days already planned</h5>
                            <p class="form-section-sub">What to do when someone already has a shift that day. Approved leave is always skipped.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="toggle-cards">
                            <label class="toggle-card tone-success">
                                <input type="radio" name="mode" value="empty" @checked($mode !== 'replace')>
                                <span class="toggle-card-icon"><i class="ti ti-shield-check"></i></span>
                                <span class="toggle-card-body">
                                    <span class="toggle-card-title">Fill empty days only</span>
                                    <span class="toggle-card-hint">Keep shifts that are already planned</span>
                                </span>
                            </label>
                            <label class="toggle-card tone-rose">
                                <input type="radio" name="mode" value="replace" @checked($mode === 'replace')>
                                <span class="toggle-card-icon"><i class="ti ti-refresh"></i></span>
                                <span class="toggle-card-body">
                                    <span class="toggle-card-title">Replace existing shifts</span>
                                    <span class="toggle-card-hint">Overwrite whatever is planned</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="leave-summary" id="assign-summary">
                    <div class="leave-summary-label">Assignment summary</div>
                    <div class="leave-summary-who" id="sum-who">No employees selected</div>
                    <div class="leave-summary-type" id="sum-shift">No shift selected</div>
                    <div class="leave-summary-days"><span id="sum-slots">0</span><small> shifts</small></div>
                    <div class="leave-summary-dates" id="sum-dates">Pick the dates</div>

                    <div class="leave-balance">
                        <div class="leave-balance-row"><span>Working days in range</span><strong id="sum-days">—</strong></div>
                        <div class="leave-balance-row"><span>Already planned (kept)</span><strong id="sum-kept">0</strong></div>
                        <div class="leave-balance-row"><span>On approved leave (skipped)</span><strong id="sum-leave">0</strong></div>
                        <div class="leave-balance-row is-total"><span>Hours scheduled</span><strong id="sum-hours">0h</strong></div>
                    </div>

                    <div class="leave-alert is-warning" id="sum-replace" hidden><i class="ti ti-alert-triangle"></i> <span></span></div>
                    <div class="leave-alert is-danger" id="sum-branch" hidden><i class="ti ti-alert-circle"></i> <span></span></div>
                    <div class="leave-alert is-danger" id="sum-range" hidden><i class="ti ti-alert-circle"></i> <span></span></div>

                    <button type="submit" class="btn btn-light w-100 mt-3 fw-semibold" id="assign-submit"><i class="ti ti-calendar-plus font-14 me-1"></i> Assign shift</button>
                    <div class="leave-summary-note" id="sum-note">Counts are a preview; the server re-checks everything.</div>
                </div>
            </div>
        </form>
    @endif
@endsection

@if ($shifts->isNotEmpty() && $employees->isNotEmpty())
    @push('scripts')
        <script>
            (function () {
                var employees = @json($employeePayload);
                var shifts = @json($shiftPayload);
                var rostered = @json($rostered);
                var leave = @json($onLeave);
                var win = @json($window);
                var maxDays = {{ (int) $maxDays }};
                var $ = function (id) { return document.getElementById(id); };
                var start = $('assign-start'), end = $('assign-end');
                var people = Array.prototype.slice.call(document.querySelectorAll('.assign-person'));
                var rosterSets = {};
                Object.keys(rostered).forEach(function (id) {
                    rosterSets[id] = {};
                    rostered[id].forEach(function (d) { rosterSets[id][d] = true; });
                });

                function pad(n) { return String(n).padStart(2, '0'); }
                function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
                function parse(v) { return new Date(v + 'T00:00:00'); }
                function nice(v) { return parse(v).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' }); }
                function fmt(n) { return (Math.round(n * 100) / 100).toString(); }
                function plural(n, w) { return n + ' ' + w + (n === 1 ? '' : 's'); }
                function onLeave(id, date) {
                    return (leave[id] || []).some(function (l) { return l.from <= date && l.to >= date; });
                }
                function checkedIds() {
                    return people.filter(function (p) { return p.querySelector('input').checked; })
                        .map(function (p) { return p.querySelector('input').value; });
                }
                function selectedDays() {
                    return Array.prototype.map.call(document.querySelectorAll('input[name="days[]"]:checked'), function (i) { return parseInt(i.value, 10); });
                }

                function refresh() {
                    var ids = checkedIds();
                    var radio = document.querySelector('input[name="shift_id"]:checked');
                    var shift = radio ? shifts[radio.value] : null;
                    var mode = (document.querySelector('input[name="mode"]:checked') || {}).value || 'empty';
                    var days = selectedDays();

                    $('picked-count').textContent = ids.length + ' selected';
                    $('sum-who').textContent = !ids.length ? 'No employees selected'
                        : (ids.length === 1 ? employees[ids[0]].name : plural(ids.length, 'employee'));
                    $('sum-shift').textContent = shift ? shift.name + ' (' + shift.code + ') · ' + shift.time : 'No shift selected';
                    $('assign-summary').className = 'leave-summary' + (shift ? ' tone-' + shift.tone : '');

                    // Dates in range on the chosen weekdays.
                    var dates = [], rangeError = '';
                    if (start.value && end.value) {
                        if (end.value < start.value) {
                            rangeError = 'The end date is before the start date.';
                        } else {
                            var span = Math.round((parse(end.value) - parse(start.value)) / 86400000) + 1;
                            if (span > maxDays) {
                                rangeError = 'That is ' + span + ' days - assign at most ' + maxDays + ' days at a time.';
                            } else {
                                for (var d = parse(start.value); iso(d) <= end.value; d.setDate(d.getDate() + 1)) {
                                    if (days.indexOf(d.getDay() || 7) !== -1) dates.push(iso(d));
                                }
                            }
                        }
                        $('sum-dates').textContent = start.value === end.value ? nice(start.value) : nice(start.value) + ' → ' + nice(end.value);
                    } else {
                        $('sum-dates').textContent = 'Pick the dates';
                    }
                    $('sum-range').hidden = !rangeError && !(start.value && end.value && !days.length);
                    $('sum-range').querySelector('span').textContent = rangeError || 'Pick at least one working day.';
                    $('sum-days').textContent = start.value && end.value && !rangeError ? dates.length : '—';

                    var assign = 0, kept = 0, skipped = 0, replaced = 0, otherBranch = [];
                    ids.forEach(function (id) {
                        if (shift && shift.branch_id && String(employees[id].branch_id) !== String(shift.branch_id)) {
                            otherBranch.push(employees[id].name);
                            return;
                        }
                        dates.forEach(function (date) {
                            var has = rosterSets[id] && rosterSets[id][date];
                            if (onLeave(id, date)) { skipped++; return; }
                            if (has && mode === 'empty') { kept++; return; }
                            if (has) replaced++;
                            assign++;
                        });
                    });

                    $('sum-slots').textContent = assign;
                    $('sum-kept').textContent = mode === 'empty' ? kept : '—';
                    $('sum-leave').textContent = skipped;
                    $('sum-hours').textContent = shift ? fmt(assign * shift.hours) + 'h' : '0h';

                    $('sum-replace').hidden = !replaced;
                    $('sum-replace').querySelector('span').textContent = plural(replaced, 'planned shift') + ' will be replaced.';
                    $('sum-branch').hidden = !otherBranch.length;
                    $('sum-branch').querySelector('span').textContent = 'This shift belongs to another branch than ' + otherBranch.slice(0, 3).join(', ')
                        + (otherBranch.length > 3 ? ' and ' + (otherBranch.length - 3) + ' more' : '') + ' - they will be skipped.';

                    var outside = dates.length && (dates[0] < win.from || dates[dates.length - 1] > win.to);
                    $('sum-note').textContent = outside
                        ? 'Planned shifts and leave outside ' + nice(win.from) + ' – ' + nice(win.to) + ' are not previewed; the server still checks them.'
                        : 'Approved leave is always skipped. The server re-checks everything.';

                    $('assign-submit').disabled = !ids.length || !shift || !dates.length || !!rangeError;
                }

                // Employee search / department filter.
                function filterPeople() {
                    var q = $('emp-search').value.trim().toLowerCase();
                    var dept = $('emp-dept') ? $('emp-dept').value : '';
                    var shown = 0;
                    people.forEach(function (p) {
                        var show = (!q || p.dataset.name.indexOf(q) !== -1) && (!dept || p.dataset.dept === dept);
                        p.hidden = !show;
                        if (show) shown++;
                    });
                    $('people-empty').hidden = shown > 0;
                }
                $('emp-search').addEventListener('input', filterPeople);
                if ($('emp-dept')) $('emp-dept').addEventListener('change', filterPeople);
                $('emp-all').addEventListener('click', function () {
                    people.forEach(function (p) { if (!p.hidden) p.querySelector('input').checked = true; });
                    refresh();
                });
                $('emp-none').addEventListener('click', function () {
                    people.forEach(function (p) { p.querySelector('input').checked = false; });
                    refresh();
                });

                document.querySelectorAll('#date-chips .quick-chip').forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        var today = new Date(); today.setHours(0, 0, 0, 0);
                        var monday = new Date(today); monday.setDate(today.getDate() - ((today.getDay() + 6) % 7));
                        var from, to;
                        switch (chip.dataset.range) {
                            case 'this-week': from = new Date(monday); to = new Date(monday); to.setDate(to.getDate() + 6); break;
                            case 'next-week': from = new Date(monday); from.setDate(from.getDate() + 7); to = new Date(from); to.setDate(to.getDate() + 6); break;
                            case 'next-2-weeks': from = new Date(monday); from.setDate(from.getDate() + 7); to = new Date(from); to.setDate(to.getDate() + 13); break;
                            case 'this-month': from = new Date(today); to = new Date(today.getFullYear(), today.getMonth() + 1, 0); break;
                            case 'next-month': from = new Date(today.getFullYear(), today.getMonth() + 1, 1); to = new Date(today.getFullYear(), today.getMonth() + 2, 0); break;
                        }
                        start.value = iso(from); end.value = iso(to);
                        refresh();
                    });
                });
                document.querySelectorAll('[data-days]').forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        var want = chip.dataset.days.split(',');
                        document.querySelectorAll('input[name="days[]"]').forEach(function (i) { i.checked = want.indexOf(i.value) !== -1; });
                        refresh();
                    });
                });

                start.addEventListener('change', function () {
                    if (!end.value || end.value < start.value) end.value = start.value;
                    refresh();
                });
                $('assign-form').addEventListener('change', refresh);
                $('assign-form').addEventListener('submit', function (e) {
                    var replaced = !$('sum-replace').hidden;
                    if (replaced && !confirm($('sum-replace').querySelector('span').textContent + ' Continue?')) e.preventDefault();
                });

                refresh();
            })();
        </script>
    @endpush
@endif
