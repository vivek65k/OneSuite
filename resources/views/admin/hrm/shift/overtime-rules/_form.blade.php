@php
    use App\Support\ShiftTime;

    $isEditing = $overtimeRule->exists;
    $fmt = fn ($n) => $n === null || $n === '' ? '' : rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $selectedShift = (string) old('shift_id', $isEditing ? $overtimeRule->shift_id : request('shift_id'));
    $threshold = old('daily_threshold_hours', $fmt($overtimeRule->daily_threshold_hours) ?: '');
    $multiplier = old('multiplier', $fmt($overtimeRule->multiplier ?? 1.5));
    $isActive = (bool) old('is_active', $isEditing ? $overtimeRule->is_active : true);
    $cancelUrl = route('admin.overtime-rules.index');
    $rates = ['1.25' => 'Premium', '1.5' => 'Time and a half', '2' => 'Double time', '2.5' => 'Double and a half', '3' => 'Triple time'];
    $shiftPayload = $shifts->mapWithKeys(fn ($s) => [$s->id => ['name' => $s->name, 'net' => ShiftTime::netHours($s)]]);
@endphp

<div class="record-header">
    <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
    <span class="list-avatar list-avatar-square record-avatar tone-amber" aria-hidden="true"><i class="ti ti-alarm font-20"></i></span>
    <div class="record-header-text">
        <div class="record-header-title">{{ $isEditing ? $overtimeRule->name : 'New overtime rule' }}</div>
        <div class="record-header-meta">
            @if ($isEditing)
                <span>{{ $overtimeRule->shift?->name ?? 'All shifts' }}</span>
                <span>After {{ $fmt($overtimeRule->daily_threshold_hours) }} h at {{ $fmt($overtimeRule->multiplier) }}×</span>
                <span class="status-dot status-dot-{{ $overtimeRule->is_active ? 'success' : 'muted' }}">{{ $overtimeRule->is_active ? 'Active' : 'Inactive' }}</span>
            @else
                Decide when a working day turns into overtime, and how it is paid.
            @endif
        </div>
    </div>
    <div class="record-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3">
            <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create rule' }}
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
                    <h5 class="form-section-title">Name</h5>
                    <p class="form-section-sub">How the rule shows up on reports.</p>
                </div>
            </div>
            <div class="form-section-body">
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $overtimeRule->name) }}" maxlength="255" placeholder="e.g. Standard overtime" required aria-label="Rule name">
                <div class="quick-chips">
                    @foreach (['Standard overtime', 'Night shift overtime', 'Weekend overtime', 'Holiday overtime'] as $suggestion)
                        <button type="button" class="quick-chip" data-name="{{ $suggestion }}">{{ $suggestion }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card form-section tone-teal">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-clock"></i></span>
                <div>
                    <h5 class="form-section-title">Applies to</h5>
                    <p class="form-section-sub">A shift's own rule takes priority over the all-shifts rule. Only one active rule per choice.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="ot-shift-picker" role="radiogroup" aria-label="Applies to">
                    <label class="ot-shift-option tone-violet">
                        <input type="radio" name="shift_id" value="" @checked($selectedShift === '')>
                        <span class="ot-shift-option-code"><i class="ti ti-layers-intersect"></i></span>
                        <span class="ot-shift-option-body">
                            <span class="ot-shift-option-name">All shifts</span>
                            <span class="ot-shift-option-meta">Default for shifts without their own rule</span>
                            @if (isset($taken['all']))
                                <span class="ot-shift-option-taken"><i class="ti ti-alert-triangle"></i> Active: {{ $taken['all'] }}</span>
                            @endif
                        </span>
                    </label>
                    @foreach ($shifts as $shift)
                        @php $style = ShiftTime::style($shift); @endphp
                        <label class="ot-shift-option tone-{{ $style['tone'] }} {{ $shift->is_active ? '' : 'is-inactive' }}">
                            <input type="radio" name="shift_id" value="{{ $shift->id }}" @checked($selectedShift === (string) $shift->id)>
                            <span class="ot-shift-option-code">{{ $shift->code }}</span>
                            <span class="ot-shift-option-body">
                                <span class="ot-shift-option-name">{{ $shift->name }}{{ $shift->is_active ? '' : ' (inactive)' }}</span>
                                <span class="ot-shift-option-meta">{{ ShiftTime::format($shift->start_time) }}–{{ ShiftTime::format($shift->end_time) }} · {{ $fmt(ShiftTime::netHours($shift)) }} h work</span>
                                @if (isset($taken[(string) $shift->id]))
                                    <span class="ot-shift-option-taken"><i class="ti ti-alert-triangle"></i> Active: {{ $taken[(string) $shift->id] }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card form-section tone-amber">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-alarm"></i></span>
                <div>
                    <h5 class="form-section-title">When overtime starts</h5>
                    <p class="form-section-sub">Hours worked in a day beyond this count as overtime.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <div class="stepper">
                        <button type="button" class="btn-icon" data-step="-0.5" aria-label="Fewer hours"><i class="ti ti-minus"></i></button>
                        <input type="number" step="0.25" min="1" max="23.75" class="form-control @error('daily_threshold_hours') is-invalid @enderror" id="daily_threshold_hours" name="daily_threshold_hours" value="{{ $threshold }}" required aria-label="Daily threshold in hours">
                        <button type="button" class="btn-icon" data-step="0.5" aria-label="More hours"><i class="ti ti-plus"></i></button>
                    </div>
                    <span class="text-muted font-13">hours a day</span>
                    <div class="quick-chips mt-0 ms-2">
                        @foreach ([8, 8.5, 9, 10, 12] as $preset)
                            <button type="button" class="quick-chip" data-threshold="{{ $preset }}">{{ $preset }} h</button>
                        @endforeach
                        <button type="button" class="quick-chip" id="threshold-match" hidden>Match shift</button>
                    </div>
                </div>
                <div class="form-hint" id="threshold-hint" hidden><i class="ti ti-alert-triangle"></i> <span></span></div>
            </div>
        </div>

        <div class="card form-section tone-rose mb-0">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-trending-up"></i></span>
                <div>
                    <h5 class="form-section-title">How it is paid</h5>
                    <p class="form-section-sub">Each overtime hour is paid at this multiple of the normal hourly rate.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="ot-rates">
                    @foreach ($rates as $value => $label)
                        <button type="button" class="ot-rate {{ (float) $multiplier === (float) $value ? 'is-active' : '' }}" data-rate="{{ $value }}">
                            <strong>{{ $value }}×</strong><span>{{ $label }}</span>
                        </button>
                    @endforeach
                    <div class="ot-rate-custom">
                        <label class="form-label font-12 mb-1" for="multiplier">Custom</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.05" min="1" max="10" class="form-control @error('multiplier') is-invalid @enderror" id="multiplier" name="multiplier" value="{{ $multiplier }}" required>
                            <span class="input-group-text">×</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="leave-summary tone-amber mb-3" id="ot-preview">
            <div class="leave-summary-label">Rule preview</div>
            <div class="leave-summary-who" id="pv-scope">All shifts</div>
            <div class="leave-summary-type" id="pv-rule">Overtime after — h at —×</div>

            <div class="ot-preview-day">
                <span class="ot-day-regular" id="pv-regular"></span>
                <span class="ot-day-overtime" id="pv-overtime"></span>
                <span class="ot-preview-worked" id="pv-worked-mark"></span>
            </div>
            <div class="ot-day-scale is-light"><span>0h</span><span>4h</span><span>8h</span><span>12h</span><span>16h</span></div>

            <div class="leave-balance">
                <label class="d-flex justify-content-between font-13 mb-1" for="pv-worked"><span>If someone works</span><strong id="pv-worked-label">10 h</strong></label>
                <input type="range" class="form-range ot-range" id="pv-worked" min="0" max="16" step="0.5" value="10">
                <div class="leave-balance-row"><span>Overtime hours</span><strong id="pv-ot">—</strong></div>
                <div class="leave-balance-row"><span>Paid as</span><strong id="pv-paid">—</strong></div>
                <div class="leave-balance-row is-total">
                    <span>At hourly rate <input type="number" min="0" step="1" class="ot-rate-input" id="pv-rate" placeholder="rate" aria-label="Hourly rate"></span>
                    <strong id="pv-amount">—</strong>
                </div>
            </div>
            <div class="leave-alert is-warning" id="pv-clash" hidden><i class="ti ti-alert-triangle"></i> <span></span></div>
        </div>

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
                        <span class="toggle-card-hint">Only active rules are applied to overtime.</span>
                    </span>
                    <span class="switch-btn" aria-hidden="true"></span>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="record-footer">
    <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-de-primary text-light px-4">
        <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create rule' }}
    </button>
</div>

@push('scripts')
    <script>
        (function () {
            var shifts = @json($shiftPayload);
            var taken = @json($taken);
            var $ = function (id) { return document.getElementById(id); };
            var threshold = $('daily_threshold_hours'), multiplier = $('multiplier'), worked = $('pv-worked'), rate = $('pv-rate'), active = $('is_active');

            function fmt(n) { return (Math.round(n * 100) / 100).toString(); }
            function pct(h) { return Math.max(0, Math.min(100, h / 16 * 100)) + '%'; }
            function scope() { var r = document.querySelector('input[name="shift_id"]:checked'); return r ? r.value : ''; }

            function refresh() {
                var t = parseFloat(threshold.value), m = parseFloat(multiplier.value), w = parseFloat(worked.value);
                var sid = scope(), shift = shifts[sid];
                $('pv-scope').textContent = shift ? shift.name : 'All shifts';
                $('pv-rule').textContent = 'Overtime after ' + (isNaN(t) ? '—' : fmt(t)) + ' h at ' + (isNaN(m) ? '—' : fmt(m)) + '×';
                $('pv-regular').style.width = isNaN(t) ? '0' : pct(t);
                $('pv-overtime').style.left = isNaN(t) ? '100%' : pct(t);
                $('pv-worked-mark').style.left = pct(w);
                $('pv-worked-label').textContent = fmt(w) + ' h';

                var ot = isNaN(t) ? 0 : Math.max(0, w - t), paid = isNaN(m) ? 0 : ot * m;
                $('pv-ot').textContent = fmt(ot) + ' h';
                $('pv-paid').textContent = fmt(paid) + ' h of pay';
                var r = parseFloat(rate.value);
                $('pv-amount').textContent = isNaN(r) ? '—' : (paid * r).toLocaleString(undefined, { maximumFractionDigits: 2 });

                // Threshold below the shift's own working hours turns normal hours into overtime.
                var early = shift && !isNaN(t) && t < shift.net;
                $('threshold-hint').hidden = !early;
                if (early) $('threshold-hint').querySelector('span').textContent = shift.name + ' already has ' + fmt(shift.net) + ' working hours - a lower threshold would count normal hours as overtime.';
                $('threshold-match').hidden = !shift;
                if (shift) { $('threshold-match').textContent = 'Match shift (' + fmt(shift.net) + ' h)'; $('threshold-match').dataset.threshold = shift.net; }

                var clash = active.checked ? taken[sid === '' ? 'all' : sid] : null;
                $('pv-clash').hidden = !clash;
                if (clash) $('pv-clash').querySelector('span').textContent = '“' + clash + '” is already active for this - save this rule as inactive, or deactivate that one first.';

                document.querySelectorAll('.ot-rate').forEach(function (b) { b.classList.toggle('is-active', parseFloat(b.dataset.rate) === m); });
            }

            document.querySelectorAll('[data-step]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var v = (parseFloat(threshold.value) || 0) + parseFloat(btn.dataset.step);
                    threshold.value = fmt(Math.max(1, Math.min(23.75, v)));
                    refresh();
                });
            });
            document.querySelectorAll('[data-threshold]').forEach(function (chip) {
                chip.addEventListener('click', function () { threshold.value = chip.dataset.threshold; refresh(); });
            });
            document.querySelectorAll('[data-rate]').forEach(function (b) {
                b.addEventListener('click', function () { multiplier.value = b.dataset.rate; refresh(); });
            });
            document.querySelectorAll('[data-name]').forEach(function (chip) {
                chip.addEventListener('click', function () { $('name').value = chip.dataset.name; $('name').focus(); });
            });
            [threshold, multiplier, worked, rate].forEach(function (el) { el.addEventListener('input', refresh); });
            document.querySelectorAll('input[name="shift_id"], #is_active').forEach(function (el) {
                el.addEventListener('change', function () {
                    // A fresh rule for a shift starts at that shift's working hours.
                    var shift = shifts[scope()];
                    if (el.name === 'shift_id' && shift && !threshold.value) threshold.value = fmt(shift.net);
                    refresh();
                });
            });
            if (!threshold.value) threshold.value = shifts[scope()] ? fmt(shifts[scope()].net) : '9';
            refresh();
        })();
    </script>
@endpush
