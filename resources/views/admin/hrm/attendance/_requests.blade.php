{{--
    Shared list for Regularization / WFH / Overtime requests.
    Expects: $type ('regularization'|'wfh'|'overtime'), $requests, $state, $statusCounts, $kpis, $employees.
--}}
@php
    $config = [
        'regularization' => ['title' => 'Regularization Requests', 'noun' => 'regularization', 'tone' => 'amber', 'icon' => 'ti-edit', 'route' => 'admin.regularization-requests', 'model' => \App\Models\RegularizationRequest::class,
            'blurb' => 'Missed punches and time corrections. Approving updates the attendance record for that day.'],
        'wfh' => ['title' => 'Work From Home', 'noun' => 'WFH', 'tone' => 'sky', 'icon' => 'ti-home', 'route' => 'admin.wfh-requests', 'model' => \App\Models\WfhRequest::class,
            'blurb' => 'Remote work days. Approving marks every day in the range as Work From Home.'],
        'overtime' => ['title' => 'Overtime', 'noun' => 'overtime', 'tone' => 'pink', 'icon' => 'ti-alarm', 'route' => 'admin.overtime-requests', 'model' => \App\Models\OvertimeRequest::class,
            'blurb' => 'Extra hours worked. Approved hours flow into the monthly attendance report.'],
    ][$type];

    $statusMeta = [
        'pending' => ['tone' => 'amber', 'icon' => 'ti-clock'],
        'approved' => ['tone' => 'success', 'icon' => 'ti-circle-check'],
        'rejected' => ['tone' => 'danger', 'icon' => 'ti-circle-x'],
    ];
    $statuses = $config['model']::STATUSES;
    $indexRoute = $config['route'].'.index';

    $filterLabels = ['search' => 'Search', 'status' => 'Status', 'employee_id' => 'Employee', 'from' => 'From', 'to' => 'To'];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => match ($key) {
            'status' => $statuses[$state[$key]] ?? $state[$key],
            'employee_id' => $employees[$state[$key]] ?? $state[$key],
            'from', 'to' => \Illuminate\Support\Carbon::parse($state[$key])->format('M j, Y'),
            default => $state[$key],
        }]);

    // Every link carries the full state - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route($indexRoute, array_filter(
        array_merge($state, ['page' => null], $overrides) + ['per_page' => 25],
        fn ($value) => $value !== null && $value !== ''
    ));

    $currentStatus = (string) ($state['status'] ?? '');
    $tabs = collect(['' => ['label' => 'All', 'tone' => 'indigo']])
        ->merge(collect($statuses)->map(fn ($label, $key) => ['label' => $label, 'tone' => $statusMeta[$key]['tone'] ?? 'slate']));

    $panelKeys = ['employee_id', 'from', 'to'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
    $canCreate = auth()->user()->can('create', $config['model']);
    $oldest = $kpis['oldest_pending'] ? \Illuminate\Support\Carbon::parse($kpis['oldest_pending']) : null;
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
@endphp

<x-page-title :title="$config['title']" :breadcrumbs="['Attendance' => route('admin.attendance.dashboard')]" />

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="kpi-strip">
    <x-kpi-card label="Waiting for review" :value="$kpis['pending']" icon="ti-clock" tone="amber"
                :hint="$oldest ? 'Oldest waiting '.$oldest->diffForHumans(null, true) : 'Nothing pending'" :href="$listUrl(['status' => 'pending'])" />
    <x-kpi-card label="Approved this month" :value="$kpis['approved_month']" icon="ti-circle-check" tone="success" :href="$listUrl(['status' => 'approved'])" />
    <x-kpi-card label="Rejected this month" :value="$kpis['rejected_month']" icon="ti-circle-x" tone="danger" :href="$listUrl(['status' => 'rejected'])" />
    <x-kpi-card label="All requests" :value="$statusCounts['all'] ?? 0" :icon="$config['icon']" :tone="$config['tone']" hint="Matching current filters" />
</div>

@if ($canCreate)
    <div class="collapse {{ $errors->any() ? 'show' : '' }}" id="new-request-panel">
        <div class="card form-section tone-{{ $config['tone'] }} mb-3">
            <div class="form-section-head form-section-head-compact">
                <span class="form-section-icon form-section-icon-sm"><i class="ti ti-plus"></i></span>
                <h5 class="form-section-title">New {{ $config['noun'] }} request</h5>
            </div>
            <div class="form-section-body">
                @if ($errors->any())
                    <div class="alert alert-danger py-2 font-13">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
                @endif
                <form method="POST" action="{{ route($config['route'].'.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label" for="req-employee">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" id="req-employee" class="form-select" required>
                            <option value="">Select employee</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('employee_id') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($type === 'regularization')
                        <div class="col-md-3">
                            <label class="form-label" for="req-date">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" id="req-date" class="form-control" value="{{ old('date') }}" max="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="req-in">Check in</label>
                            <input type="time" name="requested_check_in" id="req-in" class="form-control" value="{{ old('requested_check_in') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="req-out">Check out</label>
                            <input type="time" name="requested_check_out" id="req-out" class="form-control" value="{{ old('requested_check_out') }}">
                        </div>
                    @elseif ($type === 'wfh')
                        <div class="col-md-4">
                            <label class="form-label" for="req-start">From <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="req-start" class="form-control" value="{{ old('start_date') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="req-end">To <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="req-end" class="form-control" value="{{ old('end_date') }}" required>
                        </div>
                    @else
                        <div class="col-md-4">
                            <label class="form-label" for="req-date">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" id="req-date" class="form-control" value="{{ old('date') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="req-hours">Hours <span class="text-danger">*</span></label>
                            <input type="number" step="0.25" min="0.25" max="24" name="hours" id="req-hours" class="form-control" value="{{ old('hours') }}" placeholder="e.g. 2.5" required>
                            <div class="quick-chips">
                                @foreach ([1, 2, 3, 4] as $h)
                                    <button type="button" class="quick-chip" onclick="document.getElementById('req-hours').value={{ $h }}">{{ $h }}h</button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="col-md-10">
                        <label class="form-label" for="req-reason">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" id="req-reason" class="form-control" value="{{ old('reason') }}" maxlength="1000" placeholder="Why is this needed?" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-de-primary text-light w-100"><i class="ti ti-send font-14 me-1"></i> Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="card list-view">
    <form method="GET" action="{{ route($indexRoute) }}">
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
                        data-bs-toggle="collapse" data-bs-target="#req-filter-panel" aria-expanded="false" aria-controls="req-filter-panel">
                    <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                    @if ($panelFilterCount)
                        <span class="list-filter-count">{{ $panelFilterCount }}</span>
                    @endif
                </button>
                @if ($canCreate)
                    <button type="button" class="btn btn-de-primary btn-sm text-light px-3" data-bs-toggle="collapse" data-bs-target="#new-request-panel" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}" aria-controls="new-request-panel">
                        <i class="ti ti-plus font-14 me-1"></i> New request
                    </button>
                @endif
            </div>
        </div>

        <div class="collapse" id="req-filter-panel">
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
                    <div class="col-sm-6 col-lg-5">
                        <label class="form-label">{{ $type === 'wfh' ? 'Starting between' : 'Dated between' }}</label>
                        <div class="list-date-range">
                            <input type="date" name="from" class="form-control form-control-sm" value="{{ $state['from'] ?? '' }}" aria-label="From">
                            <span>to</span>
                            <input type="date" name="to" class="form-control form-control-sm" value="{{ $state['to'] ?? '' }}" aria-label="To">
                        </div>
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
                    <th>{{ ['regularization' => 'Correction', 'wfh' => 'Dates', 'overtime' => 'Overtime'][$type] }}</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $item)
                    @php
                        $meta = $statusMeta[$item->status] ?? ['tone' => 'slate', 'icon' => 'ti-point'];
                        $employee = $item->employee;
                        $initials = collect(preg_split('/\s+/', trim((string) $employee?->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                        $isPending = $item->status === 'pending';
                    @endphp
                    <tr class="{{ $isPending ? 'is-pending-row' : '' }}">
                        <td>
                            @if ($employee)
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></div>
                                        <div class="list-user-meta">{{ $employee->department?->name ?? $employee->employee_code }} · asked {{ $item->created_at?->diffForHumans() }}</div>
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @if ($type === 'regularization')
                                <div class="when-cell">
                                    <span class="when-block tone-{{ $config['tone'] }}"><span class="when-block-month">{{ $item->date->format('M') }}</span><span class="when-block-day">{{ $item->date->format('j') }}</span></span>
                                    <span class="time-pair">
                                        <span><i class="ti ti-login text-success"></i> {{ \App\Support\Attendance::formatTime($item->requested_check_in) ?? '—' }}</span>
                                        <span><i class="ti ti-logout text-danger"></i> {{ \App\Support\Attendance::formatTime($item->requested_check_out) ?? '—' }}</span>
                                    </span>
                                </div>
                            @elseif ($type === 'wfh')
                                @php $days = $item->start_date->diffInDays($item->end_date) + 1; @endphp
                                <div class="fw-semibold font-13">{{ $item->start_date->format('M j') }}{{ $days > 1 ? ' – '.$item->end_date->format('M j, Y') : ', '.$item->start_date->format('Y') }}</div>
                                <span class="countdown tone-sky"><i class="ti ti-home"></i> {{ (int) $days }} {{ Str::plural('day', (int) $days) }}</span>
                            @else
                                <div class="when-cell">
                                    <span class="when-block tone-{{ $config['tone'] }}"><span class="when-block-month">{{ $item->date->format('M') }}</span><span class="when-block-day">{{ $item->date->format('j') }}</span></span>
                                    <span class="hours-figure">{{ rtrim(rtrim(number_format((float) $item->hours, 2), '0'), '.') }}<small>h</small></span>
                                </div>
                            @endif
                        </td>
                        <td class="comment-cell">
                            <span class="comment-excerpt" title="{{ $item->reason }}">{{ $item->reason }}</span>
                        </td>
                        <td>
                            <span class="tag tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i>{{ $statuses[$item->status] ?? $item->status }}</span>
                            @if (! $isPending && $item->reviewed_at)
                                <div class="list-subtext mt-1">{{ $item->reviewedBy ? 'by '.$item->reviewedBy->name.' · ' : '' }}{{ $item->reviewed_at->diffForHumans() }}</div>
                            @endif
                            @if ($item->remarks)
                                <div class="list-subtext mt-1 text-truncate" style="max-width: 200px" title="{{ $item->remarks }}"><i class="ti ti-message-circle"></i> {{ $item->remarks }}</div>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <div class="list-row-actions">
                                @if ($isPending)
                                    @can('update', $item)
                                        <form action="{{ route($config['route'].'.approve', $item) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm review-btn is-approve" title="Approve"><i class="ti ti-check"></i> Approve</button>
                                        </form>
                                        <div class="dropdown d-inline-block">
                                            <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Reject">
                                                <i class="ti ti-x"></i> Reject
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                                <form action="{{ route($config['route'].'.reject', $item) }}" method="POST">
                                                    @csrf
                                                    <label class="form-label font-12 fw-semibold">Reason for rejecting (optional)</label>
                                                    <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="Shared with the employee"></textarea>
                                                    <button type="submit" class="btn btn-sm btn-danger w-100"><i class="ti ti-x font-14 me-1"></i> Reject request</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endcan
                                @endif
                                @can('delete', $item)
                                    <form action="{{ route($config['route'].'.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this request?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="ti {{ $config['icon'] }} font-24 d-block mb-2"></i>
                            <div class="fw-semibold text-body mb-1">
                                {{ $currentStatus === 'pending' && $activeFilters->count() <= 1 ? 'All caught up - nothing waiting for review' : 'No requests found' }}
                            </div>
                            <span class="font-13 d-block mb-1">{{ $config['blurb'] }}</span>
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
