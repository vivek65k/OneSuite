@extends('layouts.admin')

@section('title', 'Diversity Reports')

@php
    use Illuminate\Support\Str;

    $male = $byGender['male'] ?? 0;
    $female = $byGender['female'] ?? 0;
    $other = ($byGender['other'] ?? 0) + ($byGender['unspecified'] ?? 0);
    $pct = fn ($n) => $total > 0 ? round($n / $total * 100) : 0;
    $typeMax = max(1, $byEmploymentType->max());
@endphp

@section('content')
    <x-page-title title="Diversity Reports" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $total > 0 ? 'violet' : 'slate' }}" aria-hidden="true"><i class="ti ti-users font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $total }} active {{ Str::plural('employee', $total) }}</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-info">{{ $male }} male · {{ $pct($male) }}%</span>
                <span>{{ $female }} female · {{ $pct($female) }}%</span>
                @if ($other > 0)
                    <span>{{ $other }} other/unspecified</span>
                @endif
            </div>
        </div>
        <div class="record-header-actions">
            <x-export-csv-button route="admin.diversity-reports.export" />
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Total Active" :value="$total" icon="ti-users" tone="violet" />
        <x-kpi-card label="Male" :value="$male" icon="ti-mars" tone="sky" :hint="$pct($male).'% of headcount'" />
        <x-kpi-card label="Female" :value="$female" icon="ti-venus" tone="pink" :hint="$pct($female).'% of headcount'" />
        <x-kpi-card label="Other / Unspecified" :value="$other" icon="ti-question-mark" tone="slate" :hint="$other > 0 ? $pct($other).'% of headcount' : null" />
    </div>

    <div class="row g-3">
        <div class="col-xl-5">
            <div class="card list-view tone-indigo mb-0 h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-briefcase"></i> By Employment Type</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($byEmploymentType as $name => $count)
                                <tr>
                                    <td class="fw-semibold">{{ $name }}</td>
                                    <td style="min-width: 140px">
                                        <div class="mini-progress tone-indigo">
                                            <div class="mini-progress-bar is-toned"><span style="width: {{ round($count / $typeMax * 100) }}%"></span></div>
                                        </div>
                                    </td>
                                    <td class="text-end"><span class="tag tone-indigo">{{ $count }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-5"><i class="ti ti-briefcase font-24 d-block mb-2"></i>No data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card list-view mb-0 h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-sitemap"></i> Gender by Department</div>
                    <div class="stack-legend ms-auto">
                        <span><i class="legend-dot tone-sky"></i>Male</span>
                        <span><i class="legend-dot tone-pink"></i>Female</span>
                        <span><i class="legend-dot tone-slate"></i>Other</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr><th>Department</th><th style="min-width: 160px">Split</th><th class="text-end">Total</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($byDepartmentGender as $department => $counts)
                                @php
                                    $m = $counts['male'] ?? 0;
                                    $f = $counts['female'] ?? 0;
                                    $o = ($counts['other'] ?? 0) + ($counts['unspecified'] ?? 0);
                                    $deptTotal = $m + $f + $o;
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $department }}</td>
                                    <td>
                                        <div class="stack-bar" title="{{ $m }} male, {{ $f }} female, {{ $o }} other">
                                            @if ($m > 0)<span class="tone-sky" style="flex: {{ $m }}"></span>@endif
                                            @if ($f > 0)<span class="tone-pink" style="flex: {{ $f }}"></span>@endif
                                            @if ($o > 0)<span class="tone-slate" style="flex: {{ $o }}"></span>@endif
                                        </div>
                                    </td>
                                    <td class="text-end">{{ $deptTotal }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-5"><i class="ti ti-sitemap font-24 d-block mb-2"></i>No data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
