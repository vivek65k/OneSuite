@extends('layouts.admin')

@section('title', 'My Leave')

@php
    use App\Models\LeaveRequest;
    use App\Support\LeaveTypeStyle;
    use Illuminate\Support\Str;

    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
    $dayWord = fn ($n) => $fmt($n).' '.((float) $n === 1.0 ? 'day' : 'days');
    $pageUrl = fn (array $overrides = []) => route('admin.my-leave.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All requests', 'tone' => 'indigo', 'count' => $counts['all']],
        'pending' => ['label' => 'Waiting for approval', 'tone' => 'amber', 'count' => $counts['pending']],
        'upcoming' => ['label' => 'Upcoming', 'tone' => 'sky', 'count' => $counts['upcoming']],
        'taken' => ['label' => 'Taken', 'tone' => 'success', 'count' => $counts['taken']],
        'closed' => ['label' => 'Rejected or cancelled', 'tone' => 'slate', 'count' => $counts['closed']],
    ];
    $statusTones = ['pending' => 'amber', 'approved' => 'success', 'rejected' => 'rose', 'cancelled' => 'slate'];
    $statusIcons = ['pending' => 'ti-clock', 'approved' => 'ti-circle-check', 'rejected' => 'ti-circle-x', 'cancelled' => 'ti-x'];
    $next = $kpis['upcoming'];
    $formErrors = $errors->hasAny(['leave_type_id', 'start_date', 'end_date', 'half_day', 'reason']);
    $balanceByType = $balances->keyBy(fn ($b) => $b['balance']->leave_type_id);
@endphp

