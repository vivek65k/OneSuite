@extends('layouts.admin')

@section('title', 'Performance Reports')

@php
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $query = array_filter(['cycle_id' => $cycle?->id, 'department_id' => $departmentId, 'sort' => $sort === 'name' ? null : $sort], fn ($v) => $v !== null && $v !== '');
    $pageUrl = fn (array $overrides = []) => route('admin.performance-reports.index', array_filter(array_merge($query, $overrides), fn ($v) => $v !== null && $v !== ''));
    $stars = fn ($rating) => str_repeat('★', (int) round((float) $rating)).str_repeat('☆', max(0, 5 - (int) round((float) $rating)));
    $has = $rows->isNotEmpty();
    $donePct = $kpis['total'] ? round($kpis['completed'] / $kpis['total'] * 100) : 0;
    $maxBucket = max(1, (int) $distribution->max());
    $starTone = [5 => 'success', 4 => 'teal', 3 => 'indigo', 2 => 'amber', 1 => 'rose'];
    $rateTone = fn ($pct) => $pct === null ? 'slate' : ($pct >= 100 ? 'success' : ($pct >= 70 ? 'amber' : 'rose'));
    $sorts = ['name' => 'A–Z', 'rating' => 'Rating', 'goals' => 'Goals', 'kpi' => 'KPIs'];
@endphp

