@php
    $isEditing = $leaveType->exists;
    $style = \App\Support\LeaveTypeStyle::for(old('name', $leaveType->name), old('code', $leaveType->code));
    $isPaid = (bool) old('is_paid', $isEditing ? $leaveType->is_paid : true);
    $carries = (bool) old('carry_forward', $leaveType->carry_forward);
    $isActive = (bool) old('is_active', $isEditing ? $leaveType->is_active : true);
    $days = old('days_per_year', $leaveType->days_per_year);
    $fmt = fn ($n) => $n === null || $n === '' ? '' : rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $cancelUrl = route('admin.leave-types.index');
@endphp

<div class="record-header">
    <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
    <span class="list-avatar list-avatar-square record-avatar tone-{{ $style['tone'] }}" id="header-icon" aria-hidden="true"><i class="ti {{ $style['icon'] }} font-20"></i></span>
    <div class="record-header-text">
        <div class="record-header-title">{{ $isEditing ? $leaveType->name : 'New leave type' }}</div>
        <div class="record-header-meta">
            @if ($isEditing)
                <span class="list-code">{{ $leaveType->code }}</span>
                <span class="status-dot status-dot-{{ $leaveType->is_active ? 'success' : 'muted' }}">{{ $leaveType->is_active ? 'Active' : 'Inactive' }}</span>
            @else
                Define how much leave employees get and the rules around it.
            @endif
        </div>
    </div>
    <div class="record-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3">
            <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create leave type' }}
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
                    <p class="form-section-sub">What this leave is called, and its short code on reports and payslips.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $leaveType->name) }}" placeholder="e.g. Casual Leave" maxlength="255" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @unless ($isEditing)
                            <div class="quick-chips">
                                @foreach (['Casual Leave', 'Sick Leave', 'Earned Leave', 'Maternity Leave', 'Paternity Leave', 'Loss of Pay'] as $preset)
                                    <button type="button" class="quick-chip" data-preset="{{ $preset }}">{{ $preset }}</button>
                                @endforeach
                            </div>
                        @endunless
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $leaveType->code) }}" placeholder="e.g. CL" maxlength="50" required>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-hint" id="code-hint" hidden><i class="ti ti-alert-circle"></i> <span></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-section tone-success">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-calendar-stats"></i></span>
                <div>
                    <h5 class="form-section-title">Entitlement</h5>
                    <p class="form-section-sub">How many days each employee gets per year.</p>
                </div>
            </div>
            <div class="form-section-body">
                <label class="form-label" for="days_per_year">Days per year <span class="text-danger">*</span></label>
                <div class="stepper">
                    <button type="button" class="btn-icon" data-step="-1" aria-label="Fewer days"><i class="ti ti-minus"></i></button>
                    <input type="number" step="0.5" min="0" max="365" class="form-control @error('days_per_year') is-invalid @enderror" id="days_per_year" name="days_per_year" value="{{ $fmt($days) }}" required aria-label="Days per year">
                    <button type="button" class="btn-icon" data-step="1" aria-label="More days"><i class="ti ti-plus"></i></button>
                    <div class="quick-chips mt-0 ms-2">
                        @foreach ([6, 8, 12, 15, 18, 24] as $preset)
                            <button type="button" class="quick-chip" data-days="{{ $preset }}">{{ $preset }}</button>
                        @endforeach
                    </div>
                </div>
                @error('days_per_year')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-hint" id="accrual-hint"><i class="ti ti-info-circle"></i> <span></span></div>
            </div>
        </div>

        <div class="card form-section tone-teal">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-adjustments-horizontal"></i></span>
                <div>
                    <h5 class="form-section-title">Rules</h5>
                    <p class="form-section-sub">Pay treatment and what happens to unused days at year end.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="toggle-cards">
                    <label class="toggle-card tone-success">
                        <input type="checkbox" name="is_paid" value="1" id="is_paid" @checked($isPaid)>
                        <span class="toggle-card-icon"><i class="ti ti-coin"></i></span>
                        <span class="toggle-card-body">
                            <span class="toggle-card-title">Paid leave</span>
                            <span class="toggle-card-hint">Salary continues on these days. Off = loss of pay.</span>
                        </span>
                        <span class="switch-btn" aria-hidden="true"></span>
                    </label>
                    <label class="toggle-card tone-teal">
                        <input type="checkbox" name="carry_forward" value="1" id="carry_forward" @checked($carries)>
                        <span class="toggle-card-icon"><i class="ti ti-arrow-forward"></i></span>
                        <span class="toggle-card-body">
                            <span class="toggle-card-title">Carry forward unused days</span>
                            <span class="toggle-card-hint">Leftover balance rolls into next year.</span>
                        </span>
                        <span class="switch-btn" aria-hidden="true"></span>
                    </label>
                </div>

                <div class="carry-limit" id="carry-limit" @unless ($carries) hidden @endunless>
                    <label class="form-label" for="max_carry_forward_days">Maximum days carried forward</label>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <input type="number" step="0.5" min="0" max="365" class="form-control @error('max_carry_forward_days') is-invalid @enderror" style="max-width: 140px" id="max_carry_forward_days" name="max_carry_forward_days" value="{{ $fmt(old('max_carry_forward_days', $leaveType->max_carry_forward_days)) }}" placeholder="No limit">
                        <span class="list-subtext">Leave empty for no limit.</span>
                    </div>
                    @error('max_carry_forward_days')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Live preview of the card as it will appear on the list --}}
        <div class="policy-card tone-{{ $style['tone'] }} mb-3" id="preview-card">
            <div class="policy-card-head">
                <span class="policy-card-icon" id="preview-icon"><i class="ti {{ $style['icon'] }}"></i></span>
                <div class="min-w-0 flex-grow-1">
                    <div class="policy-preview-label">Preview</div>
                    <h5 class="policy-card-title" id="preview-name">{{ old('name', $leaveType->name) ?: 'Leave type name' }}</h5>
                    <div class="policy-card-sub"><span class="list-code" id="preview-code">{{ old('code', $leaveType->code) ?: 'CODE' }}</span></div>
                </div>
            </div>
            <div class="policy-card-figure">
                <span class="policy-card-days" id="preview-days">{{ $fmt($days) ?: '0' }}</span>
                <span class="policy-card-unit">days / year</span>
            </div>
            <div class="policy-card-rules">
                <span class="rule-chip" id="preview-paid"></span>
                <span class="rule-chip" id="preview-carry"></span>
            </div>
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
                        <span class="toggle-card-hint">Inactive types can't be applied for, but their history is kept.</span>
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
                        <dt>Leave requests</dt>
                        <dd>{{ $usage['requests'] }}</dd>
                        <dt>Employee balances</dt>
                        <dd>{{ $usage['balances'] }}</dd>
                        <dt>Last updated</dt>
                        <dd>{{ $leaveType->updated_at?->diffForHumans() ?? '—' }}</dd>
                    </dl>
                    @if ($usage['requests'] || $usage['balances'])
                        <div class="form-hint"><i class="ti ti-info-circle"></i> In use, so it can't be deleted - deactivate it instead. Changing days per year affects new allocations, not balances already given.</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<div class="record-footer">
    <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
    <button type="submit" class="btn btn-de-primary text-light px-4">
        <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create leave type' }}
    </button>
