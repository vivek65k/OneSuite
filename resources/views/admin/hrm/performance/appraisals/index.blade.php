@extends('layouts.admin')

@section('title', 'Appraisals')

@php
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $pageUrl = fn (array $overrides = []) => route('admin.appraisals.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All appraisals', 'tone' => 'indigo', 'count' => $counts['all']],
        'self_pending' => ['label' => 'Waiting for self review', 'tone' => 'slate', 'count' => $counts['self_pending']],
        'manager_pending' => ['label' => 'Waiting for manager', 'tone' => 'amber', 'count' => $counts['manager_pending']],
        'ready' => ['label' => 'Ready to complete', 'tone' => 'teal', 'count' => $counts['ready']],
        'completed' => ['label' => 'Completed', 'tone' => 'success', 'count' => $counts['completed']],
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
    $activeCycles = $cycles->where('status', 'active')->values();
    $current = $kpis['active_cycle'];
    $daysLeft = $current ? (int) today()->diffInDays($current->period_end, false) : null;
    $oldCycle = (string) old('appraisal_cycle_id', $state['cycle_id'] && $activeCycles->contains('id', $state['cycle_id']) ? $state['cycle_id'] : ($activeCycles->first()?->id ?? ''));
    $oldIds = collect((array) old('employee_ids', []))->map(fn ($v) => (string) $v);
    $stars = fn ($rating) => $rating === null ? '' : str_repeat('★', (int) round((float) $rating)).str_repeat('☆', max(0, 5 - (int) round((float) $rating)));
    $year = now()->year;
    $half = now()->month <= 6 ? [now()->startOfYear(), now()->startOfYear()->addMonths(5)->endOfMonth(), 'H1 '.$year] : [now()->startOfYear()->addMonths(6), now()->endOfYear(), 'H2 '.$year];
    $presets = [
        ['Annual '.$year, now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
        [$half[2], $half[0]->toDateString(), $half[1]->toDateString()],
        ['Q'.now()->quarter.' '.$year, now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()],
    ];
@endphp

@section('content')
    <x-page-title title="Appraisals" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card :label="$current ? 'Open cycle' : 'Cycles'" :value="$current ? Str::limit($current->name, 18) : '—'" icon="ti-refresh" :tone="$current ? 'indigo' : 'slate'"
                    :hint="$current ? ($daysLeft >= 0 ? 'Ends '.$current->period_end->format('M j, Y').' · '.$daysLeft.' '.Str::plural('day', $daysLeft).' left' : 'Ended '.$current->period_end->format('M j, Y').' · still open') : 'Create a cycle to begin'" />
        <x-kpi-card label="In progress" :value="$kpis['open']" icon="ti-clipboard-list" tone="amber"
                    :hint="$kpis['open'] ? $kpis['self_pending'].' self · '.$kpis['manager_pending'].' manager · '.$kpis['ready'].' ready' : 'No appraisal is open'" />
        <x-kpi-card label="Ready to complete" :value="$kpis['ready']" icon="ti-user-check" :tone="$kpis['ready'] ? 'teal' : 'slate'" :hint="$kpis['ready'] ? 'Manager review is in' : 'Nothing waiting for sign-off'" :href="$pageUrl(['tab' => 'ready'])" />
        <x-kpi-card label="Completed" :value="$kpis['completed']" icon="ti-circle-check" tone="success" :hint="$kpis['average'] !== null ? 'Average rating '.$num($kpis['average']).' of 5' : 'No ratings yet'" :href="$pageUrl(['tab' => 'completed'])" />
    </div>

    <div class="row g-3 mb-3">
        {{-- Cycles --}}
        <div class="col-xl-5">
            <div class="card side-list tone-indigo mb-0 h-100">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-refresh"></i></span>
                    <span class="fw-semibold">Appraisal cycles</span>
                    <span class="side-list-count">{{ $cycles->count() }}</span>
                </div>
                @forelse ($cycles as $cycle)
                    @php
                        $stat = $cycleStats[$cycle->id] ?? ['total' => 0, 'completed' => 0];
                        $open = $cycle->status === 'active';
                        $done = $stat['total'] ? round($stat['completed'] / $stat['total'] * 100) : 0;
                    @endphp
                    <div class="side-list-item kpi-lib-item {{ $open ? '' : 'is-off' }}">
                        <span class="list-avatar list-avatar-square tone-{{ $open ? 'indigo' : 'slate' }}" aria-hidden="true"><i class="ti {{ $open ? 'ti-refresh' : 'ti-lock' }}"></i></span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold"><a href="{{ $pageUrl(['cycle_id' => $cycle->id, 'tab' => null]) }}" class="text-reset" title="Show this cycle's appraisals">{{ $cycle->name }}</a> @unless ($open)<span class="tag tone-slate">Closed</span>@endunless</div>
                            <div class="font-12 text-muted">{{ $cycle->period_start?->format('M j, Y') }} – {{ $cycle->period_end?->format('M j, Y') }}</div>
                            @if ($stat['total'])
                                <div class="mini-progress tone-success mt-1">
                                    <div class="mini-progress-bar is-toned"><span style="width: {{ $done }}%"></span></div>
                                    <span class="mini-progress-label">{{ $stat['completed'] }}/{{ $stat['total'] }} done</span>
                                </div>
                            @else
                                <div class="font-12 text-muted">No appraisals started yet</div>
                            @endif
                        </div>
                        <div class="list-row-actions">
                            @if ($canUpdate)
                                <form action="{{ route('admin.appraisal-cycles.update', $cycle) }}" method="POST" class="d-inline" @if ($open) onsubmit="return confirm('Close {{ addslashes($cycle->name) }}? No new appraisals can be started in it until it is reopened.');" @endif>
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="{{ $open ? 'closed' : 'active' }}">
                                    <button type="submit" class="btn-icon" title="{{ $open ? 'Close cycle' : 'Reopen cycle' }}"><i class="ti {{ $open ? 'ti-lock' : 'ti-lock-open' }} font-16"></i></button>
                                </form>
                                <button type="button" class="btn-icon btn-icon-accent" title="Change cycle" data-bs-toggle="modal" data-bs-target="#cycle-modal"
                                        data-action="{{ route('admin.appraisal-cycles.update', $cycle) }}" data-name="{{ $cycle->name }}" data-start="{{ $cycle->period_start?->toDateString() }}" data-end="{{ $cycle->period_end?->toDateString() }}"><i class="ti ti-pencil font-16"></i></button>
                            @endif
                            @if ($canDelete && ! ($cycleInUse[$cycle->id] ?? true))
                                <form action="{{ route('admin.appraisal-cycles.destroy', $cycle) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this cycle? Nothing has been recorded in it yet.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete cycle"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="card-body empty-state py-4">
                        <div class="empty-state-icon"><i class="ti ti-refresh"></i></div>
                        <div class="fw-semibold">No cycles yet</div>
                        <div class="font-13 text-muted">A cycle is the period people are appraised for, like "Annual {{ $year }}".</div>
                    </div>
                @endforelse
                @if ($canCreate)
                    <form method="POST" action="{{ route('admin.appraisal-cycles.store') }}" class="kpi-add" id="cycle-add">
                        @csrf
                        <div class="row g-2">
                            <div class="col-12">
                                <input type="text" name="name" maxlength="255" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="New cycle, e.g. Annual {{ $year }}" aria-label="Cycle name" required>
                            </div>
                            <div class="col-6">
                                <input type="date" name="period_start" class="form-control form-control-sm @error('period_start') is-invalid @enderror" value="{{ old('period_start') }}" aria-label="Starts" title="Starts" required>
                            </div>
                            <div class="col-6">
                                <input type="date" name="period_end" class="form-control form-control-sm @error('period_end') is-invalid @enderror" value="{{ old('period_end') }}" aria-label="Ends" title="Ends" required>
                            </div>
                            <div class="col-12 d-flex align-items-center flex-wrap gap-2">
                                <div class="quick-chips mt-0">
                                    @foreach ($presets as $preset)
                                        <button type="button" class="quick-chip" data-cycle-name="{{ $preset[0] }}" data-cycle-start="{{ $preset[1] }}" data-cycle-end="{{ $preset[2] }}">{{ $preset[0] }}</button>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-de-primary btn-sm text-light text-nowrap ms-auto"><i class="ti ti-plus font-14"></i> Add cycle</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        {{-- Start appraisals --}}
        <div class="col-xl-7">
            @if ($canCreate)
                <form method="POST" action="{{ route('admin.appraisals.store') }}" class="card form-section tone-teal mb-0 h-100" id="start-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-player-play"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Start appraisals</h5>
                            <p class="form-section-sub">Open an appraisal for each person in a cycle. They then do a self review and their manager reviews them.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        @if ($activeCycles->isEmpty())
                            <div class="run-note tone-amber"><i class="ti ti-alert-triangle"></i><span>{{ $cycles->isEmpty() ? 'Create an appraisal cycle first, then you can start appraisals in it.' : 'Every cycle is closed. Reopen one, or create a new cycle.' }}</span></div>
                        @else
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label" for="start-cycle">Cycle</label>
                                    <select name="appraisal_cycle_id" id="start-cycle" class="form-select @error('appraisal_cycle_id') is-invalid @enderror" required>
                                        @foreach ($activeCycles as $cycle)
                                            <option value="{{ $cycle->id }}" @selected($oldCycle === (string) $cycle->id)>{{ $cycle->name }} · {{ $cycle->period_start?->format('M Y') }} – {{ $cycle->period_end?->format('M Y') }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                        <label class="form-label mb-0">Who to appraise</label>
                                        <span class="count-pill" id="start-picked">0 selected</span>
                                        <div class="quick-chips mt-0 ms-auto">
                                            <button type="button" class="quick-chip" data-pick="all">Everyone not started</button>
                                            @foreach ($departments as $id => $name)
                                                <button type="button" class="quick-chip" data-pick="{{ $id }}">{{ $name }}</button>
                                            @endforeach
                                            <button type="button" class="quick-chip" data-pick="none">Clear</button>
                                        </div>
                                    </div>
                                    <div class="pick-list @error('employee_ids') is-invalid @enderror" id="start-people">
                                        @forelse ($payable as $person)
                                            <label class="pick-item">
                                                <input type="checkbox" class="form-check-input" name="employee_ids[]" value="{{ $person->id }}" data-department="{{ $person->department_id }}" @checked($oldIds->contains((string) $person->id))>
                                                <x-avatar :person="$person" />
                                                <span class="pick-item-text">
                                                    <span class="pick-item-name">{{ $person->name }}</span>
                                                    <span class="pick-item-meta">{{ collect([$person->employee_code, $person->department?->name])->filter()->implode(' · ') }}</span>
                                                </span>
                                                <span class="tag tone-success" data-role="enrolled" hidden><i class="ti ti-check"></i>Already started</span>
                                            </label>
                                        @empty
                                            <div class="text-muted font-13 p-3">No employees yet.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-player-play font-14 me-1"></i> <span id="start-submit">Start appraisal</span></button>
                            </div>
                        @endif
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.appraisals.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Appraisal stage">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or code" aria-label="Search appraisals">
                    </div>
                    @if ($cycles->count() > 1)
                        <select name="cycle_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Cycle">
                            <option value="">All cycles</option>
                            @foreach ($cycles as $cycle)
                                <option value="{{ $cycle->id }}" @selected($state['cycle_id'] === $cycle->id)>{{ $cycle->name }}</option>
                            @endforeach
                        </select>
                    @elseif ($state['cycle_id'])
                        <input type="hidden" name="cycle_id" value="{{ $state['cycle_id'] }}">
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
                        <th>Cycle</th>
                        <th style="min-width: 250px">Stage</th>
                        <th>Goals and KPIs</th>
                        <th>Overall</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appraisals as $appraisal)
                        @php
                            $selfDone = $appraisal->self_review_rating !== null;
                            $managerDone = $appraisal->manager_review_rating !== null;
                            $completed = $appraisal->status === 'completed';
                            $key = $appraisal->employee_id.':'.$appraisal->appraisal_cycle_id;
                            $goal = $goalProgress[$key] ?? null;
                            $kpi = $kpiAchievement[$key] ?? null;
                            $hasIncrement = $rewarded->has($appraisal->id);
                            $steps = [
                                ['Self', $selfDone, $selfDone ? $appraisal->self_review_rating.'/5' : 'Waiting'],
                                ['Manager', $managerDone, $managerDone ? $appraisal->manager_review_rating.'/5' : 'Waiting'],
                                ['Completed', $completed, $completed ? ($appraisal->completed_at?->format('M j') ?? 'Done') : 'Open'],
                            ];
                        @endphp
                        <tr>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$appraisal->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $appraisal->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$appraisal->employee?->employee_code, $appraisal->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $appraisal->appraisalCycle?->name ?? '—' }}</div>
                                @if ($appraisal->appraisalCycle && $appraisal->appraisalCycle->status !== 'active')
                                    <span class="tag tone-slate">Closed cycle</span>
                                @endif
                            </td>
                            <td>
                                <div class="appr-track">
                                    @foreach ($steps as $step)
                                        <div class="appr-step {{ $step[1] ? 'is-done' : '' }}">
                                            <span class="appr-dot"><i class="ti {{ $step[1] ? 'ti-check' : 'ti-circle-dashed' }}"></i></span>
                                            <span class="appr-label">{{ $step[0] }}</span>
                                            <span class="appr-meta">{{ $step[2] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($managerDone && $appraisal->managerReviewedBy)
                                    <div class="payslip-parts">Manager review by {{ $appraisal->managerReviewedBy->name }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($goal || $kpi)
                                    @if ($goal)
                                        <div class="font-13"><i class="ti ti-target text-muted"></i> {{ $goal->goals }} {{ Str::plural('goal', $goal->goals) }} · {{ round((float) $goal->progress) }}%</div>
                                    @endif
                                    @if ($kpi)
                                        <div class="font-13"><i class="ti ti-chart-line text-muted"></i> {{ $kpi['kpis'] }} {{ Str::plural('KPI', $kpi['kpis']) }}{{ $kpi['achievement'] !== null ? ' · '.$num(round($kpi['achievement'], 1)).'%' : ' · no results' }}</div>
                                    @endif
                                @else
                                    <span class="text-muted font-13">None in this cycle</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($completed && $appraisal->overall_rating !== null)
                                    <div class="rating-stars" title="{{ $num($appraisal->overall_rating) }} of 5">{{ $stars($appraisal->overall_rating) }}</div>
                                    <div class="payslip-parts">{{ $num($appraisal->overall_rating) }} of 5{{ $hasIncrement ? ' · increment given' : '' }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $appraisal)
                                        @if (! $completed && ($selfDone || $managerDone))
                                            <form action="{{ route('admin.appraisals.complete', $appraisal) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $managerDone ? 'Complete this appraisal with the manager rating of '.$appraisal->manager_review_rating.'?' : 'There is no manager review yet. Complete it with the self rating of '.$appraisal->self_review_rating.'?' }}');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-check"></i> Complete</button>
                                            </form>
                                        @elseif (! $completed)
                                            <a href="{{ route('admin.self-reviews.index') }}" class="btn btn-sm review-btn" title="No review has been recorded yet"><i class="ti ti-pencil"></i> Record review</a>
                                        @elseif (! $hasIncrement)
                                            <form action="{{ route('admin.appraisals.reopen', $appraisal) }}" method="POST" class="d-inline" onsubmit="return confirm('Reopen this appraisal? The overall rating is cleared; the reviews are kept.');">
                                                @csrf
                                                <button type="submit" class="btn-icon" title="Reopen"><i class="ti ti-arrow-back-up font-16"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                    @unless ($completed)
                                        @can('delete', $appraisal)
                                            <form action="{{ route('admin.appraisals.destroy', $appraisal) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this appraisal? Any reviews recorded on it are lost.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Remove appraisal"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-clipboard-list font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No appraisals match</div>
                                    <span class="font-13"><a href="{{ route('admin.appraisals.index') }}">Show all appraisals</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No appraisals yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Create a cycle, then start appraisals in it with the form above.' : 'Appraisals started by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($appraisals->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $appraisals->links() }}</div></div>
        @endif
    </div>

    <div class="modal fade" id="cycle-modal" tabindex="-1" aria-labelledby="cycle-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="cycle-form">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="cycle-modal-title">Change cycle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="cycle-name">Name</label>
                            <input type="text" name="name" id="cycle-name" maxlength="255" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="cycle-start">Starts</label>
                            <input type="date" name="period_start" id="cycle-start" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="cycle-end">Ends</label>
                            <input type="date" name="period_end" id="cycle-end" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save cycle</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };

            var cycleModal = el('cycle-modal');
            if (cycleModal) {
                cycleModal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('cycle-form').action = b.dataset.action;
                    el('cycle-name').value = b.dataset.name; el('cycle-start').value = b.dataset.start; el('cycle-end').value = b.dataset.end;
                });
            }

            var add = el('cycle-add');
            if (add) {
                add.querySelectorAll('[data-cycle-name]').forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        add.querySelector('[name="name"]').value = chip.dataset.cycleName;
                        add.querySelector('[name="period_start"]').value = chip.dataset.cycleStart;
                        add.querySelector('[name="period_end"]').value = chip.dataset.cycleEnd;
                    });
                });
            }

            var form = el('start-form'), cycle = el('start-cycle');
            if (!form || !cycle) return;
            var enrolled = @json($enrolled);
            var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name="employee_ids[]"]'));

            // People already in the chosen cycle can't be started again, so lock them.
            function mark() {
                var inCycle = (enrolled[cycle.value] || []).map(String);
                boxes.forEach(function (b) {
                    var has = inCycle.indexOf(b.value) !== -1;
                    b.disabled = has;
                    if (has) b.checked = false;
                    b.closest('.pick-item').querySelector('[data-role="enrolled"]').hidden = !has;
                });
                count();
            }

            function count() {
                var picked = boxes.filter(function (b) { return b.checked; }).length;
                var free = boxes.filter(function (b) { return !b.disabled; }).length;
                el('start-picked').textContent = picked + ' selected' + (free === 0 ? ' · everyone is already in this cycle' : '');
                el('start-submit').textContent = picked > 1 ? 'Start ' + picked + ' appraisals' : 'Start appraisal';
            }

            cycle.addEventListener('change', mark);
            boxes.forEach(function (b) { b.addEventListener('change', count); });
            form.querySelectorAll('[data-pick]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var pick = chip.dataset.pick;
                    boxes.forEach(function (b) { b.checked = !b.disabled && (pick === 'all' || (pick !== 'none' && b.dataset.department === pick)); });
                    count();
                });
            });
            form.addEventListener('submit', function (event) {
                if (!boxes.some(function (b) { return b.checked; })) {
                    event.preventDefault();
                    el('start-people').classList.add('is-invalid');
                    el('start-picked').textContent = 'Pick at least one person';
                }
            });
            mark();
        })();
    </script>
@endpush