@section('content')
    <x-page-title title="My Leave" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @error('leave')
        <div class="alert alert-danger d-flex align-items-center gap-2"><i class="ti ti-alert-circle font-18"></i> {{ $message }}</div>
    @enderror

    <div class="kpi-strip">
        <x-kpi-card label="Available to request" :value="$balances->isNotEmpty() ? $dayWord($kpis['available']) : '—'" icon="ti-plane" tone="teal" :hint="$balances->isNotEmpty() ? 'across '.$kpis['types'].' leave '.Str::plural('type', $kpis['types']).' in '.$year : 'No leave allocated for '.$year" />
        <x-kpi-card label="Waiting for approval" :value="$dayWord($kpis['pending'])" icon="ti-clock" :tone="$kpis['pending_count'] ? 'amber' : 'indigo'" :hint="$kpis['pending_count'] ? $kpis['pending_count'].' '.Str::plural('request', $kpis['pending_count']).' not decided yet' : 'Nothing is waiting'" :href="$kpis['pending_count'] ? $pageUrl(['tab' => 'pending']) : null" />
        <x-kpi-card label="Approved in {{ $year }}" :value="$dayWord($kpis['taken'])" icon="ti-calendar-event" tone="success" hint="Taken or booked" />
        <x-kpi-card label="Next leave" :value="$next ? $next->start_date->format('M j') : '—'" icon="ti-calendar" tone="sky" :hint="$next ? ($next->start_date->lte(today()) ? 'On leave now until '.$next->end_date->format('M j') : $dayWord($next->days).' of '.($next->leaveType?->name ?? 'leave').' · in '.(int) today()->diffInDays($next->start_date).' days') : 'No approved leave coming up'" />
    </div>

    <div class="card form-section tone-teal">
        <div class="form-section-head form-section-head-compact">
            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-plane"></i></span>
            <h5 class="form-section-title">Leave balance {{ $year }}</h5>
            @if ($years->count() > 1)
                <form method="GET" action="{{ route('admin.my-leave.index') }}" class="ms-auto">
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Year">
                        @foreach ($years as $option)
                            <option value="{{ $option }}" @selected($option === $year)>{{ $option }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
        <div class="form-section-body">
            <div class="row g-3">
                @forelse ($balances as $row)
                    @php
                        $b = $row['balance'];
                        $style = LeaveTypeStyle::for($b->leaveType?->name, $b->leaveType?->code);
                        $usedPercent = $row['total'] > 0 ? min(100, round((float) $b->used / $row['total'] * 100)) : 0;
                        $extras = collect([(float) $b->carried_forward ? $fmt($b->carried_forward).' carried forward' : null, (float) $b->adjusted ? ((float) $b->adjusted > 0 ? '+' : '').$fmt($b->adjusted).' adjusted' : null, (float) $b->encashed ? $fmt($b->encashed).' encashed' : null])->filter()->implode(' · ');
                    @endphp
                    <div class="col-md-6 col-xl-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="list-avatar list-avatar-square tone-{{ $style['tone'] }}"><i class="ti {{ $style['icon'] }}"></i></span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $b->leaveType?->name ?? 'Removed leave type' }}</div>
                                    <div class="font-12 text-muted">{{ $fmt($b->allocated) }} allocated{{ $extras ? ' · '.$extras : '' }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold font-18 {{ $row['remaining'] <= 0 ? 'text-danger' : '' }}">{{ $fmt($row['remaining']) }}</div>
                                    <div class="font-11 text-muted">left of {{ $fmt($row['total']) }}</div>
                                </div>
                            </div>
                            <div class="mini-progress tone-{{ $style['tone'] }}">
                                <div class="mini-progress-bar is-toned"><span style="width: {{ $usedPercent }}%"></span></div>
                                <span class="mini-progress-label">{{ $fmt($b->used) }} used</span>
                            </div>
                            @if ($row['pending'] > 0)
                                <div class="font-12 text-warning mt-2"><i class="ti ti-clock"></i> {{ $dayWord($row['pending']) }} waiting for approval, so {{ $dayWord($row['available']) }} can still be requested</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-muted font-13">No leave has been allocated to you for {{ $year }} yet. HR sets this up under Leave Balances.</div>
                @endforelse
            </div>
        </div>
    </div>

    @if ($canApply && $year === now()->year)
        <form method="POST" action="{{ route('admin.my-leave.store') }}" id="apply-form" class="row g-3 mb-3">
            @csrf
            <div class="col-lg-8">
                <div class="card form-section tone-indigo mb-0 h-100">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-calendar-event"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Apply for leave</h5>
                            <p class="form-section-sub">Pick the type of leave and the dates. Your request goes to your manager for approval.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        @if ($formErrors)
                            <div class="alert alert-danger py-2 font-13">
                                @foreach (collect(['leave_type_id', 'start_date', 'end_date', 'half_day', 'reason'])->flatMap(fn ($field) => $errors->get($field)) as $message)<div>{{ $message }}</div>@endforeach
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Leave type <span class="text-danger">*</span></label>
                                <div class="leave-type-picker" id="type-picker" role="radiogroup" aria-label="Leave type">
                                    @foreach ($leaveTypes as $type)
                                        @php
                                            $style = LeaveTypeStyle::for($type->name, $type->code);
                                            $bal = $balanceByType[$type->id] ?? null;
                                        @endphp
                                        <label class="leave-type-option tone-{{ $style['tone'] }} {{ $bal && $bal['available'] <= 0 ? 'is-empty' : '' }}">
                                            <input type="radio" name="leave_type_id" value="{{ $type->id }}" data-name="{{ $type->name }}" data-tone="{{ $style['tone'] }}" @checked((string) old('leave_type_id') === (string) $type->id) required>
                                            <span class="leave-type-option-icon"><i class="ti {{ $style['icon'] }}"></i></span>
                                            <span class="leave-type-option-body">
                                                <span class="leave-type-option-name">{{ $type->name }}</span>
                                                <span class="leave-type-option-balance">{{ $bal ? $fmt($bal['available']).' available' : ($type->is_paid ? 'No allowance set' : 'Unpaid') }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="apply-start">From <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="apply-start" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" min="{{ $client['joined'] }}" max="{{ $client['maxDate'] }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="apply-end">To <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="apply-end" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" max="{{ $client['maxDate'] }}" required>
                            </div>
                            <div class="col-12">
                                <div class="quick-chips mt-0" id="date-chips">
                                    <button type="button" class="quick-chip" data-range="today">Today</button>
                                    <button type="button" class="quick-chip" data-range="tomorrow">Tomorrow</button>
                                    <button type="button" class="quick-chip" data-range="friday">This Friday</button>
                                    <button type="button" class="quick-chip" data-range="next-mon">Next Monday</button>
                                    <button type="button" class="quick-chip" data-range="next-week">Next week (Mon–Fri)</button>
                                </div>
                            </div>
                            <div class="col-md-6" id="half-day-wrap" hidden>
                                <label class="form-label" for="apply-half">Half day?</label>
                                <select name="half_day" id="apply-half" class="form-select">
                                    <option value="">Full day</option>
                                    @foreach (LeaveRequest::HALF_DAYS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('half_day') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="apply-reason">Reason <span class="text-danger">*</span></label>
                                <input type="text" name="reason" id="apply-reason" class="form-control @error('reason') is-invalid @enderror" value="{{ old('reason') }}" maxlength="1000" placeholder="e.g. Family function" required>
                                <div class="quick-chips">
                                    @foreach (['Personal work', 'Feeling unwell', 'Family function', 'Travel', 'Medical appointment'] as $reason)
                                        <button type="button" class="quick-chip" data-reason="{{ $reason }}">{{ $reason }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="leave-summary" id="leave-summary">
                    <div class="leave-summary-label">Request summary</div>
                    <div class="leave-summary-type" id="sum-type">No leave type selected</div>
                    <div class="leave-summary-days"><span id="sum-days">0</span><small> days</small></div>
                    <div class="leave-summary-dates" id="sum-dates">Pick the dates</div>
                    <div class="leave-summary-dates" id="sum-skipped" hidden></div>

                    <div class="leave-balance" id="sum-balance" hidden>
                        <div class="leave-balance-row"><span>Available now</span><strong id="bal-now">—</strong></div>
                        <div class="leave-balance-row"><span>This request</span><strong id="bal-req">—</strong></div>
                        <div class="leave-balance-row is-total"><span>Left after</span><strong id="bal-after">—</strong></div>
                    </div>

                    <div class="leave-alert is-danger" id="sum-problem" hidden><i class="ti ti-alert-circle"></i> <span></span></div>

                    <button type="submit" class="btn btn-light w-100 mt-3 fw-semibold" id="apply-submit"><i class="ti ti-send font-14 me-1"></i> Send for approval</button>
                    <div class="leave-summary-note">
                        {{ $policy['exclude_weekends'] || $policy['exclude_holidays'] ? 'Weekends and holidays in the range aren\'t counted.' : 'Every calendar day in the range counts as leave.' }}
                        @if ($policy['min_notice_days']) Apply at least {{ $policy['min_notice_days'] }} {{ Str::plural('day', $policy['min_notice_days']) }} ahead. @endif
                        @if ($policy['max_consecutive_days']) At most {{ $policy['max_consecutive_days'] }} days in one request. @endif
                    </div>
                </div>
            </div>
        </form>
    @elseif (! $canApply)
        <div class="alert alert-warning d-flex align-items-center gap-2"><i class="ti ti-info-circle font-18"></i>
            {{ $leaveTypes->isEmpty() ? 'No leave types are set up for your branch yet, so leave can\'t be requested. Ask HR.' : 'Your employee record is '.$employee->status.', so leave can\'t be requested.' }}
        </div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <nav class="list-tabs" aria-label="Request status">
                @foreach ($tabs as $value => $tab)
                    @if ($value === '' || $tab['count'] || $state['tab'] === $value)
                        <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
            @if ($year !== now()->year)
                <div class="list-toolbar-actions"><a href="{{ route('admin.my-leave.index') }}" class="filter-chip">Year: <strong>{{ $year }}</strong> <i class="ti ti-x"></i></a></div>
            @endif
        </div>
        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Leave</th>
                        <th>Dates</th>
                        <th>Days</th>
                        <th style="width: 28%">Reason</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $r)
                        @php
                            $style = LeaveTypeStyle::for($r->leaveType?->name, $r->leaveType?->code);
                            $flow = $waiting[$r->id] ?? null;
                            $canWithdraw = $r->status === 'pending';
                            $canCancel = $r->status === 'approved' && $r->start_date->gt(today());
                        @endphp
                        <tr class="{{ $r->status === 'pending' ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-square tone-{{ $style['tone'] }}"><i class="ti {{ $style['icon'] }}"></i></span>
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $r->leaveType?->name ?? 'Removed leave type' }}</div>
                                        <div class="list-user-meta">requested {{ $r->created_at?->format('M j, Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                {{ $r->start_date->format('D, M j') }}{{ $r->end_date->isSameDay($r->start_date) ? '' : ' – '.$r->end_date->format('D, M j') }}
                                <div class="payslip-parts">
                                    @if ($r->status === 'approved' && $r->start_date->lte(today()) && $r->end_date->gte(today()))
                                        on leave now
                                    @elseif ($r->start_date->gt(today()))
                                        starts in {{ (int) today()->diffInDays($r->start_date) }} {{ Str::plural('day', (int) today()->diffInDays($r->start_date)) }}
                                    @else
                                        {{ $r->start_date->format('Y') }}
                                    @endif
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <span class="fw-semibold">{{ $fmt($r->days) }}</span>
                                @if ($r->half_day)
                                    <div class="payslip-parts">{{ LeaveRequest::HALF_DAYS[$r->half_day] ?? $r->half_day }}</div>
                                @endif
                            </td>
                            <td><span title="{{ Str::length((string) $r->reason) > 90 ? $r->reason : '' }}">{{ Str::limit((string) $r->reason, 90) }}</span></td>
                            <td>
                                <span class="tag tone-{{ $statusTones[$r->status] ?? 'slate' }}"><i class="ti {{ $statusIcons[$r->status] ?? 'ti-point' }}"></i>{{ LeaveRequest::STATUSES[$r->status] ?? Str::headline($r->status) }}</span>
                                @if ($r->status === 'pending' && $flow)
                                    <div class="payslip-parts">{{ $flow['name'] ? 'waiting on '.$flow['name'] : 'waiting on HR' }}{{ $flow['required'] > 1 ? ' · step '.min($flow['done'] + 1, $flow['required']).' of '.$flow['required'] : '' }}</div>
                                @elseif (in_array($r->status, ['approved', 'rejected'], true) && ($r->reviewedBy || $r->reviewed_at))
                                    <div class="payslip-parts">{{ collect([$r->reviewedBy?->name ? 'by '.$r->reviewedBy->name : null, $r->reviewed_at?->format('M j')])->filter()->implode(' · ') }}</div>
                                @endif
                                @if ($r->remarks)
                                    <div class="payslip-parts {{ $r->status === 'rejected' ? 'text-danger' : '' }}" title="{{ $r->remarks }}">“{{ Str::limit($r->remarks, 70) }}”</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($canWithdraw || $canCancel)
                                    <form action="{{ route('admin.my-leave.cancel', $r) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm(@js($canCancel ? 'Cancel this approved leave? '.$dayWord($r->days).' will go back to your balance and your approvers will be told.' : 'Withdraw this leave request?'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm review-btn is-reject"><i class="ti ti-x"></i> {{ $canCancel ? 'Cancel leave' : 'Withdraw' }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-calendar-off font-24 d-block mb-2"></i>
                                @if ($state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">Nothing here</div>
                                    <span class="font-13"><a href="{{ $pageUrl(['tab' => null]) }}">Show all requests</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No leave requested in {{ $year }}</div>
                                    <span class="font-13">Requests you send appear here with who they are waiting on.</span>
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
        (function () {
            var form = document.getElementById('apply-form');
            if (!form) return;
            var data = @json($client);
            var p = data.policy, r = p.rules;
            var $ = function (id) { return document.getElementById(id); };
            var start = $('apply-start'), end = $('apply-end'), half = $('apply-half'), reason = $('apply-reason');

            function fmt(n) { return (Math.round(n * 100) / 100).toString(); }
            function pad(n) { return String(n).padStart(2, '0'); }
            function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
            function at(v) { return new Date(v + 'T00:00:00'); }
            function nice(v) { return at(v).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' }); }
            function plural(n, word) { return fmt(n) + ' ' + word + (n === 1 ? '' : 's'); }
            function selected() { return form.querySelector('input[name="leave_type_id"]:checked'); }
            function holidayOn(d) {
                var day = iso(d);
                return p.holidays.some(function (h) { return h.date === day || (h.yearly && parseInt(h.date.slice(0, 4), 10) <= d.getFullYear() && h.date.slice(5) === day.slice(5)); });
            }
            // Count days the same way the server's leave policy does.
            function count() {
                var out = { days: 0, weekends: 0, holidays: 0, sandwiched: 0 };
                if (!start.value || !end.value || end.value < start.value) return out;
                var kinds = [];
                for (var d = at(start.value); d <= at(end.value) && kinds.length < 400; d.setDate(d.getDate() + 1)) {
                    var holiday = r.exclude_holidays && holidayOn(d);
                    var weekend = r.exclude_weekends && p.workingDays[d.getDay()] === false;
                    kinds.push(holiday ? 'holiday' : (weekend ? 'weekend' : 'work'));
                }
                var first = kinds.indexOf('work'), last = kinds.lastIndexOf('work');
                kinds.forEach(function (k, i) {
                    if (k === 'work') out.days++;
                    else if (r.sandwich_rule && first !== -1 && i > first && i < last) { out.days++; out.sandwiched++; }
                    else if (k === 'weekend') out.weekends++;
                    else out.holidays++;
                });
                if (half.value && r.allow_half_day && start.value === end.value && out.days === 1) out.days = 0.5;
                return out;
            }
            // The first reason the server would refuse this, so it is seen before sending.
            function problem(c, type) {
                if (!start.value || !end.value) return '';
                if (end.value < start.value) return 'The last day is before the first day.';
                if (data.joined && start.value < data.joined) return 'Leave can\'t start before your joining date.';
                if (end.value > data.maxDate) return 'Leave can be requested up to a year ahead.';
                if (start.value.slice(0, 4) !== end.value.slice(0, 4)) return 'This runs across two years. Request each year\'s days separately.';
                if (c.days === 0) return 'Only weekends and holidays in that range; there is nothing to take.';
                if (r.max_consecutive_days && c.days > r.max_consecutive_days) return 'At most ' + r.max_consecutive_days + ' days are allowed in one request.';
                var gap = Math.round((at(start.value) - at(data.today)) / 86400000);
                if (r.min_notice_days && gap >= 0 && gap < r.min_notice_days) return 'Leave must be requested at least ' + plural(r.min_notice_days, 'day') + ' in advance.';
                if (r.max_backdated_days !== null && gap < -r.max_backdated_days) return r.max_backdated_days === 0 ? 'Leave can\'t be requested for past dates.' : 'Past leave can only be requested up to ' + r.max_backdated_days + ' days back.';
                var clash = data.booked.find(function (b) { return b.from <= end.value && b.to >= start.value; });
                if (clash) return 'You already have ' + clash.status + ' ' + (clash.type || 'leave') + ' from ' + nice(clash.from) + ' to ' + nice(clash.to) + '.';
                if (type && start.value.slice(0, 4) === String(data.year) && data.balances[type.value] !== undefined && c.days > data.balances[type.value]) return 'You only have ' + plural(data.balances[type.value], 'day') + ' of ' + type.dataset.name + ' available.';
                return '';
            }

            function refresh() {
                var type = selected(), c = count();
                var halfWrap = $('half-day-wrap');
                halfWrap.hidden = !(r.allow_half_day && start.value && start.value === end.value);
                if (halfWrap.hidden) half.value = '';
                if (start.value) end.min = start.value;

                var skipped = [];
                if (c.weekends) skipped.push(plural(c.weekends, 'weekend day'));
                if (c.holidays) skipped.push(plural(c.holidays, 'holiday'));
                var text = (skipped.length ? 'Not counted: ' + skipped.join(', ') : '') + (c.sandwiched ? (skipped.length ? ' · ' : '') + plural(c.sandwiched, 'sandwiched day') + ' counted' : '');
                $('sum-skipped').hidden = !text;
                $('sum-skipped').textContent = text;
                $('sum-type').textContent = type ? type.dataset.name + (data.paid[type.value] ? ' · paid' : ' · unpaid') : 'No leave type selected';
                $('sum-days').textContent = fmt(c.days);
                $('sum-dates').textContent = start.value && end.value && end.value >= start.value ? (start.value === end.value ? nice(start.value) : nice(start.value) + ' → ' + nice(end.value)) : 'Pick the dates';
                $('leave-summary').className = 'leave-summary' + (type ? ' tone-' + type.dataset.tone : '');

                var bal = type && start.value.slice(0, 4) === String(data.year) ? data.balances[type.value] : undefined;
                $('sum-balance').hidden = bal === undefined;
                if (bal !== undefined) {
                    $('bal-now').textContent = fmt(bal) + ' d';
                    $('bal-req').textContent = '− ' + fmt(c.days) + ' d';
                    $('bal-after').textContent = fmt(bal - c.days) + ' d';
                    $('bal-after').classList.toggle('text-danger', bal - c.days < 0);
                }
                var why = problem(c, type);
                $('sum-problem').hidden = !why;
                $('sum-problem').querySelector('span').textContent = why;
                $('leave-summary').classList.toggle('has-problem', !!why);
                $('apply-submit').disabled = !!why;
            }

            $('date-chips').addEventListener('click', function (event) {
                var chip = event.target.closest('[data-range]');
                if (!chip) return;
                var from = at(data.today), to;
                var toDow = function (d, dow) { var x = new Date(d); x.setDate(x.getDate() + ((dow - x.getDay() + 7) % 7)); return x; };
                if (chip.dataset.range === 'tomorrow') from.setDate(from.getDate() + 1);
                if (chip.dataset.range === 'friday') from = toDow(from, 5);
                if (chip.dataset.range === 'next-mon' || chip.dataset.range === 'next-week') { from.setDate(from.getDate() + 1); from = toDow(from, 1); }
                to = new Date(from);
                if (chip.dataset.range === 'next-week') to.setDate(to.getDate() + 4);
                start.value = iso(from); end.value = iso(to);
                refresh();
            });
            form.querySelectorAll('[data-reason]').forEach(function (chip) { chip.addEventListener('click', function () { reason.value = chip.dataset.reason; }); });
            start.addEventListener('change', function () { if (!end.value || end.value < start.value) end.value = start.value; refresh(); });
            [end, half].forEach(function (input) { input.addEventListener('change', refresh); });
            form.querySelectorAll('input[name="leave_type_id"]').forEach(function (input) { input.addEventListener('change', refresh); });
            // One submit, even on a double click.
            form.addEventListener('submit', function () { setTimeout(function () { $('apply-submit').disabled = true; }, 0); });
            refresh();
        })();
    </script>
@endpush
