@extends('layouts.admin')

@section('title', 'Attendance Rules')

@php
    use App\Support\ShiftTime;

    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $grace = (int) old('late_grace_minutes', $setting->late_grace_minutes);
    $half = old('half_day_threshold_hours', $fmt($setting->half_day_threshold_hours));
    $full = old('full_day_threshold_hours', $fmt($setting->full_day_threshold_hours));
    $weekend = old('weekend_overtime_multiplier', $fmt($setting->weekend_overtime_multiplier));
    $autoAbsent = (bool) old('auto_mark_absent_if_no_checkin', $setting->auto_mark_absent_if_no_checkin);
    $exampleShift = $shifts->first();
    $exampleStart = $exampleShift ? ShiftTime::toMinutes($exampleShift->start_time) : 540;

    // Shifts with their own grace / day-length settings take precedence over these defaults.
    $overrides = $shifts->filter(fn ($s) => (int) $s->grace_minutes !== (int) $setting->late_grace_minutes
        || ($s->full_day_hours !== null && (float) $s->full_day_hours !== (float) $setting->full_day_threshold_hours)
        || ($s->half_day_hours !== null && (float) $s->half_day_hours !== (float) $setting->half_day_threshold_hours));
@endphp

@section('content')
    <x-page-title title="Attendance Rules" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.attendance-rules.update') }}" id="rules-form">
        @csrf
        @method('PUT')

        <div class="record-header">
            <span class="list-avatar list-avatar-square record-avatar tone-teal" aria-hidden="true"><i class="ti ti-clipboard-check font-20"></i></span>
            <div class="record-header-text">
                <div class="record-header-title">Attendance rules</div>
                <div class="record-header-meta">
                    <span><i class="ti ti-building-store"></i> {{ $branchName ?? 'All branches' }}</span>
                    @if ($setting->exists)
                        <span>Last saved {{ $setting->updated_at?->diffForHumans() }}</span>
                    @else
                        <span class="status-dot status-dot-muted">Using defaults - not saved yet</span>
                    @endif
                    @unless ($canEdit)
                        <span class="tag tag-outline tone-slate"><i class="ti ti-lock"></i>View only</span>
                    @endunless
                </div>
            </div>
            @if ($canEdit)
                <div class="record-header-actions">
                    <button type="reset" class="btn btn-light btn-sm px-3" id="rules-reset">Reset</button>
                    <button type="submit" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-check font-14 me-1"></i> Save rules</button>
                </div>
            @endif
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Please fix {{ $errors->count() === 1 ? 'this issue' : 'these '.$errors->count().' issues' }} before saving:</div>
                <ul class="mb-0 ps-3 font-13">
                    @foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach
                </ul>
            </div>
        @endif

        <fieldset @disabled(! $canEdit)>
            <div class="row g-3">
                <div class="col-lg-8">
                    {{-- Late arrivals --}}
                    <div class="card form-section tone-amber">
                        <div class="form-section-head">
                            <span class="form-section-icon"><i class="ti ti-clock"></i></span>
                            <div>
                                <h5 class="form-section-title">Late arrivals</h5>
                                <p class="form-section-sub">Check-ins within the grace period after the shift starts aren't marked late.</p>
                            </div>
                        </div>
                        <div class="form-section-body">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <div class="stepper">
                                    <button type="button" class="btn-icon" data-step="-5" data-target="late_grace_minutes" aria-label="Less grace"><i class="ti ti-minus"></i></button>
                                    <input type="number" min="0" max="180" step="1" class="form-control @error('late_grace_minutes') is-invalid @enderror" id="late_grace_minutes" name="late_grace_minutes" value="{{ $grace }}" required aria-label="Grace minutes">
                                    <button type="button" class="btn-icon" data-step="5" data-target="late_grace_minutes" aria-label="More grace"><i class="ti ti-plus"></i></button>
                                </div>
                                <span class="text-muted font-13">minutes grace</span>
                                <div class="quick-chips mt-0 ms-2">
                                    @foreach ([0, 5, 10, 15, 30] as $preset)
                                        <button type="button" class="quick-chip" data-set="late_grace_minutes" data-value="{{ $preset }}">{{ $preset ? $preset.' min' : 'None' }}</button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rule-timeline" aria-hidden="true">
                                <div class="rule-timeline-track">
                                    <span class="rule-timeline-ontime"></span>
                                    <span class="rule-timeline-grace" id="tl-grace"></span>
                                    <span class="rule-timeline-late" id="tl-late"></span>
                                </div>
                                <div class="rule-timeline-labels">
                                    <span>Shift starts <strong>{{ ShiftTime::format(sprintf('%02d:%02d', intdiv($exampleStart, 60), $exampleStart % 60)) }}</strong></span>
                                    <span class="text-danger">Late from <strong id="tl-late-at">—</strong></span>
                                </div>
                                <div class="form-hint mt-1"><i class="ti ti-info-circle"></i> Example uses {{ $exampleShift ? $exampleShift->name : 'a 9:00 AM start' }}. The timeline covers the first hour.</div>
                            </div>
                        </div>
                    </div>

                    {{-- Day length --}}
                    <div class="card form-section tone-teal">
                        <div class="form-section-head">
                            <span class="form-section-icon"><i class="ti ti-ruler-2"></i></span>
                            <div>
                                <h5 class="form-section-title">What counts as a day</h5>
                                <p class="form-section-sub">Hours worked decide whether a day is a full day, a half day or too short.</p>
                            </div>
                        </div>
                        <div class="form-section-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="half_day_threshold_hours">Half day from</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="stepper">
                                            <button type="button" class="btn-icon" data-step="-0.5" data-target="half_day_threshold_hours" aria-label="Fewer hours"><i class="ti ti-minus"></i></button>
                                            <input type="number" min="0.5" max="23.5" step="0.25" class="form-control @error('half_day_threshold_hours') is-invalid @enderror" id="half_day_threshold_hours" name="half_day_threshold_hours" value="{{ $half }}" required>
                                            <button type="button" class="btn-icon" data-step="0.5" data-target="half_day_threshold_hours" aria-label="More hours"><i class="ti ti-plus"></i></button>
                                        </div>
                                        <span class="text-muted font-13">hours</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="full_day_threshold_hours">Full day from</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="stepper">
                                            <button type="button" class="btn-icon" data-step="-0.5" data-target="full_day_threshold_hours" aria-label="Fewer hours"><i class="ti ti-minus"></i></button>
                                            <input type="number" min="1" max="24" step="0.25" class="form-control @error('full_day_threshold_hours') is-invalid @enderror" id="full_day_threshold_hours" name="full_day_threshold_hours" value="{{ $full }}" required>
                                            <button type="button" class="btn-icon" data-step="0.5" data-target="full_day_threshold_hours" aria-label="More hours"><i class="ti ti-plus"></i></button>
                                        </div>
                                        <span class="text-muted font-13">hours</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="quick-chips mt-0">
                                        @foreach ([[4, 8], [4.5, 9], [4, 7.5], [3, 6]] as [$h, $f])
                                            <button type="button" class="quick-chip" data-half="{{ $h }}" data-full="{{ $f }}">{{ $h }} h / {{ $f }} h</button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="day-scale" aria-hidden="true">
                                        <span class="day-scale-short" id="ds-short"><span>Too short</span></span>
                                        <span class="day-scale-half" id="ds-half"><span>Half day</span></span>
                                        <span class="day-scale-full" id="ds-full"><span>Full day</span></span>
                                    </div>
                                    <div class="ot-day-scale"><span>0h</span><span>3h</span><span>6h</span><span>9h</span><span>12h</span></div>
                                    <div class="form-hint text-danger" id="day-order" hidden><i class="ti ti-alert-circle"></i> <span>A full day must need more hours than a half day.</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Absence + weekend --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card form-section tone-rose h-100 mb-0">
                                <div class="form-section-head form-section-head-compact">
                                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-user-x"></i></span>
                                    <h5 class="form-section-title">No check-in</h5>
                                </div>
                                <div class="form-section-body">
                                    <label class="toggle-card tone-rose mb-0">
                                        <input type="checkbox" name="auto_mark_absent_if_no_checkin" value="1" id="auto_mark_absent_if_no_checkin" @checked($autoAbsent)>
                                        <span class="toggle-card-body">
                                            <span class="toggle-card-title">Mark absent automatically</span>
                                            <span class="toggle-card-hint">Rostered days with no check-in become Absent, unless on leave or a holiday.</span>
                                        </span>
                                        <span class="switch-btn" aria-hidden="true"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card form-section tone-violet h-100 mb-0">
                                <div class="form-section-head form-section-head-compact">
                                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-calendar-stats"></i></span>
                                    <h5 class="form-section-title">Weekend work</h5>
                                </div>
                                <div class="form-section-body">
                                    <div class="ot-rates">
                                        @foreach (['1' => 'Normal rate', '1.5' => 'Time and a half', '2' => 'Double time'] as $value => $label)
                                            <button type="button" class="ot-rate ot-rate-sm" data-set="weekend_overtime_multiplier" data-value="{{ $value }}"><strong>{{ $value }}×</strong><span>{{ $label }}</span></button>
                                        @endforeach
                                    </div>
                                    <div class="input-group input-group-sm mt-2" style="max-width: 160px">
                                        <span class="input-group-text">Rate</span>
                                        <input type="number" min="1" max="10" step="0.05" class="form-control @error('weekend_overtime_multiplier') is-invalid @enderror" id="weekend_overtime_multiplier" name="weekend_overtime_multiplier" value="{{ $weekend }}" required>
                                        <span class="input-group-text">×</span>
                                    </div>
                                    <div class="form-hint"><i class="ti ti-info-circle"></i> Weekday overtime uses <a href="{{ route('admin.overtime-rules.index') }}">Overtime Rules</a> ({{ $overtimeRules }} active).</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="leave-summary rules-impact" id="impact">
                        <div class="leave-summary-label">Impact on the last {{ $sampleDays }} days</div>
                        @if (count($sample))
                            <div class="leave-summary-who"><span id="im-count">{{ count($sample) }}</span> attendance days</div>
                            <div class="leave-summary-type">How these rules would classify them</div>

                            <div class="impact-group">
                                <div class="impact-head"><span>Arrival</span><span id="im-arrival-n"></span></div>
                                <div class="impact-bar" id="im-arrival"></div>
                                <div class="impact-legend">
                                    <span><i class="impact-dot is-good"></i> On time <strong id="im-ontime">0</strong></span>
                                    <span><i class="impact-dot is-bad"></i> Late <strong id="im-late">0</strong></span>
                                </div>
                            </div>

                            <div class="impact-group">
                                <div class="impact-head"><span>Day length</span><span id="im-length-n"></span></div>
                                <div class="impact-bar" id="im-length"></div>
                                <div class="impact-legend">
                                    <span><i class="impact-dot is-good"></i> Full <strong id="im-full">0</strong></span>
                                    <span><i class="impact-dot is-warn"></i> Half <strong id="im-half">0</strong></span>
                                    <span><i class="impact-dot is-bad"></i> Short <strong id="im-short">0</strong></span>
                                </div>
                            </div>
                            <div class="leave-summary-note text-start mt-2">Updates as you change the numbers. Days without a roster or a check-out are left out of that bar.</div>
                        @else
                            <div class="leave-summary-who">No recent attendance</div>
                            <div class="leave-summary-type">Once check-ins are recorded, this shows how many days would count as late, half or full.</div>
                        @endif
                    </div>

                    <div class="card side-list tone-indigo mt-3">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-clock"></i></span>
                            <span class="fw-semibold">Shift overrides</span>
                            <span class="side-list-count">{{ $overrides->count() }}</span>
                        </div>
                        @if ($overrides->isEmpty())
                            <div class="side-list-empty"><i class="ti ti-circle-check"></i> Every active shift uses these rules.</div>
                        @else
                            <div>
                                @foreach ($overrides as $shift)
                                    @php $style = ShiftTime::style($shift); @endphp
                                    <div class="side-list-item">
                                        <span class="roster-option-code tone-{{ $style['tone'] }}">{{ $shift->code }}</span>
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">{{ $shift->name }}</div>
                                            <div class="list-subtext">
                                                {{ (int) $shift->grace_minutes }} min grace
                                                @if ($shift->full_day_hours) · full {{ $fmt($shift->full_day_hours) }} h @endif
                                                @if ($shift->half_day_hours) · half {{ $fmt($shift->half_day_hours) }} h @endif
                                            </div>
                                        </div>
                                        @can('update', $shift)
                                            <a href="{{ route('admin.shifts.edit', $shift) }}" class="btn-icon ms-auto" title="Edit shift"><i class="ti ti-pencil font-16"></i></a>
                                        @endcan
                                    </div>
                                @endforeach
                            </div>
                            <div class="px-3 py-2 list-subtext">A shift's own settings win over these defaults.</div>
                        @endif
                    </div>
                </div>
            </div>
        </fieldset>

        @if ($canEdit)
            <div class="record-footer">
                <button type="reset" class="btn btn-light px-4">Reset</button>
                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Save rules</button>
            </div>
        @endif
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var sample = @json($sample);
            var $ = function (id) { return document.getElementById(id); };
            var grace = $('late_grace_minutes'), half = $('half_day_threshold_hours'), full = $('full_day_threshold_hours'), weekend = $('weekend_overtime_multiplier');
            var start = {{ $exampleStart }};

            function fmt(n) { return (Math.round(n * 100) / 100).toString(); }
            function clock(m) { m = ((m % 1440) + 1440) % 1440; var h = Math.floor(m / 60), mm = m % 60; return ((h % 12) || 12) + ':' + String(mm).padStart(2, '0') + (h < 12 ? ' AM' : ' PM'); }
            function pct(v, max) { return Math.max(0, Math.min(100, v / max * 100)) + '%'; }
            function bar(el, parts, total) {
                el.innerHTML = '';
                parts.forEach(function (p) {
                    if (!p.n) return;
                    var s = document.createElement('span');
                    s.className = 'impact-part ' + p.cls;
                    s.style.width = (p.n / total * 100) + '%';
                    s.title = p.label + ': ' + p.n;
                    el.appendChild(s);
                });
            }

            function refresh() {
                var g = parseInt(grace.value, 10) || 0, h = parseFloat(half.value) || 0, f = parseFloat(full.value) || 0;

                // Grace timeline over the first hour after the start.
                $('tl-grace').style.width = pct(Math.min(g, 60), 60);
                $('tl-late').style.left = pct(Math.min(g, 60), 60);
                $('tl-late-at').textContent = clock(start + g + 1);

                // Day scale 0-12 h.
                $('ds-short').style.width = pct(h, 12);
                $('ds-half').style.left = pct(h, 12);
                $('ds-half').style.width = pct(Math.max(0, f - h), 12);
                $('ds-full').style.left = pct(f, 12);
                $('day-order').hidden = f > h;

                document.querySelectorAll('[data-set="weekend_overtime_multiplier"]').forEach(function (b) {
                    b.classList.toggle('is-active', parseFloat(b.dataset.value) === parseFloat(weekend.value));
                });

                if (!sample.length) return;
                var onTime = 0, late = 0, fullN = 0, halfN = 0, shortN = 0;
                sample.forEach(function (d) {
                    if (d.l !== null) { if (d.l > g) late++; else onTime++; }
                    if (d.w !== null) { if (d.w >= f) fullN++; else if (d.w >= h) halfN++; else shortN++; }
                });
                $('im-ontime').textContent = onTime; $('im-late').textContent = late;
                $('im-full').textContent = fullN; $('im-half').textContent = halfN; $('im-short').textContent = shortN;
                $('im-arrival-n').textContent = (onTime + late) ? Math.round(late / (onTime + late) * 100) + '% late' : 'no rostered days';
                $('im-length-n').textContent = (fullN + halfN + shortN) ? Math.round(fullN / (fullN + halfN + shortN) * 100) + '% full days' : 'no check-outs';
                bar($('im-arrival'), [{ n: onTime, cls: 'is-good', label: 'On time' }, { n: late, cls: 'is-bad', label: 'Late' }], Math.max(1, onTime + late));
                bar($('im-length'), [{ n: fullN, cls: 'is-good', label: 'Full' }, { n: halfN, cls: 'is-warn', label: 'Half' }, { n: shortN, cls: 'is-bad', label: 'Short' }], Math.max(1, fullN + halfN + shortN));
            }

            document.querySelectorAll('[data-step]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var input = $(btn.dataset.target);
                    var v = (parseFloat(input.value) || 0) + parseFloat(btn.dataset.step);
                    input.value = fmt(Math.max(parseFloat(input.min), Math.min(parseFloat(input.max), v)));
                    refresh();
                });
            });
            document.querySelectorAll('[data-set]').forEach(function (btn) {
                btn.addEventListener('click', function () { $(btn.dataset.set).value = btn.dataset.value; refresh(); });
            });
            document.querySelectorAll('[data-half]').forEach(function (btn) {
                btn.addEventListener('click', function () { half.value = btn.dataset.half; full.value = btn.dataset.full; refresh(); });
            });
            [grace, half, full, weekend].forEach(function (el) { el.addEventListener('input', refresh); });
            $('rules-form').addEventListener('reset', function () { setTimeout(refresh, 0); });
            refresh();
        })();
    </script>
@endpush
