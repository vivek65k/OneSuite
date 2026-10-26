@extends('layouts.admin')

@section('title', 'Learning Progress')

@php
    use App\Http\Controllers\Admin\Hrm\CertificationController;
    use App\Models\AssessmentResult;
    use App\Models\Certification;
    use App\Models\TrainingEnrollment;
    use App\Models\TrainingSession;
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $query = fn (array $overrides = []) => array_filter(
        array_merge($state, $overrides),
        fn ($value, $key) => $value !== null && $value !== '' && ! ($key === 'sort' && $value === 'name') && ! ($key === 'dir' && array_merge($state, $overrides)['sort'] === 'name' && $value === 'asc'),
        ARRAY_FILTER_USE_BOTH
    );
    $pageUrl = fn (array $overrides = []) => route('admin.learning-progress.index', $query($overrides));
    // A column heading that sorts by itself; clicking the active one flips the direction.
    $sortUrl = fn (string $key) => $pageUrl(['sort' => $key, 'dir' => $state['sort'] === $key ? ($state['dir'] === 'asc' ? 'desc' : 'asc') : ($key === 'name' ? 'asc' : 'desc')]);
    $sortIcon = fn (string $key) => $state['sort'] === $key ? ($state['dir'] === 'asc' ? 'ti-arrow-up' : 'ti-arrow-down') : null;
    $stateMeta = [
        'active' => ['In training', 'indigo', 'ti-player-play'],
        'behind' => ['Behind', 'rose', 'ti-alert-triangle'],
        'done' => ['All completed', 'success', 'ti-circle-check'],
        'none' => ['Nothing assigned', 'slate', 'ti-circle'],
    ];
    $tabs = ['' => ['label' => 'Everyone', 'tone' => 'indigo', 'count' => $counts['all']]];
    foreach ($stateMeta as $value => $meta) {
        $tabs[$value] = ['label' => $meta[0], 'tone' => $meta[1], 'count' => $counts[$value] ?? 0];
    }
    $filterValues = [
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $enrollMeta = ['assigned' => ['indigo', 'ti-clipboard-list'], 'in_progress' => ['amber', 'ti-player-play'], 'completed' => ['success', 'ti-circle-check'], 'dropped' => ['slate', 'ti-ban']];
    $dates = fn ($s) => $s->start_date->isSameDay($s->end_date)
        ? $s->start_date->format('M j, Y')
        : $s->start_date->format('M j').' – '.$s->end_date->format($s->start_date->isSameMonth($s->end_date) ? 'j, Y' : 'M j, Y');
    $canSeeAssignments = auth()->user()->can('viewAny', TrainingEnrollment::class);
    $canSeeCerts = auth()->user()->can('viewAny', Certification::class);
    $canSeeAssessments = auth()->user()->can('viewAny', \App\Models\Assessment::class);
@endphp

@section('content')
    <x-page-title title="Learning Progress" />

    <div class="kpi-strip">
        <x-kpi-card label="In training now" :value="$kpis['learning']" icon="ti-school" tone="indigo" :hint="$kpis['learning'] ? $kpis['open'].' open '.Str::plural('training', $kpis['open']).' between them' : 'Nobody has an open training'" />
        <x-kpi-card label="Completion rate" :value="$kpis['rate'] !== null ? $kpis['rate'].'%' : '—'" icon="ti-chart-line" :tone="$kpis['rate'] === null ? 'slate' : ($kpis['rate'] >= 70 ? 'success' : ($kpis['rate'] >= 40 ? 'amber' : 'rose'))" :hint="$kpis['rate'] !== null ? $kpis['completed'].' of '.$kpis['assigned'].' trainings finished' : 'Nothing assigned yet'" />
        <x-kpi-card label="Training hours" :value="$kpis['hours'] > 0 ? $num($kpis['hours']) : '—'" icon="ti-clock" tone="teal" :hint="$kpis['hours'] > 0 ? 'Completed, across '.$kpis['trained'].' '.Str::plural('person', $kpis['trained']) : 'No completed training with a duration'" />
        <x-kpi-card label="Needs attention" :value="$kpis['behind'] + $kpis['none']" icon="ti-alert-triangle" :tone="($kpis['behind'] + $kpis['none']) ? 'rose' : 'success'"
                    :hint="($kpis['behind'] + $kpis['none']) ? collect([$kpis['behind'] ? $kpis['behind'].' behind' : null, $kpis['none'] ? $kpis['none'].' with nothing assigned' : null])->filter()->implode(' · ') : 'Everyone has training and is up to date'" />
    </div>

    {{-- One person's learning record --}}
    @if ($detail)
        @php
            $person = $detail['employee'];
        @endphp
        <div class="record-header">
            <x-avatar :person="$person" />
            <div class="record-header-text">
                <div class="record-header-title">{{ $person->name }}</div>
                <div class="record-header-meta">
                    <span>{{ collect([$person->employee_code, $person->designation, $person->department?->name])->filter()->implode(' · ') }}</span>
                    <span class="tag tone-{{ $stateMeta[$detail['state']][1] }}"><i class="ti {{ $stateMeta[$detail['state']][2] }}"></i>{{ $stateMeta[$detail['state']][0] }}</span>
                </div>
            </div>
            <div class="record-header-actions">
                <a href="{{ $pageUrl(['employee_id' => null]) }}" class="btn btn-light btn-sm px-3"><i class="ti ti-arrow-left font-14 me-1"></i> Everyone</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-5">
                <div class="card list-view mb-0 h-100">
                    <div class="list-toolbar">
                        <div class="fw-semibold"><i class="ti ti-school me-1"></i> Training</div>
                        <div class="list-toolbar-actions">
                            <span class="count-pill">{{ $detail['completed'] }} of {{ $detail['assigned'] }} done</span>
                            @if ($canSeeAssignments)
                                <a href="{{ route('admin.training-enrollments.index', ['employee_id' => $person->id]) }}" class="btn btn-light btn-sm px-2" title="Open in Training Assignment"><i class="ti ti-external-link font-14"></i></a>
                            @endif
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table list-table mb-0">
                            <tbody>
                                @forelse ($detail['enrollments'] as $e)
                                    @php
                                        $s = $e->trainingSession;
                                        $late = in_array($e->status, ['assigned', 'in_progress'], true) && $s && (in_array($s->status, ['completed', 'cancelled'], true) || $s->end_date->lt(today()));
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $s?->course?->title ?? 'Removed course' }}</div>
                                            <div class="payslip-parts">{{ $s ? $dates($s) : '—' }}{{ $s?->course?->duration_hours !== null && $s?->course ? ' · '.$num($s->course->duration_hours).' h' : '' }}</div>
                                        </td>
                                        <td class="text-end">
                                            <span class="tag tone-{{ $late ? 'rose' : ($enrollMeta[$e->status][0] ?? 'slate') }}"><i class="ti {{ $late ? 'ti-alert-triangle' : ($enrollMeta[$e->status][1] ?? 'ti-circle') }}"></i>{{ $late ? 'Overdue' : (TrainingEnrollment::STATUSES[$e->status] ?? $e->status) }}</span>
                                            @if ($e->status === 'completed' && $e->completed_date)
                                                <div class="payslip-parts">{{ $e->completed_date->format('M j, Y') }}</div>
                                            @elseif ($late)
                                                <div class="payslip-parts">{{ $s->status === 'cancelled' ? 'Session cancelled' : 'Session is over' }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center text-muted py-4">No training assigned.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card list-view mb-0 h-100">
                    <div class="list-toolbar">
                        <div class="fw-semibold"><i class="ti ti-certificate me-1"></i> Certifications</div>
                        <div class="list-toolbar-actions">
                            <span class="count-pill">{{ $detail['certs'] }} valid</span>
                            @if ($canSeeCerts)
                                <a href="{{ route('admin.certifications.index', ['employee_id' => $person->id]) }}" class="btn btn-light btn-sm px-2" title="Open in Certifications"><i class="ti ti-external-link font-14"></i></a>
                            @endif
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table list-table mb-0">
                            <tbody>
                                @forelse ($detail['certifications'] as $c)
                                    @php
                                        $isValid = $c->status === 'active' && (! $c->expiry_date || $c->expiry_date->gte(today()));
                                        $isSoon = $isValid && $c->expiry_date && $c->expiry_date->lte(today()->addDays(CertificationController::SOON_DAYS));
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $c->name }}</div>
                                            <div class="payslip-parts">{{ $c->expiry_date ? ($isValid ? 'until ' : 'ran out ').$c->expiry_date->format('M j, Y') : 'No expiry' }}</div>
                                        </td>
                                        <td class="text-end"><span class="tag tone-{{ $isSoon ? 'amber' : ($isValid ? 'success' : 'rose') }}">{{ $isSoon ? 'Expiring soon' : ($isValid ? 'Valid' : 'Expired') }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center text-muted py-4">No certifications on record.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card list-view mb-0 h-100">
                    <div class="list-toolbar">
                        <div class="fw-semibold"><i class="ti ti-clipboard-check me-1"></i> Assessments</div>
                        <div class="list-toolbar-actions">
                            <span class="count-pill">{{ $detail['passed'] }}/{{ $detail['taken'] }} passed</span>
                            @if ($canSeeAssessments)
                                <a href="{{ route('admin.assessments.index', ['employee_id' => $person->id]) }}#results" class="btn btn-light btn-sm px-2" title="Open in Assessments"><i class="ti ti-external-link font-14"></i></a>
                            @endif
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table list-table mb-0">
                            <tbody>
                                @forelse ($detail['results'] as $r)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $r->assessment?->title ?? 'Removed assessment' }}</div>
                                            <div class="payslip-parts">{{ $num($r->score) }} / {{ $num($r->assessment?->max_score ?? 0) }} · {{ $r->attempted_date?->format('M j, Y') }}</div>
                                        </td>
                                        <td class="text-end"><span class="tag tone-{{ $r->status === 'pass' ? 'success' : 'rose' }}">{{ AssessmentResult::STATUSES[$r->status] ?? $r->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center text-muted py-4">No assessments taken.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.learning-progress.index') }}">
            @foreach (['tab' => $state['tab'], 'sort' => $state['sort'] === 'name' ? '' : $state['sort'], 'dir' => $state['sort'] === 'name' && $state['dir'] === 'asc' ? '' : $state['dir']] as $key => $value)
                @if ($value !== '')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Learning status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or code" aria-label="Search employees">
                    </div>
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
                    @if ($counts['all'])
                        <a href="{{ route('admin.learning-progress.export', $query()) }}" class="btn btn-light btn-sm px-3" title="Download this list as a spreadsheet"><i class="ti ti-download font-14 me-1"></i> Export</a>
                    @endif
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
                        @foreach (['name' => ['Employee', ''], 'rate' => ['Training', 'min-width: 190px'], 'hours' => ['Hours', ''], 'certs' => ['Certifications', ''], 'passed' => ['Assessments', ''], 'completed' => ['Completed', '']] as $key => [$label, $style])
                            <th @if ($style) style="{{ $style }}" @endif>
                                <a href="{{ $sortUrl($key) }}" class="text-reset text-decoration-none" title="Sort by {{ strtolower($label) }}">{{ $label }}@if ($sortIcon($key)) <i class="ti {{ $sortIcon($key) }} font-12"></i>@endif</a>
                            </th>
                        @endforeach
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $person = $row['employee'];
                            $meta = $stateMeta[$row['state']];
                        @endphp
                        <tr class="{{ $row['state'] === 'behind' ? 'is-pending-row' : '' }}">
                            <td>
                                <a href="{{ $pageUrl(['employee_id' => $person->id, 'tab' => null, 'search' => null, 'department_id' => null]) }}" class="list-user text-reset text-decoration-none" title="Open learning record">
                                    <x-avatar :person="$person" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $person->name }}</div>
                                        <div class="list-user-meta">{{ collect([$person->employee_code, $person->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </a>
                            </td>
                            <td>
                                @if ($row['assigned'])
                                    <div class="mini-progress tone-{{ $row['rate'] === 100 ? 'success' : ($row['overdue'] ? 'rose' : 'teal') }}">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ $row['rate'] }}%"></span></div>
                                        <span class="mini-progress-label">{{ $row['completed'] }}/{{ $row['assigned'] }}</span>
                                    </div>
                                    <div class="payslip-parts">{{ collect([$row['rate'].'%', $row['in_progress'] ? $row['in_progress'].' in progress' : null, ($row['open'] - $row['in_progress']) ? ($row['open'] - $row['in_progress']).' not started' : null, $row['overdue'] ? $row['overdue'].' overdue' : null, $row['dropped'] ? $row['dropped'].' dropped' : null])->filter()->implode(' · ') }}</div>
                                @else
                                    <span class="text-muted">{{ $row['dropped'] ? $row['dropped'].' dropped, nothing else' : 'Nothing assigned' }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $row['hours'] > 0 ? $num($row['hours']).' h' : '—' }}</td>
                            <td>
                                @if ($row['certs'] || $row['certs_expired'])
                                    <div class="fw-semibold">{{ $row['certs'] }} valid</div>
                                    @if ($row['certs_soon'] || $row['certs_expired'])
                                        <div class="payslip-parts">{{ collect([$row['certs_soon'] ? $row['certs_soon'].' expiring soon' : null, $row['certs_expired'] ? $row['certs_expired'].' expired' : null])->filter()->implode(' · ') }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($row['taken'])
                                    <div class="fw-semibold">{{ $row['passed'] }} / {{ $row['taken'] }} passed</div>
                                    @if ($row['average'] !== null)
                                        <div class="payslip-parts">average {{ $row['average'] }}%</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                {{ $row['completed'] }}
                                @if ($row['last'])
                                    <div class="payslip-parts">last {{ $row['last']->format('M j, Y') }}</div>
                                @endif
                            </td>
                            <td><span class="tag tone-{{ $meta[1] }}"><i class="ti {{ $meta[2] }}"></i>{{ $meta[0] }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-school font-24 d-block mb-2"></i>
                                @if ($kpis['people'])
                                    <div class="fw-semibold text-body mb-1">Nobody matches</div>
                                    <span class="font-13"><a href="{{ route('admin.learning-progress.index') }}">Show everyone</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No active employees</div>
                                    <span class="font-13">Learning progress appears here once there are employees.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $rows->links() }}</div></div>
        @endif
    </div>
@endsection
