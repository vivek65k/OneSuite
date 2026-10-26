@extends('layouts.admin')

@section('title', 'Attrition Reports')

@php
    use Illuminate\Support\Str;

    $monthMax = max(1, $byMonth->max());
    $deptMax = max(1, $byDepartment->max());
    $rateTone = $attritionRate >= 15 ? 'rose' : ($attritionRate >= 8 ? 'amber' : 'success');
@endphp

@section('content')
    <x-page-title title="Attrition Reports" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $rateTone }}" aria-hidden="true"><i class="ti ti-trending-down font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $attritionRate }}% attrition in {{ $year }}</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-{{ $rateTone === 'success' ? 'success' : ($rateTone === 'amber' ? 'warning' : 'danger') }}">{{ $resignations->count() }} {{ Str::plural('resignation', $resignations->count()) }}</span>
                <span>{{ $currentHeadcount }} current headcount</span>
            </div>
        </div>
        <div class="record-header-actions">
            <form method="GET" action="{{ route('admin.attrition-reports.index') }}" class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.attrition-reports.index', ['year' => $year - 1]) }}" class="btn btn-light btn-sm px-2" title="Previous year"><i class="ti ti-chevron-left font-14"></i></a>
                <select name="year" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Year">
                    @foreach (range(now()->year, now()->year - 6) as $y)
                        <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                    @endforeach
                </select>
                @if ($year >= now()->year)
                    <span class="btn btn-light btn-sm px-2 disabled" aria-disabled="true"><i class="ti ti-chevron-right font-14"></i></span>
                @else
                    <a href="{{ route('admin.attrition-reports.index', ['year' => $year + 1]) }}" class="btn btn-light btn-sm px-2" title="Next year"><i class="ti ti-chevron-right font-14"></i></a>
                @endif
            </form>
            @if ($resignations->isNotEmpty())
                <x-export-csv-button route="admin.attrition-reports.export" />
            @endif
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Attrition Rate" :value="$attritionRate.'%'" icon="ti-trending-down" :tone="$rateTone" :hint="$year.' year to date'" />
        <x-kpi-card label="Resignations" :value="$resignations->count()" icon="ti-logout" tone="amber" :hint="$year" />
        <x-kpi-card label="Current Headcount" :value="$currentHeadcount" icon="ti-users" tone="indigo" />
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card list-view tone-rose mb-0 h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-calendar-stats"></i> By Month</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($byMonth as $month => $count)
                                <tr>
                                    <td class="fw-semibold">{{ $month }}</td>
                                    <td style="min-width: 140px">
                                        <div class="mini-progress tone-rose">
                                            <div class="mini-progress-bar is-toned"><span style="width: {{ round($count / $monthMax * 100) }}%"></span></div>
                                        </div>
                                    </td>
                                    <td class="text-end"><span class="tag tone-rose">{{ $count }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-5"><i class="ti ti-calendar-stats font-24 d-block mb-2"></i>No resignations in {{ $year }}.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card list-view tone-amber mb-0 h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-sitemap"></i> By Department</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($byDepartment as $name => $count)
                                <tr>
                                    <td class="fw-semibold">{{ $name }}</td>
                                    <td style="min-width: 140px">
                                        <div class="mini-progress tone-amber">
                                            <div class="mini-progress-bar is-toned"><span style="width: {{ round($count / $deptMax * 100) }}%"></span></div>
                                        </div>
                                    </td>
                                    <td class="text-end"><span class="tag tone-amber">{{ $count }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-5"><i class="ti ti-sitemap font-24 d-block mb-2"></i>No resignations in {{ $year }}.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-logout"></i> Resignations in {{ $year }} <span class="list-tab-count">{{ $resignations->count() }}</span></div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Resignation Date</th>
                        <th>Last Working Day</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($resignations as $resignation)
                        <tr>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$resignation->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $resignation->employee->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $resignation->employee->department?->name ?? '—' }}</td>
                            <td>{{ $resignation->resignation_date->format('M j, Y') }}</td>
                            <td>{{ $resignation->last_working_day->format('M j, Y') }}</td>
                            <td><span class="tag tone-{{ $resignation->status === 'completed' ? 'success' : 'amber' }}">{{ \App\Models\Resignation::STATUSES[$resignation->status] ?? $resignation->status }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-trending-down font-24 d-block mb-2"></i>
                                No resignations found for {{ $year }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
