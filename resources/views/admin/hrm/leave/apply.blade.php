@extends('layouts.admin')

@section('title', 'Apply Leave')

@php
    use App\Support\LeaveTypeStyle;

    $statusMeta = [
        'pending' => ['tone' => 'amber', 'icon' => 'ti-clock'],
        'approved' => ['tone' => 'success', 'icon' => 'ti-circle-check'],
        'rejected' => ['tone' => 'danger', 'icon' => 'ti-circle-x'],
        'cancelled' => ['tone' => 'slate', 'icon' => 'ti-ban'],
    ];
    $statuses = \App\Models\LeaveRequest::STATUSES;

    $filterLabels = ['search' => 'Search', 'status' => 'Status', 'employee_id' => 'Employee', 'leave_type_id' => 'Leave type', 'from' => 'From', 'to' => 'To'];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => match ($key) {
            'status' => $statuses[$state[$key]] ?? $state[$key],
            'employee_id' => $employees[$state[$key]] ?? $state[$key],
            'leave_type_id' => $leaveTypes[$state[$key]] ?? $state[$key],
            'from', 'to' => \Illuminate\Support\Carbon::parse($state[$key])->format('M j, Y'),
            default => $state[$key],
        }]);

    // Every link carries the full state - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route('admin.leave-requests.apply', array_filter(
        array_merge($state, ['page' => null], $overrides) + ['per_page' => 25],
        fn ($value) => $value !== null && $value !== ''
    ));

    $currentStatus = (string) ($state['status'] ?? '');
    $tabs = collect(['' => ['label' => 'All requests', 'tone' => 'indigo']])
        ->merge(collect($statuses)->map(fn ($label, $key) => ['label' => $label, 'tone' => $statusMeta[$key]['tone'] ?? 'slate']));

    $panelKeys = ['employee_id', 'leave_type_id', 'from', 'to'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
    $canCreate = auth()->user()->can('create', \App\Models\LeaveRequest::class);
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];

    $typePayload = $formTypes->map(fn ($t) => [
        'id' => $t->id, 'name' => $t->name, 'code' => $t->code, 'branch_id' => $t->branch_id,
        'days_per_year' => (float) $t->days_per_year, 'is_paid' => (bool) $t->is_paid,
    ] + LeaveTypeStyle::for($t->name, $t->code))->values();
    $employeePayload = $formEmployees->mapWithKeys(fn ($e) => [$e->id => ['name' => $e->name, 'branch_id' => $e->branch_id]]);
@endphp

