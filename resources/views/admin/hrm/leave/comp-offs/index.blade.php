@extends('layouts.admin')

@section('title', 'Comp Off')

@php
    use App\Support\Attendance;

    $statusMeta = [
        'pending' => ['tone' => 'amber', 'icon' => 'ti-clock'],
        'approved' => ['tone' => 'success', 'icon' => 'ti-circle-check'],
        'rejected' => ['tone' => 'danger', 'icon' => 'ti-circle-x'],
    ];
    $statuses = \App\Models\CompOff::STATUSES;

    $filterLabels = ['search' => 'Search', 'status' => 'Status', 'employee_id' => 'Employee', 'from' => 'Worked from', 'to' => 'Worked until'];
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
    $listUrl = fn (array $overrides = []) => route('admin.comp-offs.index', array_filter(
        array_merge($state, ['page' => null], $overrides) + ['per_page' => 25],
        fn ($value) => $value !== null && $value !== ''
    ));
    $currentStatus = (string) ($state['status'] ?? '');
    $tabs = collect(['' => ['label' => 'All claims', 'tone' => 'indigo']])
        ->merge(collect($statuses)->map(fn ($label, $key) => ['label' => $label, 'tone' => $statusMeta[$key]['tone'] ?? 'slate']));
    $panelKeys = ['employee_id', 'from', 'to'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $canCreate = auth()->user()->can('create', \App\Models\CompOff::class);
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];

    $lastSat = now()->isSaturday() ? now() : now()->previous(\Carbon\CarbonInterface::SATURDAY);
    $lastSun = now()->isSunday() ? now() : now()->previous(\Carbon\CarbonInterface::SUNDAY);
@endphp

