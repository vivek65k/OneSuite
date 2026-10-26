@php
    use App\Support\ShiftTime;

    $isEditing = $shift->exists;
    $hm = fn ($t) => $t ? substr((string) $t, 0, 5) : '';
    $start = old('start_time', $hm($shift->start_time));
    $end = old('end_time', $hm($shift->end_time));
    $isNight = (bool) old('is_night_shift', $shift->is_night_shift);
    $isFlexible = (bool) old('is_flexible', $shift->is_flexible);
    $isActive = (bool) old('is_active', $isEditing ? $shift->is_active : true);
    $style = $isEditing ? ShiftTime::style($shift) : ['tone' => 'indigo', 'icon' => 'ti-clock', 'label' => 'Shift'];
    $fmt = fn ($n) => $n === null || $n === '' ? '' : rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $cancelUrl = route('admin.shifts.index');
    $presets = [
        ['label' => 'General 9–6', 'name' => 'General Shift', 'code' => 'GEN', 'start' => '09:00', 'end' => '18:00', 'break' => 60],
        ['label' => 'Morning 6–2', 'name' => 'Morning Shift', 'code' => 'MOR', 'start' => '06:00', 'end' => '14:00', 'break' => 30],
        ['label' => 'Evening 2–10', 'name' => 'Evening Shift', 'code' => 'EVE', 'start' => '14:00', 'end' => '22:00', 'break' => 30],
        ['label' => 'Night 10–6', 'name' => 'Night Shift', 'code' => 'NGT', 'start' => '22:00', 'end' => '06:00', 'break' => 45],
    ];
@endphp