@section('content')
    <x-page-title title="Apply Leave" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @error('leave_request')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="kpi-strip">
        <x-kpi-card label="Pending approval" :value="$kpis['pending']" icon="ti-clock" tone="amber" :href="$listUrl(['status' => 'pending'])" />
        <x-kpi-card label="On leave today" :value="$kpis['on_leave_today']" icon="ti-plane" tone="violet" :hint="now()->format('l, M j')" />
        <x-kpi-card label="Upcoming (30 days)" :value="$kpis['upcoming']" icon="ti-calendar-event" tone="sky" hint="Approved, starting soon" />
        <x-kpi-card label="Days taken {{ now()->year }}" :value="$fmt($kpis['days_this_year'])" icon="ti-calendar-stats" tone="success" hint="Approved leave" />
    </div>

    @if ($canCreate)
        <form method="POST" action="{{ route('admin.leave-requests.store') }}" id="apply-form" class="row g-3 mb-3">
            @csrf
            <div class="col-lg-8">
                <div class="card form-section tone-indigo mb-0 h-100">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-plane"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Apply for leave</h5>
                            <p class="form-section-sub">Pick the employee, the type of leave and the dates.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        @if ($errors->hasAny(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'reason']))
                            <div class="alert alert-danger py-2 font-13">
                                @foreach (collect(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'reason'])->flatMap(fn ($field) => $errors->get($field)) as $message)<div>{{ $message }}</div>@endforeach
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="apply-employee">Employee <span class="text-danger">*</span></label>
                                <select name="employee_id" id="apply-employee" class="form-select" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formEmployees as $employee)
                                        <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>{{ $employee->name }} ({{ $employee->employee_code }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Leave type <span class="text-danger">*</span></label>
                                <div class="leave-type-picker" id="type-picker" role="radiogroup" aria-label="Leave type">
                                    @foreach ($formTypes as $type)
                                        @php $style = LeaveTypeStyle::for($type->name, $type->code); @endphp
                                        <label class="leave-type-option tone-{{ $style['tone'] }}" data-type-id="{{ $type->id }}" data-branch="{{ $type->branch_id }}">
                                            <input type="radio" name="leave_type_id" value="{{ $type->id }}" @checked((string) old('leave_type_id') === (string) $type->id) required>
                                            <span class="leave-type-option-icon"><i class="ti {{ $style['icon'] }}"></i></span>
                                            <span class="leave-type-option-body">
                                                <span class="leave-type-option-name">{{ $type->name }}</span>
                                                <span class="leave-type-option-balance" data-balance-for="{{ $type->id }}">{{ $fmt($type->days_per_year) }} days / year</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="form-hint" id="type-hint"><i class="ti ti-info-circle"></i> <span>Select an employee to see their balances.</span></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="apply-start">From <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="apply-start" class="form-control" value="{{ old('start_date') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="apply-end">To <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="apply-end" class="form-control" value="{{ old('end_date') }}" required>
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
                                    @foreach (\App\Models\LeaveRequest::HALF_DAYS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('half_day') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="apply-reason">Reason <span class="text-danger">*</span></label>
                                <input type="text" name="reason" id="apply-reason" class="form-control" value="{{ old('reason') }}" maxlength="1000" placeholder="e.g. Family function" required>
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
                    <div class="leave-summary-who" id="sum-employee">Choose an employee</div>
                    <div class="leave-summary-type" id="sum-type">No leave type selected</div>
                    <div class="leave-summary-days"><span id="sum-days">0</span><small> days</small></div>
                    <div class="leave-summary-dates" id="sum-dates">Pick the dates</div>
                    <div class="leave-summary-dates" id="sum-skipped" hidden></div>

                    <div class="leave-balance" id="sum-balance" hidden>
                        <div class="leave-balance-row"><span>Balance now</span><strong id="bal-now">—</strong></div>
                        <div class="leave-balance-row"><span>This request</span><strong id="bal-req">—</strong></div>
                        <div class="leave-balance-row is-total"><span>Balance after</span><strong id="bal-after">—</strong></div>
                    </div>

                    <div class="leave-alert is-warning" id="sum-over" hidden><i class="ti ti-alert-triangle"></i> <span>More days than the available balance.</span></div>
                    <div class="leave-alert is-danger" id="sum-overlap" hidden><i class="ti ti-alert-circle"></i> <span></span></div>

                    <button type="submit" class="btn btn-light w-100 mt-3 fw-semibold"><i class="ti ti-send font-14 me-1"></i> Submit request</button>
                    <div class="leave-summary-note"><span id="sum-rule">Counted by the <a href="{{ route('admin.leave-policies.index') }}" class="text-white text-decoration-underline">leave policy</a>.</span> Approvers are notified.</div>
                </div>
            </div>
        </form>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.leave-requests.apply') }}">
            @foreach (['per_page' => $perPage, 'status' => $currentStatus] as $name => $value)
                @if ((string) $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Request status">
                    @foreach ($tabs as $value => $tab)
                        <a href="{{ $listUrl(['status' => $value === '' ? null : $value]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $currentStatus === (string) $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $statusCounts[$value === '' ? 'all' : $value] ?? 0 }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search employee" aria-label="Search requests">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#leave-filter-panel" aria-expanded="false" aria-controls="leave-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <div class="collapse" id="leave-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" class="form-select form-select-sm">
                                <option value="">All employees</option>
                                @foreach ($employees as $id => $name)
                                    <option value="{{ $id }}" @selected((string) ($state['employee_id'] ?? '') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Leave type</label>
                            <select name="leave_type_id" class="form-select form-select-sm">
                                <option value="">Any type</option>
                                @foreach ($leaveTypes as $id => $name)
                                    <option value="{{ $id }}" @selected((string) ($state['leave_type_id'] ?? '') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-12 col-lg-4">
                            <label class="form-label">On leave between</label>
                            <div class="list-date-range">
                                <input type="date" name="from" class="form-control form-control-sm" value="{{ $state['from'] ?? '' }}" aria-label="From">
                                <span>to</span>
                                <input type="date" name="to" class="form-control form-control-sm" value="{{ $state['to'] ?? '' }}" aria-label="To">
                            </div>
                        </div>
                        <div class="col-lg-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-de-primary btn-sm text-light flex-fill">Apply</button>
                            <a href="{{ $listUrl(array_fill_keys($panelKeys, null)) }}" class="btn btn-light btn-sm">Clear</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $listUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">
                            {{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong>
                            <i class="ti ti-x"></i>
                        </a>
                    @endforeach
                    <a href="{{ $clearAllUrl }}" class="list-clear-all">Clear all</a>
                </div>
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Leave</th>
                        <th>Dates</th>
                        <th class="text-center">Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $leaveRequest)
                        @php
                            $meta = $statusMeta[$leaveRequest->status] ?? ['tone' => 'slate', 'icon' => 'ti-point'];
                            $style = LeaveTypeStyle::for($leaveRequest->leaveType?->name, $leaveRequest->leaveType?->code);
                            $employee = $leaveRequest->employee;
                            $initials = collect(preg_split('/\s+/', trim((string) $employee?->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                            $start = $leaveRequest->start_date;
                            $end = $leaveRequest->end_date;
                            $isPending = $leaveRequest->status === 'pending';
                            $isOngoing = $leaveRequest->status === 'approved' && $start->lte(now()) && $end->gte(now()->startOfDay());
                        @endphp
                        <tr class="{{ $isPending ? 'is-pending-row' : '' }}">
                            <td>
                                @if ($employee)
                                    <div class="list-user">
                                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                        <div class="list-user-text">
                                            <div class="list-user-name"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></div>
                                            <div class="list-user-meta">{{ $employee->department?->name ?? $employee->employee_code }} · asked {{ $leaveRequest->created_at?->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td><span class="tag tone-{{ $style['tone'] }}"><i class="ti {{ $style['icon'] }}"></i>{{ $leaveRequest->leaveType?->name ?? '—' }}</span></td>
                            <td class="text-nowrap">
                                <div class="date-range-cell">
                                    <span class="when-block tone-{{ $style['tone'] }}"><span class="when-block-month">{{ $start->format('M') }}</span><span class="when-block-day">{{ $start->format('j') }}</span></span>
                                    @if (! $start->isSameDay($end))
                                        <i class="ti ti-arrow-right text-muted"></i>
                                        <span class="when-block tone-{{ $style['tone'] }}"><span class="when-block-month">{{ $end->format('M') }}</span><span class="when-block-day">{{ $end->format('j') }}</span></span>
                                    @endif
                                </div>
                                @if ($isOngoing)
                                    <span class="countdown tone-violet mt-1"><i class="ti ti-plane"></i> On leave now</span>
                                @elseif ($leaveRequest->status === 'approved' && $start->isFuture())
                                    <div class="list-subtext mt-1">starts {{ $start->diffForHumans() }}</div>
                                @endif
                            </td>
                            <td class="text-center"><span class="hours-figure">{{ $fmt($leaveRequest->days) }}<small>d</small></span></td>
                            <td class="comment-cell"><span class="comment-excerpt" title="{{ $leaveRequest->reason }}">{{ $leaveRequest->reason }}</span></td>
                            <td>
                                <span class="tag tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i>{{ $statuses[$leaveRequest->status] ?? $leaveRequest->status }}</span>
                                @if (! $isPending && $leaveRequest->reviewed_at)
                                    <div class="list-subtext mt-1">{{ $leaveRequest->reviewedBy ? 'by '.$leaveRequest->reviewedBy->name.' · ' : '' }}{{ $leaveRequest->reviewed_at->diffForHumans() }}</div>
                                @endif
                                @if ($leaveRequest->remarks)
                                    <div class="list-subtext mt-1 text-truncate" style="max-width: 200px" title="{{ $leaveRequest->remarks }}"><i class="ti ti-message-circle"></i> {{ $leaveRequest->remarks }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($isPending)
                                        @can('update', $leaveRequest)
                                            <form action="{{ route('admin.leave-requests.approve', $leaveRequest) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm review-btn is-approve" title="Approve"><i class="ti ti-check"></i> Approve</button>
                                            </form>
                                            <div class="dropdown d-inline-block">
                                                <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Reject">
                                                    <i class="ti ti-x"></i> Reject
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                                    <form action="{{ route('admin.leave-requests.reject', $leaveRequest) }}" method="POST">
                                                        @csrf
                                                        <label class="form-label font-12 fw-semibold">Reason for rejecting (optional)</label>
                                                        <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="Shared with the employee"></textarea>
                                                        <button type="submit" class="btn btn-sm btn-danger w-100"><i class="ti ti-x font-14 me-1"></i> Reject request</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endcan
                                    @endif
                                    @can('delete', $leaveRequest)
                                        @if ($leaveRequest->status !== 'approved')
                                            <form action="{{ route('admin.leave-requests.destroy', $leaveRequest) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this request?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-plane font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No leave requests found</div>
                                @if ($activeFilters->isNotEmpty())
                                    <span class="font-13"><a href="{{ $clearAllUrl }}">Clear filters</a></span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="list-footer">
            <div class="list-per-page dropdown">
                <span class="text-muted font-13">Rows per page</span>
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">{{ $perPage }}</button>
                <ul class="dropdown-menu">
                    @foreach ([10, 25, 50, 100] as $option)
                        <li><a class="dropdown-item {{ $perPage === $option ? 'active' : '' }}" href="{{ $listUrl(['per_page' => $option]) }}">{{ $option }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="ms-auto">{{ $requests->links() }}</div>
        </div>
    </div>
@endsection

@if ($canCreate)
    @push('scripts')
        <script>
            (function () {
                var employees = @json($employeePayload);
                var types = @json($typePayload);
                var balances = @json($balances);
                var booked = @json($booked);
                var $ = function (id) { return document.getElementById(id); };
                var employee = $('apply-employee'), start = $('apply-start'), end = $('apply-end'), reason = $('apply-reason');
                var typeById = {};
                types.forEach(function (t) { typeById[t.id] = t; });

                function fmt(n) { return (Math.round(n * 100) / 100).toString(); }
                function pad(n) { return String(n).padStart(2, '0'); }
                function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
                function nice(v) { return new Date(v + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' }); }
                function selectedType() {
                    var r = document.querySelector('input[name="leave_type_id"]:checked');
                    return r ? typeById[r.value] : null;
                }
                // Count days the same way the server's leave policy does.
                var policies = @json($policies);
                function policy() {
                    var emp = employees[employee.value];
                    return emp ? policies[emp.branch_id === null ? '' : String(emp.branch_id)] : null;
                }
                function holidayOn(p, d) {
                    var iso = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                    var hit = p.holidays.find(function (h) { return h.date === iso || (h.yearly && parseInt(h.date.slice(0, 4), 10) <= d.getFullYear() && h.date.slice(5) === iso.slice(5)); });
                    return hit ? hit.name : null;
                }
                function count() {
                    var out = { days: 0, weekends: 0, holidays: 0, sandwiched: 0 };
                    if (!start.value || !end.value || end.value < start.value) return out;
                    var p = policy(), r = p ? p.rules : { exclude_weekends: false, exclude_holidays: false, sandwich_rule: false, allow_half_day: false };
                    var kinds = [];
                    for (var d = new Date(start.value + 'T00:00:00'); d <= new Date(end.value + 'T00:00:00'); d.setDate(d.getDate() + 1)) {
                        var holiday = p && r.exclude_holidays && holidayOn(p, d);
                        var weekend = p && r.exclude_weekends && p.workingDays[d.getDay()] === false;
                        kinds.push(holiday ? 'holiday' : (weekend ? 'weekend' : 'work'));
                    }
                    var first = kinds.indexOf('work'), last = kinds.lastIndexOf('work');
                    kinds.forEach(function (k, i) {
                        if (k === 'work') out.days++;
                        else if (r.sandwich_rule && first !== -1 && i > first && i < last) { out.days++; out.sandwiched++; }
                        else if (k === 'weekend') out.weekends++;
                        else out.holidays++;
                    });
                    var half = document.getElementById('apply-half');
                    if (half.value && r.allow_half_day && start.value === end.value && out.days === 1) out.days = 0.5;
                    return out;
                }
                function days() { return count().days; }

                // Leave types are per branch: only show the employee's branch's types.
                function filterTypes() {
                    var emp = employees[employee.value];
                    var visible = 0;
                    document.querySelectorAll('.leave-type-option').forEach(function (opt) {
                        var branch = opt.dataset.branch;
                        var show = !emp || !branch || String(emp.branch_id) === branch;
                        opt.hidden = !show;
                        if (!show) { opt.querySelector('input').checked = false; } else { visible++; }

                        var t = typeById[opt.dataset.typeId];
                        var bal = emp && balances[employee.value] ? balances[employee.value][t.id] : null;
                        var label = opt.querySelector('[data-balance-for]');
                        opt.classList.toggle('is-empty', !!(bal && bal.remaining <= 0));
                        label.textContent = !emp ? fmt(t.days_per_year) + ' days / year'
                            : (bal ? fmt(bal.remaining) + ' of ' + fmt(bal.allocated) + ' left' : 'No balance allocated');
                    });
                    $('type-hint').querySelector('span').textContent = !emp
                        ? 'Select an employee to see their balances.'
                        : (visible ? 'Showing leave types for ' + emp.name + '\'s branch, with ' + new Date().getFullYear() + ' balances.' : 'No active leave types in this employee\'s branch.');
                }

                function refresh() {
                    var emp = employees[employee.value];
                    var type = selectedType();
                    var p = policy();
                    // Half days: only for a single date, when the policy allows them.
                    var halfWrap = document.getElementById('half-day-wrap');
                    halfWrap.hidden = !(p && p.rules.allow_half_day && start.value && start.value === end.value);
                    if (halfWrap.hidden) document.getElementById('apply-half').value = '';
                    var c = count();
                    var n = c.days;
                    var skipped = [];
                    if (c.weekends) skipped.push(c.weekends + ' weekend day' + (c.weekends === 1 ? '' : 's'));
                    if (c.holidays) skipped.push(c.holidays + ' holiday' + (c.holidays === 1 ? '' : 's'));
                    var skippedText = (skipped.length ? 'Not counted: ' + skipped.join(', ') : '') + (c.sandwiched ? (skipped.length ? ' · ' : '') + c.sandwiched + ' sandwiched day' + (c.sandwiched === 1 ? '' : 's') + ' counted' : '');
                    $('sum-skipped').hidden = !skippedText;
                    $('sum-skipped').textContent = skippedText;
                    if (start.value && end.value && end.value >= start.value && n === 0) $('sum-skipped').textContent = 'Only weekends and holidays - nothing to take.', $('sum-skipped').hidden = false;
                    $('sum-employee').textContent = emp ? emp.name : 'Choose an employee';
                    $('sum-type').textContent = type ? type.name + (type.is_paid ? ' · paid' : ' · unpaid') : 'No leave type selected';
                    $('sum-days').textContent = fmt(n);
                    $('sum-dates').textContent = start.value && end.value
                        ? (start.value === end.value ? nice(start.value) : nice(start.value) + ' → ' + nice(end.value))
                        : 'Pick the dates';
                    if (end.value && start.value && end.value < start.value) $('sum-dates').textContent = 'End date is before the start date';

                    var summary = $('leave-summary');
                    summary.className = 'leave-summary' + (type ? ' tone-' + type.tone : '');

                    var bal = emp && type && balances[employee.value] ? balances[employee.value][type.id] : null;
                    $('sum-balance').hidden = !bal;
                    $('sum-over').hidden = true;
                    if (bal) {
                        var after = bal.remaining - n;
                        $('bal-now').textContent = fmt(bal.remaining) + ' d';
                        $('bal-req').textContent = '− ' + fmt(n) + ' d';
                        $('bal-after').textContent = fmt(after) + ' d';
                        $('bal-after').classList.toggle('text-danger', after < 0);
                        $('sum-over').hidden = !(after < 0 && type.is_paid);
                    }

                    // Overlap with pending/approved leave already booked.
                    var clash = null;
                    if (emp && start.value && end.value) {
                        (booked[employee.value] || []).forEach(function (b) {
                            if (!clash && b.from <= end.value && b.to >= start.value) clash = b;
                        });
                    }
                    $('sum-overlap').hidden = !clash;
                    if (clash) {
                        $('sum-overlap').querySelector('span').textContent = 'Overlaps ' + clash.status + ' ' + (clash.type || 'leave') + ' (' + nice(clash.from) + (clash.from !== clash.to ? ' – ' + nice(clash.to) : '') + ').';
                    }
                }

                employee.addEventListener('change', function () { filterTypes(); refresh(); });
                document.querySelectorAll('input[name="leave_type_id"]').forEach(function (r) { r.addEventListener('change', refresh); });
                start.addEventListener('change', function () {
                    if (!end.value || end.value < start.value) end.value = start.value;
                    refresh();
                });
                end.addEventListener('change', refresh);
                document.getElementById('apply-half').addEventListener('change', refresh);

                document.querySelectorAll('#date-chips .quick-chip').forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        var d = new Date(), from, to;
                        switch (chip.dataset.range) {
                            case 'today': from = to = new Date(d); break;
                            case 'tomorrow': d.setDate(d.getDate() + 1); from = to = new Date(d); break;
                            case 'friday': d.setDate(d.getDate() + ((5 - d.getDay() + 7) % 7)); from = to = new Date(d); break;
                            case 'next-mon': d.setDate(d.getDate() + ((8 - d.getDay()) % 7 || 7)); from = to = new Date(d); break;
                            case 'next-week':
                                d.setDate(d.getDate() + ((8 - d.getDay()) % 7 || 7)); from = new Date(d);
                                d.setDate(d.getDate() + 4); to = new Date(d); break;
                        }
                        start.value = iso(from); end.value = iso(to);
                        refresh();
                    });
                });
                document.querySelectorAll('[data-reason]').forEach(function (chip) {
                    chip.addEventListener('click', function () { reason.value = chip.dataset.reason; reason.focus(); });
                });

                filterTypes();
                refresh();
            })();
        </script>
    @endpush
@endif
