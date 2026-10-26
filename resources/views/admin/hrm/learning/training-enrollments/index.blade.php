@extends('layouts.admin')

@section('title', 'Training Assignment')

@php
    use App\Models\TrainingEnrollment;
    use App\Models\TrainingSession;
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.training-enrollments.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $statusMeta = [
        'assigned' => ['indigo', 'ti-clipboard-list'],
        'in_progress' => ['amber', 'ti-player-play'],
        'completed' => ['success', 'ti-circle-check'],
        'dropped' => ['slate', 'ti-ban'],
    ];
    $tabs = ['' => ['label' => 'All', 'tone' => 'indigo', 'count' => $counts['all']]];
    foreach (TrainingEnrollment::STATUSES as $value => $label) {
        $tabs[$value] = ['label' => $label, 'tone' => $statusMeta[$value][0], 'count' => $counts[$value] ?? 0];
    }
    $tabs['attention'] = ['label' => 'Needs closing', 'tone' => 'rose', 'count' => $counts['attention'] ?? 0];
    $filterValues = [
        'session_id' => $sessionOptions[$state['session_id']] ?? null,
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['session_id' => 'Session', 'employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $dates = fn ($s) => $s->start_date->isSameDay($s->end_date)
        ? $s->start_date->format('M j, Y')
        : $s->start_date->format('M j').' – '.$s->end_date->format($s->start_date->isSameMonth($s->end_date) ? 'j, Y' : 'M j, Y');
    $oldIds = collect((array) old('employee_ids', []))->map(fn ($v) => (string) $v);
    $oldSession = (string) old('training_session_id', $state['session_id'] ?? '');
    $canSeeCalendar = auth()->user()->can('viewAny', TrainingSession::class);
@endphp

@section('content')
    <x-page-title title="Training Assignment" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="In training now" :value="$kpis['open']" icon="ti-school" tone="indigo" :hint="$kpis['open'] ? $kpis['open_people'].' '.Str::plural('person', $kpis['open_people']).' · '.$kpis['in_progress'].' already started' : 'Nobody is assigned right now'" />
        <x-kpi-card label="Completed" :value="$kpis['completed']" icon="ti-circle-check" tone="success" :hint="$kpis['completed'] ? 'Trainings finished so far' : 'None finished yet'" />
        <x-kpi-card label="Completion rate" :value="$kpis['rate'] !== null ? $kpis['rate'].'%' : '—'" icon="ti-chart-line" tone="teal" :hint="$kpis['rate'] !== null ? 'Of everyone assigned'.($kpis['dropped'] ? ', not counting '.$kpis['dropped'].' dropped' : '') : 'Nothing assigned yet'" />
        <x-kpi-card label="Needs closing" :value="$kpis['attention']" icon="ti-alert-triangle" :tone="$kpis['attention'] ? 'rose' : 'success'" :hint="$kpis['attention'] ? 'Session is over but the person is still open' : 'Every finished session is closed'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.training-enrollments.store') }}" class="card form-section tone-indigo mb-0 h-100" id="assign-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-user-plus"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Assign people to a session</h5>
                            <p class="form-section-sub">Pick a session, then tick everyone who should attend. They see it under My Training.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        @if ($form['sessions']->isEmpty())
                            <div class="run-note tone-amber"><i class="ti ti-alert-triangle"></i><span>There is no scheduled or ongoing session to assign people to. @if ($canSeeCalendar)<a href="{{ route('admin.training-sessions.index') }}">Schedule a session</a> first.@endif</span></div>
                        @else
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label" for="assign-session">Session</label>
                                    <select name="training_session_id" id="assign-session" class="form-select @error('training_session_id') is-invalid @enderror" required>
                                        <option value="">Select session</option>
                                        @foreach ($form['sessions'] as $s)
                                            @php
                                                $left = $s['capacity'] !== null ? max(0, $s['capacity'] - $s['taken']) : null;
                                            @endphp
                                            <option value="{{ $s['id'] }}" data-title="{{ $s['title'] }}" data-dates="{{ $s['dates'] }}" data-trainer="{{ $s['trainer'] }}" data-capacity="{{ $s['capacity'] }}" data-taken="{{ $s['taken'] }}"
                                                    data-ended="{{ $s['ended'] ? 1 : 0 }}" data-enrolled="{{ implode(',', $s['enrolled']) }}" @selected($oldSession === (string) $s['id'])>{{ $s['title'] }} · {{ $s['dates'] }}{{ $left !== null ? ' · '.($left ? $left.' '.Str::plural('seat', $left).' left' : 'full') : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                        <label class="form-label mb-0">Who attends</label>
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
                                        @forelse ($form['people'] as $person)
                                            <label class="pick-item">
                                                <input type="checkbox" class="form-check-input" name="employee_ids[]" value="{{ $person->id }}" data-department="{{ $person->department_id }}" @checked($oldIds->contains((string) $person->id))>
                                                <x-avatar :person="$person" />
                                                <span class="pick-item-text">
                                                    <span class="pick-item-name">{{ $person->name }}</span>
                                                    <span class="pick-item-meta">{{ collect([$person->employee_code, $person->department?->name])->filter()->implode(' · ') }}</span>
                                                </span>
                                                <span class="tag tone-slate" data-already hidden><i class="ti ti-check"></i>Already assigned</span>
                                            </label>
                                        @empty
                                            <div class="text-muted font-13 p-3">No active employees to assign.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> <span id="assign-submit-label">Assign</span></button>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">What this does</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">Session</span>
                                <span class="rev-compare-value promo-title" id="sum-session">Pick a session</span>
                                <span class="rev-compare-meta" id="sum-session-meta"></span>
                            </div>
                            <i class="ti ti-arrow-right"></i>
                            <div>
                                <span class="rev-compare-label">Seats after this</span>
                                <span class="rev-compare-value promo-title" id="sum-seats">—</span>
                                <span class="rev-compare-meta" id="sum-seats-meta"></span>
                            </div>
                        </div>
                        <div class="run-note tone-amber mt-3" id="sum-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>One person can be on a session only once. Someone who dropped out earlier is put back on it instead of being added twice.</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.training-enrollments.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Assignment status">
                    @foreach ($tabs as $value => $tab)
                        @if ($value !== 'attention' || $tab['count'] || $state['tab'] === 'attention')
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or course" aria-label="Search assignments">
                    </div>
                    @if ($sessionOptions->isNotEmpty())
                        <select name="session_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Session">
                            <option value="">All sessions</option>
                            @foreach ($sessionOptions as $id => $label)
                                <option value="{{ $id }}" @selected($state['session_id'] === $id)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @elseif ($state['session_id'])
                        <input type="hidden" name="session_id" value="{{ $state['session_id'] }}">
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

        @if ($activeFilters->isNotEmpty() || ($focus && $focus['can_complete']))
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $pageUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                </div>
                @if ($focus && $focus['can_complete'])
                    @can('update', new TrainingEnrollment)
                        <form method="POST" action="{{ route('admin.training-enrollments.complete-session', $focus['session']) }}" class="ms-auto" onsubmit="return confirm('Mark all {{ $focus['open'] }} open {{ Str::plural('person', $focus['open']) }} on this session as completed?');">
                            @csrf
                            <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-circle-check"></i> Mark all {{ $focus['open'] }} completed</button>
                        </form>
                    @endcan
                @endif
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Training</th>
                        <th>Session</th>
                        <th>Assigned</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        @php
                            $s = $enrollment->trainingSession;
                            $meta = $statusMeta[$enrollment->status] ?? ['slate', 'ti-circle'];
                            $open = in_array($enrollment->status, ['assigned', 'in_progress'], true);
                            $started = $s && $s->start_date->lte(today());
                            $ended = $s && $s->end_date->lt(today());
                            $bookable = $s && in_array($s->status, ['scheduled', 'ongoing'], true);
                            $cancelled = $s && $s->status === 'cancelled';
                            $needsClosing = $open && $s && ($ended || ! $bookable);
                            // The one obvious next step for this person, as a labelled button.
                            $next = match (true) {
                                ! $s || $cancelled => null,
                                $open && ($ended || $s->status === 'completed') => ['completed', 'Mark completed', 'ti-circle-check'],
                                $enrollment->status === 'assigned' && $started => ['in_progress', 'Start', 'ti-player-play'],
                                $enrollment->status === 'in_progress' => ['completed', 'Mark completed', 'ti-circle-check'],
                                $enrollment->status === 'dropped' && $bookable && ! $ended => ['assigned', 'Put back', 'ti-rotate'],
                                default => null,
                            };
                        @endphp
                        <tr class="{{ $needsClosing ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$enrollment->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $enrollment->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$enrollment->employee?->employee_code, $enrollment->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $s?->course?->title ?? 'Removed course' }}</div>
                                <div class="payslip-parts">{{ collect([$s?->trainer ? 'with '.$s->trainer->name : null, $s?->course?->duration_hours !== null && $s?->course ? rtrim(rtrim(number_format((float) $s->course->duration_hours, 2), '0'), '.').' h' : null])->filter()->implode(' · ') }}</div>
                            </td>
                            <td class="text-nowrap">
                                @if ($s)
                                    <a href="{{ $pageUrl(['session_id' => $s->id, 'tab' => null]) }}" class="text-body fw-semibold" title="Show everyone on this session">{{ $dates($s) }}</a>
                                    <div class="payslip-parts">
                                        @if ($cancelled)
                                            <span class="text-danger">Session cancelled</span>
                                        @elseif ($s->status === 'completed' || $ended)
                                            Session ended
                                        @elseif ($started)
                                            Running now
                                        @else
                                            Starts in {{ (int) today()->diffInDays($s->start_date) }} {{ Str::plural('day', (int) today()->diffInDays($s->start_date)) }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $enrollment->assigned_date?->format('M j, Y') ?? '—' }}</td>
                            <td>
                                <span class="tag tone-{{ $meta[0] }}"><i class="ti {{ $meta[1] }}"></i>{{ TrainingEnrollment::STATUSES[$enrollment->status] ?? $enrollment->status }}</span>
                                @if ($enrollment->status === 'completed' && $enrollment->completed_date)
                                    <div class="payslip-parts">on {{ $enrollment->completed_date->format('M j, Y') }}</div>
                                @elseif ($needsClosing)
                                    <div class="payslip-parts">{{ $cancelled ? 'Session was cancelled' : 'Session is over' }}, not closed</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $enrollment)
                                        @if ($next)
                                            <form method="POST" action="{{ route('admin.training-enrollments.update', $enrollment) }}" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="{{ $next[0] }}">
                                                <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti {{ $next[2] }}"></i> {{ $next[1] }}</button>
                                            </form>
                                        @endif
                                        @if ($enrollment->status === 'completed' && $bookable)
                                            <form method="POST" action="{{ route('admin.training-enrollments.update', $enrollment) }}" class="d-inline" onsubmit="return confirm('Reopen this training? The completion date will be cleared.');">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="in_progress">
                                                <button type="submit" class="btn-icon" title="Reopen (back to in progress)"><i class="ti ti-arrow-back-up font-16"></i></button>
                                            </form>
                                        @endif
                                        @if ($open)
                                            <form method="POST" action="{{ route('admin.training-enrollments.update', $enrollment) }}" class="d-inline" onsubmit="return confirm('Mark as dropped? The seat becomes free again.');">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="dropped">
                                                <button type="submit" class="btn-icon" title="Dropped out"><i class="ti ti-ban font-16"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                    @if ($enrollment->status !== 'completed')
                                        @can('delete', $enrollment)
                                            <form action="{{ route('admin.training-enrollments.destroy', $enrollment) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this assignment completely? Use Dropped instead if the person started and left.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Remove assignment"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-school font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No assignments match</div>
                                    <span class="font-13"><a href="{{ route('admin.training-enrollments.index') }}">Show all assignments</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">Nobody is assigned to training yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Assign people to a session with the form above.' : 'Assignments made by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($enrollments->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $enrollments->links() }}</div></div>
        @endif
    </div>
@endsection

@if ($canCreate)
    @push('scripts')
        <script>
            (function () {
                var form = document.getElementById('assign-form');
                var session = document.getElementById('assign-session');
                if (!form || !session) return;
                var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name="employee_ids[]"]'));
                var el = function (id) { return document.getElementById(id); };

                function render() {
                    var opt = session.options[session.selectedIndex];
                    var has = !!session.value;
                    var enrolled = has && opt.dataset.enrolled ? opt.dataset.enrolled.split(',') : [];

                    // People already on the session can't be ticked again.
                    boxes.forEach(function (b) {
                        var already = enrolled.indexOf(b.value) !== -1;
                        if (already) b.checked = false;
                        b.disabled = already;
                        b.closest('.pick-item').querySelector('[data-already]').hidden = !already;
                    });

                    var picked = boxes.filter(function (b) { return b.checked; }).length;
                    var capacity = has && opt.dataset.capacity !== '' ? parseInt(opt.dataset.capacity, 10) : null;
                    var taken = has ? parseInt(opt.dataset.taken, 10) || 0 : 0;
                    var after = taken + picked, warn = '';

                    el('assign-picked').textContent = picked + ' selected';
                    el('assign-submit-label').textContent = picked > 1 ? 'Assign ' + picked + ' people' : (picked === 1 ? 'Assign 1 person' : 'Assign');
                    el('sum-session').textContent = has ? opt.dataset.title : 'Pick a session';
                    el('sum-session-meta').textContent = has ? [opt.dataset.dates, opt.dataset.trainer ? 'with ' + opt.dataset.trainer : null].filter(Boolean).join(' · ') : '';
                    el('sum-seats').textContent = has ? (capacity !== null ? after + ' / ' + capacity : after + ' assigned') : '—';
                    el('sum-seats-meta').textContent = has ? (capacity !== null ? (after > capacity ? (after - capacity) + ' over the limit' : (capacity - after) + (capacity - after === 1 ? ' seat' : ' seats') + ' still free') : 'No seat limit') : '';

                    if (has && capacity !== null && after > capacity) warn = 'This session has ' + Math.max(0, capacity - taken) + ' of ' + capacity + ' seats left and you picked ' + picked + '. Saving will be refused.';
                    else if (has && opt.dataset.ended === '1') warn = 'This session\'s last day has already passed. It should probably be closed on the Training Calendar.';
                    el('sum-warn').hidden = !warn;
                    el('sum-warn').querySelector('span').textContent = warn;
                }

                session.addEventListener('change', render);
                boxes.forEach(function (b) { b.addEventListener('change', render); });
                form.querySelectorAll('[data-pick]').forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        var pick = chip.dataset.pick;
                        boxes.forEach(function (b) { if (!b.disabled) b.checked = pick === 'all' || (pick !== 'none' && b.dataset.department === pick); });
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
@endif
