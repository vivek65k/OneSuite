@extends('layouts.admin')

@section('title', 'Leave Encashment')

@php
    use App\Support\LeaveTypeStyle;

    $statusMeta = [
        'pending' => ['tone' => 'amber', 'icon' => 'ti-clock'],
        'approved' => ['tone' => 'success', 'icon' => 'ti-circle-check'],
        'rejected' => ['tone' => 'danger', 'icon' => 'ti-circle-x'],
    ];
    $statuses = \App\Models\LeaveEncashment::STATUSES;

    $filterLabels = ['search' => 'Search', 'status' => 'Status', 'employee_id' => 'Employee', 'leave_type_id' => 'Leave type', 'year' => 'Year'];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => match ($key) {
            'status' => $statuses[$state[$key]] ?? $state[$key],
            'employee_id' => $employees[$state[$key]] ?? $state[$key],
            'leave_type_id' => $leaveTypes[$state[$key]] ?? $state[$key],
            default => $state[$key],
        }]);

    // Every link carries the full state - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route('admin.leave-encashments.index', array_filter(
        array_merge($state, ['page' => null], $overrides) + ['per_page' => 25],
        fn ($value) => $value !== null && $value !== ''
    ));
    $currentStatus = (string) ($state['status'] ?? '');
    $tabs = collect(['' => ['label' => 'All requests', 'tone' => 'indigo']])
        ->merge(collect($statuses)->map(fn ($label, $key) => ['label' => $label, 'tone' => $statusMeta[$key]['tone'] ?? 'slate']));
    $panelKeys = ['employee_id', 'leave_type_id', 'year'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $money = fn ($n) => number_format((float) $n, 2);
    $canCreate = auth()->user()->can('create', \App\Models\LeaveEncashment::class);
    $hasFormErrors = $errors->hasAny(['employee_id', 'leave_type_id', 'year', 'days', 'amount', 'reason']);
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];

    $typePayload = $formTypes->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'branch_id' => $t->branch_id] + LeaveTypeStyle::for($t->name, $t->code))->values();
    $employeePayload = $formEmployees->mapWithKeys(fn ($e) => [$e->id => ['name' => $e->name, 'branch_id' => $e->branch_id]]);
@endphp