@section('content')
    <x-page-title title="Comp Off" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @error('comp_off')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for review" :value="$kpis['pending']" icon="ti-clock" tone="amber" :href="$listUrl(['status' => 'pending'])" />
        <x-kpi-card label="Credited this month" :value="$kpis['credited_month']" icon="ti-circle-check" tone="success" :hint="$kpis['credited_year'].' in '.now()->year" :href="$listUrl(['status' => 'approved'])" />
        <x-kpi-card label="Comp-off days available" :value="$fmt($kpis['balance'])" icon="ti-repeat" tone="amber"
                    :hint="$kpis['holders'] ? 'Held by '.$kpis['holders'].' '.Str::plural('employee', $kpis['holders']) : 'Nobody holds any'" :href="route('admin.leave-balances.index')" />
        <x-kpi-card label="How it works" value="1 day" icon="ti-info-circle" tone="indigo" hint="Credited per approved worked day" />
    </div>

    @if ($canCreate)
        <div class="collapse {{ $errors->hasAny(['employee_id', 'worked_date', 'reason']) ? 'show' : '' }}" id="new-comp-off">
            <div class="card form-section tone-amber mb-3">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-plus"></i></span>
                    <h5 class="form-section-title">Claim a comp off</h5>
                </div>
                <div class="form-section-body">
                    @if ($errors->hasAny(['employee_id', 'worked_date', 'reason']))
                        <div class="alert alert-danger py-2 font-13">@foreach (collect(['employee_id', 'worked_date', 'reason'])->flatMap(fn ($field) => $errors->get($field)) as $message)<div>{{ $message }}</div>@endforeach</div>
                    @endif
                    <form method="POST" action="{{ route('admin.comp-offs.store') }}" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label" for="co-employee">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" id="co-employee" class="form-select" required>
                                <option value="">Select employee</option>
                                @foreach ($employees as $id => $name)
                                    <option value="{{ $id }}" @selected((string) old('employee_id') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="co-date">Day worked <span class="text-danger">*</span></label>
                            <input type="date" name="worked_date" id="co-date" class="form-control" value="{{ old('worked_date') }}" max="{{ now()->toDateString() }}" required>
                            <div class="quick-chips">
                                <button type="button" class="quick-chip" data-date="{{ $lastSat->toDateString() }}">Sat {{ $lastSat->format('M j') }}</button>
                                <button type="button" class="quick-chip" data-date="{{ $lastSun->toDateString() }}">Sun {{ $lastSun->format('M j') }}</button>
                                @foreach ($recentHolidays as $holiday)
                                    <button type="button" class="quick-chip" data-date="{{ $holiday->date->toDateString() }}" title="Company holiday"><i class="ti ti-confetti"></i> {{ $holiday->name }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="co-reason">What was worked on <span class="text-danger">*</span></label>
                            <input type="text" name="reason" id="co-reason" class="form-control" value="{{ old('reason') }}" maxlength="1000" placeholder="e.g. Weekend production release" required>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#new-comp-off">Cancel</button>
                            <button type="submit" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-send font-14 me-1"></i> Submit claim</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.comp-offs.index') }}">
            @foreach (['per_page' => $perPage, 'status' => $currentStatus] as $name => $value)
                @if ((string) $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Claim status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search employee" aria-label="Search claims">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#co-filter-panel" aria-expanded="false" aria-controls="co-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    @if ($canCreate)
                        <button type="button" class="btn btn-de-primary btn-sm text-light px-3" data-bs-toggle="collapse" data-bs-target="#new-comp-off" aria-expanded="false" aria-controls="new-comp-off">
                            <i class="ti ti-plus font-14 me-1"></i> New claim
                        </button>
                    @endif
                </div>
            </div>

            <div class="collapse" id="co-filter-panel">
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
                            <label class="form-label">Worked between</label>
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
                        <th>Day worked</th>
                        <th>Evidence</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $compOff)
                        @php
                            $meta = $statusMeta[$compOff->status] ?? ['tone' => 'slate', 'icon' => 'ti-point'];
                            $employee = $compOff->employee;
                            $date = $compOff->worked_date;
                            $holiday = $holidays[$date->toDateString()] ?? null;
                            $record = $attendance->get($compOff->employee_id.'-'.$date->toDateString());
                            $dayKind = $holiday ? ['label' => $holiday, 'tone' => 'pink', 'icon' => 'ti-confetti'] : ($date->isWeekend() ? ['label' => $date->format('l'), 'tone' => 'violet', 'icon' => 'ti-calendar'] : ['label' => 'Working day', 'tone' => 'slate', 'icon' => 'ti-briefcase']);
                            $balance = $compBalances[$compOff->employee_id] ?? null;
                            $initials = collect(preg_split('/\s+/', trim((string) $employee?->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                            $isPending = $compOff->status === 'pending';
                        @endphp
                        <tr class="{{ $isPending ? 'is-pending-row' : '' }}">
                            <td>
                                @if ($employee)
                                    <div class="list-user">
                                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                        <div class="list-user-text">
                                            <div class="list-user-name"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></div>
                                            <div class="list-user-meta">
                                                {{ $employee->department?->name ?? $employee->employee_code }}
                                                @if ($balance !== null)
                                                    <span class="list-meta-sep">&middot;</span> <span title="Comp-off days available this year">{{ $fmt($balance) }} comp-off left</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <div class="when-cell">
                                    <span class="when-block tone-{{ $dayKind['tone'] }}"><span class="when-block-month">{{ $date->format('M') }}</span><span class="when-block-day">{{ $date->format('j') }}</span></span>
                                    <div>
                                        <div class="fw-semibold font-13">{{ $date->format('D, Y') }}</div>
                                        <div class="list-subtext">{{ $date->diffForHumans() }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="evidence">
                                    <span class="tag tone-{{ $dayKind['tone'] }}"><i class="ti {{ $dayKind['icon'] }}"></i>{{ $dayKind['label'] }}</span>
                                    @if ($record)
                                        @php $am = Attendance::meta($record->status); @endphp
                                        <span class="tag tag-outline tone-{{ $am['tone'] }}" title="Attendance on that day">
                                            <i class="ti {{ $am['icon'] }}"></i>{{ Attendance::label($record->status) }}{{ $record->check_in_time ? ' · '.Attendance::formatTime($record->check_in_time).'–'.(Attendance::formatTime($record->check_out_time) ?? '…') : '' }}
                                        </span>
                                    @else
                                        <span class="tag tag-outline tone-amber" title="No attendance recorded for that day"><i class="ti ti-alert-circle"></i>No attendance</span>
                                    @endif
                                </div>
                            </td>
                            <td class="comment-cell"><span class="comment-excerpt" title="{{ $compOff->reason }}">{{ $compOff->reason }}</span></td>
                            <td>
                                <span class="tag tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i>{{ $statuses[$compOff->status] ?? $compOff->status }}</span>
                                @if ($compOff->status === 'approved')
                                    <div class="list-subtext mt-1 text-success"><i class="ti ti-plus"></i> 1 day credited</div>
                                @endif
                                @if (! $isPending && $compOff->reviewed_at)
                                    <div class="list-subtext mt-1">{{ $compOff->reviewedBy ? 'by '.$compOff->reviewedBy->name.' · ' : '' }}{{ $compOff->reviewed_at->diffForHumans() }}</div>
                                @endif
                                @if ($compOff->remarks)
                                    <div class="list-subtext mt-1 text-truncate" style="max-width: 200px" title="{{ $compOff->remarks }}"><i class="ti ti-message-circle"></i> {{ $compOff->remarks }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($isPending)
                                        @can('update', $compOff)
                                            <form action="{{ route('admin.comp-offs.approve', $compOff) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm review-btn is-approve" title="Approve and credit 1 day"><i class="ti ti-check"></i> Approve</button>
                                            </form>
                                            <div class="dropdown d-inline-block">
                                                <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                                    <i class="ti ti-x"></i> Reject
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                                    <form action="{{ route('admin.comp-offs.reject', $compOff) }}" method="POST">
                                                        @csrf
                                                        <label class="form-label font-12 fw-semibold">Reason for rejecting (optional)</label>
                                                        <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="Shared with the employee"></textarea>
                                                        <div class="quick-chips mt-0 mb-2">
                                                            @foreach (['No attendance that day', 'Not pre-approved', 'Regular working day'] as $preset)
                                                                <button type="button" class="quick-chip" onclick="this.closest('form').querySelector('textarea').value = this.textContent">{{ $preset }}</button>
                                                            @endforeach
                                                        </div>
                                                        <button type="submit" class="btn btn-sm btn-danger w-100"><i class="ti ti-x font-14 me-1"></i> Reject claim</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endcan
                                    @endif
                                    @can('delete', $compOff)
                                        @if ($compOff->status !== 'approved')
                                            <form action="{{ route('admin.comp-offs.destroy', $compOff) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this claim?');">
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
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-repeat font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No comp off claims found</div>
                                @if ($activeFilters->isNotEmpty())
                                    <span class="font-13"><a href="{{ $clearAllUrl }}">Clear filters</a></span>
                                @else
                                    <span class="font-13">When someone works a weekend or holiday, claim it here - approval credits 1 day of Comp Off leave.</span>
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
        document.querySelectorAll('[data-date]').forEach(function (chip) {
            chip.addEventListener('click', function () { document.getElementById('co-date').value = chip.dataset.date; });
        });
    </script>
@endpush
