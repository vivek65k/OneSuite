@extends('layouts.admin')

@section('title', 'My Requests')

@php
    use App\Http\Controllers\Admin\Ess\MyRequestController as Requests;
    use Illuminate\Support\Str;

    $types = Requests::TYPES;
    $pageUrl = fn (array $overrides = []) => route('admin.my-requests.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All requests', 'tone' => 'indigo', 'count' => $counts['all']],
        'pending' => ['label' => 'Waiting for approval', 'tone' => 'amber', 'count' => $counts['pending']],
        'approved' => ['label' => 'Approved', 'tone' => 'success', 'count' => $counts['approved']],
        'closed' => ['label' => 'Rejected or cancelled', 'tone' => 'slate', 'count' => $counts['closed']],
    ];
    $statusMeta = [
        'pending' => ['amber', 'ti-clock', 'Waiting for approval'],
        'approved' => ['success', 'ti-circle-check', 'Approved'],
        'reimbursed' => ['success', 'ti-cash', 'Paid'],
        'rejected' => ['rose', 'ti-circle-x', 'Rejected'],
        'cancelled' => ['slate', 'ti-x', 'Cancelled'],
    ];
    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $waitingDays = $kpis['oldest'] ? (int) \Illuminate\Support\Carbon::parse($kpis['oldest'])->startOfDay()->diffInDays(today()) : null;
    // Which of the three forms a refused request came from, so its errors and typed values go back to the right one.
    $failed = $errors->any() ? (string) old('_form') : '';
    $kept = fn (string $form, string $field, string $default = '') => $failed === $form && is_scalar(old($field)) ? (string) old($field) : $default;
    $formErrors = fn (string $form) => $failed === $form ? $errors->all() : [];
@endphp

