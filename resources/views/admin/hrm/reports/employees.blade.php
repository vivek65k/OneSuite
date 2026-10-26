@extends('layouts.admin')

@section('title', 'Employee Reports')

@php
    use Illuminate\Support\Str;

    $active = $byStatus['active'] ?? 0;
    $onLeave = $byStatus['on_leave'] ?? 0;
    $terminated = $byStatus['terminated'] ?? 0;
    $pct = fn ($n) => $total > 0 ? round($n / $total * 100) : 0;

    $breakdowns = [
        ['title' => 'By Department', 'icon' => 'ti-sitemap', 'tone' => 'indigo', 'rows' => $byDepartment],
        ['title' => 'By Branch', 'icon' => 'ti-building', 'tone' => 'teal', 'rows' => $byBranch],
        ['title' => 'By Employment Type', 'icon' => 'ti-briefcase', 'tone' => 'amber', 'rows' => $byEmploymentType],
    ];
@endphp

@section('content')
    <x-page-title title="Employee Reports" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $total > 0 ? 'indigo' : 'slate' }}" aria-hidden="true"><i class="ti ti-users font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $total }} {{ Str::plural('employee', $total) }}</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-success">{{ $active }} active</span>
                @if ($onLeave > 0)
                    <span class="status-dot status-dot-warning">{{ $onLeave }} on leave</span>
                @endif
                @if ($terminated > 0)
                    <span class="status-dot status-dot-danger">{{ $terminated }} terminated</span>
                @endif
            </div>
        </div>
        <div class="record-header-actions">
            <x-export-csv-button route="admin.employee-reports.export" />
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Total Employees" :value="$total" icon="ti-users" tone="indigo" />
        <x-kpi-card label="Active" :value="$active" icon="ti-user-check" tone="success" :hint="$pct($active).'% of headcount'" />
        <x-kpi-card label="On Leave" :value="$onLeave" icon="ti-calendar-off" tone="amber" :hint="$onLeave > 0 ? $pct($onLeave).'% of headcount' : null" />
        <x-kpi-card label="Terminated" :value="$terminated" icon="ti-user-x" tone="rose" :hint="$terminated > 0 ? $pct($terminated).'% of headcount' : null" />
    </div>

    <div class="row g-3">
        @foreach ($breakdowns as $b)
            @php $max = max(1, $b['rows']->max()); @endphp
            <div class="col-xl-4">
                <div class="card list-view tone-{{ $b['tone'] }} mb-0 h-100">
                    <div class="list-toolbar">
                        <div class="list-toolbar-title"><i class="ti {{ $b['icon'] }}"></i> {{ $b['title'] }}</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover list-table mb-0">
                            <tbody>
                                @forelse ($b['rows'] as $name => $count)
                                    <tr>
                                        <td class="fw-semibold">{{ $name }}</td>
                                        <td style="min-width: 140px">
                                            <div class="mini-progress tone-{{ $b['tone'] }}">
                                                <div class="mini-progress-bar is-toned"><span style="width: {{ round($count / $max * 100) }}%"></span></div>
                                                <span class="mini-progress-label">{{ $pct($count) }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-end"><span class="tag tone-{{ $b['tone'] }}">{{ $count }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-5">
                                            <i class="ti {{ $b['icon'] }} font-24 d-block mb-2"></i>
                                            No data.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
