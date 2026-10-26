@extends('layouts.admin')

@section('title', 'Goal Management')

@php
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.goals.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All goals', 'tone' => 'indigo', 'count' => $counts['all']],
        'not_started' => ['label' => 'Not started', 'tone' => 'slate', 'count' => $counts['not_started']],
        'in_progress' => ['label' => 'In progress', 'tone' => 'amber', 'count' => $counts['in_progress']],
        'completed' => ['label' => 'Completed', 'tone' => 'success', 'count' => $counts['completed']],
        'overdue' => ['label' => 'Overdue', 'tone' => 'rose', 'count' => $counts['overdue']],
    ];
    $cycleNames = $cycles->pluck('name', 'id');
    $filterValues = [
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'cycle_id' => $cycleNames[$state['cycle_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['employee_id' => 'Employee', 'department_id' => 'Department', 'cycle_id' => 'Cycle', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $oldEmployee = (string) old('employee_id', '');
    $oldCycle = (string) old('appraisal_cycle_id', $cycles->firstWhere('status', 'active')?->id ?? '');
    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $completedPct = $kpis['total'] ? round($kpis['completed'] / $kpis['total'] * 100) : 0;
    $statusMeta = ['not_started' => ['Not started', 'slate', 'ti-circle-dashed'], 'in_progress' => ['In progress', 'amber', 'ti-clock'], 'completed' => ['Completed', 'success', 'ti-circle-check']];
@endphp

@section('content')
    <x-page-title title="Goal Management" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Open goals" :value="$kpis['open']" icon="ti-target" tone="indigo"
                    :hint="$kpis['open'] ? 'Across '.$kpis['people'].' '.Str::plural('employee', $kpis['people']).($kpis['without'] ? ' · '.$kpis['without'].' have none' : '') : ($kpis['without'] ? $kpis['without'].' '.Str::plural('employee', $kpis['without']).' have no goal' : 'No goals set yet')" />
        <x-kpi-card label="Average progress" :value="$kpis['average'] !== null ? $kpis['average'].'%' : '—'" icon="ti-chart-bar" tone="teal" :hint="$kpis['total'] ? 'Across all '.$kpis['total'].' '.Str::plural('goal', $kpis['total']) : null" />
        <x-kpi-card label="Completed" :value="$kpis['completed']" icon="ti-circle-check" tone="success" :hint="$kpis['total'] ? $completedPct.'% of all goals' : null" :href="$pageUrl(['tab' => 'completed'])" />
        <x-kpi-card label="Overdue" :value="$kpis['overdue']" icon="ti-alert-triangle" :tone="$kpis['overdue'] ? 'rose' : 'success'"
                    :hint="$kpis['overdue'] ? 'Past the target date, not finished' : ($kpis['due_soon'] ? $kpis['due_soon'].' due in the next two weeks' : 'Nothing is late')" :href="$pageUrl(['tab' => 'overdue'])" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.goals.store') }}" class="card form-section tone-indigo mb-0 h-100" id="goal-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-target"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Set a goal</h5>
                            <p class="form-section-sub">Give an employee something specific to achieve, with a date and a weight for their appraisal.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="goal-employee">Employee</label>
                                <select name="employee_id" id="goal-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($payable as $person)
                                        <option value="{{ $person->id }}" @selected($oldEmployee === (string) $person->id)>{{ $person->name }}{{ $person->department ? ' — '.$person->department->name : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="goal-cycle">Appraisal cycle <span class="text-muted fw-normal">(optional)</span></label>
                                <select name="appraisal_cycle_id" id="goal-cycle" class="form-select">
                                    <option value="" data-end="">Not tied to a cycle</option>
                                    @foreach ($cycles as $cycle)
                                        <option value="{{ $cycle->id }}" data-end="{{ $cycle->period_end?->toDateString() }}" @selected($oldCycle === (string) $cycle->id)>{{ $cycle->name }}{{ $cycle->status !== 'active' ? ' ('.$cycle->status.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="goal-title">Goal</label>
                                <input type="text" name="title" id="goal-title" value="{{ old('title') }}" maxlength="255" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. Close 12 new enterprise accounts" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="goal-description">How it will be measured <span class="text-muted fw-normal">(optional)</span></label>
                                <textarea name="description" id="goal-description" rows="2" maxlength="2000" class="form-control" placeholder="What counts as done, and how progress is tracked">{{ old('description') }}</textarea>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="goal-weight">Weight % <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="number" step="0.01" min="0" max="100" name="weight" id="goal-weight" value="{{ old('weight') }}" class="form-control @error('weight') is-invalid @enderror" placeholder="Share of the appraisal">
                                <div class="quick-chips">
                                    @foreach ([10, 20, 25, 50] as $chip)
                                        <button type="button" class="quick-chip" data-weight="{{ $chip }}">{{ $chip }}%</button>
                                    @endforeach
                                    <button type="button" class="quick-chip" data-weight="rest">All that's left</button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="goal-date">Target date <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="date" name="target_date" id="goal-date" value="{{ old('target_date') }}" max="{{ now()->addYears(5)->toDateString() }}" class="form-control @error('target_date') is-invalid @enderror">
                                <div class="quick-chips">
                                    <button type="button" class="quick-chip" data-date="{{ now()->endOfMonth()->toDateString() }}">End of month</button>
                                    <button type="button" class="quick-chip" data-date="{{ now()->endOfQuarter()->toDateString() }}">End of quarter</button>
                                    <button type="button" class="quick-chip" data-date="cycle" id="goal-date-cycle" hidden>End of cycle</button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Set goal</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-scale"></i></span>
                        <span class="fw-semibold">Weight budget</span>
                    </div>
                    <div class="card-body">
                        <div class="empty-state py-4" id="goal-budget-empty">
                            <div class="empty-state-icon"><i class="ti ti-user"></i></div>
                            <div class="fw-semibold">Pick an employee</div>
                            <div class="font-13 text-muted">You'll see how much of their 100% is already given to other goals.</div>
                        </div>
                        <div id="goal-budget" hidden>
                            <div class="rev-compare">
                                <div>
                                    <span class="rev-compare-label">Already given</span>
                                    <span class="rev-compare-value" id="goal-used">—</span>
                                    <span class="rev-compare-meta" id="goal-used-meta"></span>
                                </div>
                                <i class="ti ti-plus"></i>
                                <div>
                                    <span class="rev-compare-label">This goal</span>
                                    <span class="rev-compare-value" id="goal-this">—</span>
                                    <span class="rev-compare-meta" id="goal-left"></span>
                                </div>
                            </div>
                            <div class="stack-bar stack-bar-lg mt-3" id="goal-bar">
                                <span class="tone-slate" id="goal-bar-used" style="flex: 0"></span>
                                <span class="tone-indigo" id="goal-bar-this" style="flex: 0"></span>
                                <span class="goal-bar-rest" id="goal-bar-rest" style="flex: 100"></span>
                            </div>
                            <div class="run-note tone-amber mt-3" id="goal-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        </div>
                        <div class="run-note tone-indigo mt-3"><i class="ti ti-info-circle"></i><span>One person's goal weights in a cycle can add up to at most 100%. Leave the weight empty if you don't weight goals.</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.goals.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Goal status">
                    @foreach ($tabs as $value => $tab)
                        @if ($value !== 'overdue' || $tab['count'] || $state['tab'] === 'overdue')
                            <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                                {{ $tab['label'] }}
                                <span class="list-tab-count">{{ $tab['count'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search goal or name" aria-label="Search goals">
                    </div>
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
                        <th>Goal</th>
                        <th class="text-end">Weight</th>
                        <th>Target date</th>
                        <th style="min-width: 190px">Progress</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($goals as $goal)
                        @php
                            $done = $goal->status === 'completed';
                            $late = ! $done && $goal->target_date && $goal->target_date->lt(today());
                            $days = $goal->target_date ? (int) today()->diffInDays($goal->target_date, false) : null;
                            $meta = $statusMeta[$goal->status] ?? [$goal->status, 'slate', 'ti-circle'];
                            $tone = $done ? 'success' : ($late ? 'rose' : ($goal->status === 'in_progress' ? 'amber' : 'slate'));
                        @endphp
                        <tr class="{{ $late ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$goal->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ $pageUrl(['employee_id' => $goal->employee_id, 'tab' => null]) }}" title="Show this person's goals">{{ $goal->employee?->name ?? 'Removed employee' }}</a></div>
                                        <div class="list-user-meta">{{ collect([$goal->employee?->employee_code, $goal->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="goal-cell">
                                <div class="fw-semibold">{{ $goal->title }}</div>
                                @if ($goal->description)
                                    <div class="payslip-parts text-wrap" title="{{ $goal->description }}">{{ Str::limit($goal->description, 90) }}</div>
                                @endif
                                @if ($goal->appraisalCycle)
                                    <span class="tag tone-violet mt-1"><i class="ti ti-refresh"></i>{{ $goal->appraisalCycle->name }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $goal->weight !== null ? $num($goal->weight).'%' : '—' }}</td>
                            <td class="text-nowrap">
                                @if ($goal->target_date)
                                    <div class="fw-semibold">{{ $goal->target_date->format('M j, Y') }}</div>
                                    @if ($done)
                                        <div class="payslip-parts">Finished</div>
                                    @elseif ($late)
                                        <span class="tag tone-rose"><i class="ti ti-alert-triangle"></i>{{ abs($days) }} {{ Str::plural('day', abs($days)) }} overdue</span>
                                    @else
                                        <div class="payslip-parts">{{ $days === 0 ? 'Due today' : 'in '.$days.' '.Str::plural('day', $days) }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">No date</span>
                                @endif
                            </td>
                            <td>
                                <div class="mini-progress tone-{{ $tone }}">
                                    <div class="mini-progress-bar is-toned"><span style="width: {{ (int) $goal->progress_percent }}%"></span></div>
                                    <span class="mini-progress-label">{{ (int) $goal->progress_percent }}%</span>
                                </div>
                                <span class="tag tone-{{ $meta[1] }} mt-1"><i class="ti {{ $meta[2] }}"></i>{{ $meta[0] }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $goal)
                                        <button type="button" class="btn btn-sm review-btn is-approve" data-bs-toggle="modal" data-bs-target="#goal-modal" title="Update progress or change the goal"
                                                data-action="{{ route('admin.goals.update', $goal) }}" data-name="{{ $goal->employee?->name ?? 'Removed employee' }}" data-title="{{ $goal->title }}" data-description="{{ $goal->description }}"
                                                data-cycle="{{ $goal->appraisal_cycle_id }}" data-weight="{{ $goal->weight !== null ? $num($goal->weight) : '' }}" data-date="{{ $goal->target_date?->toDateString() }}" data-progress="{{ (int) $goal->progress_percent }}">
                                            <i class="ti ti-pencil"></i> Update
                                        </button>
                                        @unless ($done)
                                            <form action="{{ route('admin.goals.update', $goal) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="progress_percent" value="100">
                                                <button type="submit" class="btn-icon btn-icon-success" title="Mark as completed"><i class="ti ti-check font-16"></i></button>
                                            </form>
                                        @endunless
                                    @endcan
                                    @can('delete', $goal)
                                        <form action="{{ route('admin.goals.destroy', $goal) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this goal? Its progress is lost too.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Remove goal"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-target font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No goals match</div>
                                    <span class="font-13"><a href="{{ route('admin.goals.index') }}">Show all goals</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No goals yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Set the first one with the form above.' : 'Goals set by HR or managers will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($goals->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $goals->links() }}</div></div>
        @endif
    </div>

    <div class="modal fade" id="goal-modal" tabindex="-1" aria-labelledby="goal-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" action="#" class="modal-content" id="goal-edit-form">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="goal-modal-title">Update goal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="font-13 text-muted mb-3">For <strong id="edit-who" class="text-body"></strong></p>
                    <label class="form-label d-flex" for="edit-progress">Progress <strong class="ms-auto" id="edit-progress-label">0%</strong></label>
                    <input type="range" min="0" max="100" step="5" name="progress_percent" id="edit-progress" class="form-range">
                    <div class="quick-chips mt-0">
                        @foreach ([0 => 'Not started', 25 => '25%', 50 => '50%', 75 => '75%', 100 => 'Completed'] as $value => $label)
                            <button type="button" class="quick-chip" data-progress="{{ $value }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label" for="edit-title">Goal</label>
                            <input type="text" name="title" id="edit-title" maxlength="255" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="edit-description">How it will be measured</label>
                            <textarea name="description" id="edit-description" rows="2" maxlength="2000" class="form-control"></textarea>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="edit-cycle">Appraisal cycle</label>
                            <select name="appraisal_cycle_id" id="edit-cycle" class="form-select">
                                <option value="">Not tied to a cycle</option>
                                @foreach ($cycles as $cycle)
                                    <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="edit-weight">Weight %</label>
                            <input type="number" step="0.01" min="0" max="100" name="weight" id="edit-weight" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="edit-date">Target date</label>
                            <input type="date" name="target_date" id="edit-date" max="{{ now()->addYears(5)->toDateString() }}" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save goal</button>
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

            // Update dialog
            var modal = el('goal-modal');
            if (modal) {
                var range = el('edit-progress');
                var label = function () {
                    var v = parseInt(range.value, 10);
                    el('edit-progress-label').textContent = v === 100 ? '100% · Completed' : (v === 0 ? '0% · Not started' : v + '% · In progress');
                };
                modal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('goal-edit-form').action = b.dataset.action;
                    el('edit-who').textContent = b.dataset.name;
                    el('edit-title').value = b.dataset.title;
                    el('edit-description').value = b.dataset.description;
                    el('edit-cycle').value = b.dataset.cycle;
                    el('edit-weight').value = b.dataset.weight;
                    el('edit-date').value = b.dataset.date;
                    range.value = b.dataset.progress;
                    label();
                });
                range.addEventListener('input', label);
                modal.querySelectorAll('[data-progress]').forEach(function (chip) {
                    chip.addEventListener('click', function () { range.value = chip.dataset.progress; label(); });
                });
            }

            // Set-a-goal form
            var form = el('goal-form');
            if (!form) return;
            var weights = @json($weights);
            var employee = el('goal-employee'), cycle = el('goal-cycle'), weight = el('goal-weight'), date = el('goal-date');
            var budget = function () { return weights[employee.value + ':' + (cycle.value || 0)] || { weight: 0, goals: 0 }; };

            function render() {
                var picked = !!employee.value;
                el('goal-budget').hidden = !picked;
                el('goal-budget-empty').hidden = picked;
                var end = cycle.options[cycle.selectedIndex] ? cycle.options[cycle.selectedIndex].dataset.end : '';
                el('goal-date-cycle').hidden = !end;
                if (!picked) return;

                var b = budget(), mine = Math.max(0, parseFloat(weight.value) || 0);
                var left = Math.max(0, 100 - b.weight), over = b.weight + mine > 100.001, warn = '';
                el('goal-used').textContent = trim(b.weight) + '%';
                el('goal-used-meta').textContent = b.goals ? 'on ' + b.goals + (b.goals === 1 ? ' goal' : ' goals') + (cycle.value ? ' in this cycle' : ' outside a cycle') : 'No other goals ' + (cycle.value ? 'in this cycle' : 'outside a cycle');
                el('goal-this').textContent = mine ? trim(mine) + '%' : '—';
                el('goal-left').textContent = over ? 'Only ' + trim(left) + '% is left' : trim(Math.max(0, left - mine)) + '% still free';
                el('goal-bar-used').style.flex = Math.min(100, b.weight);
                el('goal-bar-this').style.flex = Math.min(mine, left);
                el('goal-bar-rest').style.flex = Math.max(0, left - mine);

                if (over) warn = 'That takes the total past 100%. At most ' + trim(left) + '% is left, so this will be rejected.';
                else if (left === 0 && !mine) warn = 'All 100% is already given to other goals. This one can only be added without a weight.';
                else if (date.value && end && date.value > end) warn = 'The target date is after this cycle ends (' + end + ').';
                el('goal-warn').hidden = !warn;
                el('goal-warn').querySelector('span').textContent = warn;
            }

            [employee, cycle, weight, date].forEach(function (input) { input.addEventListener('input', render); input.addEventListener('change', render); });
            form.querySelectorAll('[data-weight]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    weight.value = chip.dataset.weight === 'rest' ? trim(Math.max(0, 100 - budget().weight)) : chip.dataset.weight;
                    render();
                });
            });
            form.querySelectorAll('[data-date]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    date.value = chip.dataset.date === 'cycle' ? cycle.options[cycle.selectedIndex].dataset.end : chip.dataset.date;
                    render();
                });
            });
            render();
        })();
    </script>
@endpush