@section('content')
    <x-page-title title="My Requests" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any() && $failed === '')
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for approval" :value="$kpis['pending']" icon="ti-clock" :tone="$kpis['pending'] ? 'amber' : 'success'" :hint="$kpis['pending'] ? Str::limit($kpis['pending_types']->implode(', '), 44).($waitingDays ? ' · oldest '.$waitingDays.' '.Str::plural('day', $waitingDays).' ago' : '') : 'Nothing is waiting'" :href="$kpis['pending'] ? $pageUrl(['tab' => 'pending', 'type' => null]) : null" />
        <x-kpi-card label="Approved this month" :value="$kpis['approved_month']" icon="ti-circle-check" tone="success" hint="Across every kind of request" />
        <x-kpi-card label="Overtime this month" :value="$num($kpis['overtime_month']).' h'" icon="ti-history" tone="indigo" :hint="$kpis['overtime_month'] > 0 ? 'Approved hours' : 'No approved overtime yet'" />
        <x-kpi-card label="Work from home ahead" :value="$kpis['wfh_ahead'].' '.Str::plural('day', $kpis['wfh_ahead'])" icon="ti-home" tone="sky" :hint="$kpis['rejected'] ? $kpis['rejected'].' '.Str::plural('request', $kpis['rejected']).' rejected in the last 30 days' : 'Approved days still to come'" />
    </div>

    @if ($canRequest)
        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <form method="POST" action="{{ route('admin.my-requests.regularization') }}" class="card form-section tone-indigo mb-0 h-100" id="regularization-form">
                    @csrf
                    <input type="hidden" name="_form" value="regularization">
                    <div class="form-section-head form-section-head-compact">
                        <span class="form-section-icon form-section-icon-sm"><i class="ti ti-edit"></i></span>
                        <div><h5 class="form-section-title">Correct my attendance</h5><div class="font-12 text-muted">Missed a punch, or the time is wrong</div></div>
                    </div>
                    <div class="form-section-body d-flex flex-column">
                        @if ($formErrors('regularization'))
                            <div class="alert alert-danger py-2 font-13">@foreach ($formErrors('regularization') as $message)<div>{{ $message }}</div>@endforeach</div>
                        @endif
                        <label class="form-label" for="reg-date">Which day? <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="reg-date" class="form-control" value="{{ $kept('regularization', 'date', $prefill) }}" min="{{ $limits['regularize_min'] }}" max="{{ $limits['today'] }}" required>
                        <div class="row g-2 mt-1">
                            <div class="col-6">
                                <label class="form-label" for="reg-in">Check-in</label>
                                <input type="time" name="requested_check_in" id="reg-in" class="form-control" value="{{ $kept('regularization', 'requested_check_in') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="reg-out">Check-out</label>
                                <input type="time" name="requested_check_out" id="reg-out" class="form-control" value="{{ $kept('regularization', 'requested_check_out') }}">
                            </div>
                        </div>
                        <div class="form-hint"><i class="ti ti-info-circle"></i> <span id="reg-hint">Fill in the time that is missing or wrong; one of the two is enough.</span></div>
                        <label class="form-label mt-2" for="reg-reason">Why? <span class="text-danger">*</span></label>
                        <input type="text" name="reason" id="reg-reason" class="form-control" value="{{ $kept('regularization', 'reason') }}" maxlength="1000" placeholder="e.g. Forgot to check out" required>
                        <div class="quick-chips">
                            @foreach (['Forgot to check in', 'Forgot to check out', 'Device was not working', 'Was at a client site'] as $reason)
                                <button type="button" class="quick-chip" data-fill="reg-reason">{{ $reason }}</button>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-de-primary text-light w-100 mt-auto"><i class="ti ti-send font-14 me-1"></i> Send for approval</button>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <form method="POST" action="{{ route('admin.my-requests.wfh') }}" class="card form-section tone-sky mb-0 h-100" id="wfh-form">
                    @csrf
                    <input type="hidden" name="_form" value="wfh">
                    <div class="form-section-head form-section-head-compact">
                        <span class="form-section-icon form-section-icon-sm"><i class="ti ti-home"></i></span>
                        <div><h5 class="form-section-title">Work from home</h5><div class="font-12 text-muted">Up to {{ Requests::WFH_MAX_DAYS }} days in one request</div></div>
                    </div>
                    <div class="form-section-body d-flex flex-column">
                        @if ($formErrors('wfh'))
                            <div class="alert alert-danger py-2 font-13">@foreach ($formErrors('wfh') as $message)<div>{{ $message }}</div>@endforeach</div>
                        @endif
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label" for="wfh-start">From <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="wfh-start" class="form-control" value="{{ $kept('wfh', 'start_date') }}" min="{{ $limits['wfh_min'] }}" max="{{ $limits['wfh_max'] }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="wfh-end">To <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="wfh-end" class="form-control" value="{{ $kept('wfh', 'end_date') }}" required>
                            </div>
                        </div>
                        <div class="quick-chips">
                            <button type="button" class="quick-chip" data-wfh="0">Today</button>
                            <button type="button" class="quick-chip" data-wfh="1">Tomorrow</button>
                        </div>
                        <div class="form-hint"><i class="ti ti-info-circle"></i> <span id="wfh-hint">Pick the days.</span></div>
                        <label class="form-label mt-2" for="wfh-reason">Why? <span class="text-danger">*</span></label>
                        <input type="text" name="reason" id="wfh-reason" class="form-control" value="{{ $kept('wfh', 'reason') }}" maxlength="1000" placeholder="e.g. Waiting for a delivery at home" required>
                        <div class="quick-chips">
                            @foreach (['Not feeling well but able to work', 'Personal work at home', 'Transport problem'] as $reason)
                                <button type="button" class="quick-chip" data-fill="wfh-reason">{{ $reason }}</button>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-de-primary text-light w-100 mt-auto" id="wfh-submit"><i class="ti ti-send font-14 me-1"></i> Send for approval</button>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <form method="POST" action="{{ route('admin.my-requests.overtime') }}" class="card form-section tone-amber mb-0 h-100" id="overtime-form">
                    @csrf
                    <input type="hidden" name="_form" value="overtime">
                    <div class="form-section-head form-section-head-compact">
                        <span class="form-section-icon form-section-icon-sm"><i class="ti ti-clock"></i></span>
                        <div><h5 class="form-section-title">Overtime</h5><div class="font-12 text-muted">Hours worked beyond your shift</div></div>
                    </div>
                    <div class="form-section-body d-flex flex-column">
                        @if ($formErrors('overtime'))
                            <div class="alert alert-danger py-2 font-13">@foreach ($formErrors('overtime') as $message)<div>{{ $message }}</div>@endforeach</div>
                        @endif
                        <div class="row g-2">
                            <div class="col-7">
                                <label class="form-label" for="ot-date">Which day? <span class="text-danger">*</span></label>
                                <input type="date" name="date" id="ot-date" class="form-control" value="{{ $kept('overtime', 'date') }}" min="{{ $limits['overtime_min'] }}" max="{{ $limits['overtime_max'] }}" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label" for="ot-hours">Hours <span class="text-danger">*</span></label>
                                <input type="number" step="0.25" min="0.25" max="{{ Requests::OVERTIME_MAX_HOURS }}" name="hours" id="ot-hours" class="form-control" value="{{ $kept('overtime', 'hours') }}" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="quick-chips">
                            @foreach ([1, 2, 3, 4] as $hours)
                                <button type="button" class="quick-chip" data-hours="{{ $hours }}">{{ $hours }} h</button>
                            @endforeach
                        </div>
                        <div class="form-hint"><i class="ti ti-info-circle"></i> <span>Counted in quarter hours. One request per day.</span></div>
                        <label class="form-label mt-2" for="ot-reason">What was it for? <span class="text-danger">*</span></label>
                        <input type="text" name="reason" id="ot-reason" class="form-control" value="{{ $kept('overtime', 'reason') }}" maxlength="1000" placeholder="e.g. Month-end closing" required>
                        <div class="quick-chips">
                            @foreach (['Project deadline', 'Month-end closing', 'Production release'] as $reason)
                                <button type="button" class="quick-chip" data-fill="ot-reason">{{ $reason }}</button>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-de-primary text-light w-100 mt-auto"><i class="ti ti-send font-14 me-1"></i> Send for approval</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-warning d-flex align-items-center gap-2"><i class="ti ti-info-circle font-18"></i> Your employee record is {{ $employeeStatus }}, so new requests can't be made.</div>
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
            <div class="list-toolbar-actions">
                <form method="GET" action="{{ route('admin.my-requests.index') }}">
                    @if ($state['tab'] !== '')
                        <input type="hidden" name="tab" value="{{ $state['tab'] }}">
                    @endif
                    <select name="type" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Kind of request">
                        <option value="">Every kind</option>
                        @foreach ($types as $value => $type)
                            <option value="{{ $value }}" @selected($state['type'] === $value)>{{ $type['label'] }} ({{ $typeCounts[$value] ?? 0 }})</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
        @if ($state['type'] !== '')
            <div class="list-subbar">
                <div class="list-chips">
                    <a href="{{ $pageUrl(['type' => null]) }}" class="filter-chip" title="Remove filter">Kind: <strong>{{ $types[$state['type']]['label'] }}</strong> <i class="ti ti-x"></i></a>
                </div>
            </div>
        @endif
        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Request</th>
                        <th>For</th>
                        <th style="width: 30%">Details</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $type = $types[$item['type']];
                            $status = $statusMeta[$item['status']] ?? ['slate', 'ti-point', Str::headline((string) $item['status'])];
                            $step = $flow[$item['key']] ?? null;
                        @endphp
                        <tr class="{{ $item['status'] === 'pending' ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-square tone-{{ $type['tone'] }}"><i class="ti {{ $type['icon'] }}"></i></span>
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $type['label'] }}</div>
                                        <div class="list-user-meta">sent {{ $item['created_at']?->format('M j, Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">{{ $item['when'] }}</td>
                            <td>
                                <span class="fw-semibold">{{ $item['detail'] }}</span>
                                @if ($item['note'])
                                    <div class="payslip-parts" title="{{ Str::length($item['note']) > 90 ? $item['note'] : '' }}">{{ Str::limit($item['note'], 90) }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $status[0] }}"><i class="ti {{ $status[1] }}"></i>{{ $status[2] }}</span>
                                @if ($item['status'] === 'pending' && $step)
                                    <div class="payslip-parts">{{ $step['name'] ? 'waiting on '.$step['name'] : 'waiting on HR' }}{{ $step['required'] > 1 ? ' · step '.min($step['done'] + 1, $step['required']).' of '.$step['required'] : '' }}</div>
                                @elseif ($item['reviewer'] || $item['reviewed_at'])
                                    <div class="payslip-parts">{{ collect([$item['reviewer'] ? 'by '.$item['reviewer'] : null, $item['reviewed_at']?->format('M j')])->filter()->implode(' · ') }}</div>
                                @endif
                                @if ($item['remarks'])
                                    <div class="payslip-parts {{ $item['status'] === 'rejected' ? 'text-danger' : '' }}" title="{{ $item['remarks'] }}">“{{ Str::limit($item['remarks'], 70) }}”</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($type['here'] && $item['status'] === 'pending')
                                    <form action="{{ route('admin.my-requests.destroy', ['type' => $item['type'], 'id' => $item['id']]) }}" method="POST" class="d-inline" onsubmit="return confirm(@js('Withdraw this '.strtolower($type['label']).' request? It is removed and nobody will be asked to approve it.'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm review-btn is-reject"><i class="ti ti-x"></i> Withdraw</button>
                                    </form>
                                @elseif (! $type['here'])
                                    <a href="{{ route($type['route']) }}" class="btn-icon btn-icon-accent" title="Open on My {{ $type['label'] }}{{ $item['type'] === 'expense' ? 's' : '' }}"><i class="ti ti-arrow-right font-16"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-list-check font-24 d-block mb-2"></i>
                                @if ($state['tab'] !== '' || $state['type'] !== '')
                                    <div class="fw-semibold text-body mb-1">No requests match</div>
                                    <span class="font-13"><a href="{{ route('admin.my-requests.index') }}">Show all requests</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">You haven't made any requests yet</div>
                                    <span class="font-13">Everything you ask for, including leave, expenses and travel, appears here with who it is waiting on.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($shownOf[1] > $shownOf[0])
            <div class="list-subbar"><span class="font-12 text-muted">Showing the latest {{ $shownOf[0] }} of {{ $shownOf[1] }}. Older ones are on their own pages.</span></div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var $ = function (id) { return document.getElementById(id); };
            if (!$('wfh-form')) return;
            var pad = function (n) { return String(n).padStart(2, '0'); };
            var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
            var at = function (v) { return new Date(v + 'T00:00:00'); };
            var maxDays = {{ (int) Requests::WFH_MAX_DAYS }};

            document.querySelectorAll('[data-fill]').forEach(function (chip) { chip.addEventListener('click', function () { $(chip.dataset.fill).value = chip.textContent.trim(); }); });
            document.querySelectorAll('[data-hours]').forEach(function (chip) { chip.addEventListener('click', function () { $('ot-hours').value = chip.dataset.hours; }); });

            var start = $('wfh-start'), end = $('wfh-end');
            function wfh() {
                var text = 'Pick the days.', bad = false;
                if (start.value) end.min = start.value;
                if (start.value && end.value) {
                    var days = Math.round((at(end.value) - at(start.value)) / 86400000) + 1;
                    if (days < 1) { text = 'The last day is before the first day.'; bad = true; }
                    else if (days > maxDays) { text = 'One request can cover at most ' + maxDays + ' days.'; bad = true; }
                    else text = days + (days === 1 ? ' day' : ' days') + ' working from home.';
                }
                $('wfh-hint').textContent = text;
                $('wfh-hint').parentElement.classList.toggle('text-danger', bad);
                $('wfh-submit').disabled = bad;
            }
            start.addEventListener('change', function () { if (!end.value || end.value < start.value) end.value = start.value; wfh(); });
            end.addEventListener('change', wfh);
            document.querySelectorAll('[data-wfh]').forEach(function (chip) {
                chip.addEventListener('click', function () { var d = new Date(); d.setDate(d.getDate() + parseInt(chip.dataset.wfh, 10)); start.value = end.value = iso(d); wfh(); });
            });
            wfh();

            // The hours a corrected day would come to, as a sanity check before sending.
            function regularization() {
                var a = $('reg-in').value, b = $('reg-out').value, text = 'Fill in the time that is missing or wrong; one of the two is enough.';
                if (a && b) {
                    var minutes = (parseInt(b.slice(0, 2), 10) * 60 + parseInt(b.slice(3), 10)) - (parseInt(a.slice(0, 2), 10) * 60 + parseInt(a.slice(3), 10));
                    text = minutes === 0 ? 'Check-in and check-out are the same time.' : (minutes < 0 ? 'Check-out is before check-in, so it is taken as the next morning (' + ((minutes + 1440) / 60).toFixed(1) + ' h).' : 'That makes ' + (minutes / 60).toFixed(1) + ' hours at work.');
                }
                $('reg-hint').textContent = text;
            }
            [$('reg-in'), $('reg-out')].forEach(function (input) { input.addEventListener('input', regularization); });
            regularization();
            // Arriving from My Attendance with a day already chosen: go straight to the form.
            if (@json($prefill !== '')) { $('regularization-form').scrollIntoView({ block: 'center' }); $('reg-in').focus(); }
        })();
    </script>
@endpush
