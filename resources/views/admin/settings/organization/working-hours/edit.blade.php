@extends('layouts.admin')

@section('title', 'Working Hours')

@php
    use App\Support\ShiftTime;

    $hm = fn ($t) => $t ? substr((string) $t, 0, 5) : '';
    $rows = $days->map(fn ($day) => [
        'dow' => $day->day_of_week,
        'label' => $day->dayLabel(),
        'working' => (bool) old("days.{$day->day_of_week}.is_working_day", old('days') ? false : $day->is_working_day),
        'start' => old("days.{$day->day_of_week}.start_time", $hm($day->start_time)),
        'end' => old("days.{$day->day_of_week}.end_time", $hm($day->end_time)),
    ]);
    $hoursOf = fn ($r) => $r['working'] && $r['start'] && $r['end'] && $r['end'] > $r['start'] ? (ShiftTime::toMinutes($r['end']) - ShiftTime::toMinutes($r['start'])) / 60 : 0;
    $workingRows = $rows->filter(fn ($r) => $r['working']);
    $totalHours = $rows->sum($hoursOf);
    $offNames = $rows->reject(fn ($r) => $r['working'])->map(fn ($r) => substr($r['label'], 0, 3))->implode(', ');
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
    $presets = [
        ['label' => 'Mon–Fri · 9–6', 'days' => [1, 2, 3, 4, 5], 'start' => '09:00', 'end' => '18:00'],
        ['label' => 'Mon–Sat · 9–6', 'days' => [1, 2, 3, 4, 5, 6], 'start' => '09:00', 'end' => '18:00'],
        ['label' => 'Mon–Fri · 10–7', 'days' => [1, 2, 3, 4, 5], 'start' => '10:00', 'end' => '19:00'],
        ['label' => 'Mon–Fri + half Sat', 'days' => [1, 2, 3, 4, 5, 6], 'start' => '09:00', 'end' => '18:00', 'sat' => ['09:00', '13:00']],
    ];
@endphp

