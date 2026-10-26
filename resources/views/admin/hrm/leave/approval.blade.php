@extends('layouts.admin')

@section('title', 'Leave Approval')

@php
    use App\Support\LeaveTypeStyle;

    $filterLabels = ['search' => 'Search', 'employee_id' => 'Employee', 'leave_type_id' => 'Leave type', 'department_id' => 'Department', 'window' => 'Starting'];
    $windowOptions = ['urgent' => 'Within 7 days', 'later' => 'Later', 'past' => 'Already started'];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => match ($key) {
            'employee_id' => $employees[$state[$key]] ?? $state[$key],
            'leave_type_id' => $leaveTypes[$state[$key]] ?? $state[$key],
            'department_id' => $departments[$state[$key]] ?? $state[$key],
            'window' => $windowOptions[$state[$key]] ?? $state[$key],
            default => $state[$key],
        }]);

    // Every link carries the full state - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route('admin.leave-requests.approval', array_filter(
        array_merge($state, ['page' => null], $overrides) + ['per_page' => 25],
        fn ($value) => $value !== null && $value !== ''
    ));
    $currentWindow = (string) ($state['window'] ?? '');
    $currentSort = $state['sort'] ?? 'start';
    $panelKeys = ['employee_id', 'leave_type_id', 'department_id'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $oldest = $kpis['oldest'] ? \Illuminate\Support\Carbon::parse($kpis['oldest']) : null;
    $canReview = auth()->user()->can('update_leave_request');
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
@endphp

@section('content')
    <x-page-title title="Leave Approval" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for you" :value="$kpis['pending']" icon="ti-inbox" tone="amber" :hint="$oldest ? 'Oldest waiting '.$oldest->diffForHumans(null, true) : 'Inbox zero'" :href="$listUrl(['window' => null])" />
        <x-kpi-card label="Starting within 7 days" :value="$kpis['urgent']" icon="ti-alert-triangle" tone="{{ $kpis['urgent'] ? 'danger' : 'success' }}" :hint="$kpis['urgent'] ? 'Decide these first' : 'Nothing urgent'" :href="$listUrl(['window' => 'urgent'])" />
        <x-kpi-card label="Days requested" :value="$fmt($kpis['days'])" icon="ti-calendar-stats" tone="violet" hint="Across all pending requests" />
        <x-kpi-card label="Apply for someone" value="+" icon="ti-plane" tone="indigo" hint="Open the leave form" :href="route('admin.leave-requests.apply')" />
    </div>

    <div class="card list-view mb-3">
        <form method="GET" action="{{ route('admin.leave-requests.approval') }}">
            @foreach (['per_page' => $perPage, 'window' => $currentWindow, 'sort' => $currentSort === 'start' ? null : $currentSort] as $name => $value)
                @if ((string) $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="list-toolbar">
                <div class="segmented mb-3" role="group" aria-label="Starting">
                    <a href="{{ $listUrl(['window' => null]) }}" class="segmented-item {{ $currentWindow === '' ? 'active' : '' }}">All pending</a>
                    @foreach ($windowOptions as $value => $label)
                        <a href="{{ $listUrl(['window' => $value]) }}" class="segmented-item {{ $currentWindow === $value ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search employee" aria-label="Search requests">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#approval-filter-panel" aria-expanded="false" aria-controls="approval-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-arrows-sort font-14 me-1"></i> {{ $currentSort === 'oldest' ? 'Waiting longest' : 'Soonest start' }}</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item {{ $currentSort !== 'oldest' ? 'active' : '' }}" href="{{ $listUrl(['sort' => null]) }}">Soonest start</a></li>
                            <li><a class="dropdown-item {{ $currentSort === 'oldest' ? 'active' : '' }}" href="{{ $listUrl(['sort' => 'oldest']) }}">Waiting longest</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="collapse" id="approval-filter-panel">
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
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select form-select-sm">
                                <option value="">All departments</option>
                                @foreach ($departments as $id => $name)
                                    <option value="{{ $id }}" @selected((string) ($state['department_id'] ?? '') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3 d-flex align-items-end gap-2">
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

        @if ($canReview && $requests->isNotEmpty())
            <form method="POST" action="{{ route('admin.leave-requests.bulk-approve') }}" id="bulk-approve-form" class="bulk-bar" onsubmit="return confirm('Approve all selected leave requests? Balances will be deducted and attendance marked.');">
                @csrf
                <label class="bulk-select">
                    <input type="checkbox" class="form-check-input" id="select-all" aria-label="Select all on this page">
                    <span id="bulk-summary">Select requests</span>
                </label>
                <div class="bulk-actions">
                    <button type="submit" class="btn btn-sm review-btn is-approve" disabled data-bulk-action><i class="ti ti-checks"></i> Approve selected</button>
                </div>
            </form>
        @endif
    </div>

    @if ($requests->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-success"><i class="ti ti-circle-check"></i></span>
                <h5>{{ $activeFilters->isNotEmpty() ? 'No pending requests match' : 'All caught up' }}</h5>
                <p>
                    @if ($activeFilters->isNotEmpty())
                        <a href="{{ $clearAllUrl }}">Clear the filters</a> to see every pending request.
                    @else
                        There are no leave requests waiting for approval.
                    @endif
                </p>
            </div>
        </div>
    @else
        <div class="approval-list">
            @foreach ($requests as $leaveRequest)
                @php
                    $employee = $leaveRequest->employee;
                    $type = $leaveRequest->leaveType;
                    $style = LeaveTypeStyle::for($type?->name, $type?->code);
                    $start = $leaveRequest->start_date;
                    $end = $leaveRequest->end_date;
                    $startsIn = (int) now()->startOfDay()->diffInDays($start, false);
                    $balance = $balances->get($leaveRequest->employee_id.'-'.$leaveRequest->leave_type_id.'-'.$start->year);
                    $after = $balance ? (float) $balance->remaining - (float) $leaveRequest->days : null;
                    $teamClashes = $clashes[$leaveRequest->id] ?? collect();
                    $initials = collect(preg_split('/\s+/', trim((string) $employee?->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                @endphp
                <article class="approval-card tone-{{ $style['tone'] }} {{ $startsIn <= 7 ? 'is-urgent' : '' }}">
                    @if ($canReview)
                        <input type="checkbox" class="form-check-input approval-select" name="ids[]" value="{{ $leaveRequest->id }}" form="bulk-approve-form" data-row-select aria-label="Select {{ $employee?->name }}'s request">
                    @endif

                    <div class="approval-who">
                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32((string) $employee?->employee_code.$employee?->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                        <div class="min-w-0">
                            <div class="list-user-name"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee?->name }}</a></div>
                            <div class="list-user-meta">{{ $employee?->department?->name ?? $employee?->employee_code }}</div>
                            <div class="list-subtext mt-1"><i class="ti ti-clock"></i> asked {{ $leaveRequest->created_at?->diffForHumans() }}</div>
                        </div>
                    </div>

                    <div class="approval-leave">
                        <span class="tag tone-{{ $style['tone'] }}"><i class="ti {{ $style['icon'] }}"></i>{{ $type?->name ?? 'Leave' }}</span>
                        <div class="approval-dates">
                            <span class="when-block tone-{{ $style['tone'] }}"><span class="when-block-month">{{ $start->format('M') }}</span><span class="when-block-day">{{ $start->format('j') }}</span></span>
                            @unless ($start->isSameDay($end))
                                <i class="ti ti-arrow-right text-muted"></i>
                                <span class="when-block tone-{{ $style['tone'] }}"><span class="when-block-month">{{ $end->format('M') }}</span><span class="when-block-day">{{ $end->format('j') }}</span></span>
                            @endunless
                            <span class="approval-days">{{ $fmt($leaveRequest->days) }}<small>{{ (float) $leaveRequest->days == 1 ? 'day' : 'days' }}</small></span>
                        </div>
                        @if ($startsIn < 0)
                            <span class="countdown tone-danger"><i class="ti ti-alert-triangle"></i> Started {{ abs($startsIn) }}d ago</span>
                        @elseif ($startsIn === 0)
                            <span class="countdown tone-danger"><i class="ti ti-alert-triangle"></i> Starts today</span>
                        @elseif ($startsIn <= 7)
                            <span class="countdown tone-amber"><i class="ti ti-clock"></i> Starts in {{ $startsIn }}d</span>
                        @else
                            <span class="countdown tone-slate">Starts {{ $start->format('D, M j') }}</span>
                        @endif
                    </div>

                    <div class="approval-context">
                        <blockquote class="approval-reason" title="{{ $leaveRequest->reason }}">“{{ Str::limit($leaveRequest->reason, 140) }}”</blockquote>
                        <div class="approval-facts">
                            @if ($balance)
                                <span class="approval-fact {{ $after < 0 ? 'is-bad' : '' }}" title="{{ $type?->name }} balance for {{ $start->year }}">
                                    <i class="ti ti-scale"></i> Balance {{ $fmt($balance->remaining) }} → <strong>{{ $fmt($after) }}</strong>
                                    @if ($after < 0) <em>({{ $type?->is_paid ? 'overdrawn' : 'unpaid' }})</em> @endif
                                </span>
                            @else
                                <span class="approval-fact is-warn"><i class="ti ti-scale"></i> No {{ $start->year }} balance allocated</span>
                            @endif
                            @if ($teamClashes->isNotEmpty())
                                <span class="approval-fact is-warn" title="{{ $teamClashes->map(fn ($c) => $c->employee?->name.' ('.$c->status.', '.$c->start_date->format('M j').'–'.$c->end_date->format('M j').')')->implode(', ') }}">
                                    <i class="ti ti-users"></i> Also off in {{ $employee?->department?->name ?? 'team' }}: {{ $teamClashes->pluck('employee.name')->filter()->unique()->take(2)->implode(', ') }}{{ $teamClashes->pluck('employee_id')->unique()->count() > 2 ? ' +'.($teamClashes->pluck('employee_id')->unique()->count() - 2) : '' }}
                                </span>
                            @elseif ($employee?->department_id)
                                <span class="approval-fact is-good"><i class="ti ti-users"></i> No one else in {{ $employee->department?->name }} is off</span>
                            @endif
                        </div>
                    </div>

                    @if ($canReview)
                        <div class="approval-actions">
                            <form action="{{ route('admin.leave-requests.approve', $leaveRequest) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm review-btn is-approve w-100"><i class="ti ti-check"></i> Approve</button>
                            </form>
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm review-btn is-reject w-100" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                    <i class="ti ti-x"></i> Reject
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                    <form action="{{ route('admin.leave-requests.reject', $leaveRequest) }}" method="POST">
                                        @csrf
                                        <label class="form-label font-12 fw-semibold">Reason for rejecting (optional)</label>
                                        <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="Shared with {{ $employee?->name }}"></textarea>
                                        <div class="quick-chips mt-0 mb-2">
                                            @foreach (['Team is short-staffed', 'Critical deadline', 'Insufficient balance'] as $preset)
                                                <button type="button" class="quick-chip" onclick="this.closest('form').querySelector('textarea').value = this.textContent">{{ $preset }}</button>
                                            @endforeach
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-danger w-100"><i class="ti ti-x font-14 me-1"></i> Reject request</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        @if ($requests->hasPages())
            <div class="list-footer job-grid-footer">
                <div class="ms-auto">{{ $requests->links() }}</div>
            </div>
        @endif
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var all = document.getElementById('select-all');
            if (!all) return;
            var rows = Array.prototype.slice.call(document.querySelectorAll('[data-row-select]'));
            var action = document.querySelector('[data-bulk-action]');
            var summary = document.getElementById('bulk-summary');
            function refresh() {
                var picked = rows.filter(function (box) { return box.checked; }).length;
                action.disabled = picked === 0;
                summary.textContent = picked ? picked + ' selected' : 'Select requests';
                all.checked = picked > 0 && picked === rows.length;
                all.indeterminate = picked > 0 && picked < rows.length;
                rows.forEach(function (box) { box.closest('.approval-card').classList.toggle('is-selected', box.checked); });
            }
            all.addEventListener('change', function () { rows.forEach(function (box) { box.checked = all.checked; }); refresh(); });
            rows.forEach(function (box) { box.addEventListener('change', refresh); });
            refresh();
        })();
    </script>
@endpush