</div>

@push('scripts')
    <script>
        (function () {
            var rules = @json(\App\Support\LeaveTypeStyle::RULES);
            var fallbackTones = @json(\App\Support\LeaveTypeStyle::FALLBACK_TONES);
            var existingCodes = @json($existingCodes ?? []);
            var $ = function (id) { return document.getElementById(id); };
            var name = $('name'), code = $('code'), days = $('days_per_year'), paid = $('is_paid'),
                carry = $('carry_forward'), maxCarry = $('max_carry_forward_days');
            var codeTouched = code.value.trim() !== '';

            function styleFor(text) {
                var hay = (text || '').toLowerCase();
                for (var keyword in rules) {
                    if (hay.indexOf(keyword) !== -1) return { tone: rules[keyword][0], icon: rules[keyword][1] };
                }
                return { tone: fallbackTones[0], icon: 'ti-calendar-off' };
            }
            function fmt(n) { return (Math.round(n * 100) / 100).toString(); }
            function suggestCode(text) {
                var words = text.trim().split(/\s+/).filter(function (w) { return w && !/^(of|the|and)$/i.test(w); });
                var base = words.map(function (w) { return w[0]; }).join('').toUpperCase().slice(0, 4) || '';
                var candidate = base, n = 2;
                while (candidate && existingCodes.indexOf(candidate) !== -1) { candidate = base + n++; }
                return candidate;
            }
            function setTone(el, tone) {
                el.className = el.className.replace(/\btone-[a-z]+\b/g, '').trim() + ' tone-' + tone;
            }

            function refresh() {
                var style = styleFor(name.value);
                setTone($('preview-card'), style.tone);
                setTone($('header-icon'), style.tone);
                $('preview-icon').innerHTML = '<i class="ti ' + style.icon + '"></i>';
                $('header-icon').innerHTML = '<i class="ti ' + style.icon + ' font-20"></i>';
                $('preview-name').textContent = name.value.trim() || 'Leave type name';
                $('preview-code').textContent = code.value.trim().toUpperCase() || 'CODE';

                var d = parseFloat(days.value) || 0;
                $('preview-days').textContent = fmt(d);
                $('accrual-hint').querySelector('span').textContent = d > 0
                    ? 'About ' + fmt(d / 12) + ' days per month if accrued evenly.'
                    : 'Zero days - useful for unpaid or on-demand leave.';

                var p = $('preview-paid');
                p.className = 'rule-chip ' + (paid.checked ? 'is-yes tone-success' : 'is-no tone-slate');
                p.innerHTML = '<i class="ti ' + (paid.checked ? 'ti-coin' : 'ti-ban') + '"></i> ' + (paid.checked ? 'Paid' : 'Unpaid');

                var c = $('preview-carry');
                c.className = 'rule-chip ' + (carry.checked ? 'is-yes tone-teal' : 'is-no tone-slate');
                c.innerHTML = '<i class="ti ti-arrow-forward"></i> ' + (carry.checked
                    ? 'Carry forward ' + (maxCarry.value ? 'up to ' + fmt(parseFloat(maxCarry.value)) + 'd' : '(no limit)')
                    : 'No carry forward');
                $('carry-limit').hidden = !carry.checked;

                var dup = existingCodes.indexOf(code.value.trim().toUpperCase()) !== -1;
                $('code-hint').hidden = !dup;
                if (dup) $('code-hint').querySelector('span').textContent = 'This code is already used by another leave type.';
            }

            name.addEventListener('input', function () {
                if (!codeTouched) code.value = suggestCode(name.value);
                refresh();
            });
            code.addEventListener('input', function () { codeTouched = code.value.trim() !== ''; refresh(); });
            [days, paid, carry, maxCarry].forEach(function (el) {
                el.addEventListener('input', refresh);
                el.addEventListener('change', refresh);
            });

            document.querySelectorAll('[data-preset]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    name.value = chip.dataset.preset;
                    codeTouched = false;
                    code.value = suggestCode(name.value);
                    if (/loss of pay|unpaid/i.test(name.value)) { paid.checked = false; }
                    refresh();
                });
            });
            document.querySelectorAll('[data-days]').forEach(function (chip) {
                chip.addEventListener('click', function () { days.value = chip.dataset.days; refresh(); });
            });
            document.querySelectorAll('.stepper [data-step]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    days.value = Math.max(0, Math.min(365, (parseFloat(days.value) || 0) + parseFloat(btn.dataset.step)));
                    refresh();
                });
            });

            refresh();
        })();
    </script>
@endpush