<div class="record-header">
    <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
    <span class="list-avatar list-avatar-square record-avatar tone-{{ $style['tone'] }}" id="shift-header-icon" aria-hidden="true"><i class="ti {{ $style['icon'] }} font-20"></i></span>
    <div class="record-header-text">
        <div class="record-header-title">{{ $isEditing ? $shift->name : 'New shift' }}</div>
        <div class="record-header-meta">
            @if ($isEditing)
                <span class="list-code">{{ $shift->code }}</span>
                <span>{{ ShiftTime::format($shift->start_time) }} – {{ ShiftTime::format($shift->end_time) }}</span>
                <span class="status-dot status-dot-{{ $shift->is_active ? 'success' : 'muted' }}">{{ $shift->is_active ? 'Active' : 'Inactive' }}</span>
            @else
                Set the working hours, then assign the shift on the roster.
            @endif
        </div>
    </div>
    <div class="record-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3">
            <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create shift' }}
        </button>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Please fix {{ $errors->count() === 1 ? 'this issue' : 'these '.$errors->count().' issues' }} before saving:</div>
        <ul class="mb-0 ps-3 font-13">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card form-section tone-indigo">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-id"></i></span>
                <div>
                    <h5 class="form-section-title">Basics</h5>
                    <p class="form-section-sub">What the shift is called and its short code on rosters.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $shift->name) }}" maxlength="255" placeholder="e.g. General Shift" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $shift->code) }}" maxlength="50" placeholder="e.g. GEN" required>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-hint" id="code-hint" hidden><i class="ti ti-alert-circle"></i> <span>This code is already used by another shift.</span></div>
                    </div>
                    @unless ($isEditing)
                        <div class="col-12">
                            <div class="quick-chips mt-0">
                                <span class="list-subtext me-1">Start from:</span>
                                @foreach ($presets as $preset)
                                    <button type="button" class="quick-chip" data-preset='@json($preset)'>{{ $preset['label'] }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endunless
                </div>
            </div>
        </div>

        <div class="card form-section tone-teal">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-clock"></i></span>
                <div>
                    <h5 class="form-section-title">Timing</h5>
                    <p class="form-section-sub">When the shift runs. An end time before the start runs past midnight.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="start_time">Starts <span class="text-danger">*</span></label>
                        <input type="time" class="form-control @error('start_time') is-invalid @enderror" id="start_time" name="start_time" value="{{ $start }}" required>
                        @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="end_time">Ends <span class="text-danger">*</span></label>
                        <input type="time" class="form-control @error('end_time') is-invalid @enderror" id="end_time" name="end_time" value="{{ $end }}" required>
                        @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="break_minutes">Break (minutes) <span class="text-danger">*</span></label>
                        <input type="number" min="0" max="600" class="form-control @error('break_minutes') is-invalid @enderror" id="break_minutes" name="break_minutes" value="{{ old('break_minutes', $shift->break_minutes ?? 60) }}" required>
                        @error('break_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="quick-chips">
                            @foreach ([0, 30, 45, 60] as $m)
                                <button type="button" class="quick-chip" data-set="break_minutes" data-value="{{ $m }}">{{ $m }}m</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="shift-preview">
                            <div class="shift-track shift-track-card" id="preview-track"></div>
                            <div class="shift-track-scale"><span>12a</span><span>6a</span><span>12p</span><span>6p</span><span>12a</span></div>
                            <div class="shift-preview-facts">
                                <span><i class="ti ti-clock"></i> On site <strong id="pv-span">—</strong></span>
                                <span><i class="ti ti-briefcase"></i> Working <strong id="pv-net">—</strong></span>
                                <span id="pv-night" hidden><i class="ti ti-moon"></i> <strong>Runs past midnight</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-amber">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-adjustments-horizontal"></i></span>
                <div>
                    <h5 class="form-section-title">Attendance rules</h5>
                    <p class="form-section-sub">How late someone can arrive, and how many hours count as a full or half day.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="grace_minutes">Grace period (minutes) <span class="text-danger">*</span></label>
                        <input type="number" min="0" max="120" class="form-control @error('grace_minutes') is-invalid @enderror" id="grace_minutes" name="grace_minutes" value="{{ old('grace_minutes', $shift->grace_minutes ?? 10) }}" required>
                        @error('grace_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="quick-chips">
                            @foreach ([0, 5, 10, 15] as $m)
                                <button type="button" class="quick-chip" data-set="grace_minutes" data-value="{{ $m }}">{{ $m }}m</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="full_day_hours">Full day (hours)</label>
                        <input type="number" step="0.25" min="0" max="24" class="form-control @error('full_day_hours') is-invalid @enderror" id="full_day_hours" name="full_day_hours" value="{{ $fmt(old('full_day_hours', $shift->full_day_hours)) }}" placeholder="e.g. 8">
                        @error('full_day_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="half_day_hours">Half day (hours)</label>
                        <input type="number" step="0.25" min="0" max="24" class="form-control @error('half_day_hours') is-invalid @enderror" id="half_day_hours" name="half_day_hours" value="{{ $fmt(old('half_day_hours', $shift->half_day_hours)) }}" placeholder="e.g. 4">
                        @error('half_day_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <div class="quick-chips mt-0"><button type="button" class="quick-chip" id="suggest-hours" hidden></button></div>
                    </div>
                </div>

                <div class="toggle-cards mt-3">
                    <label class="toggle-card tone-violet">
                        <input type="checkbox" name="is_night_shift" value="1" id="is_night_shift" @checked($isNight)>
                        <span class="toggle-card-icon"><i class="ti ti-moon"></i></span>
                        <span class="toggle-card-body">
                            <span class="toggle-card-title">Night shift</span>
                            <span class="toggle-card-hint">Set automatically when the shift runs past midnight.</span>
                        </span>
                        <span class="switch-btn" aria-hidden="true"></span>
                    </label>
                    <label class="toggle-card tone-teal">
                        <input type="checkbox" name="is_flexible" value="1" id="is_flexible" @checked($isFlexible)>
                        <span class="toggle-card-icon"><i class="ti ti-arrows-right-left"></i></span>
                        <span class="toggle-card-body">
                            <span class="toggle-card-title">Flexible start</span>
                            <span class="toggle-card-hint">Employees may start within a window around the start time.</span>
                        </span>
                        <span class="switch-btn" aria-hidden="true"></span>
                    </label>
                </div>
                <div class="carry-limit tone-teal" id="flex-window" @unless ($isFlexible) hidden @endunless>
                    <label class="form-label" for="flexible_window_minutes">Flexible window (± minutes)</label>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <input type="number" min="0" max="600" class="form-control @error('flexible_window_minutes') is-invalid @enderror" style="max-width: 140px" id="flexible_window_minutes" name="flexible_window_minutes" value="{{ old('flexible_window_minutes', $shift->flexible_window_minutes) }}" placeholder="e.g. 60">
                        <span class="list-subtext" id="flex-hint"></span>
                    </div>
                    @error('flexible_window_minutes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card form-section tone-violet">
            <div class="form-section-head form-section-head-compact">
                <span class="form-section-icon form-section-icon-sm"><i class="ti ti-power"></i></span>
                <h5 class="form-section-title">Availability</h5>
            </div>
            <div class="form-section-body">
                <label class="toggle-card tone-success mb-0">
                    <input type="checkbox" name="is_active" value="1" id="is_active" @checked($isActive)>
                    <span class="toggle-card-body">
                        <span class="toggle-card-title">Active</span>
                        <span class="toggle-card-hint">Inactive shifts can't be assigned, but past rosters are kept.</span>
                    </span>
                    <span class="switch-btn" aria-hidden="true"></span>
                </label>
            </div>
        </div>

        @if ($isEditing)
            <div class="card form-section tone-slate">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-info-square"></i></span>
                    <h5 class="form-section-title">Usage</h5>
                </div>
                <div class="form-section-body">
                    <dl class="record-meta">
                        <dt>Roster assignments</dt>
                        <dd>{{ $usage['rosters'] }}</dd>
                        <dt>Upcoming</dt>
                        <dd>{{ $usage['upcoming'] }}</dd>
                        <dt>Overtime rules</dt>
                        <dd>{{ $usage['overtime_rules'] }}</dd>
                    </dl>
                    @if ($usage['rosters'])
                        <div class="form-hint"><i class="ti ti-info-circle"></i> Assigned on the roster, so it can't be deleted - deactivate it instead. Time changes apply to all its roster days.</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<div class="record-footer">
    <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-de-primary text-light px-4">
        <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create shift' }}
    </button>
</div>

@push('scripts')
    <script>
        (function () {
            var existingCodes = @json($existingCodes ?? []);
            var $ = function (id) { return document.getElementById(id); };
            var start = $('start_time'), end = $('end_time'), brk = $('break_minutes'), grace = $('grace_minutes'),
                night = $('is_night_shift'), flexible = $('is_flexible'), flexWin = $('flexible_window_minutes'),
                full = $('full_day_hours'), half = $('half_day_hours'), code = $('code'), suggest = $('suggest-hours');

            function mins(v) { if (!v) return null; var p = v.split(':'); return (+p[0]) * 60 + (+p[1]); }
            function fmtH(m) { var h = Math.floor(m / 60), r = m % 60; return h + 'h' + (r ? ' ' + r + 'm' : ''); }
            function clock(m) { m = ((m % 1440) + 1440) % 1440; var h = Math.floor(m / 60), mm = m % 60; return (h % 12 || 12) + ':' + String(mm).padStart(2, '0') + (h < 12 ? ' AM' : ' PM'); }
            function pct(m) { return (m / 1440 * 100).toFixed(3) + '%'; }
            function bar(cls, left, width, title) {
                var el = document.createElement('span');
                el.className = cls; el.style.left = pct(left); el.style.width = pct(width);
                if (title) el.title = title;
                return el;
            }

            function refresh() {
                var s = mins(start.value), e = mins(end.value), track = $('preview-track');
                track.innerHTML = '';
                if (s === null || e === null || s === e) {
                    $('pv-span').textContent = '—'; $('pv-net').textContent = '—'; $('pv-night').hidden = true; suggest.hidden = true;
                    return;
                }
                var crosses = e <= s, span = crosses ? 1440 - s + e : e - s;
                var tone = night.checked || crosses ? 'violet' : 'teal';
                if (crosses) {
                    track.appendChild(bar('shift-track-bar tone-' + tone, s, 1440 - s));
                    track.appendChild(bar('shift-track-bar tone-' + tone, 0, e));
                } else {
                    track.appendChild(bar('shift-track-bar tone-' + tone, s, span));
                }
                var g = parseInt(grace.value, 10) || 0, b = parseInt(brk.value, 10) || 0;
                if (g) track.appendChild(bar('shift-track-grace', s, Math.min(g, span), g + ' min grace'));
                if (b && !crosses && b < span) track.appendChild(bar('shift-track-break', s + (span - b) / 2, b, b + ' min break'));

                var net = Math.max(0, span - b);
                $('pv-span').textContent = fmtH(span);
                $('pv-net').textContent = fmtH(net);
                $('pv-night').hidden = !crosses;
                if (crosses && !night.checked) { night.checked = true; }

                var fullSuggest = Math.round(net / 60 * 4) / 4, halfSuggest = Math.round(fullSuggest / 2 * 4) / 4;
                suggest.hidden = !(net > 0 && (String(parseFloat(full.value)) !== String(fullSuggest) || String(parseFloat(half.value)) !== String(halfSuggest)));
                suggest.textContent = 'Use full day ' + fullSuggest + 'h · half day ' + halfSuggest + 'h (from working hours)';
                suggest.dataset.full = fullSuggest; suggest.dataset.half = halfSuggest;

                $('flex-window').hidden = !flexible.checked;
                var w = parseInt(flexWin.value, 10) || 0;
                $('flex-hint').textContent = flexible.checked && w ? 'Start any time ' + clock(s - w) + ' – ' + clock(s + w) : '';

                var dup = existingCodes.indexOf(code.value.trim().toUpperCase()) !== -1;
                $('code-hint').hidden = !dup;
            }

            [start, end, brk, grace, night, flexible, flexWin, full, half, code].forEach(function (el) {
                el.addEventListener('input', refresh); el.addEventListener('change', refresh);
            });
            suggest.addEventListener('click', function () { full.value = suggest.dataset.full; half.value = suggest.dataset.half; refresh(); });
            document.querySelectorAll('[data-set]').forEach(function (chip) {
                chip.addEventListener('click', function () { $(chip.dataset.set).value = chip.dataset.value; refresh(); });
            });
            document.querySelectorAll('[data-preset]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var p = JSON.parse(chip.dataset.preset);
                    $('name').value = p.name; code.value = p.code; start.value = p.start; end.value = p.end; brk.value = p.break;
                    night.checked = mins(p.end) <= mins(p.start);
                    full.value = ''; half.value = '';
                    refresh();
                    suggest.click();
                });
            });
            refresh();
        })();
    </script>
@endpush