@section('content')
    <x-page-title title="Working Hours" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Working days" :value="$workingRows->count().' / 7'" icon="ti-calendar-stats" tone="indigo" hint="Days a week" data-kpi="days" />
        <x-kpi-card label="Weekly hours" :value="$fmt($totalHours).' h'" icon="ti-clock" tone="teal" :hint="$workingRows->count() ? 'Avg '.$fmt($totalHours / $workingRows->count()).' h a day' : 'No working days'" data-kpi="hours" />
        <x-kpi-card label="Weekly off" :value="$offNames ?: 'None'" icon="ti-calendar-off" tone="pink" :hint="$leavePolicyActive ? 'Skipped when counting leave' : 'Not skipped in leave yet'" :href="route('admin.leave-policies.index')" data-kpi="off" />
        <x-kpi-card label="Opens / closes" :value="$workingRows->isNotEmpty() ? ShiftTime::format($workingRows->min('start')).' – '.ShiftTime::format($workingRows->max('end')) : '—'" icon="ti-building-store" tone="amber" hint="Earliest start, latest end" data-kpi="span" />
    </div>

    <form method="POST" action="{{ route('admin.working-hours.update') }}" id="wh-form">
        @csrf
        @method('PUT')

        <div class="record-header">
            <span class="list-avatar list-avatar-square record-avatar tone-indigo" aria-hidden="true"><i class="ti ti-calendar-time font-20"></i></span>
            <div class="record-header-text">
                <div class="record-header-title">Weekly schedule</div>
                <div class="record-header-meta">
                    <span><i class="ti ti-building-store"></i> {{ $branchName ?? 'All branches' }}</span>
                    <span>Office days and hours - used for weekly offs in leave and as the default working day.</span>
                    <span class="tag tone-amber" id="wh-dirty" hidden><i class="ti ti-pencil"></i>Unsaved changes</span>
                </div>
            </div>
            <div class="record-header-actions">
                <button type="reset" class="btn btn-light btn-sm px-3">Reset</button>
                <button type="submit" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-check font-14 me-1"></i> Save hours</button>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3 font-13">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card form-section tone-indigo mb-0">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-calendar-stats"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Days and hours</h5>
                            <p class="form-section-sub">Switch a day off to make it a weekly off.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="quick-chips mt-0 mb-3">
                            <span class="text-muted font-12 me-1">Quick set:</span>
                            @foreach ($presets as $preset)
                                <button type="button" class="quick-chip" data-preset='@json($preset)'>{{ $preset['label'] }}</button>
                            @endforeach
                        </div>

                        <div class="wh-days">
                            @foreach ($rows as $row)
                                @php $dow = $row['dow']; $isWeekendDay = in_array($dow, [0, 6], true); @endphp
                                <div class="wh-day {{ $row['working'] ? 'is-working' : 'is-off' }} {{ $errors->has("days.{$dow}.start_time") || $errors->has("days.{$dow}.end_time") ? 'has-error' : '' }}" data-dow="{{ $dow }}">
                                    <label class="wh-day-toggle">
                                        <input type="checkbox" name="days[{{ $dow }}][is_working_day]" value="1" @checked($row['working']) data-working>
                                        <span class="switch-btn" aria-hidden="true"></span>
                                        <span class="wh-day-name {{ $isWeekendDay ? 'is-weekend' : '' }}">{{ $row['label'] }}</span>
                                    </label>
                                    <div class="wh-day-times">
                                        <input type="time" class="form-control form-control-sm" name="days[{{ $dow }}][start_time]" value="{{ $row['start'] }}" aria-label="{{ $row['label'] }} start" data-start>
                                        <span class="text-muted">to</span>
                                        <input type="time" class="form-control form-control-sm" name="days[{{ $dow }}][end_time]" value="{{ $row['end'] }}" aria-label="{{ $row['label'] }} end" data-end>
                                    </div>
                                    <div class="wh-day-track">
                                        <div class="shift-track"><span class="shift-track-bar tone-indigo" data-bar></span></div>
                                    </div>
                                    <div class="wh-day-hours" data-hours>—</div>
                                    <span class="wh-day-off">Weekly off</span>
                                    <button type="button" class="btn-icon wh-day-copy" data-copy title="Copy these hours to every working day"><i class="ti ti-copy"></i></button>
                                </div>
                            @endforeach
                        </div>
                        <div class="shift-track-scale wh-scale"><span>12a</span><span>6a</span><span>12p</span><span>6p</span><span>12a</span></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="leave-summary wh-summary" id="wh-summary">
                    <div class="leave-summary-label">Your week</div>
                    <div class="leave-summary-days"><span id="ws-hours">0</span><small> hours</small></div>
                    <div class="leave-summary-dates" id="ws-days">—</div>
                    <div class="wh-week" id="ws-week" aria-hidden="true">
                        @foreach ($rows as $row)
                            <div class="wh-week-col" data-week="{{ $row['dow'] }}">
                                <span class="wh-week-bar"><span></span></span>
                                <span class="wh-week-label">{{ substr($row['label'], 0, 1) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="leave-alert is-warning" id="ws-warn" hidden><i class="ti ti-alert-triangle"></i> <span></span></div>
                </div>

                <div class="card side-list tone-slate mt-3">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-link"></i></span>
                        <span class="fw-semibold">Where this is used</span>
                    </div>
                    <a href="{{ route('admin.leave-policies.index') }}" class="side-list-item">
                        <span class="tag {{ $leavePolicyActive ? 'tone-success' : 'tone-slate' }}"><i class="ti ti-file-certificate"></i></span>
                        <div class="min-w-0">
                            <div class="fw-semibold">Leave policy</div>
                            <div class="list-subtext">{{ $leavePolicyActive ? 'Weekly offs aren\'t counted as leave' : 'Not skipping weekly offs yet' }}</div>
                        </div>
                    </a>
                    <a href="{{ route('admin.shifts.index') }}" class="side-list-item">
                        <span class="tag tone-indigo"><i class="ti ti-clock"></i></span>
                        <div class="min-w-0">
                            <div class="fw-semibold">Shifts</div>
                            <div class="list-subtext">Rostered people follow their shift's hours instead</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="record-footer">
            <button type="reset" class="btn btn-light px-4">Reset</button>
            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Save hours</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var form = document.getElementById('wh-form');
            var rows = Array.prototype.slice.call(document.querySelectorAll('.wh-day'));
            var names = { 0: 'Sun', 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat' };
            var initial = new FormData(form);

            function mins(t) { if (!t) return null; var p = t.split(':'); return parseInt(p[0], 10) * 60 + parseInt(p[1], 10); }
            function fmt(n) { return (Math.round(n * 10) / 10).toString(); }
            function clock(m) { var h = Math.floor(m / 60), mm = m % 60; return ((h % 12) || 12) + ':' + String(mm).padStart(2, '0') + ' ' + (h < 12 ? 'AM' : 'PM'); }
            function setKpi(key, value, hint) {
                var card = document.querySelector('[data-kpi="' + key + '"]');
                if (!card) return;
                card.querySelector('.kpi-card-value').textContent = value;
                var h = card.querySelector('.kpi-card-hint'); if (h && hint !== undefined) h.textContent = hint;
            }

            function refresh() {
                var total = 0, working = [], off = [], earliest = null, latest = null, problems = [];
                rows.forEach(function (row) {
                    var on = row.querySelector('[data-working]').checked;
                    var s = mins(row.querySelector('[data-start]').value), e = mins(row.querySelector('[data-end]').value);
                    var dow = row.dataset.dow, h = 0, bad = false;
                    row.classList.toggle('is-working', on);
                    row.classList.toggle('is-off', !on);
                    if (on) {
                        working.push(names[dow]);
                        if (s === null || e === null) { bad = true; problems.push(names[dow] + ' needs hours'); }
                        else if (e <= s) { bad = true; problems.push(names[dow] + ' ends before it starts'); }
                        else { h = (e - s) / 60; earliest = earliest === null ? s : Math.min(earliest, s); latest = latest === null ? e : Math.max(latest, e); }
                    } else { off.push(names[dow]); }
                    total += h;
                    row.classList.toggle('has-error', bad);
                    var bar = row.querySelector('[data-bar]');
                    bar.style.left = (on && h ? s / 1440 * 100 : 0) + '%';
                    bar.style.width = (on && h ? (e - s) / 1440 * 100 : 0) + '%';
                    row.querySelector('[data-hours]').textContent = on ? (h ? fmt(h) + ' h' : '—') : '';
                    var col = document.querySelector('[data-week="' + dow + '"] .wh-week-bar > span');
                    col.style.height = Math.min(100, h / 12 * 100) + '%';
                    col.parentNode.parentNode.classList.toggle('is-off', !on);
                });

                document.getElementById('ws-hours').textContent = fmt(total);
                document.getElementById('ws-days').textContent = working.length ? working.length + ' working days · off ' + (off.join(', ') || 'none') : 'No working days';
                if (!working.length) problems.unshift('Keep at least one working day');
                if (total > 60) problems.push(fmt(total) + ' hours is a long week');
                var warn = document.getElementById('ws-warn');
                warn.hidden = !problems.length;
                warn.querySelector('span').textContent = problems.join(' · ');

                setKpi('days', working.length + ' / 7');
                setKpi('hours', fmt(total) + ' h', working.length ? 'Avg ' + fmt(total / working.length) + ' h a day' : 'No working days');
                setKpi('off', off.join(', ') || 'None');
                setKpi('span', earliest !== null ? clock(earliest) + ' – ' + clock(latest) : '—');

                // Unsaved-changes badge.
                var now = new FormData(form), dirty = false;
                var keys = new Set(Array.from(initial.keys()).concat(Array.from(now.keys())));
                keys.forEach(function (k) { if (k !== '_token' && k !== '_method' && initial.get(k) !== now.get(k)) dirty = true; });
                document.getElementById('wh-dirty').hidden = !dirty;
            }

            document.querySelectorAll('[data-preset]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var p = JSON.parse(chip.dataset.preset);
                    rows.forEach(function (row) {
                        var dow = parseInt(row.dataset.dow, 10), on = p.days.indexOf(dow) !== -1;
                        row.querySelector('[data-working]').checked = on;
                        if (on) {
                            var sat = dow === 6 && p.sat;
                            row.querySelector('[data-start]').value = sat ? p.sat[0] : p.start;
                            row.querySelector('[data-end]').value = sat ? p.sat[1] : p.end;
                        }
                    });
                    refresh();
                });
            });
            document.querySelectorAll('[data-copy]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var src = btn.closest('.wh-day'), s = src.querySelector('[data-start]').value, e = src.querySelector('[data-end]').value;
                    rows.forEach(function (row) {
                        if (row.querySelector('[data-working]').checked) { row.querySelector('[data-start]').value = s; row.querySelector('[data-end]').value = e; }
                    });
                    refresh();
                });
            });
            form.addEventListener('input', refresh);
            form.addEventListener('change', refresh);
            form.addEventListener('reset', function () { setTimeout(refresh, 0); });
            refresh();
        })();
    </script>
@endpush
