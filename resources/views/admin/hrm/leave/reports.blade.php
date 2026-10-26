@extends('layouts.admin')

@section('title', 'Leave Reports')

@php
    use App\Support\LeaveTypeStyle;

    $pageUrl = fn (array $overrides = []) => route('admin.leave-reports.index', array_filter(
        array_merge($state, ['year' => $year], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $pct = fn ($part, $whole) => $whole > 0 ? (int) round($part / $whole * 100) : 0;
    $utilTone = fn (int $p) => $p >= 70 ? 'success' : ($p >= 35 ? 'teal' : ($p >= 15 ? 'amber' : 'danger'));
    $utilization = $pct($totals['used'], $totals['entitled']);
    $sortOptions = ['name' => 'Employee A-Z', 'used' => 'Most days used', 'remaining' => 'Most days left', 'util_desc' => 'Highest utilization', 'util_asc' => 'Lowest utilization'];
    $currentSort = $state['sort'] ?? 'name';
    $typeTones = $byType->mapWithKeys(fn ($t) => [$t['name'] => LeaveTypeStyle::for($t['name'], $t['code'])]);
    $initialsOf = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $filtersOn = filled($state['search'] ?? null) || filled($state['department_id'] ?? null) || filled($state['type'] ?? null);
@endphp

@section('content')
    <x-page-title title="Leave Reports" />

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="month-nav">
                <a href="{{ $pageUrl(['year' => $year - 1]) }}" class="btn-icon" title="Previous year"><i class="ti ti-chevron-left font-18"></i></a>
                <div class="month-nav-label"><span class="fw-bold">{{ $year }}</span><span class="text-muted">leave year</span></div>
                <a href="{{ $pageUrl(['year' => $year + 1]) }}" class="btn-icon" title="Next year"><i class="ti ti-chevron-right font-18"></i></a>
                @if ($year !== (int) now()->year)
                    <a href="{{ $pageUrl(['year' => now()->year]) }}" class="btn btn-light btn-sm ms-1">This year</a>
                @endif
            </div>
            <form method="GET" action="{{ route('admin.leave-reports.index') }}" class="list-toolbar-actions">
                <input type="hidden" name="year" value="{{ $year }}">
                @if ($currentSort !== 'name')
                    <input type="hidden" name="sort" value="{{ $currentSort }}">
                @endif
                <div class="list-search">
                    <i class="ti ti-search"></i>
                    <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search employee" aria-label="Search employee">
                </div>
                <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                    <option value="">All departments</option>
                    @foreach ($departments as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($state['department_id'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="type" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Leave type">
                    <option value="">All leave types</option>
                    @foreach ($typeNames as $name)
                        <option value="{{ $name }}" @selected(($state['type'] ?? '') === $name)>{{ $name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('admin.leave-reports.export', array_filter(array_merge($state, ['year' => $year]))) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export CSV</a>
            </form>
        </div>
        @if ($filtersOn)
            <div class="list-subbar border-bottom-0">
                <span class="list-subtext">Showing a filtered view of {{ $year }}.</span>
                <a href="{{ route('admin.leave-reports.index', ['year' => $year]) }}" class="list-clear-all">Clear filters</a>
            </div>
        @endif
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Leave utilization" :value="$utilization.'%'" icon="ti-chart-pie" :tone="$utilTone($utilization)" :hint="$fmt($totals['used']).' of '.$fmt($totals['entitled']).' days taken'" />
        <x-kpi-card label="Days still owed" :value="$fmt($totals['remaining'])" icon="ti-calendar-stats" tone="amber" hint="Unused balance - the leave liability" />
        <x-kpi-card label="Encashed" :value="$fmt($totals['encashed']).' d'" icon="ti-cash" tone="success" :hint="$encashedAmount > 0 ? ($currency ? $currency.' ' : '').number_format($encashedAmount, 2).' paid out' : 'Nothing paid out'" />
        <x-kpi-card label="Requests approved" :value="$requestStats['approval_rate'] !== null ? $requestStats['approval_rate'].'%' : '—'" icon="ti-circle-check" tone="indigo"
                    :hint="$requestStats['approved'].' approved · '.$requestStats['rejected'].' rejected'.($requestStats['pending'] ? ' · '.$requestStats['pending'].' pending' : '')" />
    </div>

    <div class="row g-3 mb-3">
        {{-- Monthly trend --}}
        <div class="col-xl-7">
            <div class="card side-list tone-indigo h-100 mb-0">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-chart-bar"></i></span>
                    <span class="fw-semibold">Leave days taken by month</span>
                    <span class="list-subtext ms-auto">Approved, {{ $year }}</span>
                </div>
                <div class="card-body">
                    <div class="trend-chart trend-chart-12">
                        @foreach ($monthly as $m => $byName)
                            @php $total = array_sum($byName); $monthDate = \Illuminate\Support\Carbon::create($year, $m, 1); @endphp
                            <div class="trend-col {{ $monthDate->isSameMonth(now()) ? 'is-today' : '' }}" title="{{ $monthDate->format('F') }}: {{ $total }} {{ Str::plural('day', $total) }}">
                                <span class="trend-value">{{ $total ?: '' }}</span>
                                <span class="trend-bar" style="height: {{ $total ? max(6, round($total / $monthlyMax * 100)) : 0 }}%">
                                    @foreach ($byName as $name => $count)
                                        <span class="tone-{{ $typeTones[$name]['tone'] ?? LeaveTypeStyle::for($name)['tone'] }}" style="flex: {{ $count }}"></span>
                                    @endforeach
                                </span>
                                <span class="trend-label">{{ $monthDate->format('M') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="stack-legend justify-content-center mt-2">
                        @foreach ($byType as $type)
                            <span><i class="legend-dot tone-{{ $typeTones[$type['name']]['tone'] }}"></i>{{ $type['name'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- By leave type --}}
        <div class="col-xl-5">
            <div class="card side-list tone-violet h-100 mb-0">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-calendar-off"></i></span>
                    <span class="fw-semibold">By leave type</span>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    @forelse ($byType as $type)
                        @php $style = $typeTones[$type['name']]; $u = $pct($type['used'], $type['entitled']); @endphp
                        <div class="type-usage">
                            <div class="type-usage-head">
                                <span class="tag tone-{{ $style['tone'] }}"><i class="ti {{ $style['icon'] }}"></i>{{ $type['name'] }}</span>
                                <span class="list-subtext">{{ $type['employees'] }} {{ Str::plural('employee', $type['employees']) }}</span>
                                <span class="type-usage-pct tone-{{ $style['tone'] }}">{{ $u }}%</span>
                            </div>
                            <div class="stack-bar" title="Used {{ $fmt($type['used']) }} · Encashed {{ $fmt($type['encashed']) }} · Remaining {{ $fmt($type['remaining']) }} of {{ $fmt($type['entitled']) }}">
                                @if ($type['used'] > 0)<span class="tone-{{ $style['tone'] }}" style="flex: {{ $type['used'] }}"></span>@endif
                                @if ($type['encashed'] > 0)<span class="tone-success is-hatched" style="flex: {{ $type['encashed'] }}"></span>@endif
                                @if ($type['remaining'] > 0)<span class="is-unmarked" style="flex: {{ $type['remaining'] }}"></span>@endif
                            </div>
                            <div class="type-usage-foot">
                                <span><strong>{{ $fmt($type['used']) }}</strong> used</span>
                                @if ($type['encashed'] > 0)<span><strong>{{ $fmt($type['encashed']) }}</strong> encashed</span>@endif
                                <span><strong>{{ $fmt($type['remaining']) }}</strong> left of {{ $fmt($type['entitled']) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="side-list-empty text-muted"><i class="ti ti-info-circle"></i> No balances for {{ $year }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card side-list tone-teal h-100 mb-0">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-sitemap"></i></span>
                    <span class="fw-semibold">By department</span>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    @forelse ($byDepartment as $dept)
                        @php $u = $pct($dept['used'], $dept['entitled']); @endphp
                        <div>
                            <div class="d-flex justify-content-between font-13 mb-1">
                                <span class="fw-semibold text-truncate">{{ $dept['name'] }}</span>
                                <span class="list-subtext">{{ $fmt($dept['used']) }}/{{ $fmt($dept['entitled']) }} d</span>
                            </div>
                            <div class="mini-progress tone-{{ $utilTone($u) }}">
                                <div class="mini-progress-bar is-toned"><span style="width: {{ $u }}%"></span></div>
                                <span class="mini-progress-label">{{ $u }}%</span>
                            </div>
                        </div>
                    @empty
                        <div class="side-list-empty text-muted"><i class="ti ti-info-circle"></i> No data</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card side-list tone-violet h-100 mb-0">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-plane"></i></span>
                    <span class="fw-semibold">Most leave taken</span>
                </div>
                @forelse ($topTakers as $person)
                    <div class="side-list-item">
                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($person['employee']->employee_code.$person['employee']->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($person['employee']->name) }}</span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold font-13 text-truncate">{{ $person['employee']->name }}</div>
                            <div class="list-subtext">{{ $pct($person['used'], $person['entitled']) }}% of entitlement</div>
                        </div>
                        <span class="countdown tone-violet">{{ $fmt($person['used']) }} d</span>
                    </div>
                @empty
                    <div class="side-list-empty text-muted"><i class="ti ti-info-circle"></i> No leave taken in {{ $year }}</div>
                @endforelse
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card side-list tone-amber h-100 mb-0">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-alert-triangle"></i></span>
                    <span class="fw-semibold">Hasn't taken leave</span>
                </div>
                @forelse ($notTaking as $person)
                    <div class="side-list-item">
                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($person['employee']->employee_code.$person['employee']->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($person['employee']->name) }}</span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold font-13 text-truncate">{{ $person['employee']->name }}</div>
                            <div class="list-subtext">only {{ $fmt($person['used']) }} of {{ $fmt($person['entitled']) }} days used</div>
                        </div>
                        <span class="countdown tone-amber" title="Days left">{{ $fmt($person['remaining']) }} left</span>
                    </div>
                @empty
                    <div class="side-list-empty"><i class="ti ti-circle-check"></i> Everyone is taking their leave</div>
                @endforelse
                <div class="px-3 pb-2 list-subtext">Low usage for this point in the year - a burnout / year-end liability risk.</div>
            </div>
        </div>
    </div>

    {{-- Detail --}}
    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title">
                <span class="list-toolbar-title-icon tone-indigo"><i class="ti ti-table"></i></span>
                <div>
                    <div class="fw-semibold">Balance detail</div>
                    <div class="text-muted font-12">{{ $summary->count() }} {{ Str::plural('balance', $summary->count()) }} · {{ $summary->pluck('employee_id')->unique()->count() }} {{ Str::plural('employee', $summary->pluck('employee_id')->unique()->count()) }}</div>
                </div>
            </div>
            <div class="list-toolbar-actions">
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-arrows-sort font-14 me-1"></i> {{ $sortOptions[$currentSort] ?? 'Sort' }}</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach ($sortOptions as $value => $label)
                            <li><a class="dropdown-item {{ $currentSort === $value ? 'active' : '' }}" href="{{ $pageUrl(['sort' => $value === 'name' ? null : $value]) }}">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Leave type</th>
                        <th class="text-center">Allocated</th>
                        <th class="text-center">Carried</th>
                        <th class="text-center">Adjusted</th>
                        <th class="text-center">Encashed</th>
                        <th style="min-width: 170px">Used</th>
                        <th class="text-center">Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($summary as $balance)
                        @php
                            $style = LeaveTypeStyle::for($balance->leaveType->name, $balance->leaveType->code);
                            $entitled = (float) $balance->allocated + (float) $balance->carried_forward + (float) $balance->adjusted;
                            $u = $pct((float) $balance->used, $entitled);
                            $employee = $balance->employee;
                        @endphp
                        <tr>
                            <td>
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($employee->name) }}</span>
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></div>
                                        <div class="list-user-meta">{{ $employee->department?->name ?? $employee->employee_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="tag tone-{{ $style['tone'] }}"><i class="ti {{ $style['icon'] }}"></i>{{ $balance->leaveType->name }}</span></td>
                            <td class="text-center">{{ $fmt($balance->allocated) }}</td>
                            <td class="text-center {{ (float) $balance->carried_forward ? '' : 'text-muted' }}">{{ $fmt($balance->carried_forward) }}</td>
                            <td class="text-center {{ (float) $balance->adjusted ? ((float) $balance->adjusted > 0 ? 'text-success' : 'text-danger') : 'text-muted' }}">{{ (float) $balance->adjusted > 0 ? '+' : '' }}{{ $fmt($balance->adjusted) }}</td>
                            <td class="text-center {{ (float) $balance->encashed ? '' : 'text-muted' }}">{{ $fmt($balance->encashed) }}</td>
                            <td>
                                <div class="mini-progress tone-{{ $style['tone'] }}">
                                    <div class="mini-progress-bar is-toned"><span style="width: {{ min(100, $u) }}%"></span></div>
                                    <span class="mini-progress-label">{{ $fmt($balance->used) }} · {{ $u }}%</span>
                                </div>
                            </td>
                            <td class="text-center"><span class="heatmap-count {{ (float) $balance->remaining <= 0 ? 'tone-danger' : ((float) $balance->remaining <= 2 ? 'tone-amber' : 'tone-success') }}">{{ $fmt($balance->remaining) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="ti ti-chart-bar font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No leave balances for {{ $year }}</div>
                                <span class="font-13">Allocate balances on the <a href="{{ route('admin.leave-balances.index', ['year' => $year]) }}">Leave Balance</a> page first.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($summary->isNotEmpty())
                    <tfoot>
                        <tr class="report-total">
                            <td colspan="2">Total</td>
                            <td class="text-center">{{ $fmt($summary->sum('allocated')) }}</td>
                            <td class="text-center">{{ $fmt($summary->sum('carried_forward')) }}</td>
                            <td class="text-center">{{ $fmt($summary->sum('adjusted')) }}</td>
                            <td class="text-center">{{ $fmt($totals['encashed']) }}</td>
                            <td>{{ $fmt($totals['used']) }} · {{ $utilization }}%</td>
                            <td class="text-center">{{ $fmt($totals['remaining']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
