@extends('layouts.admin')

@section('title', 'Overtime')

@php
    use App\Support\ShiftTime;
    use Illuminate\Support\Str;

    $statuses = \App\Models\OvertimeRequest::STATUSES;
    $statusMeta = [
        'pending' => ['tone' => 'amber', 'icon' => 'ti-clock'],
        'approved' => ['tone' => 'success', 'icon' => 'ti-circle-check'],
        'rejected' => ['tone' => 'danger', 'icon' => 'ti-circle-x'],
    ];
    $checkMeta = [
        'ok' => ['label' => 'Matches attendance', 'tone' => 'success', 'icon' => 'ti-circle-check'],
        'over' => ['label' => 'More than attendance shows', 'tone' => 'danger', 'icon' => 'ti-alert-triangle'],
        'no_record' => ['label' => 'No attendance record', 'tone' => 'slate', 'icon' => 'ti-help'],
        'no_punch' => ['label' => 'Incomplete punches', 'tone' => 'amber', 'icon' => 'ti-alert-circle'],
        'absent' => ['label' => 'Marked absent', 'tone' => 'rose', 'icon' => 'ti-user-x'],
        'leave' => ['label' => 'On leave', 'tone' => 'violet', 'icon' => 'ti-plane'],
        'future' => ['label' => 'Pre-approval', 'tone' => 'indigo', 'icon' => 'ti-calendar-event'],
    ];

    $filterLabels = ['search' => 'Search', 'status' => 'Status', 'employee_id' => 'Employee', 'from' => 'From', 'to' => 'To'];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => match ($key) {
            'status' => $statuses[$state[$key]] ?? $state[$key],
            'employee_id' => $employees[$state[$key]] ?? $state[$key],
            'from', 'to' => rescue(fn () => \Illuminate\Support\Carbon::parse($state[$key])->format('M j, Y'), $state[$key], false),
            default => $state[$key],
        }]);

    // Every link carries the full state - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route('admin.overtime-requests.index', array_filter(
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
    $canCreate = auth()->user()->can('create', \App\Models\OvertimeRequest::class);
    $canReview = auth()->user()->can('update', new \App\Models\OvertimeRequest());
    $hasPendingOnPage = $requests->getCollection()->contains('status', 'pending');
    $oldest = $kpis['oldest_pending'] ? \Illuminate\Support\Carbon::parse($kpis['oldest_pending']) : null;
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $avatarTone = fn ($e) => $avatarTones[crc32($e->employee_code.$e->name) % count($avatarTones)];
    $initialsOf = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $maxLeader = max(1, (float) ($leaders->max('hours') ?? 1));
@endphp

