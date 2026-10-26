@extends('layouts.admin')

@section('title', 'Attendance Reports')

@php
    use App\Support\Attendance;

    $reportUrl = fn (array $overrides = []) => route('admin.attendance.reports', array_filter(
        array_merge($state, ['month' => $month], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $sortOptions = ['name' => 'Name A-Z', 'rate_desc' => 'Best attendance', 'rate_asc' => 'Lowest attendance', 'absent' => 'Most absences', 'overtime' => 'Most overtime'];
    $currentSort = $state['sort'] ?? 'name';
    $columns = ['present', 'absent', 'half_day', 'on_leave', 'wfh'];
    $rateTone = fn (?int $rate) => $rate === null ? 'slate' : ($rate >= 95 ? 'success' : ($rate >= 85 ? 'teal' : ($rate >= 70 ? 'amber' : 'danger')));
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
@endphp

@section('content')
    <x-page-title title="Attendance Reports" :breadcrumbs="['Attendance' => route('admin.attendance.dashboard')]" />

    <div class="kpi-strip">
        <x-kpi-card label="Average attendance" :value="$kpis['avg_rate'] !== null ? $kpis['avg_rate'].'%' : '—'" icon="ti-chart-pie" :tone="$rateTone($kpis['avg_rate'])" :hint="$start->format('F Y')" />
        <x-kpi-card label="Perfect attendance" :value="$kpis['perfect']" icon="ti-award" tone="success" hint="No absences or half days" />
        <x-kpi-card label="Total absences" :value="$kpis['absences']" icon="ti-user-x" tone="danger" :href="$reportUrl(['sort' => 'absent'])" />
        <x-kpi-card label="Approved overtime" :value="$fmt($kpis['overtime']).' h'" icon="ti-alarm" tone="pink" :href="$reportUrl(['sort' => 'overtime'])" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="month-nav">
                <a href="{{ $reportUrl(['month' => $start->copy()->subMonth()->format('Y-m')]) }}" class="btn-icon" title="Previous month"><i class="ti ti-chevron-left font-18"></i></a>
                <div class="month-nav-label">
                    <span class="fw-bold">{{ $start->format('F') }}</span>
                    <span class="text-muted">{{ $start->format('Y') }}</span>
                </div>
                <a href="{{ $reportUrl(['month' => $start->copy()->addMonth()->format('Y-m')]) }}" class="btn-icon" title="Next month"><i class="ti ti-chevron-right font-18"></i></a>
            </div>

            <form method="GET" action="{{ route('admin.attendance.reports') }}" class="list-toolbar-actions">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="sort" value="{{ $currentSort }}">
                <div class="list-search">
                    <i class="ti ti-search"></i>
                    <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search name or code" aria-label="Search employees">
                </div>
                <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                    <option value="">All departments</option>
                    @foreach ($departments as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($state['department_id'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-arrows-sort font-14 me-1"></i> {{ $sortOptions[$currentSort] ?? 'Sort' }}</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach ($sortOptions as $value => $label)
                            <li><a class="dropdown-item {{ $currentSort === $value ? 'active' : '' }}" href="{{ $reportUrl(['sort' => $value]) }}">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <a href="{{ route('admin.attendance.reports.export', array_filter(array_merge($state, ['month' => $month]))) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export CSV</a>
            </form>
        </div>

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table report-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th style="min-width: 180px">Attendance</th>
                        @foreach ($columns as $status)
                            <th class="text-center"><span class="heat-cell heat-cell-sm tone-{{ Attendance::meta($status)['tone'] }}">{{ Attendance::meta($status)['letter'] }}</span> {{ Attendance::label($status) }}</th>
                        @endforeach
                        <th class="text-center">Overtime</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($summary as $row)
                        @php
                            $employee = $row['employee'];
                            $initials = collect(preg_split('/\s+/', trim($employee->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                        @endphp
                        <tr>
                            <td>
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></div>
                                        <div class="list-user-meta">{{ $employee->department?->name ?? $employee->employee_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($row['rate'] !== null)
                                    <div class="mini-progress tone-{{ $rateTone($row['rate']) }}">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ $row['rate'] }}%"></span></div>
                                        <span class="mini-progress-label">{{ $row['rate'] }}%</span>
                                    </div>
                                    <div class="list-subtext mt-1">{{ $row['marked'] }} working {{ Str::plural('day', $row['marked']) }} marked</div>
                                @else
                                    <span class="text-muted font-12">No attendance marked</span>
                                @endif
                            </td>
                            @foreach ($columns as $status)
                                <td class="text-center">
                                    <span class="heatmap-count {{ $row[$status] ? 'tone-'.Attendance::meta($status)['tone'] : '' }}">{{ $row[$status] }}</span>
                                </td>
                            @endforeach
                            <td class="text-center">
                                @if ($row['overtime_hours'] > 0)
                                    <span class="hours-figure">{{ $fmt($row['overtime_hours']) }}<small>h</small></span>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + count($columns) }}" class="text-center text-muted py-5">
                                <i class="ti ti-chart-bar font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No employees found</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($summary->isNotEmpty())
                    <tfoot>
                        <tr class="report-total">
                            <td>Total · {{ $summary->count() }} {{ Str::plural('employee', $summary->count()) }}</td>
                            <td>{{ $kpis['avg_rate'] !== null ? $kpis['avg_rate'].'% average' : '—' }}</td>
                            @foreach ($columns as $status)
                                <td class="text-center">{{ $totals[$status] }}</td>
                            @endforeach
                            <td class="text-center">{{ $fmt($totals['overtime_hours']) }}h</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