@section('content')
    <x-page-title title="Performance Reports" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $cycle ? 'indigo' : 'slate' }}" aria-hidden="true"><i class="ti ti-report-analytics font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $cycle?->name ?? 'No appraisal cycle yet' }}{{ $departmentId && isset($departments[$departmentId]) ? ' · '.$departments[$departmentId] : '' }}</div>
            <div class="record-header-meta">
                @if ($cycle)
                    <span>{{ $cycle->period_start?->format('M j, Y') }} – {{ $cycle->period_end?->format('M j, Y') }}</span>
                    <span class="status-dot status-dot-{{ $cycle->status === 'active' ? 'success' : 'warning' }}">{{ $cycle->status === 'active' ? 'Cycle open' : 'Cycle closed' }}</span>
                    <span>{{ $kpis['completed'] }}/{{ $kpis['total'] }} appraisals completed</span>
                @else
                    <span>Reports are built from an appraisal cycle.</span>
                @endif
            </div>
        </div>
        <div class="record-header-actions">
            @if ($cycles->isNotEmpty())
                <form method="GET" action="{{ route('admin.performance-reports.index') }}" class="d-flex align-items-center gap-2">
                    <select name="cycle_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Appraisal cycle">
                        @foreach ($cycles as $option)
                            <option value="{{ $option->id }}" @selected($cycle?->id === $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                    @if ($departments->count() > 1)
                        <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected($departmentId === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if ($sort !== 'name')
                        <input type="hidden" name="sort" value="{{ $sort }}">
                    @endif
                </form>
            @endif
            @if ($has)
                <a href="{{ route('admin.performance-reports.export', $query) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-download font-14 me-1"></i> Export CSV</a>
            @endif
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Appraisals completed" :value="$kpis['total'] ? $kpis['completed'].' / '.$kpis['total'] : '—'" icon="ti-clipboard-check" :tone="$kpis['total'] && $kpis['completed'] === $kpis['total'] ? 'success' : 'indigo'" :hint="$kpis['total'] ? $donePct.'% of this cycle' : 'No appraisals in this cycle'" />
        <x-kpi-card label="Average rating" :value="$kpis['rating'] !== null ? $num($kpis['rating']).' / 5' : '—'" icon="ti-star" tone="amber" :hint="$kpis['rated'] ? 'Across '.$kpis['rated'].' rated '.Str::plural('person', $kpis['rated']) : 'Nobody is rated yet'" />
        <x-kpi-card label="Goal progress" :value="$kpis['goal_progress'] !== null ? $kpis['goal_progress'].'%' : '—'" icon="ti-target" tone="teal" :hint="$kpis['goals'] ? $kpis['goals_done'].' of '.$kpis['goals'].' goals completed' : 'No goals in this cycle'" />
        <x-kpi-card label="KPI achievement" :value="$kpis['kpi'] !== null ? $num($kpis['kpi']).'%' : '—'" icon="ti-chart-line" :tone="$rateTone($kpis['kpi'])" :hint="$kpis['kpis'] ? 'Across '.$kpis['kpis'].' KPI '.Str::plural('target', $kpis['kpis']) : 'No KPIs in this cycle'" />
    </div>

    @if ($notStarted->isNotEmpty())
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="ti ti-alert-triangle font-18"></i>
            <div><strong>{{ $notStarted->count() }} {{ Str::plural('person', $notStarted->count()) }} with goals or KPIs in this cycle {{ $notStarted->count() === 1 ? 'has' : 'have' }} no appraisal</strong>, so {{ $notStarted->count() === 1 ? 'they are' : 'they are' }} missing from this report: {{ $notStarted->take(5)->implode(', ') }}{{ $notStarted->count() > 5 ? ' and '.($notStarted->count() - 5).' more' : '' }}. @can('create', \App\Models\Appraisal::class)<a href="{{ route('admin.appraisals.index') }}">Start their appraisals</a>@endcan</div>
        </div>
    @endif

    @if ($has)
        <div class="row g-3 mb-3">
            <div class="col-xl-4">
                <div class="card side-list tone-amber mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-chart-bar"></i></span>
                        <span class="fw-semibold">How the ratings spread</span>
                        <span class="side-list-count">{{ $kpis['rated'] }}</span>
                    </div>
                    <div class="card-body">
                        @foreach ($distribution as $star => $count)
                            <div class="rating-row">
                                <span class="rating-row-label">{{ $star }} <span class="rating-stars">★</span></span>
                                <div class="mini-progress-bar is-toned tone-{{ $starTone[$star] }}"><span style="width: {{ round($count / $maxBucket * 100) }}%"></span></div>
                                <span class="rating-row-count">{{ $count }}</span>
                            </div>
                        @endforeach
                        @unless ($kpis['rated'])
                            <div class="font-13 text-muted mt-2">Nobody has a rating yet. Ratings appear once a self or manager review is recorded.</div>
                        @endunless
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card side-list tone-success mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-award"></i></span>
                        <span class="fw-semibold">Top rated</span>
                    </div>
                    @forelse ($top as $i => $row)
                        <div class="side-list-item">
                            <span class="rank-badge rank-{{ $i + 1 }}">{{ $i + 1 }}</span>
                            <x-avatar :person="$row['employee']" />
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold">{{ $row['name'] }}</div>
                                <div class="font-12 text-muted">{{ $row['department'] ?? 'No department' }}{{ $row['completed'] ? '' : ' · not final yet' }}</div>
                            </div>
                            <span class="rating-stars" title="{{ $num($row['rating']) }} of 5">{{ $num($row['rating']) }} ★</span>
                        </div>
                    @empty
                        <div class="card-body font-13 text-muted">Nobody is rated yet.</div>
                    @endforelse
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card side-list tone-rose mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-alert-triangle"></i></span>
                        <span class="fw-semibold">Worth a conversation</span>
                        <span class="side-list-count">{{ $attention->count() }}</span>
                    </div>
                    @forelse ($attention as $row)
                        <div class="side-list-item">
                            <x-avatar :person="$row['employee']" />
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold">{{ $row['name'] }}</div>
                                <div class="font-12 text-muted">{{ collect([
                                    $row['rating'] !== null && $row['rating'] <= 2 ? 'Rated '.$num($row['rating']).' of 5' : null,
                                    $row['goal_progress'] !== null && $row['goal_progress'] < 40 ? 'Goals at '.round($row['goal_progress']).'%' : null,
                                    $row['kpi_achievement'] !== null && $row['kpi_achievement'] < 60 ? 'KPIs at '.$num($row['kpi_achievement']).'%' : null,
                                ])->filter()->implode(' · ') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="card-body font-13 text-muted">Nobody is rated 2 or lower, under 40% on goals, or under 60% on KPIs.</div>
                    @endforelse
                </div>
            </div>
        </div>

        @if ($byDepartment->count() > 1)
            <div class="card list-view mb-3">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-sitemap"></i> By department</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Department</th>
                                <th class="text-center">People</th>
                                <th class="text-center">Completed</th>
                                <th>Average rating</th>
                                <th style="min-width: 160px">Goal progress</th>
                                <th style="min-width: 160px">KPI achievement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($byDepartment as $dept)
                                <tr class="{{ $departmentId && $dept['id'] === $departmentId ? 'table-active' : '' }}">
                                    <td>
                                        @if ($dept['id'])
                                            <a href="{{ $pageUrl(['department_id' => $departmentId === $dept['id'] ? null : $dept['id']]) }}" class="fw-semibold" title="Show only this department">{{ $dept['name'] }}</a>
                                        @else
                                            <span class="fw-semibold">{{ $dept['name'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $dept['people'] }}</td>
                                    <td class="text-center">{{ $dept['completed'] }}/{{ $dept['people'] }}</td>
                                    <td class="text-nowrap">@if ($dept['rating'] !== null)<span class="rating-stars">{{ $stars($dept['rating']) }}</span> <span class="font-13">{{ $num($dept['rating']) }}</span>@else<span class="text-muted">—</span>@endif</td>
                                    <td>
                                        @if ($dept['goal_progress'] !== null)
                                            <div class="mini-progress tone-teal"><div class="mini-progress-bar is-toned"><span style="width: {{ min(100, $dept['goal_progress']) }}%"></span></div><span class="mini-progress-label">{{ $dept['goal_progress'] }}%</span></div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($dept['kpi'] !== null)
                                            <div class="mini-progress tone-{{ $rateTone($dept['kpi']) }}"><div class="mini-progress-bar is-toned"><span style="width: {{ min(100, $dept['kpi']) }}%"></span></div><span class="mini-progress-label">{{ $num($dept['kpi']) }}%</span></div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-users"></i> Everyone in this cycle <span class="list-tab-count">{{ $rows->count() }}</span></div>
            @if ($has)
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" id="perf-search" class="form-control form-control-sm" placeholder="Search employee" aria-label="Search the report">
                    </div>
                    <div class="segmented" role="group" aria-label="Sort by">
                        @foreach ($sorts as $value => $label)
                            <a href="{{ $pageUrl(['sort' => $value === 'name' ? null : $value]) }}" class="segmented-item {{ $sort === $value ? 'active' : '' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if ($departmentId && isset($departments[$departmentId]))
            <div class="list-subbar">
                <div class="list-chips">
                    <a href="{{ $pageUrl(['department_id' => null]) }}" class="filter-chip" title="Remove filter">Department: <strong>{{ $departments[$departmentId] }}</strong> <i class="ti ti-x"></i></a>
                </div>
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0" id="perf-table">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th class="text-center">Self</th>
                        <th class="text-center">Manager</th>
                        <th class="text-center">360</th>
                        <th>Overall</th>
                        <th style="min-width: 150px">Goals</th>
                        <th style="min-width: 150px">KPIs</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr data-name="{{ mb_strtolower($row['name'].' '.$row['code'].' '.$row['department']) }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$row['employee']" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $row['name'] }}</div>
                                        <div class="list-user-meta">{{ collect([$row['code'], $row['department']])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">{{ $row['self'] ?? '—' }}</td>
                            <td class="text-center">{{ $row['manager'] ?? '—' }}</td>
                            <td class="text-center" title="{{ $row['feedback_entries'] ? 'From '.$row['feedback_entries'].' '.Str::plural('reviewer', $row['feedback_entries']) : 'No 360 feedback' }}">{{ $row['feedback'] !== null ? $num($row['feedback']) : '—' }}</td>
                            <td class="text-nowrap">
                                @if ($row['overall'] !== null)
                                    <div class="rating-stars" title="{{ $num($row['overall']) }} of 5">{{ $stars($row['overall']) }}</div>
                                    <div class="payslip-parts">{{ $num($row['overall']) }} of 5</div>
                                @elseif ($row['rating'] !== null)
                                    <span class="text-muted font-13" title="Not final: the appraisal is still open">{{ $num($row['rating']) }} so far</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($row['goals_total'])
                                    <div class="mini-progress tone-teal"><div class="mini-progress-bar is-toned"><span style="width: {{ min(100, $row['goal_progress']) }}%"></span></div><span class="mini-progress-label">{{ round($row['goal_progress']) }}%</span></div>
                                    <div class="payslip-parts">{{ $row['goals_completed'] }} of {{ $row['goals_total'] }} done</div>
                                @else
                                    <span class="text-muted font-13">No goals</span>
                                @endif
                            </td>
                            <td>
                                @if ($row['kpi_achievement'] !== null)
                                    <div class="mini-progress tone-{{ $rateTone($row['kpi_achievement']) }}"><div class="mini-progress-bar is-toned"><span style="width: {{ min(100, $row['kpi_achievement']) }}%"></span></div><span class="mini-progress-label">{{ $num($row['kpi_achievement']) }}%</span></div>
                                    <div class="payslip-parts">{{ $row['kpis_total'] }} {{ Str::plural('KPI', $row['kpis_total']) }}</div>
                                @elseif ($row['kpis_total'])
                                    <span class="text-muted font-13">{{ $row['kpis_total'] }} {{ Str::plural('KPI', $row['kpis_total']) }}, no results</span>
                                @else
                                    <span class="text-muted font-13">No KPIs</span>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $row['completed'] ? 'success' : 'amber' }}"><i class="ti {{ $row['completed'] ? 'ti-circle-check' : 'ti-clock' }}"></i>{{ $row['completed'] ? 'Completed' : 'In progress' }}</span>
                                @if ($row['increment'] !== null)
                                    <div class="payslip-parts">+{{ $num($row['increment']) }}% increment given</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="ti ti-report-analytics font-24 d-block mb-2"></i>
                                @if (! $cycle)
                                    <div class="fw-semibold text-body mb-1">No appraisal cycle yet</div>
                                    <span class="font-13">@can('create', \App\Models\Appraisal::class)Create one on the <a href="{{ route('admin.appraisals.index') }}">Appraisals</a> page, then start appraisals in it.@else Reports appear once HR creates an appraisal cycle.@endcan</span>
                                @elseif ($departmentId)
                                    <div class="fw-semibold text-body mb-1">Nobody in this department has an appraisal in {{ $cycle->name }}</div>
                                    <span class="font-13"><a href="{{ $pageUrl(['department_id' => null]) }}">Show all departments</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No appraisals in {{ $cycle->name }} yet</div>
                                    <span class="font-13">@can('create', \App\Models\Appraisal::class)<a href="{{ route('admin.appraisals.index') }}">Start appraisals</a> for this cycle to see them here.@else Appraisals will appear here once they are started.@endcan</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var search = document.getElementById('perf-search');
            if (!search) return;
            var rows = document.querySelectorAll('#perf-table tbody tr[data-name]');
            search.addEventListener('input', function () {
                var q = search.value.trim().toLowerCase();
                rows.forEach(function (row) { row.hidden = q && row.dataset.name.indexOf(q) === -1; });
            });
        })();
    </script>
@endpush