@section('content')
    <x-page-title title="Overtime" :breadcrumbs="['Attendance' => route('admin.attendance.dashboard')]" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2"><i class="ti ti-alert-circle font-18"></i> {{ session('error') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for review" :value="$kpis['pending']" icon="ti-clock" tone="amber" :href="$listUrl(['status' => 'pending'])"
                    :hint="$kpis['pending'] ? $fmt($kpis['pending_hours']).' h'.($oldest ? ' · oldest '.$oldest->diffForHumans(null, true) : '') : 'Nothing pending'" />
        <x-kpi-card label="Approved this month" :value="$fmt($kpis['approved_hours']).' h'" icon="ti-alarm" tone="pink"
                    :hint="$kpis['approved_month'].' '.Str::plural('request', $kpis['approved_month']).' · '.$kpis['people_month'].' '.Str::plural('person', $kpis['people_month'])"
                    :href="$listUrl(['status' => 'approved', 'from' => now()->startOfMonth()->toDateString(), 'to' => null])" />
        <x-kpi-card label="Paid as" :value="$fmt($kpis['paid_hours']).' h'" icon="ti-trending-up" tone="violet" hint="This month, after overtime rates" :href="route('admin.overtime-rules.index')" />
        <x-kpi-card label="Rejected this month" :value="$kpis['rejected_month']" icon="ti-circle-x" tone="danger" :href="$listUrl(['status' => 'rejected'])" />
    </div>

    @if ($leaders->isNotEmpty())
        <div class="card side-list tone-pink mb-3">
            <div class="side-list-head">
                <span class="side-list-icon"><i class="ti ti-trophy"></i></span>
                <span class="fw-semibold">Most overtime in {{ now()->format('F') }}</span>
                <span class="list-subtext ms-auto">Approved hours</span>
            </div>
            <div class="ot-leaders">
                @foreach ($leaders as $leader)
                    @php $person = $leader['employee']; @endphp
                    @if ($person)
                        <a href="{{ $listUrl(['employee_id' => $person->id, 'status' => 'approved', 'from' => now()->startOfMonth()->toDateString()]) }}" class="ot-leader">
                            <span class="list-avatar list-avatar-{{ $avatarTone($person) }}" aria-hidden="true">{{ $initialsOf($person->name) }}</span>
                            <span class="ot-leader-body">
                                <span class="ot-leader-name">{{ $person->name }}</span>
                                <span class="week-hours-bar"><span style="width: {{ $leader['hours'] / $maxLeader * 100 }}%"></span></span>
                            </span>
                            <span class="hours-figure">{{ $fmt($leader['hours']) }}<small>h</small></span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @if ($canCreate)
        <div class="collapse {{ $errors->any() ? 'show' : '' }}" id="new-request-panel">
            <form method="POST" action="{{ route('admin.overtime-requests.store') }}" id="ot-form" class="row g-3 mb-3" data-lookup="{{ route('admin.overtime-requests.lookup') }}">
                @csrf
                <div class="col-lg-8">
                    <div class="card form-section tone-pink mb-0 h-100">
                        <div class="form-section-head">
                            <span class="form-section-icon"><i class="ti ti-alarm"></i></span>
                            <div class="flex-grow-1">
                                <h5 class="form-section-title">New overtime request</h5>
                                <p class="form-section-sub">Claim extra hours worked, or pre-approve them up to 30 days ahead.</p>
                            </div>
                            <button type="button" class="btn-icon" data-bs-toggle="collapse" data-bs-target="#new-request-panel" title="Close"><i class="ti ti-x font-18"></i></button>
                        </div>
                        <div class="form-section-body">
                            @if ($errors->any())
                                <div class="alert alert-danger py-2 font-13">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
                            @endif
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label" for="ot-employee">Employee <span class="text-danger">*</span></label>
                                    <select name="employee_id" id="ot-employee" class="form-select" required>
                                        <option value="">Select employee</option>
                                        @foreach ($formEmployees as $employee)
                                            <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>{{ $employee->name }} ({{ $employee->employee_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="ot-date">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date" id="ot-date" class="form-control" value="{{ old('date', today()->toDateString()) }}"
                                           min="{{ today()->subDays(90)->toDateString() }}" max="{{ today()->addDays(30)->toDateString() }}" required>
                                    <div class="quick-chips">
                                        <button type="button" class="quick-chip" data-offset="0">Today</button>
                                        <button type="button" class="quick-chip" data-offset="-1">Yesterday</button>
                                        <button type="button" class="quick-chip" data-offset="-2">2 days ago</button>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="ot-hours">Extra hours <span class="text-danger">*</span></label>
                                    <div class="stepper">
                                        <button type="button" class="btn-icon" data-step="-0.5" aria-label="Fewer hours"><i class="ti ti-minus"></i></button>
                                        <input type="number" step="0.25" min="0.25" max="16" name="hours" id="ot-hours" class="form-control" value="{{ old('hours') }}" placeholder="0" required>
                                        <button type="button" class="btn-icon" data-step="0.5" aria-label="More hours"><i class="ti ti-plus"></i></button>
                                    </div>
                                    <div class="quick-chips">
                                        @foreach ([1, 2, 3, 4] as $h)
                                            <button type="button" class="quick-chip" data-hours="{{ $h }}">{{ $h }} h</button>
                                        @endforeach
                                        <button type="button" class="quick-chip tone-success" id="ot-use-extra" hidden></button>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label" for="ot-reason">Reason <span class="text-danger">*</span></label>
                                    <input type="text" name="reason" id="ot-reason" class="form-control" value="{{ old('reason') }}" maxlength="1000" placeholder="What needed the extra time?" required>
                                    <div class="quick-chips">
                                        @foreach (['Project deadline', 'Month-end closing', 'Covering a colleague', 'Client support', 'Stock count'] as $reason)
                                            <button type="button" class="quick-chip" data-reason="{{ $reason }}">{{ $reason }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="leave-summary ot-summary" id="ot-summary">
                        <div class="leave-summary-label">That day</div>
                        <div class="leave-summary-who" id="os-who">Choose an employee</div>
                        <div class="leave-summary-type" id="os-shift">—</div>
                        <div class="leave-balance">
                            <div class="leave-balance-row"><span>Attendance</span><strong id="os-punch">—</strong></div>
                            <div class="leave-balance-row"><span>Hours worked</span><strong id="os-worked">—</strong></div>
                            <div class="leave-balance-row"><span id="os-base-label">Overtime starts after</span><strong id="os-base">—</strong></div>
                            <div class="leave-balance-row is-total"><span>Extra per attendance</span><strong id="os-extra">—</strong></div>
                        </div>
                        <div class="leave-summary-days mt-3"><span id="os-paid">0</span><small> h paid</small></div>
                        <div class="leave-summary-dates" id="os-rate">No overtime rule - paid at 1×</div>
                        <div class="leave-alert is-warning" id="os-warn" hidden><i class="ti ti-alert-triangle"></i> <span></span></div>
                        <div class="leave-alert is-danger" id="os-block" hidden><i class="ti ti-alert-circle"></i> <span></span></div>
                        <button type="submit" class="btn btn-light w-100 mt-3 fw-semibold" id="ot-submit"><i class="ti ti-send font-14 me-1"></i> Submit request</button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.overtime-requests.index') }}">
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
                            <label class="form-label">Dated between</label>
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

        @if ($canReview && $hasPendingOnPage)
            <form method="POST" action="{{ route('admin.overtime-requests.bulk-approve') }}" id="bulk-approve-form" class="bulk-bar" onsubmit="return confirm('Approve all selected overtime requests?');">
                @csrf
                <label class="bulk-select">
                    <input type="checkbox" class="form-check-input" id="select-all" aria-label="Select all pending on this page">
                    <span id="bulk-summary">Select pending requests</span>
                </label>
                <div class="bulk-actions">
                    <button type="submit" class="btn btn-sm review-btn is-approve" disabled data-bulk-action><i class="ti ti-checks"></i> Approve selected</button>
                </div>
            </form>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Day</th>
                        <th>Claimed</th>
                        <th>Attendance check</th>
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
                            $isPending = $item->status === 'pending';
                            $ctx = $context[$item->id];
                            $check = $checkMeta[$ctx['check']];
                            $shift = $ctx['shift'];
                        @endphp
                        <tr class="{{ $isPending ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    @if ($canReview && $isPending)
                                        <input type="checkbox" class="form-check-input me-1" name="ids[]" value="{{ $item->id }}" form="bulk-approve-form" data-row-select aria-label="Select {{ $employee?->name }}'s request">
                                    @endif
                                    @if ($employee)
                                        <span class="list-avatar list-avatar-{{ $avatarTone($employee) }}" aria-hidden="true">{{ $initialsOf($employee->name) }}</span>
                                        <div class="list-user-text">
                                            <div class="list-user-name"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></div>
                                            <div class="list-user-meta">{{ $employee->department?->name ?? $employee->employee_code }} · asked {{ $item->created_at?->diffForHumans() }}</div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="date-range-cell">
                                    <span class="when-block tone-pink"><span class="when-block-month">{{ $item->date->format('M') }}</span><span class="when-block-day">{{ $item->date->format('j') }}</span></span>
                                    <div>
                                        <div class="fw-semibold font-13">{{ $item->date->format('D') }}</div>
                                        @if ($shift)
                                            <span class="swap-mini tone-{{ ShiftTime::style($shift)['tone'] }}" title="{{ $shift->name }}">{{ $shift->code }} · {{ ShiftTime::format($shift->start_time) }}</span>
                                        @else
                                            <span class="swap-mini is-off">No shift</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <span class="hours-figure">{{ $fmt($item->hours) }}<small>h</small></span>
                                <div class="list-subtext" title="{{ $ctx['rule'] ? 'Rule: '.$ctx['rule']->name : 'No overtime rule applies' }}">
                                    paid as {{ $fmt($ctx['paid']) }} h · {{ $fmt($ctx['rule']?->multiplier ?? 1) }}×
                                </div>
                            </td>
                            <td>
                                <span class="tag tone-{{ $check['tone'] }}"><i class="ti {{ $check['icon'] }}"></i>{{ $check['label'] }}{{ $ctx['check'] === 'leave' ? ' ('.$ctx['leave'].')' : '' }}</span>
                                @if ($ctx['worked'] !== null)
                                    <div class="list-subtext mt-1">
                                        Worked {{ $fmt($ctx['worked']) }} h
                                        @if ($ctx['baseline'] !== null)
                                            · {{ $fmt($ctx['extra']) }} h over {{ $fmt($ctx['baseline']) }} h
                                        @endif
                                    </div>
                                @elseif ($ctx['record'] && $ctx['record']->check_in_time)
                                    <div class="list-subtext mt-1">In {{ \App\Support\Attendance::formatTime($ctx['record']->check_in_time) }}, no check-out</div>
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
                                            <form action="{{ route('admin.overtime-requests.approve', $item) }}" method="POST" class="d-inline"
                                                  @if (in_array($ctx['check'], ['over', 'absent', 'leave'], true)) onsubmit="return confirm({{ json_encode($check['label'].' - approve anyway?') }});" @endif>
                                                @csrf
                                                <button type="submit" class="btn btn-sm review-btn is-approve" title="Approve"><i class="ti ti-check"></i> Approve</button>
                                            </form>
                                            <div class="dropdown d-inline-block">
                                                <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Reject">
                                                    <i class="ti ti-x"></i> Reject
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                                    <form action="{{ route('admin.overtime-requests.reject', $item) }}" method="POST">
                                                        @csrf
                                                        <label class="form-label font-12 fw-semibold">Reason for rejecting (optional)</label>
                                                        <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="Shared with the employee">{{ $ctx['check'] === 'over' ? 'Attendance shows only '.$fmt($ctx['extra']).' h of extra time.' : '' }}</textarea>
                                                        <button type="submit" class="btn btn-sm btn-danger w-100"><i class="ti ti-x font-14 me-1"></i> Reject request</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endcan
                                    @endif
                                    @can('delete', $item)
                                        @if ($item->status !== 'approved')
                                            <form action="{{ route('admin.overtime-requests.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this request?');">
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
                                <i class="ti ti-alarm font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">
                                    {{ $currentStatus === 'pending' && $activeFilters->count() <= 1 ? 'All caught up - nothing waiting for review' : 'No overtime requests found' }}
                                </div>
                                <span class="font-13 d-block mb-1">Approved hours flow into the monthly attendance report.</span>
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

@push('scripts')
    <script>
        // Bulk approve selection.
        (function () {
            var all = document.getElementById('select-all');
            if (!all) return;
            var rows = Array.prototype.slice.call(document.querySelectorAll('[data-row-select]'));
            var action = document.querySelector('[data-bulk-action]');
            var summary = document.getElementById('bulk-summary');
            function refresh() {
                var picked = rows.filter(function (box) { return box.checked; }).length;
                action.disabled = picked === 0;
                summary.textContent = picked ? picked + ' selected' : 'Select pending requests';
                all.checked = picked > 0 && picked === rows.length;
                all.indeterminate = picked > 0 && picked < rows.length;
                rows.forEach(function (box) { box.closest('tr').classList.toggle('is-selected', box.checked); });
            }
            all.addEventListener('change', function () { rows.forEach(function (box) { box.checked = all.checked; }); refresh(); });
            rows.forEach(function (box) { box.addEventListener('change', refresh); });
            refresh();
        })();

        // New request: look up that day's shift, attendance and overtime rule.
        (function () {
            var form = document.getElementById('ot-form');
            if (!form) return;
            var $ = function (id) { return document.getElementById(id); };
            var employee = $('ot-employee'), date = $('ot-date'), hours = $('ot-hours'), info = null, seq = 0;

            function fmt(n) { return (Math.round(n * 100) / 100).toString(); }
            function pad(n) { return String(n).padStart(2, '0'); }
            function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

            function render() {
                var h = parseFloat(hours.value) || 0;
                var rate = info && info.rule ? info.rule.multiplier : 1;
                $('os-who').textContent = employee.value ? employee.selectedOptions[0].textContent : 'Choose an employee';
                $('os-shift').textContent = !info ? '—' : (info.shift ? info.shift.name + ' · ' + info.shift.time : 'No shift rostered');
                $('os-punch').textContent = !info ? '—' : (info.attendance ? (info.attendance.in || '—') + ' → ' + (info.attendance.out || '—') : 'No record');
                $('os-worked').textContent = info && info.attendance && info.attendance.worked !== null ? fmt(info.attendance.worked) + ' h' : '—';
                $('os-base-label').textContent = info && info.rule ? 'Overtime after (' + info.rule.name + ')' : 'Overtime starts after';
                $('os-base').textContent = info && info.baseline !== null ? fmt(info.baseline) + ' h' : '—';
                $('os-extra').textContent = info && info.extra !== null ? fmt(info.extra) + ' h' : '—';
                $('os-paid').textContent = fmt(h * rate);
                $('os-rate').textContent = info && info.rule ? fmt(h) + ' h × ' + fmt(rate) + ' (' + info.rule.name + ')' : 'No overtime rule - paid at 1×';

                var useExtra = $('ot-use-extra');
                useExtra.hidden = !(info && info.extra > 0);
                if (info && info.extra > 0) { useExtra.textContent = 'Use ' + fmt(info.extra) + ' h from attendance'; useExtra.dataset.hours = info.extra; }

                var block = '', warn = '';
                if (info && info.existing) block = 'Already has a ' + info.existing.status + ' request for this day (' + fmt(info.existing.hours) + ' h).';
                else if (info && info.leave) block = 'On approved leave that day (' + info.leave + ').';
                else if (info && info.extra !== null && h > info.extra + {{ \App\Http\Controllers\Admin\Hrm\OvertimeRequestController::TOLERANCE_HOURS }}) warn = 'Attendance shows only ' + fmt(info.extra) + ' h of extra time.';
                else if (info && !info.attendance && date.value <= iso(new Date())) warn = 'No attendance record for this day yet.';
                $('os-block').hidden = !block; $('os-block').querySelector('span').textContent = block;
                $('os-warn').hidden = !warn; $('os-warn').querySelector('span').textContent = warn;
                $('ot-submit').disabled = !!block;
                $('ot-summary').className = 'leave-summary ot-summary' + (block ? ' has-problem' : '');
            }

            function lookup() {
                info = null; render();
                if (!employee.value || !date.value) return;
                var mine = ++seq;
                fetch(form.dataset.lookup + '?' + new URLSearchParams({ employee_id: employee.value, date: date.value }), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) { if (mine === seq) { info = data; render(); } })
                    .catch(function () {});
            }

            document.querySelectorAll('#ot-form [data-offset]').forEach(function (chip) {
                chip.addEventListener('click', function () { var d = new Date(); d.setDate(d.getDate() + parseInt(chip.dataset.offset, 10)); date.value = iso(d); lookup(); });
            });
            document.querySelectorAll('#ot-form [data-hours]').forEach(function (chip) {
                chip.addEventListener('click', function () { hours.value = chip.dataset.hours; render(); });
            });
            $('ot-use-extra').addEventListener('click', function () { hours.value = fmt(parseFloat(this.dataset.hours)); render(); });
            document.querySelectorAll('#ot-form [data-step]').forEach(function (btn) {
                btn.addEventListener('click', function () { hours.value = fmt(Math.max(0.25, Math.min(16, (parseFloat(hours.value) || 0) + parseFloat(btn.dataset.step)))); render(); });
            });
            document.querySelectorAll('#ot-form [data-reason]').forEach(function (chip) {
                chip.addEventListener('click', function () { $('ot-reason').value = chip.dataset.reason; $('ot-reason').focus(); });
            });
            employee.addEventListener('change', lookup);
            date.addEventListener('change', lookup);
            hours.addEventListener('input', render);
            lookup();
        })();
    </script>
@endpush
