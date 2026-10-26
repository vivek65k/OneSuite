@extends('layouts.admin')

@section('title', 'Training Reports')

@php
    use App\Models\Course;
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $query = array_filter(['year' => $state['year'], 'category' => $state['category'], 'sort' => $state['sort'] === 'name' ? null : $state['sort']], fn ($v) => $v !== null && $v !== '');
    $pageUrl = fn (array $overrides = []) => route('admin.training-reports.index', array_filter(array_merge($query, $overrides), fn ($v) => $v !== null && $v !== ''));
    $rateTone = fn ($pct) => $pct === null ? 'slate' : ($pct >= 70 ? 'success' : ($pct >= 40 ? 'amber' : 'rose'));
    $modeIcon = ['online' => 'ti-device-laptop', 'offline' => 'ti-building', 'hybrid' => 'ti-switch-horizontal'];
    $sorts = ['name' => 'A–Z', 'enrolled' => 'Enrolled', 'completion' => 'Completion', 'hours' => 'Hours', 'pass' => 'Pass rate'];
    $has = $totals['sessions'] > 0 || $totals['taken'] > 0;
    $maxCategory = max(1, (int) $byCategory->max('enrolled'));
    $period = $state['year'] ? 'in '.$state['year'] : 'so far';
    $canSeeCourses = auth()->user()->can('viewAny', Course::class);
@endphp

