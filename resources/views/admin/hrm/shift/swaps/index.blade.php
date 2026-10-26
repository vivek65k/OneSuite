@extends('layouts.admin')

@section('title', 'Shift Swap')

@php
    use App\Support\ShiftTime;
    use Illuminate\Support\Str;

    $statuses = \App\Models\ShiftSwapRequest::STATUSES;
    $statusMeta = [
        'pending' => ['tone' => 'amber', 'icon' => 'ti-clock'],
        'approved' => ['tone' => 'success', 'icon' => 'ti-circle-check'],
        'rejected' => ['tone' => 'danger', 'icon' => 'ti-circle-x'],
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
    $listUrl = fn (array $overrides = []) => route('admin.shift-swaps.index', array_filter(
        array_merge($state, ['page' => null], $overrides) + ['per_page' => 25],
        fn ($value) => $value !== null && $value !== ''
    ));

    $currentStatus = (string) ($state['status'] ?? '');
    $tabs = collect(['' => ['label' => 'All requests', 'tone' => 'indigo']])
        ->merge(collect($statuses)->map(fn ($label, $key) => ['label' => $label, 'tone' => $statusMeta[$key]['tone'] ?? 'slate']));
    $panelKeys = ['employee_id', 'from', 'to'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
    $canCreate = isset($formEmployees);
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $avatarTone = fn ($e) => $avatarTones[crc32($e->employee_code.$e->name) % count($avatarTones)];
    $initialsOf = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $isOnLeave = fn ($employeeId, $date) => $leave->contains(fn ($l) => (int) $l->employee_id === (int) $employeeId && $l->start_date->lte($date) && $l->end_date->gte($date));
@endphp

@section('content')
    <x-page-title title="Shift Swap" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2"><i class="ti ti-alert-circle font-18"></i> {{ session('error') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Pending review" :value="$kpis['pending']" icon="ti-clock" tone="amber" :href="$listUrl(['status' => 'pending'])"
                    :hint="$kpis['stale'] ? $kpis['stale'].' past their date' : ($kpis['pending_soon'] ? $kpis['pending_soon'].' due in the next 7 days' : 'Nothing urgent')" />
        <x-kpi-card label="Upcoming swaps" :value="$kpis['upcoming']" icon="ti-calendar-event" tone="teal" hint="Approved, today or later" :href="$listUrl(['status' => 'approved', 'from' => today()->toDateString()])" />
        <x-kpi-card label="Approved this month" :value="$kpis['approved_month']" icon="ti-circle-check" tone="success" :hint="now()->format('F Y')" />
        <x-kpi-card label="Rejected this month" :value="$kpis['rejected_month']" icon="ti-circle-x" tone="rose" :hint="now()->format('F Y')" />
    </div>

    @if ($canCreate)
        <div class="collapse {{ $errors->any() ? 'show' : '' }}" id="swap-form-panel">
            <form method="POST" action="{{ route('admin.shift-swaps.store') }}" id="swap-form" class="row g-3 mb-3">
                @csrf
                <div class="col-lg-8">
                    <div class="card form-section tone-indigo mb-0 h-100">
                        <div class="form-section-head">
                            <span class="form-section-icon"><i class="ti ti-arrows-right-left"></i></span>
                            <div class="flex-grow-1">
                                <h5 class="form-section-title">New swap request</h5>
                                <p class="form-section-sub">Two people in the same branch trade their shifts for one day.</p>
                            </div>
                            <button type="button" class="btn-icon" data-bs-toggle="collapse" data-bs-target="#swap-form-panel" title="Close"><i class="ti ti-x font-18"></i></button>
                        </div>
                        <div class="form-section-body">
                            @if ($errors->any())
                                <div class="alert alert-danger py-2 font-13">
                                    @foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach
                                </div>
                            @endif

                            <div class="swap-people">
                                <div>
                                    <label class="form-label" for="swap-a">Employee <span class="text-danger">*</span></label>
                                    <select name="employee_id" id="swap-a" class="form-select" required>
                                        <option value="">Who wants to swap?</option>
                                        @foreach ($formEmployees as $employee)
                                            <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>{{ $employee->name }} ({{ $employee->employee_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="swap-flip" id="swap-flip" title="Switch the two people"><i class="ti ti-arrows-right-left"></i></button>
                                <div>
                                    <label class="form-label" for="swap-b">Swap with <span class="text-danger">*</span></label>
                                    <select name="swap_with_employee_id" id="swap-b" class="form-select" required>
                                        <option value="">Choose a colleague</option>
                                        @foreach ($formEmployees as $employee)
                                            <option value="{{ $employee->id }}" data-branch="{{ $employee->branch_id }}" @selected((string) old('swap_with_employee_id') === (string) $employee->id)>{{ $employee->name }} ({{ $employee->employee_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mt-0">
                                <div class="col-md-5">
                                    <label class="form-label" for="swap-date">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date" id="swap-date" class="form-control" value="{{ old('date') }}" min="{{ today()->toDateString() }}" required>
                                    <div class="quick-chips" id="swap-date-chips">
                                        <button type="button" class="quick-chip" data-offset="0">Today</button>
                                        <button type="button" class="quick-chip" data-offset="1">Tomorrow</button>
                                        <button type="button" class="quick-chip" data-weekday="6">Saturday</button>
                                        <button type="button" class="quick-chip" data-weekday="1">Next Monday</button>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label" for="swap-reason">Reason <span class="text-danger">*</span></label>
                                    <input type="text" name="reason" id="swap-reason" class="form-control" value="{{ old('reason') }}" maxlength="1000" placeholder="e.g. Doctor's appointment in the morning" required>
                                    <div class="quick-chips">
                                        @foreach (['Personal appointment', 'Family function', 'Travel', 'Study / exam', 'Mutual preference'] as $reason)
                                            <button type="button" class="quick-chip" data-reason="{{ $reason }}">{{ $reason }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="swap-preview" id="swap-preview">
                        <div class="leave-summary-label">Swap preview</div>
                        <div class="swap-preview-date" id="pv-date">Pick a date</div>
                        <div class="swap-preview-grid">
                            <div class="swap-preview-side">
                                <div class="swap-preview-name" id="pv-a-name">Employee</div>
                                <div class="swap-chip" id="pv-a-now">—</div>
                                <div class="swap-preview-after"><i class="ti ti-arrow-down"></i> will work</div>
                                <div class="swap-chip" id="pv-a-after">—</div>
                            </div>
                            <span class="swap-preview-icon"><i class="ti ti-arrows-right-left"></i></span>
                            <div class="swap-preview-side">
                                <div class="swap-preview-name" id="pv-b-name">Colleague</div>
                                <div class="swap-chip" id="pv-b-now">—</div>
                                <div class="swap-preview-after"><i class="ti ti-arrow-down"></i> will work</div>
                                <div class="swap-chip" id="pv-b-after">—</div>
                            </div>
                        </div>
                        <div class="leave-alert is-danger" id="pv-problem" hidden><i class="ti ti-alert-circle"></i> <span></span></div>
                        <div class="leave-alert is-warning" id="pv-note" hidden><i class="ti ti-info-circle"></i> <span></span></div>
                        <button type="submit" class="btn btn-light w-100 mt-3 fw-semibold" id="swap-submit"><i class="ti ti-send font-14 me-1"></i> Submit request</button>
                        <div class="leave-summary-note">Rosters change only when the request is approved.</div>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.shift-swaps.index') }}">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search name or reason" aria-label="Search swaps">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#swap-filter-panel" aria-expanded="false" aria-controls="swap-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    @if ($canCreate)
                        <button type="button" class="btn btn-de-primary btn-sm text-light px-3" data-bs-toggle="collapse" data-bs-target="#swap-form-panel" aria-controls="swap-form-panel">
                            <i class="ti ti-plus font-14 me-1"></i> New swap
                        </button>
                    @endif
                </div>
            </div>

            <div class="collapse" id="swap-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-4">
                            <label class="form-label">Employee (either side)</label>
                            <select name="employee_id" class="form-select form-select-sm">
                                <option value="">All employees</option>
                                @foreach ($employees as $id => $name)
                                    <option value="{{ $id }}" @selected((string) ($state['employee_id'] ?? '') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-12 col-lg-5">
                            <label class="form-label">Swap date between</label>
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
                        <th>Date</th>
                        <th>Swap <span class="text-muted fw-normal">(shift on the day)</span></th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $swap)
                        @php
                            $meta = $statusMeta[$swap->status] ?? ['tone' => 'slate', 'icon' => 'ti-point'];
                            $isPending = $swap->status === 'pending';
                            $day = $swap->date;
                            $sides = [$swap->employee, $swap->swapWithEmployee];
                            $warnings = [];
                            if ($isPending && $day->lt(today())) { $warnings[] = 'Date has passed'; }
                            foreach ($sides as $person) {
                                if ($isPending && $person && $isOnLeave($person->id, $day)) { $warnings[] = $person->name.' is on leave'; }
                            }
                        @endphp
                        <tr class="{{ $isPending ? 'is-pending-row' : '' }}">
                            <td class="text-nowrap">
                                <div class="date-range-cell">
                                    <span class="when-block tone-{{ $meta['tone'] }}"><span class="when-block-month">{{ $day->format('M') }}</span><span class="when-block-day">{{ $day->format('j') }}</span></span>
                                    <div>
                                        <div class="fw-semibold">{{ $day->format('l') }}</div>
                                        @if ($day->isToday())
                                            <span class="countdown tone-indigo">Today</span>
                                        @elseif ($day->isFuture())
                                            <div class="list-subtext">in {{ (int) today()->diffInDays($day) }} {{ Str::plural('day', (int) today()->diffInDays($day)) }}</div>
                                        @else
                                            <div class="list-subtext">{{ $day->diffForHumans() }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="swap-pair">
                                    @foreach ($sides as $i => $person)
                                        @if ($i === 1)
                                            <span class="swap-pair-icon" title="{{ $isPending ? 'Will trade shifts' : 'Traded shifts' }}"><i class="ti ti-arrows-right-left"></i></span>
                                        @endif
                                        @if ($person)
                                            @php
                                                $shift = $rostered->get($person->id.'|'.$day->toDateString())?->shift;
                                                $tone = $shift ? ShiftTime::style($shift)['tone'] : null;
                                            @endphp
                                            <div class="list-user">
                                                <span class="list-avatar list-avatar-{{ $avatarTone($person) }}" aria-hidden="true">{{ $initialsOf($person->name) }}</span>
                                                <div class="list-user-text min-w-0">
                                                    <div class="list-user-name text-truncate"><a href="{{ route('admin.employees.show', $person) }}">{{ $person->name }}</a></div>
                                                    @if ($shift)
                                                        <span class="swap-mini tone-{{ $tone }}" title="{{ $shift->name }} {{ ShiftTime::format($shift->start_time) }}–{{ ShiftTime::format($shift->end_time) }}">{{ $shift->code }} · {{ ShiftTime::format($shift->start_time) }}</span>
                                                    @else
                                                        <span class="swap-mini is-off">Off</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">Removed employee</span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td class="comment-cell">
                                <span class="comment-excerpt" title="{{ $swap->reason }}">{{ $swap->reason }}</span>
                                @foreach ($warnings as $warning)
                                    <div><span class="tag tone-rose mt-1"><i class="ti ti-alert-triangle"></i>{{ $warning }}</span></div>
                                @endforeach
                            </td>
                            <td>
                                <span class="tag tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i>{{ $statuses[$swap->status] ?? $swap->status }}</span>
                                @if (! $isPending && $swap->reviewed_at)
                                    <div class="list-subtext mt-1">{{ $swap->reviewedBy ? 'by '.$swap->reviewedBy->name.' · ' : '' }}{{ $swap->reviewed_at->diffForHumans() }}</div>
                                @elseif ($isPending)
                                    <div class="list-subtext mt-1">asked {{ $swap->created_at?->diffForHumans() }}</div>
                                @endif
                                @if ($swap->remarks)
                                    <div class="list-subtext mt-1 text-truncate" style="max-width: 200px" title="{{ $swap->remarks }}"><i class="ti ti-message-circle"></i> {{ $swap->remarks }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($isPending)
                                        @can('update', $swap)
                                            <form action="{{ route('admin.shift-swaps.approve', $swap) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve and swap their shifts on {{ $day->format('M j') }}?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm review-btn is-approve" title="Approve"><i class="ti ti-check"></i> Approve</button>
                                            </form>
                                            <div class="dropdown d-inline-block">
                                                <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Reject">
                                                    <i class="ti ti-x"></i> Reject
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                                    <form action="{{ route('admin.shift-swaps.reject', $swap) }}" method="POST">
                                                        @csrf
                                                        <label class="form-label font-12 fw-semibold">Reason for rejecting (optional)</label>
                                                        <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="Shared with the employees"></textarea>
                                                        <button type="submit" class="btn btn-sm btn-danger w-100"><i class="ti ti-x font-14 me-1"></i> Reject request</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endcan
                                    @endif
                                    @can('delete', $swap)
                                        @if ($swap->status !== 'approved')
                                            <form action="{{ route('admin.shift-swaps.destroy', $swap) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this request?');">
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
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-arrows-right-left font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No shift swap requests found</div>
                                @if ($activeFilters->isNotEmpty())
                                    <span class="font-13"><a href="{{ $clearAllUrl }}">Clear filters</a></span>
                                @elseif ($canCreate)
                                    <span class="font-13"><a href="#swap-form-panel" data-bs-toggle="collapse">Create the first one</a></span>
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
                var data = @json($formPayload);
                var $ = function (id) { return document.getElementById(id); };
                var a = $('swap-a'), b = $('swap-b'), date = $('swap-date'), reason = $('swap-reason');

                function pad(n) { return String(n).padStart(2, '0'); }
                function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
                function nice(v) { return new Date(v + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' }); }
                function shiftOn(id, day) {
                    var sid = data.roster[id] && data.roster[id][day];
                    return sid ? data.shifts[sid] : null;
                }
                function onLeave(id, day) {
                    return (data.leave[id] || []).some(function (l) { return l.from <= day && l.to >= day; });
                }
                function chip(el, shift, off) {
                    el.className = 'swap-chip' + (shift ? ' tone-' + shift.tone : ' is-off');
                    el.textContent = shift ? shift.code : off;
                    if (shift) {
                        var small = document.createElement('small');
                        small.textContent = shift.time;
                        el.appendChild(small);
                    }
                }

                function refresh() {
                    var ea = data.employees[a.value], eb = data.employees[b.value], day = date.value;
                    // Only colleagues from the same branch can swap.
                    Array.prototype.forEach.call(b.options, function (opt) {
                        if (!opt.value) return;
                        opt.disabled = opt.value === a.value || (ea && String(opt.dataset.branch) !== String(ea.branch_id));
                    });
                    if (b.selectedOptions[0] && b.selectedOptions[0].disabled) { b.value = ''; eb = null; }

                    $('pv-a-name').textContent = ea ? ea.name : 'Employee';
                    $('pv-b-name').textContent = eb ? eb.name : 'Colleague';
                    $('pv-date').textContent = day ? nice(day) : 'Pick a date';

                    var sa = ea && day ? shiftOn(a.value, day) : null, sb = eb && day ? shiftOn(b.value, day) : null;
                    var known = day && day <= data.until;
                    chip($('pv-a-now'), sa, ea && day ? (known ? 'Off' : '?') : '—');
                    chip($('pv-b-now'), sb, eb && day ? (known ? 'Off' : '?') : '—');
                    chip($('pv-a-after'), sb, ea && eb && day ? (known ? 'Off' : '?') : '—');
                    chip($('pv-b-after'), sa, ea && eb && day ? (known ? 'Off' : '?') : '—');

                    var problem = '';
                    if (ea && eb && day) {
                        var away = [a, b].filter(function (s) { return onLeave(s.value, day); }).map(function (s) { return data.employees[s.value].name; });
                        if (away.length) problem = away.join(' and ') + ' is on approved leave that day.';
                        else if (known && !sa && !sb) problem = 'Neither of them has a shift that day - nothing to swap.';
                        else if (sa && sb && sa === sb) problem = 'They already work the same shift that day.';
                    }
                    $('pv-problem').hidden = !problem;
                    $('pv-problem').querySelector('span').textContent = problem;
                    $('pv-note').hidden = !(day && !known);
                    $('pv-note').querySelector('span').textContent = 'Rosters beyond ' + nice(data.until) + ' are not previewed; they are checked on submit.';
                    $('swap-preview').className = 'swap-preview' + (problem ? ' has-problem' : '');
                    $('swap-submit').disabled = !!problem;
                }

                $('swap-flip').addEventListener('click', function () {
                    var t = a.value; a.value = b.value; refresh(); b.value = t; refresh();
                });
                document.querySelectorAll('#swap-date-chips .quick-chip').forEach(function (c) {
                    c.addEventListener('click', function () {
                        var d = new Date();
                        if (c.dataset.offset) d.setDate(d.getDate() + parseInt(c.dataset.offset, 10));
                        else { var w = parseInt(c.dataset.weekday, 10); d.setDate(d.getDate() + (((w - d.getDay()) + 7) % 7 || 7)); }
                        date.value = iso(d); refresh();
                    });
                });
                document.querySelectorAll('[data-reason]').forEach(function (c) {
                    c.addEventListener('click', function () { reason.value = c.dataset.reason; reason.focus(); });
                });
                [a, b, date].forEach(function (el) { el.addEventListener('change', refresh); });
                refresh();
            })();
        </script>
    @endpush
@endif
