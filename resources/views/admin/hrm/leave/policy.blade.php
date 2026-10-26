@extends('layouts.admin')

@section('title', 'Leave Policy')

@php
    $v = fn ($key) => old($key, $setting->{$key});
    $flag = fn ($key) => (bool) old($key, $setting->{$key});
    $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];
    $offDays = collect($dayNames)->filter(fn ($name, $dow) => ! ($policy->workingDays[$dow] ?? true))->values();
@endphp

@section('content')
    <x-page-title title="Leave Policy" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.leave-policies.update') }}" id="policy-form">
        @csrf
        @method('PUT')

        <div class="record-header">
            <span class="list-avatar list-avatar-square record-avatar tone-violet" aria-hidden="true"><i class="ti ti-file-certificate font-20"></i></span>
            <div class="record-header-text">
                <div class="record-header-title">Leave policy</div>
                <div class="record-header-meta">
                    <span><i class="ti ti-building-store"></i> {{ $branchName ?? 'All branches' }}</span>
                    @if ($setting->exists)
                        <span class="status-dot status-dot-success">Active · saved {{ $setting->updated_at?->diffForHumans() }}</span>
                    @else
                        <span class="status-dot status-dot-muted">Not active yet</span>
                    @endif
                    @unless ($canEdit)
                        <span class="tag tag-outline tone-slate"><i class="ti ti-lock"></i>View only</span>
                    @endunless
                </div>
            </div>
            @if ($canEdit)
                <div class="record-header-actions">
                    <a href="{{ route('admin.leave-types.index') }}" class="btn btn-light btn-sm px-3"><i class="ti ti-list font-14 me-1"></i> Leave types</a>
                    <button type="submit" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-check font-14 me-1"></i> {{ $setting->exists ? 'Save policy' : 'Activate policy' }}</button>
                </div>
            @endif
        </div>

        @unless ($setting->exists)
            <div class="alert alert-warning d-flex gap-2">
                <i class="ti ti-alert-triangle font-18"></i>
                <div>
                    <strong>No leave policy yet - leave is counted as every calendar day, weekends and holidays included.</strong>
                    <div class="font-13">The settings below are recommended starting values. They take effect for new requests once you {{ $canEdit ? 'activate the policy' : 'an administrator activates the policy' }}.</div>
                </div>
            </div>
        @endunless

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3 font-13">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif

        <fieldset @disabled(! $canEdit)>
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card form-section tone-indigo">
                        <div class="form-section-head">
                            <span class="form-section-icon"><i class="ti ti-calendar-stats"></i></span>
                            <div>
                                <h5 class="form-section-title">Counting leave days</h5>
                                <p class="form-section-sub">Which days inside a leave request count against the balance.</p>
                            </div>
                        </div>
                        <div class="form-section-body">
                            <div class="toggle-cards">
                                <label class="toggle-card tone-indigo">
                                    <input type="checkbox" name="exclude_weekends" value="1" id="exclude_weekends" @checked($flag('exclude_weekends'))>
                                    <span class="toggle-card-icon"><i class="ti ti-calendar-off"></i></span>
                                    <span class="toggle-card-body">
                                        <span class="toggle-card-title">Skip weekly offs</span>
                                        <span class="toggle-card-hint">Days off: <strong>{{ $offDays->isEmpty() ? 'none' : $offDays->implode(', ') }}</strong> · from <a href="{{ route('admin.working-hours.edit') }}">Working Days</a></span>
                                    </span>
                                    <span class="switch-btn" aria-hidden="true"></span>
                                </label>
                                <label class="toggle-card tone-pink">
                                    <input type="checkbox" name="exclude_holidays" value="1" id="exclude_holidays" @checked($flag('exclude_holidays'))>
                                    <span class="toggle-card-icon"><i class="ti ti-gift"></i></span>
                                    <span class="toggle-card-body">
                                        <span class="toggle-card-title">Skip holidays</span>
                                        <span class="toggle-card-hint"><strong>{{ $holidaysThisYear }}</strong> in {{ now()->year }} · from the <a href="{{ route('admin.holidays.index') }}">Holiday Calendar</a></span>
                                    </span>
                                    <span class="switch-btn" aria-hidden="true"></span>
                                </label>
                            </div>

                            <label class="toggle-card tone-amber mt-3" id="sandwich-card">
                                <input type="checkbox" name="sandwich_rule" value="1" id="sandwich_rule" @checked($flag('sandwich_rule'))>
                                <span class="toggle-card-icon"><i class="ti ti-layers-intersect"></i></span>
                                <span class="toggle-card-body">
                                    <span class="toggle-card-title">Sandwich rule</span>
                                    <span class="toggle-card-hint">Weekends or holidays squeezed <em>between</em> two leave days count as leave too.</span>
                                    <span class="sandwich-demo" aria-hidden="true">
                                        <span class="is-leave">Fri</span><span class="is-off" data-sandwich>Sat</span><span class="is-off" data-sandwich>Sun</span><span class="is-leave">Mon</span>
                                        <span class="sandwich-demo-result" id="sandwich-result">= 2 days</span>
                                    </span>
                                </span>
                                <span class="switch-btn" aria-hidden="true"></span>
                            </label>
                        </div>
                    </div>

                    <div class="card form-section tone-teal">
                        <div class="form-section-head form-section-head-compact">
                            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-clock"></i></span>
                            <h5 class="form-section-title">Half days</h5>
                        </div>
                        <div class="form-section-body">
                            <label class="toggle-card tone-teal mb-0">
                                <input type="checkbox" name="allow_half_day" value="1" id="allow_half_day" @checked($flag('allow_half_day'))>
                                <span class="toggle-card-body">
                                    <span class="toggle-card-title">Allow half-day leave</span>
                                    <span class="toggle-card-hint">A single day can be taken as the first or second half and counts as 0.5.</span>
                                </span>
                                <span class="switch-btn" aria-hidden="true"></span>
                            </label>
                        </div>
                    </div>

                    <div class="card form-section tone-amber mb-0">
                        <div class="form-section-head">
                            <span class="form-section-icon"><i class="ti ti-send"></i></span>
                            <div>
                                <h5 class="form-section-title">When leave can be requested</h5>
                                <p class="form-section-sub">Notice and backdating apply to employees applying for themselves; HR can still record leave for anyone.</p>
                            </div>
                        </div>
                        <div class="form-section-body">
                            <div class="policy-limit">
                                <div class="policy-limit-label">
                                    <strong>Advance notice</strong>
                                    <span>How many days ahead leave must be requested.</span>
                                </div>
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <input type="number" min="0" max="90" class="form-control form-control-sm policy-limit-input @error('min_notice_days') is-invalid @enderror" id="min_notice_days" name="min_notice_days" value="{{ $v('min_notice_days') }}" required>
                                    <span class="text-muted font-13">days</span>
                                    <div class="quick-chips mt-0">
                                        @foreach ([0 => 'Same day', 1 => '1 day', 3 => '3 days', 7 => '1 week', 14 => '2 weeks'] as $value => $label)
                                            <button type="button" class="quick-chip" data-set="min_notice_days" data-value="{{ $value }}">{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="policy-limit">
                                <div class="policy-limit-label">
                                    <strong>Past dates</strong>
                                    <span>How far back employees can apply for leave already taken.</span>
                                </div>
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <input type="number" min="0" max="365" class="form-control form-control-sm policy-limit-input @error('max_backdated_days') is-invalid @enderror" id="max_backdated_days" name="max_backdated_days" value="{{ $v('max_backdated_days') }}" placeholder="No limit">
                                    <span class="text-muted font-13">days back</span>
                                    <div class="quick-chips mt-0">
                                        @foreach (['0' => 'Not allowed', '7' => '1 week', '30' => '30 days', '90' => '90 days', '' => 'No limit'] as $value => $label)
                                            <button type="button" class="quick-chip" data-set="max_backdated_days" data-value="{{ $value }}">{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="policy-limit">
                                <div class="policy-limit-label">
                                    <strong>Longest single request</strong>
                                    <span>Counted leave days in one request, for anyone.</span>
                                </div>
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <input type="number" min="1" max="365" class="form-control form-control-sm policy-limit-input @error('max_consecutive_days') is-invalid @enderror" id="max_consecutive_days" name="max_consecutive_days" value="{{ $v('max_consecutive_days') }}" placeholder="No limit">
                                    <span class="text-muted font-13">days</span>
                                    <div class="quick-chips mt-0">
                                        @foreach (['5' => '5', '10' => '10', '15' => '15', '30' => '30', '' => 'No limit'] as $value => $label)
                                            <button type="button" class="quick-chip" data-set="max_consecutive_days" data-value="{{ $value }}">{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="leave-summary policy-try" id="try">
                        <div class="leave-summary-label">Try a date range</div>
                        <div class="policy-try-dates">
                            <input type="date" id="try-start" class="form-control form-control-sm" aria-label="From">
                            <span>→</span>
                            <input type="date" id="try-end" class="form-control form-control-sm" aria-label="To">
                        </div>
                        <label class="policy-try-half"><input type="checkbox" id="try-half"> Half day</label>
                        <div class="leave-summary-days mt-2"><span id="try-days">0</span><small> leave days</small></div>
                        <div class="policy-strip" id="try-strip"></div>
                        <div class="leave-balance">
                            <div class="leave-balance-row"><span>Calendar days</span><strong id="try-calendar">0</strong></div>
                            <div class="leave-balance-row"><span>Weekly offs skipped</span><strong id="try-weekends">0</strong></div>
                            <div class="leave-balance-row"><span>Holidays skipped</span><strong id="try-holidays">0</strong></div>
                            <div class="leave-balance-row"><span>Sandwiched (counted)</span><strong id="try-sandwiched">0</strong></div>
                        </div>
                        <div class="leave-alert is-warning" id="try-warn" hidden><i class="ti ti-alert-triangle"></i> <span></span></div>
                        <div class="leave-summary-note text-start mt-2">Uses the settings on the left as you change them.</div>
                    </div>

                    <div class="card side-list tone-slate mt-3">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-info-circle"></i></span>
                            <span class="fw-semibold">How changes apply</span>
                        </div>
                        <div class="px-3 py-2 font-13 text-muted">
                            New requests are counted with this policy. The {{ $upcoming }} pending or upcoming {{ \Illuminate\Support\Str::plural('request', $upcoming) }} already made keep the days they were counted with.
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>

        @if ($canEdit)
            <div class="record-footer">
                <a href="{{ route('admin.leave-types.index') }}" class="btn btn-light px-4">Leave types</a>
                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> {{ $setting->exists ? 'Save policy' : 'Activate policy' }}</button>
            </div>
        @endif
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var base = @json($policy->toClient());
            var $ = function (id) { return document.getElementById(id); };
            var ws = $('exclude_weekends'), hs = $('exclude_holidays'), sw = $('sandwich_rule'), half = $('allow_half_day');
            var tStart = $('try-start'), tEnd = $('try-end'), tHalf = $('try-half');

            function pad(n) { return String(n).padStart(2, '0'); }
            function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
            function holidayOn(d) {
                var s = iso(d);
                var hit = base.holidays.find(function (h) { return h.date === s || (h.yearly && parseInt(h.date.slice(0, 4), 10) <= d.getFullYear() && h.date.slice(5) === s.slice(5)); });
                return hit ? hit.name : null;
            }

            // Default sample: this Friday to next Monday - shows weekends and the sandwich rule.
            var fri = new Date(); fri.setDate(fri.getDate() + ((5 - fri.getDay() + 7) % 7));
            var mon = new Date(fri); mon.setDate(mon.getDate() + 3);
            tStart.value = iso(fri); tEnd.value = iso(mon);

            function refresh() {
                var sandwichPossible = ws.checked || hs.checked;
                $('sandwich-card').classList.toggle('is-muted', !sandwichPossible);
                sw.disabled = !sandwichPossible;
                if (!sandwichPossible) sw.checked = false;
                $('sandwich-result').textContent = '= ' + (sw.checked ? 4 : (ws.checked ? 2 : 4)) + ' days';
                document.querySelectorAll('[data-sandwich]').forEach(function (el) { el.classList.toggle('is-counted', sw.checked || !ws.checked); });

                ['min_notice_days', 'max_backdated_days', 'max_consecutive_days'].forEach(function (id) {
                    document.querySelectorAll('[data-set="' + id + '"]').forEach(function (b) { b.classList.toggle('is-active', b.dataset.value === $(id).value); });
                });

                // Live breakdown of the sample range.
                var strip = $('try-strip'); strip.innerHTML = '';
                var out = { calendar: 0, weekends: 0, holidays: 0, sandwiched: 0, days: 0 };
                $('try-half').closest('label').hidden = !half.checked;
                if (tStart.value && tEnd.value && tEnd.value >= tStart.value) {
                    var kinds = [];
                    for (var d = new Date(tStart.value + 'T00:00:00'); d <= new Date(tEnd.value + 'T00:00:00') && kinds.length < 62; d.setDate(d.getDate() + 1)) {
                        var h = hs.checked && holidayOn(d), w = ws.checked && base.workingDays[d.getDay()] === false;
                        kinds.push({ date: new Date(d), kind: h ? 'holiday' : (w ? 'weekend' : 'work'), name: h || '' });
                    }
                    var first = kinds.findIndex(function (k) { return k.kind === 'work'; });
                    var last = kinds.length - 1 - kinds.slice().reverse().findIndex(function (k) { return k.kind === 'work'; });
                    kinds.forEach(function (k, i) {
                        out.calendar++;
                        var state = k.kind;
                        if (k.kind === 'work') out.days++;
                        else if (sw.checked && first !== -1 && i > first && i < last) { out.days++; out.sandwiched++; state = 'sandwich'; }
                        else if (k.kind === 'weekend') out.weekends++;
                        else out.holidays++;
                        var cell = document.createElement('span');
                        cell.className = 'policy-day is-' + state;
                        cell.textContent = k.date.getDate();
                        cell.title = k.date.toDateString() + (k.name ? ' · ' + k.name : '') + ' · ' + ({ work: 'counted', sandwich: 'sandwiched - counted', weekend: 'weekly off - skipped', holiday: 'holiday - skipped' })[state];
                        strip.appendChild(cell);
                    });
                    if (tHalf.checked && half.checked && tStart.value === tEnd.value && out.days === 1) out.days = 0.5;
                }
                $('try-days').textContent = out.days;
                $('try-calendar').textContent = out.calendar;
                $('try-weekends').textContent = out.weekends;
                $('try-holidays').textContent = out.holidays;
                $('try-sandwiched').textContent = out.sandwiched;

                var max = parseInt($('max_consecutive_days').value, 10), warn = '';
                if (out.calendar && out.days === 0) warn = 'Only weekly offs and holidays - this request would be refused.';
                else if (max && out.days > max) warn = 'Longer than the ' + max + '-day limit - this request would be refused.';
                $('try-warn').hidden = !warn;
                $('try-warn').querySelector('span').textContent = warn;
            }

            document.querySelectorAll('[data-set]').forEach(function (b) {
                b.addEventListener('click', function () { $(b.dataset.set).value = b.dataset.value; refresh(); });
            });
            [ws, hs, sw, half, tHalf].forEach(function (el) { el.addEventListener('change', refresh); });
            [tStart, tEnd, $('min_notice_days'), $('max_backdated_days'), $('max_consecutive_days')].forEach(function (el) { el.addEventListener('input', refresh); });
            refresh();
        })();
    </script>
@endpush