@section('content')
    <x-page-title title="Training Reports" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-indigo" aria-hidden="true"><i class="ti ti-report-analytics font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $state['year'] ? 'Training in '.$state['year'] : 'All training to date' }}{{ $state['category'] !== '' ? ' · '.$state['category'] : '' }}</div>
            <div class="record-header-meta">
                <span>{{ $totals['run'] }} of {{ $totals['courses'] }} {{ Str::plural('course', $totals['courses']) }} had a session</span>
                @if ($totals['cancelled'])
                    <span>{{ $totals['cancelled'] }} cancelled {{ Str::plural('session', $totals['cancelled']) }} left out</span>
                @endif
            </div>
        </div>
        <div class="record-header-actions">
            <form method="GET" action="{{ route('admin.training-reports.index') }}" class="d-flex align-items-center gap-2">
                @if ($years->isNotEmpty() || $state['year'])
                    <select name="year" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Year">
                        <option value="">All time</option>
                        @foreach ($years->push($state['year'])->filter()->unique()->sortDesc() as $year)
                            <option value="{{ $year }}" @selected($state['year'] === (int) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                @endif
                @if ($categories->isNotEmpty())
                    <select name="category" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Category">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected($state['category'] === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                @endif
                @if ($state['sort'] !== 'name')
                    <input type="hidden" name="sort" value="{{ $state['sort'] }}">
                @endif
            </form>
            @if ($rows->isNotEmpty())
                <a href="{{ route('admin.training-reports.export', $query) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-download font-14 me-1"></i> Export CSV</a>
            @endif
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Sessions held" :value="$totals['sessions']" icon="ti-calendar-event" tone="indigo" :hint="$totals['sessions'] ? 'Across '.$totals['run'].' '.Str::plural('course', $totals['run']).' '.$period : 'No session '.$period" />
        <x-kpi-card label="People trained" :value="$totals['people']" icon="ti-users" tone="teal" :hint="$totals['people'] ? $num($totals['hours']).' training hours completed' : 'Nobody has completed a training '.$period" />
        <x-kpi-card label="Completion rate" :value="$totals['completion'] !== null ? $totals['completion'].'%' : '—'" icon="ti-chart-line" :tone="$rateTone($totals['completion'])" :hint="$totals['enrolled'] ? $totals['completed'].' of '.$totals['enrolled'].' enrolments finished'.($totals['dropped'] ? ' · '.$totals['dropped'].' dropped' : '') : 'Nobody enrolled '.$period" />
        <x-kpi-card label="Assessment pass rate" :value="$totals['pass'] !== null ? $totals['pass'].'%' : '—'" icon="ti-clipboard-check" :tone="$rateTone($totals['pass'])" :hint="$totals['taken'] ? $totals['passed'].' of '.$totals['taken'].' results passed' : 'No assessment results '.$period" />
    </div>

    @if ($totals['overdue'])
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="ti ti-alert-triangle font-18"></i>
            <div><strong>{{ $totals['overdue'] }} {{ Str::plural('enrolment', $totals['overdue']) }} {{ $totals['overdue'] === 1 ? 'is' : 'are' }} still open on sessions that have ended</strong>, which holds the completion rate down. @can('viewAny', \App\Models\TrainingEnrollment::class)<a href="{{ route('admin.training-enrollments.index', ['tab' => 'attention']) }}">Close them on Training Assignment</a>@endcan</div>
        </div>
    @endif

    @if ($has)
        <div class="row g-3 mb-3">
            <div class="col-xl-4">
                <div class="card side-list tone-indigo mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-chart-bar"></i></span>
                        <span class="fw-semibold">Enrolments by category</span>
                        <span class="side-list-count">{{ $totals['enrolled'] }}</span>
                    </div>
                    <div class="card-body">
                        @foreach ($byCategory->take(6) as $group)
                            <div class="rating-row" title="{{ $group['completed'] }} of {{ $group['enrolled'] }} completed · {{ $group['courses'] }} {{ Str::plural('course', $group['courses']) }}">
                                <span class="rating-row-label text-truncate" style="flex: 0 0 110px; width: 110px; max-width: 110px">{{ $group['name'] }}</span>
                                <div class="mini-progress-bar is-toned tone-indigo"><span style="width: {{ round($group['enrolled'] / $maxCategory * 100) }}%"></span></div>
                                <span class="rating-row-count">{{ $group['enrolled'] }}</span>
                            </div>
                        @endforeach
                        @unless ($totals['enrolled'])
                            <div class="font-13 text-muted mt-2">Nobody was enrolled {{ $period }}.</div>
                        @endunless
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card side-list tone-success mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-award"></i></span>
                        <span class="fw-semibold">Most attended</span>
                    </div>
                    @forelse ($top as $i => $row)
                        <div class="side-list-item">
                            <span class="rank-badge rank-{{ $i + 1 }}">{{ $i + 1 }}</span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold">{{ $row['course']->title }}</div>
                                <div class="font-12 text-muted">{{ $row['sessions'] }} {{ Str::plural('session', $row['sessions']) }} · {{ $row['completion'] }}% completed</div>
                            </div>
                            <span class="fw-semibold" title="People enrolled">{{ $row['enrolled'] }}</span>
                        </div>
                    @empty
                        <div class="card-body font-13 text-muted">Nobody was enrolled {{ $period }}.</div>
                    @endforelse
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card side-list tone-rose mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-alert-triangle"></i></span>
                        <span class="fw-semibold">Worth a look</span>
                        <span class="side-list-count">{{ $attention->count() }}</span>
                    </div>
                    @forelse ($attention as $row)
                        <div class="side-list-item">
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold">{{ $row['course']->title }}</div>
                                <div class="font-12 text-muted">{{ collect([
                                    $row['overdue'] ? $row['overdue'].' still open after the session ended' : null,
                                    $row['completion'] !== null && $row['closed'] > 0 && $row['completion'] < 50 ? 'Only '.$row['completion'].'% completed' : null,
                                    $row['pass'] !== null && $row['pass'] < 50 ? 'Only '.$row['pass'].'% passed the assessment' : null,
                                ])->filter()->implode(' · ') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="card-body font-13 text-muted">No course is under 50% on completion or pass rate, and no finished session has people left open.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-book"></i> By course <span class="list-tab-count">{{ $rows->count() }}</span></div>
            @if ($rows->isNotEmpty())
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" id="report-search" class="form-control form-control-sm" placeholder="Search course" aria-label="Search the report">
                    </div>
                    <div class="segmented" role="group" aria-label="Sort by">
                        @foreach ($sorts as $value => $label)
                            <a href="{{ $pageUrl(['sort' => $value === 'name' ? null : $value]) }}" class="segmented-item {{ $state['sort'] === $value ? 'active' : '' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        @if ($neverRun && ! $state['year'])
            <div class="list-subbar"><span class="font-13 text-muted"><i class="ti ti-info-circle"></i> {{ $neverRun }} active {{ Str::plural('course', $neverRun) }} {{ $neverRun === 1 ? 'has' : 'have' }} never had a session.</span></div>
        @endif
        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0" id="report-table">
                <thead class="thead-light">
                    <tr>
                        <th>Course</th>
                        <th class="text-center">Sessions</th>
                        <th style="min-width: 190px">Completion</th>
                        <th class="text-end">Hours delivered</th>
                        <th style="min-width: 170px">Assessments</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $course = $row['course'];
                            $notStarted = $row['enrolled'] - $row['completed'] - $row['in_progress'];
                        @endphp
                        <tr data-search="{{ mb_strtolower($course->title.' '.$row['category']) }}">
                            <td>
                                <div class="fw-semibold">{{ $course->title }}@unless ($course->is_active) <span class="tag tone-slate">Switched off</span>@endunless</div>
                                <div class="payslip-parts"><i class="ti {{ $modeIcon[$course->delivery_mode] ?? 'ti-book' }}"></i> {{ collect([Course::DELIVERY_MODES[$course->delivery_mode] ?? $course->delivery_mode, $row['category'], $course->duration_hours !== null ? $num($course->duration_hours).' h' : null])->filter()->implode(' · ') }}</div>
                            </td>
                            <td class="text-center">
                                @if ($row['sessions'] || $row['cancelled'])
                                    {{ $row['sessions'] }}
                                    @if ($row['cancelled'])
                                        <div class="payslip-parts">{{ $row['cancelled'] }} cancelled</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($row['enrolled'])
                                    <div class="mini-progress tone-{{ $rateTone($row['completion']) }}">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ $row['completion'] }}%"></span></div>
                                        <span class="mini-progress-label">{{ $row['completed'] }}/{{ $row['enrolled'] }}</span>
                                    </div>
                                    <div class="payslip-parts">{{ collect([$row['completion'].'%', $row['in_progress'] ? $row['in_progress'].' in progress' : null, $notStarted ? $notStarted.' not started' : null, $row['dropped'] ? $row['dropped'].' dropped' : null])->filter()->implode(' · ') }}</div>
                                @else
                                    <span class="text-muted">{{ $row['dropped'] ? $row['dropped'].' dropped, nobody else' : ($row['sessions'] ? 'Nobody enrolled' : 'No session '.$period) }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">{{ $row['hours'] > 0 ? $num($row['hours']).' h' : '—' }}</td>
                            <td>
                                @if ($row['taken'])
                                    <div class="mini-progress tone-{{ $rateTone($row['pass']) }}">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ $row['pass'] }}%"></span></div>
                                        <span class="mini-progress-label">{{ $row['passed'] }}/{{ $row['taken'] }}</span>
                                    </div>
                                    <div class="payslip-parts">{{ $row['pass'] }}% passed{{ $row['average'] !== null ? ' · average score '.$row['average'].'%' : '' }}</div>
                                @else
                                    <span class="text-muted">{{ $row['assessments'] ? 'No results '.$period : 'No assessment' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-report-analytics font-24 d-block mb-2"></i>
                                @if ($state['category'] !== '')
                                    <div class="fw-semibold text-body mb-1">No courses in this category</div>
                                    <span class="font-13"><a href="{{ $pageUrl(['category' => null]) }}">Show all categories</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No courses yet</div>
                                    <span class="font-13">The report fills in once courses have sessions. @if ($canSeeCourses)<a href="{{ route('admin.courses.index') }}">Go to Courses</a>@endif</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    <tr id="report-none" hidden><td colspan="5" class="text-center text-muted py-4">No course matches your search.</td></tr>
                </tbody>
                @if ($rows->count() > 1)
                    <tfoot>
                        <tr class="report-total">
                            <td class="fw-semibold">Total · {{ $totals['courses'] }} courses</td>
                            <td class="text-center fw-bold">{{ $totals['sessions'] }}</td>
                            <td class="fw-bold">{{ $totals['enrolled'] ? $totals['completed'].' / '.$totals['enrolled'].' · '.$totals['completion'].'%' : '—' }}</td>
                            <td class="text-end fw-bold">{{ $totals['hours'] > 0 ? $num($totals['hours']).' h' : '—' }}</td>
                            <td class="fw-bold">{{ $totals['taken'] ? $totals['passed'].' / '.$totals['taken'].' · '.$totals['pass'].'%' : '—' }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var input = document.getElementById('report-search');
            if (!input) return;
            var rows = Array.prototype.slice.call(document.querySelectorAll('#report-table tbody tr[data-search]'));
            input.addEventListener('input', function () {
                var needle = input.value.trim().toLowerCase(), shown = 0;
                rows.forEach(function (row) {
                    var match = row.dataset.search.indexOf(needle) !== -1;
                    row.hidden = !match;
                    shown += match ? 1 : 0;
                });
                document.getElementById('report-none').hidden = shown > 0;
            });
        })();
    </script>
@endpush
