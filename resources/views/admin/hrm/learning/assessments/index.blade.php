@extends('layouts.admin')

@section('title', 'Assessments')

@php
    use App\Models\Assessment;
    use App\Models\AssessmentResult;
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $pageUrl = fn (array $overrides = []) => route('admin.assessments.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All results', 'tone' => 'indigo', 'count' => $counts['all']],
        'pass' => ['label' => 'Passed', 'tone' => 'success', 'count' => $counts['pass']],
        'fail' => ['label' => 'Failed', 'tone' => 'rose', 'count' => $counts['fail']],
    ];
    $assessmentNames = $assessments->pluck('title', 'id');
    $filterValues = [
        'assessment_id' => $assessmentNames[$state['assessment_id']] ?? null,
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['assessment_id' => 'Assessment', 'employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $passRate = $kpis['results'] ? (int) round($kpis['passed'] / $kpis['results'] * 100) : null;
    $oldSheet = (string) old('assessment_id', $state['assessment_id'] ?? '');
    $oldScores = (array) old('scores', []);
@endphp

@section('content')
    <x-page-title title="Assessments" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Assessments" :value="$kpis['assessments']" icon="ti-clipboard-check" tone="indigo" :hint="$kpis['assessments'] ? ($kpis['unused'] ? $kpis['unused'].' with no scores yet' : 'All have scores recorded') : 'None set up yet'" />
        <x-kpi-card label="Results recorded" :value="$kpis['results']" icon="ti-list-check" tone="teal" :hint="$kpis['results'] ? 'For '.$kpis['people'].' '.Str::plural('person', $kpis['people']) : 'No scores entered yet'" />
        <x-kpi-card label="Pass rate" :value="$passRate !== null ? $passRate.'%' : '—'" icon="ti-circle-check" :tone="$passRate === null ? 'slate' : ($passRate >= 70 ? 'success' : ($passRate >= 40 ? 'amber' : 'rose'))" :hint="$passRate !== null ? $kpis['passed'].' passed · '.$kpis['failed'].' failed' : 'Nothing to measure yet'" />
        <x-kpi-card label="Average score" :value="$kpis['average'] !== null ? $num($kpis['average']).'%' : '—'" icon="ti-chart-line" tone="amber" :hint="$kpis['average'] !== null ? 'Of the maximum, across all results' : 'Nothing to measure yet'" />
    </div>

    <div class="row g-3 mb-3">
        {{-- Assessments --}}
        <div class="col-xl-7">
            <div class="card list-view mb-0 h-100">
                <div class="list-toolbar">
                    <div class="fw-semibold"><i class="ti ti-clipboard-check me-1"></i> Assessments</div>
                    <div class="list-toolbar-actions"><span class="count-pill">{{ $assessments->count() }}</span></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Assessment</th>
                                <th style="min-width: 140px">Pass mark</th>
                                <th style="min-width: 130px">Taken</th>
                                <th class="text-end"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assessments as $a)
                                @php
                                    $s = $stats[$a->id] ?? ['taken' => 0, 'passed' => 0, 'best' => 0];
                                    $passPct = (float) $a->max_score > 0 ? round((float) $a->passing_score / (float) $a->max_score * 100) : 0;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $a->title }}</div>
                                        <div class="payslip-parts"><i class="ti ti-book"></i> {{ $a->course?->title ?? 'Removed course' }}{{ $a->course && ! $a->course->is_active ? ' · switched off' : '' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $num($a->passing_score) }} <span class="text-muted fw-normal">/ {{ $num($a->max_score) }}</span></div>
                                        <div class="payslip-parts">{{ $passPct }}% needed to pass</div>
                                    </td>
                                    <td>
                                        @if ($s['taken'])
                                            <div class="mini-progress tone-{{ $s['passed'] === $s['taken'] ? 'success' : 'teal' }}">
                                                <div class="mini-progress-bar is-toned"><span style="width: {{ round($s['passed'] / $s['taken'] * 100) }}%"></span></div>
                                                <span class="mini-progress-label">{{ $s['passed'] }}/{{ $s['taken'] }}</span>
                                            </div>
                                            <div class="payslip-parts">passed</div>
                                        @else
                                            <span class="text-muted">Nobody yet</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div class="list-row-actions">
                                            @if ($s['taken'])
                                                <a href="{{ $pageUrl(['assessment_id' => $a->id, 'tab' => null]) }}#results" class="btn-icon" title="See the results"><i class="ti ti-list-check font-16"></i></a>
                                            @endif
                                            @can('update', $a)
                                                <button type="button" class="btn-icon btn-icon-accent" title="Change assessment" data-bs-toggle="modal" data-bs-target="#assessment-modal"
                                                        data-action="{{ route('admin.assessments.update', $a) }}" data-title="{{ $a->title }}" data-course="{{ $a->course?->title }}" data-max="{{ $num($a->max_score) }}" data-pass="{{ $num($a->passing_score) }}" data-taken="{{ $s['taken'] }}" data-best="{{ $num($s['best']) }}"><i class="ti ti-pencil font-16"></i></button>
                                            @endcan
                                            @unless ($s['taken'])
                                                @can('delete', $a)
                                                    <form action="{{ route('admin.assessments.destroy', $a) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this assessment? Nobody has a score on it.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete assessment"><i class="ti ti-trash font-16"></i></button>
                                                    </form>
                                                @endcan
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="ti ti-clipboard-check font-24 d-block mb-2"></i>
                                        <div class="fw-semibold text-body mb-1">No assessments yet</div>
                                        <span class="font-13">{{ $canCreate ? 'Add the first test or quiz with the form on the right.' : 'Assessments set up by HR will appear here.' }}</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Add an assessment --}}
        <div class="col-xl-5">
            @if ($canCreate)
                <form method="POST" action="{{ route('admin.assessments.store') }}" class="card form-section tone-indigo mb-0 h-100" id="assessment-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-plus"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Add an assessment</h5>
                            <p class="form-section-sub">A test or quiz that belongs to a course, with the score needed to pass.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        @if ($courses->isEmpty())
                            <div class="run-note tone-amber"><i class="ti ti-alert-triangle"></i><span>There are no active courses. @can('viewAny', \App\Models\Course::class)<a href="{{ route('admin.courses.index') }}">Add a course</a> first.@endcan</span></div>
                        @else
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="assessment-course">Course</label>
                                    <select name="course_id" id="assessment-course" class="form-select @error('course_id') is-invalid @enderror" required>
                                        <option value="">Select course</option>
                                        @foreach ($courses as $id => $title)
                                            <option value="{{ $id }}" @selected((string) old('course_id') === (string) $id)>{{ $title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="assessment-title">Name</label>
                                    <input type="text" name="title" id="assessment-title" value="{{ old('title') }}" maxlength="255" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. Final quiz" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="assessment-max">Maximum score</label>
                                    <input type="number" step="0.01" min="1" max="9999.99" name="max_score" id="assessment-max" value="{{ old('max_score', 100) }}" class="form-control @error('max_score') is-invalid @enderror" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="assessment-pass">Pass mark</label>
                                    <input type="number" step="0.01" min="0.01" max="9999.99" name="passing_score" id="assessment-pass" value="{{ old('passing_score', 40) }}" class="form-control @error('passing_score') is-invalid @enderror" required>
                                    <div class="quick-chips">
                                        @foreach ([40, 50, 60, 70, 80] as $pct)
                                            <button type="button" class="quick-chip" data-pct="{{ $pct }}">{{ $pct }}%</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="run-note tone-indigo mt-3"><i class="ti ti-info-circle"></i><span id="assessment-hint"></span></div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Add assessment</button>
                            </div>
                        @endif
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- Score sheet --}}
    @if ($canScore && $assessments->isNotEmpty())
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.assessment-results.store') }}" class="card form-section tone-teal mb-0 h-100" id="sheet-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-list-check"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Score sheet</h5>
                            <p class="form-section-sub">Pick an assessment and type the score next to each person who took it. Leave the others empty.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="sheet-assessment">Assessment</label>
                                <select name="assessment_id" id="sheet-assessment" class="form-select @error('assessment_id') is-invalid @enderror" required>
                                    <option value="">Select assessment</option>
                                    @foreach ($assessments as $a)
                                        <option value="{{ $a->id }}" data-max="{{ $num($a->max_score) }}" data-pass="{{ $num($a->passing_score) }}" data-title="{{ $a->title }}" @selected($oldSheet === (string) $a->id)>{{ $a->title }} · {{ $a->course?->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="sheet-date">Taken on</label>
                                <input type="date" name="attempted_date" id="sheet-date" value="{{ old('attempted_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control @error('attempted_date') is-invalid @enderror" required>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                    <label class="form-label mb-0">Scores</label>
                                    <span class="count-pill" id="sheet-count">0 entered</span>
                                </div>
                                <div class="pick-list @error('scores') is-invalid @enderror" id="sheet-people">
                                    @forelse ($sheetPeople as $person)
                                        <label class="pick-item">
                                            <x-avatar :person="$person" />
                                            <span class="pick-item-text">
                                                <span class="pick-item-name">{{ $person->name }}</span>
                                                <span class="pick-item-meta">{{ collect([$person->employee_code, $person->department?->name])->filter()->implode(' · ') }}<span data-was></span></span>
                                            </span>
                                            <span class="tag" data-verdict hidden></span>
                                            <input type="number" step="0.01" min="0" name="scores[{{ $person->id }}]" value="{{ is_scalar($oldScores[$person->id] ?? null) ? $oldScores[$person->id] : '' }}" data-employee="{{ $person->id }}" class="form-control form-control-sm ms-auto" style="width: 96px" placeholder="—" aria-label="Score for {{ $person->name }}">
                                        </label>
                                    @empty
                                        <div class="text-muted font-13 p-3">No active employees to score.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> <span id="sheet-submit">Save scores</span></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">What this records</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">Assessment</span>
                                <span class="rev-compare-value promo-title" id="sum-title">Pick an assessment</span>
                                <span class="rev-compare-meta" id="sum-mark"></span>
                            </div>
                            <i class="ti ti-arrow-right"></i>
                            <div>
                                <span class="rev-compare-label">Outcome</span>
                                <span class="rev-compare-value promo-title" id="sum-outcome">—</span>
                                <span class="rev-compare-meta" id="sum-average"></span>
                            </div>
                        </div>
                        <div class="run-note tone-amber mt-3" id="sum-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>Each person has one result per assessment. Entering a score for someone who already has one replaces it (a retake).</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Results --}}
    <div class="card list-view" id="results">
        <form method="GET" action="{{ route('admin.assessments.index') }}#results">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Result">
                    @foreach ($tabs as $value => $tab)
                        <a href="{{ $pageUrl(['tab' => $value ?: null]) }}#results" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name, assessment or course" aria-label="Search results">
                    </div>
                    @if ($assessments->count() > 1)
                        <select name="assessment_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Assessment">
                            <option value="">All assessments</option>
                            @foreach ($assessmentNames as $id => $title)
                                <option value="{{ $id }}" @selected($state['assessment_id'] === $id)>{{ $title }}</option>
                            @endforeach
                        </select>
                    @elseif ($state['assessment_id'])
                        <input type="hidden" name="assessment_id" value="{{ $state['assessment_id'] }}">
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
                        <a href="{{ $pageUrl([$filter['key'] => null]) }}#results" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Assessment</th>
                        <th style="min-width: 170px">Score</th>
                        <th>Result</th>
                        <th>Taken on</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $result)
                        @php
                            $a = $result->assessment;
                            $max = (float) ($a?->max_score ?? 0);
                            $pct = $max > 0 ? min(100, round((float) $result->score / $max * 100)) : 0;
                            $passed = $result->status === 'pass';
                        @endphp
                        <tr>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$result->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $result->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$result->employee?->employee_code, $result->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $a?->title ?? 'Removed assessment' }}</div>
                                <div class="payslip-parts"><i class="ti ti-book"></i> {{ $a?->course?->title ?? '—' }}</div>
                            </td>
                            <td>
                                <div class="mini-progress tone-{{ $passed ? 'success' : 'rose' }}">
                                    <div class="mini-progress-bar is-toned"><span style="width: {{ $pct }}%"></span></div>
                                    <span class="mini-progress-label">{{ $num($result->score) }}/{{ $num($max) }}</span>
                                </div>
                                <div class="payslip-parts">{{ $pct }}% · pass mark {{ $num($a?->passing_score ?? 0) }}</div>
                            </td>
                            <td><span class="tag tone-{{ $passed ? 'success' : 'rose' }}"><i class="ti {{ $passed ? 'ti-circle-check' : 'ti-x' }}"></i>{{ AssessmentResult::STATUSES[$result->status] ?? $result->status }}</span></td>
                            <td class="text-nowrap">{{ $result->attempted_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('delete', $result)
                                        <form action="{{ route('admin.assessment-results.destroy', $result) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this result? To correct a score, enter the right one on the score sheet instead.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Remove result"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-list-check font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No results match</div>
                                    <span class="font-13"><a href="{{ route('admin.assessments.index') }}#results">Show all results</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No results recorded yet</div>
                                    <span class="font-13">{{ $assessments->isEmpty() ? 'Add an assessment first, then enter scores for it.' : ($canScore ? 'Enter scores with the score sheet above.' : 'Scores entered by HR will appear here.') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($results->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $results->fragment('results')->links() }}</div></div>
        @endif
    </div>

    <div class="modal fade" id="assessment-modal" tabindex="-1" aria-labelledby="assessment-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="assessment-edit">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="assessment-modal-title">Change assessment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="font-13 text-muted mb-3" id="edit-course"></div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="edit-title">Name</label>
                            <input type="text" name="title" id="edit-title" maxlength="255" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-max">Maximum score</label>
                            <input type="number" step="0.01" min="1" max="9999.99" name="max_score" id="edit-max" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-pass">Pass mark</label>
                            <input type="number" step="0.01" min="0.01" max="9999.99" name="passing_score" id="edit-pass" class="form-control" required>
                        </div>
                    </div>
                    <div class="font-12 text-muted mt-2" id="edit-note"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var existing = @json($existing);
            var fmt = function (n) { return String(Math.round(n * 100) / 100); };

            // Edit dialog
            var modal = el('assessment-modal');
            if (modal) {
                var taken = 0, best = 0, was = 0;
                var note = function () {
                    var max = parseFloat(el('edit-max').value), pass = parseFloat(el('edit-pass').value);
                    el('edit-note').textContent = pass > max ? 'The pass mark can\'t be higher than the maximum.'
                        : (taken && max < best ? 'Someone already scored ' + fmt(best) + ', so the maximum can\'t go below that.'
                        : (taken && pass !== was ? 'Changing the pass mark re-grades the ' + taken + (taken === 1 ? ' result' : ' results') + ' already recorded.'
                        : (max > 0 && pass > 0 ? fmt(pass / max * 100) + '% needed to pass.' : '')));
                };
                modal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('assessment-edit').action = b.dataset.action;
                    el('edit-course').textContent = b.dataset.course ? 'Course: ' + b.dataset.course : '';
                    el('edit-title').value = b.dataset.title; el('edit-max').value = b.dataset.max; el('edit-pass').value = b.dataset.pass;
                    taken = parseInt(b.dataset.taken, 10) || 0; best = parseFloat(b.dataset.best) || 0; was = parseFloat(b.dataset.pass);
                    note();
                });
                ['edit-max', 'edit-pass'].forEach(function (id) { el(id).addEventListener('input', note); });
            }

            // Add form
            var addForm = el('assessment-form'), max = el('assessment-max'), pass = el('assessment-pass');
            if (addForm && max) {
                var hint = function () {
                    var m = parseFloat(max.value), p = parseFloat(pass.value);
                    el('assessment-hint').textContent = !(m > 0) || !(p > 0) ? 'Enter the maximum score and the pass mark.'
                        : (p > m ? 'The pass mark can\'t be higher than the maximum score.' : 'Anyone scoring ' + fmt(p) + ' or more out of ' + fmt(m) + ' passes (' + fmt(p / m * 100) + '%).');
                };
                [max, pass].forEach(function (input) { input.addEventListener('input', hint); });
                addForm.querySelectorAll('[data-pct]').forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        var m = parseFloat(max.value);
                        if (!(m > 0)) { max.focus(); return; }
                        pass.value = fmt(m * parseInt(chip.dataset.pct, 10) / 100);
                        hint();
                    });
                });
                hint();
            }

            // Score sheet
            var sheet = el('sheet-form');
            if (!sheet) return;
            var pick = el('sheet-assessment');
            var inputs = Array.prototype.slice.call(sheet.querySelectorAll('input[data-employee]'));

            function render() {
                var opt = pick.options[pick.selectedIndex], has = !!pick.value;
                var m = has ? parseFloat(opt.dataset.max) : null, p = has ? parseFloat(opt.dataset.pass) : null;
                var before = has && existing[pick.value] ? existing[pick.value] : {};
                var entered = 0, passed = 0, total = 0, over = 0, replacing = 0;

                inputs.forEach(function (input) {
                    var row = input.closest('.pick-item'), verdict = row.querySelector('[data-verdict]');
                    var old = before[input.dataset.employee];
                    var value = input.value.trim() === '' ? null : parseFloat(input.value);
                    if (has) input.max = m; else input.removeAttribute('max');
                    row.querySelector('[data-was]').textContent = old !== undefined ? ' · scored ' + fmt(old) + ' before' : '';
                    input.placeholder = old !== undefined ? fmt(old) : '—';
                    verdict.hidden = value === null || !has || isNaN(value);
                    if (value === null || isNaN(value)) return;
                    entered++;
                    if (!has) return;
                    if (old !== undefined) replacing++;
                    if (value > m || value < 0) { over++; verdict.className = 'tag tone-amber'; verdict.textContent = 'Out of range'; return; }
                    total += value / m * 100;
                    var ok = value >= p;
                    passed += ok ? 1 : 0;
                    verdict.className = 'tag tone-' + (ok ? 'success' : 'rose');
                    verdict.textContent = ok ? 'Pass' : 'Fail';
                });

                var valid = entered - over;
                el('sheet-count').textContent = entered + ' entered';
                el('sheet-submit').textContent = entered > 1 ? 'Save ' + entered + ' scores' : (entered === 1 ? 'Save 1 score' : 'Save scores');
                el('sum-title').textContent = has ? opt.dataset.title : 'Pick an assessment';
                el('sum-mark').textContent = has ? 'Pass mark ' + fmt(p) + ' of ' + fmt(m) : '';
                el('sum-outcome').textContent = has && valid ? passed + ' pass · ' + (valid - passed) + ' fail' : '—';
                el('sum-average').textContent = has && valid ? 'Average ' + fmt(total / valid) + '%' : (has ? 'Type the scores' : '');
                var warn = over ? over + (over === 1 ? ' score is' : ' scores are') + ' outside 0 to ' + fmt(m) + '. Saving will be refused.'
                    : (replacing ? replacing + (replacing === 1 ? ' person already has' : ' people already have') + ' a score on this assessment. It will be replaced.' : '');
                el('sum-warn').hidden = !warn;
                el('sum-warn').querySelector('span').textContent = warn;
            }

            pick.addEventListener('change', render);
            inputs.forEach(function (input) { input.addEventListener('input', render); });
            sheet.addEventListener('submit', function (event) {
                if (!inputs.some(function (input) { return input.value.trim() !== ''; })) {
                    event.preventDefault();
                    el('sheet-people').classList.add('is-invalid');
                    el('sheet-count').textContent = 'Enter at least one score';
                }
            });
            render();
        })();
    </script>
@endpush