@section('content')
    <x-page-title title="Leave Encashment" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @error('encashment')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for review" :value="$kpis['pending']" icon="ti-clock" tone="amber" :hint="$fmt($kpis['pending_days']).' days · '.($currency ? $currency.' ' : '').$money($kpis['pending_amount'])" :href="$listUrl(['status' => 'pending'])" />
        <x-kpi-card label="Days encashed {{ $year }}" :value="$fmt($kpis['approved_days'])" icon="ti-calendar-stats" tone="violet" hint="Approved" :href="$listUrl(['status' => 'approved', 'year' => $year])" />
        <x-kpi-card label="Paid out {{ $year }}" :value="($currency ? $currency.' ' : '').$money($kpis['approved_amount'])" icon="ti-cash" tone="success" hint="Approved amounts" />
        <x-kpi-card label="Rules" value="Paid only" icon="ti-info-circle" tone="indigo" hint="Up to the unused balance" />
    </div>

    @if ($canCreate)
        <div class="collapse {{ $hasFormErrors ? 'show' : '' }}" id="new-encashment">
            <form method="POST" action="{{ route('admin.leave-encashments.store') }}" class="row g-3 mb-3">
                @csrf
                <input type="hidden" name="year" value="{{ old('year', $year) }}">
                <div class="col-lg-8">
                    <div class="card form-section tone-success mb-0 h-100">
                        <div class="form-section-head">
                            <span class="form-section-icon"><i class="ti ti-cash"></i></span>
                            <div>
                                <h5 class="form-section-title">Encash unused leave</h5>
                                <p class="form-section-sub">Convert unused paid leave into pay, from the {{ $year }} balance.</p>
                            </div>
                        </div>
                        <div class="form-section-body">
                            @if ($hasFormErrors)
                                <div class="alert alert-danger py-2 font-13">@foreach (collect(['employee_id', 'leave_type_id', 'year', 'days', 'amount', 'reason'])->flatMap(fn ($field) => $errors->get($field)) as $message)<div>{{ $message }}</div>@endforeach</div>
                            @endif
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="enc-employee">Employee <span class="text-danger">*</span></label>
                                    <select name="employee_id" id="enc-employee" class="form-select" required>
                                        <option value="">Select employee</option>
                                        @foreach ($formEmployees as $employee)
                                            <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>{{ $employee->name }} ({{ $employee->employee_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Leave type <span class="text-danger">*</span></label>
                                    <div class="leave-type-picker">
                                        @foreach ($formTypes as $type)
                                            @php $style = LeaveTypeStyle::for($type->name, $type->code); @endphp
                                            <label class="leave-type-option tone-{{ $style['tone'] }}" data-type-id="{{ $type->id }}" data-branch="{{ $type->branch_id }}">
                                                <input type="radio" name="leave_type_id" value="{{ $type->id }}" @checked((string) old('leave_type_id') === (string) $type->id) required>
                                                <span class="leave-type-option-icon"><i class="ti {{ $style['icon'] }}"></i></span>
                                                <span class="leave-type-option-body">
                                                    <span class="leave-type-option-name">{{ $type->name }}</span>
                                                    <span class="leave-type-option-balance" data-available-for="{{ $type->id }}">Select an employee</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @if ($formTypes->isEmpty())
                                        <div class="form-hint"><i class="ti ti-info-circle"></i> No active paid leave types to encash.</div>
                                    @endif
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="enc-days">Days to encash <span class="text-danger">*</span></label>
                                    <div class="stepper">
                                        <button type="button" class="btn-icon" data-step="-0.5" aria-label="Fewer days"><i class="ti ti-minus"></i></button>
                                        <input type="number" step="0.5" min="0.5" max="365" name="days" id="enc-days" class="form-control" value="{{ old('days', 1) }}" required aria-label="Days to encash">
                                        <button type="button" class="btn-icon" data-step="0.5" aria-label="More days"><i class="ti ti-plus"></i></button>
                                    </div>
                                    <div class="quick-chips"><button type="button" class="quick-chip" id="enc-all" disabled>All available</button></div>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label" for="enc-amount">Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text input-group-text-tone">{{ $currency ?: '¤' }}</span>
                                        <input type="number" step="0.01" min="0" name="amount" id="enc-amount" class="form-control" value="{{ old('amount') }}" placeholder="0.00">
                                    </div>
                                    <div class="quick-chips"><button type="button" class="quick-chip" id="enc-suggest" hidden></button></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="enc-reason">Reason <span class="text-danger">*</span></label>
                                    <input type="text" name="reason" id="enc-reason" class="form-control" value="{{ old('reason') }}" maxlength="1000" placeholder="e.g. Year-end encashment of unused leave" required>
                                    <div class="quick-chips">
                                        @foreach (['Year-end encashment', 'Full & final settlement', 'Unused leave above carry-forward cap'] as $preset)
                                            <button type="button" class="quick-chip" data-reason="{{ $preset }}">{{ $preset }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="leave-summary tone-success" id="enc-summary" style="--tone: var(--success); --tone-border: rgba(31, 157, 120, 0.35);">
                        <div class="leave-summary-label">Encashment summary</div>
                        <div class="leave-summary-who" id="enc-sum-employee">Choose an employee</div>
                        <div class="leave-summary-type" id="enc-sum-type">No leave type selected</div>
                        <div class="leave-summary-days"><span id="enc-sum-days">1</span><small> days</small></div>
                        <div class="leave-summary-dates" id="enc-sum-amount">No amount entered</div>
                        <div class="leave-balance" id="enc-sum-balance" hidden>
                            <div class="leave-balance-row"><span>Available to encash</span><strong id="enc-bal-now">—</strong></div>
                            <div class="leave-balance-row"><span>This request</span><strong id="enc-bal-req">—</strong></div>
                            <div class="leave-balance-row is-total"><span>Left after</span><strong id="enc-bal-after">—</strong></div>
                        </div>
                        <div class="leave-alert is-danger" id="enc-over" hidden><i class="ti ti-alert-circle"></i> <span>More than the available balance - this will be refused.</span></div>
                        <button type="submit" class="btn btn-light w-100 mt-3 fw-semibold"><i class="ti ti-send font-14 me-1"></i> Submit request</button>
                        <div class="leave-summary-note">Available = unused {{ $year }} balance minus other pending encashments.</div>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.leave-encashments.index') }}">
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
                            data-bs-toggle="collapse" data-bs-target="#enc-filter-panel" aria-expanded="false" aria-controls="enc-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    @if ($canCreate)
                        <button type="button" class="btn btn-de-primary btn-sm text-light px-3" data-bs-toggle="collapse" data-bs-target="#new-encashment" aria-expanded="{{ $hasFormErrors ? 'true' : 'false' }}" aria-controls="new-encashment">
                            <i class="ti ti-plus font-14 me-1"></i> New encashment
                        </button>
                    @endif
                </div>
            </div>

            <div class="collapse" id="enc-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-4">
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
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label">Year</label>
                            <input type="number" name="year" min="2000" max="2100" class="form-control form-control-sm" value="{{ $state['year'] ?? '' }}" placeholder="Any">
                        </div>
                        <div class="col-lg-3 d-flex align-items-end gap-2">
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
                        <th class="text-center">Days</th>
                        <th>Amount</th>
                        <th>Balance impact</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $encashment)
                        @php
                            $meta = $statusMeta[$encashment->status] ?? ['tone' => 'slate', 'icon' => 'ti-point'];
                            $style = LeaveTypeStyle::for($encashment->leaveType?->name, $encashment->leaveType?->code);
                            $employee = $encashment->employee;
                            $initials = collect(preg_split('/\s+/', trim((string) $employee?->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                            $isPending = $encashment->status === 'pending';
                            $bal = $rowBalances->get($encashment->employee_id.'-'.$encashment->leave_type_id.'-'.$encashment->year);
                        @endphp
                        <tr class="{{ $isPending ? 'is-pending-row' : '' }}">
                            <td>
                                @if ($employee)
                                    <div class="list-user">
                                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                        <div class="list-user-text">
                                            <div class="list-user-name"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></div>
                                            <div class="list-user-meta">{{ $employee->department?->name ?? $employee->employee_code }} · asked {{ $encashment->created_at?->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $style['tone'] }}"><i class="ti {{ $style['icon'] }}"></i>{{ $encashment->leaveType?->name ?? '—' }}</span>
                                <div class="list-subtext mt-1">{{ $encashment->year }} balance</div>
                            </td>
                            <td class="text-center"><span class="hours-figure">{{ $fmt($encashment->days) }}<small>d</small></span></td>
                            <td class="text-nowrap">
                                @if ($encashment->amount !== null)
                                    <span class="salary-figure"><span class="salary-currency">{{ $currency }}</span> {{ $money($encashment->amount) }}</span>
                                    @if ((float) $encashment->days > 0)
                                        <div class="list-subtext">{{ $money((float) $encashment->amount / (float) $encashment->days) }} / day</div>
                                    @endif
                                @else
                                    <span class="text-muted font-12">Not set</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($bal)
                                    @if ($isPending)
                                        @php $after = (float) $bal->remaining - (float) $encashment->days; @endphp
                                        <span class="approval-fact {{ $after < 0 ? 'is-bad' : '' }}"><i class="ti ti-scale"></i> {{ $fmt($bal->remaining) }} → <strong>{{ $fmt($after) }}</strong></span>
                                    @else
                                        <span class="approval-fact"><i class="ti ti-scale"></i> {{ $fmt($bal->remaining) }} left now</span>
                                    @endif
                                @else
                                    <span class="approval-fact is-warn"><i class="ti ti-scale"></i> No balance</span>
                                @endif
                                <div class="comment-excerpt mt-1 font-12" title="{{ $encashment->reason }}">{{ Str::limit($encashment->reason, 60) }}</div>
                            </td>
                            <td>
                                <span class="tag tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i>{{ $statuses[$encashment->status] ?? $encashment->status }}</span>
                                @if (! $isPending && $encashment->reviewed_at)
                                    <div class="list-subtext mt-1">{{ $encashment->reviewedBy ? 'by '.$encashment->reviewedBy->name.' · ' : '' }}{{ $encashment->reviewed_at->diffForHumans() }}</div>
                                @endif
                                @if ($encashment->remarks)
                                    <div class="list-subtext mt-1 text-truncate" style="max-width: 200px" title="{{ $encashment->remarks }}"><i class="ti ti-message-circle"></i> {{ $encashment->remarks }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($isPending)
                                        @can('update', $encashment)
                                            <form action="{{ route('admin.leave-encashments.approve', $encashment) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-check"></i> Approve</button>
                                            </form>
                                            <div class="dropdown d-inline-block">
                                                <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                    <i class="ti ti-x"></i> Reject
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                                    <form action="{{ route('admin.leave-encashments.reject', $encashment) }}" method="POST">
                                                        @csrf
                                                        <label class="form-label font-12 fw-semibold">Reason for rejecting (optional)</label>
                                                        <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="Shared with the employee"></textarea>
                                                        <button type="submit" class="btn btn-sm btn-danger w-100"><i class="ti ti-x font-14 me-1"></i> Reject request</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endcan
                                    @endif
                                    @can('delete', $encashment)
                                        @if ($encashment->status !== 'approved')
                                            <form action="{{ route('admin.leave-encashments.destroy', $encashment) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this request?');">
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
                                <i class="ti ti-cash font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No encashment requests found</div>
                                @if ($activeFilters->isNotEmpty())
                                    <span class="font-13"><a href="{{ $clearAllUrl }}">Clear filters</a></span>
                                @else
                                    <span class="font-13">Use <strong>New encashment</strong> to pay out unused paid leave.</span>
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
                var available = @json($available);
                var rates = @json($dailyRates);
                var currency = @json($currency ?: '');
                var $ = function (id) { return document.getElementById(id); };
                var employee = $('enc-employee'), days = $('enc-days'), amount = $('enc-amount'), all = $('enc-all'), suggest = $('enc-suggest');
                var typeById = {};
                types.forEach(function (t) { typeById[t.id] = t; });
                function fmt(n) { return (Math.round(n * 100) / 100).toString(); }
                function money(n) { return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
                function selectedType() {
                    var r = document.querySelector('input[name="leave_type_id"]:checked');
                    return r ? typeById[r.value] : null;
                }
                function availableFor(typeId) {
                    return employee.value && available[employee.value] && available[employee.value][typeId] !== undefined ? available[employee.value][typeId] : 0;
                }

                function filterTypes() {
                    var emp = employees[employee.value];
                    document.querySelectorAll('.leave-type-option').forEach(function (opt) {
                        var branch = opt.dataset.branch;
                        var show = !emp || !branch || String(emp.branch_id) === branch;
                        opt.hidden = !show;
                        if (!show) opt.querySelector('input').checked = false;
                        var avail = availableFor(opt.dataset.typeId);
                        opt.classList.toggle('is-empty', !!emp && avail <= 0);
                        opt.querySelector('[data-available-for]').textContent = emp ? fmt(avail) + ' days encashable' : 'Select an employee';
                    });
                }

                function refresh() {
                    var emp = employees[employee.value];
                    var type = selectedType();
                    var n = parseFloat(days.value) || 0;
                    $('enc-sum-employee').textContent = emp ? emp.name : 'Choose an employee';
                    $('enc-sum-type').textContent = type ? type.name : 'No leave type selected';
                    $('enc-sum-days').textContent = fmt(n);
                    $('enc-sum-amount').textContent = amount.value ? (currency ? currency + ' ' : '') + money(amount.value) : 'No amount entered';

                    var avail = type ? availableFor(type.id) : null;
                    $('enc-sum-balance').hidden = !(emp && type);
                    all.disabled = !(emp && type && avail > 0);
                    all.textContent = avail > 0 ? 'All available (' + fmt(avail) + ')' : 'All available';
                    if (emp && type) {
                        $('enc-bal-now').textContent = fmt(avail) + ' d';
                        $('enc-bal-req').textContent = '− ' + fmt(n) + ' d';
                        $('enc-bal-after').textContent = fmt(avail - n) + ' d';
                        $('enc-bal-after').classList.toggle('text-danger', avail - n < 0);
                    }
                    $('enc-over').hidden = !(emp && type && n > avail);

                    var rate = emp ? rates[employee.value] : null;
                    suggest.hidden = !(rate && n > 0);
                    if (rate && n > 0) {
                        suggest.textContent = 'Use ' + (currency ? currency + ' ' : '') + money(rate * n) + ' (monthly salary ÷ 30 × ' + fmt(n) + ' days)';
                        suggest.dataset.value = (Math.round(rate * n * 100) / 100).toFixed(2);
                    }
                }

                employee.addEventListener('change', function () { filterTypes(); refresh(); });
                document.querySelectorAll('input[name="leave_type_id"]').forEach(function (r) { r.addEventListener('change', refresh); });
                [days, amount].forEach(function (el) { el.addEventListener('input', refresh); });
                all.addEventListener('click', function () { var t = selectedType(); if (t) { days.value = availableFor(t.id); refresh(); } });
                suggest.addEventListener('click', function () { amount.value = suggest.dataset.value; refresh(); });
                document.querySelectorAll('.stepper [data-step]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        days.value = Math.max(0.5, (parseFloat(days.value) || 0) + parseFloat(btn.dataset.step));
                        refresh();
                    });
                });
                document.querySelectorAll('[data-reason]').forEach(function (chip) {
                    chip.addEventListener('click', function () { $('enc-reason').value = chip.dataset.reason; });
                });

                filterTypes();
                refresh();
            })();
        </script>
    @endpush
@endif
