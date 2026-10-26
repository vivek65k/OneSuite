@extends('layouts.admin')

@section('title', 'KPIs')

@php
    use App\Models\EmployeeKpi;
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $pageUrl = fn (array $overrides = []) => route('admin.kpis.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All scorecard lines', 'tone' => 'indigo', 'count' => $counts['all']],
        'on_target' => ['label' => 'On target', 'tone' => 'success', 'count' => $counts['on_target']],
        'behind' => ['label' => 'Behind target', 'tone' => 'rose', 'count' => $counts['behind']],
        'unmeasured' => ['label' => 'No result yet', 'tone' => 'amber', 'count' => $counts['unmeasured']],
    ];
    $kpiNames = $kpis->pluck('name', 'id');
    $cycleNames = $cycles->pluck('name', 'id');
    $filterValues = [
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'kpi_id' => $kpiNames[$state['kpi_id']] ?? null,
        'cycle_id' => $cycleNames[$state['cycle_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['employee_id' => 'Employee', 'department_id' => 'Department', 'kpi_id' => 'KPI', 'cycle_id' => 'Cycle', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $activeKpis = $kpis->where('is_active', true)->values();
    $oldIds = collect((array) old('employee_ids', []))->map(fn ($v) => (string) $v);
    $oldCycle = (string) old('appraisal_cycle_id', $cycles->firstWhere('status', 'active')?->id ?? '');
    $canUpdateKpi = auth()->user()->can('update_kpi');
    $canDeleteKpi = auth()->user()->can('delete_kpi');
@endphp

@section('content')
    <x-page-title title="KPIs" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="KPI library" :value="$kpiStats['library']" icon="ti-list-check" tone="indigo" :hint="$kpiStats['inactive'] ? $kpiStats['inactive'].' switched off' : ($kpiStats['library'] ? 'Measures you can assign' : 'Add your first KPI')" />
        <x-kpi-card label="Assigned" :value="$kpiStats['assigned']" icon="ti-users" tone="teal" :hint="$kpiStats['assigned'] ? 'Across '.$kpiStats['people'].' '.Str::plural('employee', $kpiStats['people']) : 'Nobody has a KPI yet'" />
        <x-kpi-card label="Average achievement" :value="$kpiStats['average'] !== null ? $num($kpiStats['average']).'%' : '—'" icon="ti-target" :tone="$kpiStats['average'] === null ? 'slate' : ($kpiStats['average'] >= 100 ? 'success' : 'amber')"
                    :hint="$kpiStats['measured'] ? $kpiStats['on_target'].' of '.$kpiStats['measured'].' measured are on target' : 'No results recorded yet'" :href="$pageUrl(['tab' => 'on_target'])" />
        <x-kpi-card label="No result yet" :value="$kpiStats['unmeasured']" icon="ti-clock" :tone="$kpiStats['unmeasured'] ? 'amber' : 'success'" :hint="$kpiStats['unmeasured'] ? 'Waiting for an actual figure' : 'Every line has a result'" :href="$pageUrl(['tab' => 'unmeasured'])" />
    </div>

    <div class="row g-3 mb-3">
        {{-- Library --}}
        <div class="col-xl-5">
            <div class="card side-list tone-indigo mb-0 h-100">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-list-check"></i></span>
                    <span class="fw-semibold">KPI library</span>
                    <span class="side-list-count">{{ $kpis->count() }}</span>
                </div>
                @forelse ($kpis as $kpi)
                    @php $used = (int) ($usage[$kpi->id] ?? 0); @endphp
                    <div class="side-list-item kpi-lib-item {{ $kpi->is_active ? '' : 'is-off' }}">
                        <span class="list-avatar list-avatar-square tone-{{ $kpi->is_active ? 'indigo' : 'slate' }}" aria-hidden="true"><i class="ti ti-chart-line"></i></span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold">{{ $kpi->name }} @if ($kpi->unit)<span class="tag tone-slate">{{ $kpi->unit }}</span>@endif</div>
                            <div class="font-12 text-muted">{{ $kpi->is_active ? '' : 'Switched off · ' }}{{ $used ? 'On '.$used.' scorecard '.Str::plural('line', $used) : 'Not assigned yet' }}{{ $kpi->description ? ' · '.Str::limit($kpi->description, 60) : '' }}</div>
                        </div>
                        <div class="list-row-actions">
                            @if ($canUpdateKpi)
                                <form action="{{ route('admin.kpis.update', $kpi) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="is_active" value="{{ $kpi->is_active ? 0 : 1 }}">
                                    <button type="submit" class="btn-icon {{ $kpi->is_active ? 'btn-icon-success' : '' }}" title="{{ $kpi->is_active ? 'Switch off (keeps existing scorecards)' : 'Switch on' }}"><i class="ti {{ $kpi->is_active ? 'ti-toggle-right' : 'ti-toggle-left' }} font-18"></i></button>
                                </form>
                                <button type="button" class="btn-icon btn-icon-accent" title="Change KPI" data-bs-toggle="modal" data-bs-target="#kpi-modal"
                                        data-action="{{ route('admin.kpis.update', $kpi) }}" data-name="{{ $kpi->name }}" data-unit="{{ $kpi->unit }}" data-description="{{ $kpi->description }}"><i class="ti ti-pencil font-16"></i></button>
                            @endif
                            @if ($canDeleteKpi && ! $used)
                                <form action="{{ route('admin.kpis.destroy', $kpi) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this KPI from the library?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete KPI"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="card-body empty-state py-4">
                        <div class="empty-state-icon"><i class="ti ti-list-check"></i></div>
                        <div class="fw-semibold">No KPIs yet</div>
                        <div class="font-13 text-muted">A KPI is something you measure, like "Sales closed" or "Tickets resolved".</div>
                    </div>
                @endforelse
                @if ($canCreateKpi)
                    <form method="POST" action="{{ route('admin.kpis.store') }}" class="kpi-add">
                        @csrf
                        <div class="row g-2">
                            <div class="col-8">
                                <input type="text" name="name" maxlength="255" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="New KPI, e.g. Sales closed" aria-label="KPI name" required>
                            </div>
                            <div class="col-4">
                                <input type="text" name="unit" maxlength="50" class="form-control form-control-sm" value="{{ old('unit') }}" placeholder="Unit" aria-label="Unit" list="kpi-units">
                                <datalist id="kpi-units"><option value="%"><option value="count"><option value="hours"><option value="days"><option value="score"><option value="amount"></datalist>
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <input type="text" name="description" maxlength="1000" class="form-control form-control-sm" value="{{ old('description') }}" placeholder="How it is measured (optional)" aria-label="Description">
                                <button type="submit" class="btn btn-de-primary btn-sm text-light text-nowrap"><i class="ti ti-plus font-14"></i> Add KPI</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        {{-- Assign --}}
        <div class="col-xl-7">
            @if ($canAssign)
                <form method="POST" action="{{ route('admin.kpis.assign') }}" class="card form-section tone-teal mb-0 h-100" id="assign-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-user-plus"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Assign a KPI</h5>
                            <p class="form-section-sub">Give one KPI and target to one person or many. Record the result later on their scorecard line.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        @if ($activeKpis->isEmpty())
                            <div class="run-note tone-amber"><i class="ti ti-alert-triangle"></i><span>Add a KPI to the library first, then you can assign it here.</span></div>
                        @else
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="assign-kpi">KPI</label>
                                    <select name="kpi_id" id="assign-kpi" class="form-select @error('kpi_id') is-invalid @enderror" required>
                                        <option value="" data-unit="">Select KPI</option>
                                        @foreach ($activeKpis as $kpi)
                                            <option value="{{ $kpi->id }}" data-unit="{{ $kpi->unit }}" @selected((string) old('kpi_id') === (string) $kpi->id)>{{ $kpi->name }}{{ $kpi->unit ? ' ('.$kpi->unit.')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="assign-cycle">Appraisal cycle <span class="text-muted fw-normal">(optional)</span></label>
                                    <select name="appraisal_cycle_id" id="assign-cycle" class="form-select">
                                        <option value="">Not tied to a cycle</option>
                                        @foreach ($cycles as $cycle)
                                            <option value="{{ $cycle->id }}" @selected($oldCycle === (string) $cycle->id)>{{ $cycle->name }}{{ $cycle->status !== 'active' ? ' ('.$cycle->status.')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="assign-target">Target <span class="text-muted fw-normal" id="assign-unit"></span></label>
                                    <input type="number" step="0.01" min="0.01" name="target_value" id="assign-target" value="{{ old('target_value') }}" class="form-control @error('target_value') is-invalid @enderror" placeholder="What counts as 100%" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="assign-weight">Weight % <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="number" step="0.01" min="0" max="100" name="weight" id="assign-weight" value="{{ old('weight') }}" class="form-control @error('weight') is-invalid @enderror" placeholder="Share of the appraisal">
                                </div>
                                <div class="col-md-12">
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                        <label class="form-label mb-0">Who gets it</label>
                                        <span class="count-pill" id="assign-picked">0 selected</span>
                                        <div class="quick-chips mt-0 ms-auto">
                                            <button type="button" class="quick-chip" data-pick="all">Everyone</button>
                                            @foreach ($departments as $id => $name)
                                                <button type="button" class="quick-chip" data-pick="{{ $id }}">{{ $name }}</button>
                                            @endforeach
                                            <button type="button" class="quick-chip" data-pick="none">Clear</button>
                                        </div>
                                    </div>
                                    <div class="pick-list @error('employee_ids') is-invalid @enderror" id="assign-people">
                                        @forelse ($payable as $person)
                                            <label class="pick-item">
                                                <input type="checkbox" class="form-check-input" name="employee_ids[]" value="{{ $person->id }}" data-department="{{ $person->department_id }}" data-name="{{ $person->name }}" @checked($oldIds->contains((string) $person->id))>
                                                <x-avatar :person="$person" />
                                                <span class="pick-item-text">
                                                    <span class="pick-item-name">{{ $person->name }}</span>
                                                    <span class="pick-item-meta">{{ collect([$person->employee_code, $person->department?->name])->filter()->implode(' · ') }}</span>
                                                </span>
                                                <span class="tag tone-slate" data-role="budget" hidden></span>
                                            </label>
                                        @empty
                                            <div class="text-muted font-13 p-3">No employees yet.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <div class="run-note tone-amber mt-3" id="assign-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> <span id="assign-submit">Assign KPI</span></button>
                            </div>
                        @endif
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- Scorecards --}}
    <div class="card list-view">
        <form method="GET" action="{{ route('admin.kpis.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Achievement">
                    @foreach ($tabs as $value => $tab)
                        <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or KPI" aria-label="Search scorecards">
                    </div>
                    @if ($kpis->count() > 1)
                        <select name="kpi_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="KPI">
                            <option value="">All KPIs</option>
                            @foreach ($kpis as $kpi)
                                <option value="{{ $kpi->id }}" @selected($state['kpi_id'] === $kpi->id)>{{ $kpi->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if ($cycles->isNotEmpty())
                        <select name="cycle_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Appraisal cycle">
                            <option value="">All cycles</option>
                            @foreach ($cycles as $cycle)
                                <option value="{{ $cycle->id }}" @selected($state['cycle_id'] === $cycle->id)>{{ $cycle->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if ($departments->count() > 1)
                        <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected($state['department_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Employee">
                        <option value="">All employees</option>
                        @foreach ($employees as $id => $name)
                            <option value="{{ $id }}" @selected($state['employee_id'] === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $pageUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>KPI</th>
                        <th class="text-end">Weight</th>
                        <th class="text-end">Target</th>
                        <th class="text-end">Actual</th>
                        <th style="min-width: 170px">Achievement</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assignments as $assignment)
                        @php
                            $achieved = $assignment->achievementPercent();
                            $tone = $achieved === null ? 'slate' : ($achieved >= 100 ? 'success' : ($achieved >= 70 ? 'amber' : 'rose'));
                            $unit = $assignment->kpi?->unit;
                        @endphp
                        <tr>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$assignment->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ $pageUrl(['employee_id' => $assignment->employee_id, 'tab' => null]) }}" title="Show this person's scorecard">{{ $assignment->employee?->name ?? 'Removed employee' }}</a></div>
                                        <div class="list-user-meta">{{ collect([$assignment->employee?->employee_code, $assignment->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $assignment->kpi?->name ?? 'Removed KPI' }}</div>
                                @if ($assignment->appraisalCycle)
                                    <span class="tag tone-violet"><i class="ti ti-refresh"></i>{{ $assignment->appraisalCycle->name }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $assignment->weight !== null ? $num($assignment->weight).'%' : '—' }}</td>
                            <td class="text-end text-nowrap">{{ $num($assignment->target_value) }}@if ($unit) <span class="text-muted font-12">{{ $unit }}</span>@endif</td>
                            <td class="text-end text-nowrap">
                                @can('update', $assignment)
                                    <form method="POST" action="{{ route('admin.employee-kpis.update', $assignment) }}" class="kpi-actual">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" step="0.01" min="0" name="actual_value" value="{{ $assignment->actual_value !== null ? $num($assignment->actual_value) : '' }}" class="form-control form-control-sm text-end" placeholder="—" aria-label="Actual for {{ $assignment->kpi?->name }}">
                                        <button type="submit" class="btn-icon btn-icon-success" title="Save result"><i class="ti ti-check font-16"></i></button>
                                    </form>
                                @else
                                    {{ $assignment->actual_value !== null ? $num($assignment->actual_value) : '—' }}
                                @endcan
                            </td>
                            <td>
                                @if ($achieved !== null)
                                    <div class="mini-progress tone-{{ $tone }}">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ min(100, max(0, $achieved)) }}%"></span></div>
                                        <span class="mini-progress-label">{{ $num($achieved) }}%</span>
                                    </div>
                                    <div class="payslip-parts">{{ $achieved >= 100 ? 'Target met'.($achieved > 100 ? ', '.$num($achieved - 100).'% over' : '') : $num((float) $assignment->target_value - (float) $assignment->actual_value).' short of target' }}</div>
                                @else
                                    <span class="tag tone-amber"><i class="ti ti-clock"></i>No result yet</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $assignment)
                                        <button type="button" class="btn-icon btn-icon-accent" title="Change target or weight" data-bs-toggle="modal" data-bs-target="#line-modal"
                                                data-action="{{ route('admin.employee-kpis.update', $assignment) }}" data-who="{{ ($assignment->employee?->name ?? 'Removed employee').' · '.($assignment->kpi?->name ?? 'KPI') }}"
                                                data-target="{{ $num($assignment->target_value) }}" data-actual="{{ $assignment->actual_value !== null ? $num($assignment->actual_value) : '' }}" data-weight="{{ $assignment->weight !== null ? $num($assignment->weight) : '' }}"><i class="ti ti-pencil font-16"></i></button>
                                    @endcan
                                    @can('delete', $assignment)
                                        <form action="{{ route('admin.employee-kpis.destroy', $assignment) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this KPI from the scorecard? Its target and result are lost.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Remove from scorecard"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-target font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No scorecard lines match</div>
                                    <span class="font-13"><a href="{{ route('admin.kpis.index') }}">Show all</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">Nobody has a KPI yet</div>
                                    <span class="font-13">{{ $canAssign ? 'Add a KPI to the library, then assign it with the form above.' : 'KPIs assigned by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($assignments->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $assignments->links() }}</div></div>
        @endif
    </div>

    <div class="modal fade" id="kpi-modal" tabindex="-1" aria-labelledby="kpi-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="kpi-form">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="kpi-modal-title">Change KPI</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-8">
                            <label class="form-label" for="kpi-name">Name</label>
                            <input type="text" name="name" id="kpi-name" maxlength="255" class="form-control" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label" for="kpi-unit">Unit</label>
                            <input type="text" name="unit" id="kpi-unit" maxlength="50" class="form-control" list="kpi-units">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="kpi-description">How it is measured</label>
                            <textarea name="description" id="kpi-description" rows="2" maxlength="1000" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="font-12 text-muted mt-2">Renaming a KPI renames it on every scorecard that uses it.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save KPI</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="line-modal" tabindex="-1" aria-labelledby="line-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="line-form">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="line-modal-title">Change scorecard line</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="font-13 text-muted mb-3"><strong id="line-who" class="text-body"></strong></p>
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label" for="line-target">Target</label>
                            <input type="number" step="0.01" min="0.01" name="target_value" id="line-target" class="form-control" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label" for="line-actual">Actual</label>
                            <input type="number" step="0.01" min="0" name="actual_value" id="line-actual" class="form-control" placeholder="Not yet">
                        </div>
                        <div class="col-4">
                            <label class="form-label" for="line-weight">Weight %</label>
                            <input type="number" step="0.01" min="0" max="100" name="weight" id="line-weight" class="form-control">
                        </div>
                    </div>
                    <div class="font-12 text-muted mt-2" id="line-preview"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save line</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var trim = function (n) { return String(Math.round(n * 100) / 100); };

            var kpiModal = el('kpi-modal');
            if (kpiModal) {
                kpiModal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('kpi-form').action = b.dataset.action;
                    el('kpi-name').value = b.dataset.name; el('kpi-unit').value = b.dataset.unit; el('kpi-description').value = b.dataset.description;
                });
            }

            var lineModal = el('line-modal');
            if (lineModal) {
                var preview = function () {
                    var t = parseFloat(el('line-target').value), a = parseFloat(el('line-actual').value);
                    el('line-preview').textContent = t > 0 && !isNaN(a) ? 'Achievement: ' + trim(a / t * 100) + '% of target.' : 'Leave "Actual" empty until there is a result.';
                };
                lineModal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('line-form').action = b.dataset.action;
                    el('line-who').textContent = b.dataset.who;
                    el('line-target').value = b.dataset.target; el('line-actual').value = b.dataset.actual; el('line-weight').value = b.dataset.weight;
                    preview();
                });
                [el('line-target'), el('line-actual')].forEach(function (input) { input.addEventListener('input', preview); });
            }

            var form = el('assign-form');
            if (!form || !el('assign-kpi')) return;
            var weights = @json($weights);
            var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name="employee_ids[]"]'));
            var kpi = el('assign-kpi'), cycle = el('assign-cycle'), weight = el('assign-weight');

            function render() {
                var picked = boxes.filter(function (b) { return b.checked; });
                var mine = Math.max(0, parseFloat(weight.value) || 0);
                var unit = kpi.options[kpi.selectedIndex] ? kpi.options[kpi.selectedIndex].dataset.unit : '';
                var over = [];
                el('assign-unit').textContent = unit ? '(' + unit + ')' : '';
                el('assign-picked').textContent = picked.length + ' selected';
                el('assign-submit').textContent = picked.length > 1 ? 'Assign to ' + picked.length + ' people' : 'Assign KPI';
                boxes.forEach(function (b) {
                    var used = weights[b.value + ':' + (cycle.value || 0)] || 0;
                    var tag = b.closest('.pick-item').querySelector('[data-role="budget"]');
                    tag.hidden = !used;
                    tag.textContent = trim(used) + '% weight used';
                    if (b.checked && mine && used + mine > 100.001) over.push(b.dataset.name + ' (' + trim(Math.max(0, 100 - used)) + '% left)');
                });
                el('assign-warn').hidden = !over.length;
                el('assign-warn').querySelector('span').textContent = over.length ? 'A weight of ' + trim(mine) + '% takes these people past 100%: ' + over.slice(0, 3).join(', ') + (over.length > 3 ? ' and ' + (over.length - 3) + ' more' : '') + '. This will be rejected.' : '';
            }

            boxes.forEach(function (b) { b.addEventListener('change', render); });
            [kpi, cycle, weight].forEach(function (input) { input.addEventListener('input', render); input.addEventListener('change', render); });
            form.querySelectorAll('[data-pick]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var pick = chip.dataset.pick;
                    boxes.forEach(function (b) { b.checked = pick === 'all' || (pick !== 'none' && b.dataset.department === pick); });
                    render();
                });
            });
            form.addEventListener('submit', function (event) {
                if (!boxes.some(function (b) { return b.checked; })) {
                    event.preventDefault();
                    el('assign-people').classList.add('is-invalid');
                    el('assign-picked').textContent = 'Pick at least one person';
                }
            });
            render();
        })();
    </script>
@endpush
