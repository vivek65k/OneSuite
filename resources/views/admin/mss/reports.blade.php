@extends('layouts.admin')

@section('title', 'Team Reports')

@php
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
    $money = fn ($n) => number_format((float) $n, fmod((float) $n, 1) ? 2 : 0);
    $pageUrl = fn (array $overrides = []) => route('admin.team-reports.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $monthUrl = fn ($date) => $pageUrl(['month' => $date->format('Y-m') === now()->format('Y-m') ? null : $date->format('Y-m')]);
    $rateTone = fn (?int $rate) => $rate === null ? 'slate' : ($rate >= 90 ? 'success' : ($rate >= 75 ? 'amber' : 'rose'));
    $gaps = $totals['absent'] + $totals['missing'];
    $maxOvertime = max(1, (float) $rows->max('overtime'));
    $maxSpend = max(1, (float) $rows->max(fn ($r) => $r['expenses'] + $r['expenses_pending']));
    $year = $start->year;
    $teamLink = fn ($person) => route('admin.team-attendance.index', array_filter(['employee_id' => $person->id, 'month' => $state['month']]));
@endphp

@section('content')
    <x-page-title title="Team Reports" />

    @unless ($hasTeam)
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-users font-24 d-block mb-2"></i>
                <div class="fw-semibold text-body mb-1">Nobody reports to you</div>
                <span class="font-13">These reports cover the people below you in the reporting line. HR sets who reports to whom on each employee's profile.</span>
            </div>
        </div>
    @else
        <div class="card list-view">
            <div class="list-toolbar">
                <div class="month-nav pb-0">
                    <a href="{{ $monthUrl($start->copy()->subMonth()) }}" class="btn-icon" title="Previous month"><i class="ti ti-chevron-left font-18"></i></a>
                    <div class="month-nav-label"><span class="fw-bold">{{ $start->format('F') }}</span><span class="text-muted">{{ $start->format('Y') }}</span></div>
                    @if ($isCurrent)
                        <span class="btn-icon disabled opacity-50" title="Next month hasn't happened yet" aria-disabled="true"><i class="ti ti-chevron-right font-18"></i></span>
                    @else
                        <a href="{{ $monthUrl($start->copy()->addMonth()) }}" class="btn-icon" title="Next month"><i class="ti ti-chevron-right font-18"></i></a>
                        <a href="{{ $pageUrl(['month' => null]) }}" class="btn btn-light btn-sm ms-1">This month</a>
                    @endif
                </div>
                <form method="GET" action="{{ route('admin.team-reports.index') }}" class="list-toolbar-actions">
                    @if ($hasIndirect)
                        <select name="scope" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Who to include">
                            <option value="">Everyone below me</option>
                            <option value="direct" @selected($state['scope'] === 'direct')>Direct reports only</option>
                        </select>
                    @endif
                    <input type="month" name="month" class="form-control form-control-sm" style="width: 160px" value="{{ $month }}" max="{{ now()->format('Y-m') }}" onchange="this.form.submit()" aria-label="Go to month">
                    @if ($rows->isNotEmpty())
                        <a href="{{ route('admin.team-reports.export', array_filter($state)) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export</a>
                    @endif
                </form>
            </div>
            @if ($isCurrent)
                <div class="list-subbar"><span class="font-12 text-muted"><i class="ti ti-info-circle"></i> {{ $start->format('F') }} is still running: attendance is counted up to today, {{ today()->format('M j') }}.</span></div>
            @endif
        </div>

        <div class="kpi-strip">
            <x-kpi-card label="Attendance" :value="$totals['rate'] !== null ? $totals['rate'].'%' : '—'" icon="ti-user-check" :tone="$rateTone($totals['rate'])" :hint="$totals['working'] ? $num($totals['worked']).' of '.$totals['working'].' working days'.($lowest && $lowest['rate'] < 75 ? ' · lowest: '.Str::before($lowest['employee']->name, ' ').' '.$lowest['rate'].'%' : '') : 'No working days yet'" />
            <x-kpi-card label="Absent or not marked" :value="$gaps" icon="ti-alert-circle" :tone="$gaps ? 'rose' : 'success'" :hint="$gaps ? $totals['absent'].' absent · '.$totals['missing'].' with nothing marked'.($totals['late'] ? ' · '.$totals['late'].' late '.Str::plural('arrival', $totals['late']) : '') : ($totals['late'] ? $totals['late'].' late '.Str::plural('arrival', $totals['late']) : 'Every day is accounted for')" />
            <x-kpi-card label="Leave taken" :value="$num($totals['leave_month']).' '.((float) $totals['leave_month'] === 1.0 ? 'day' : 'days')" icon="ti-plane" tone="violet" :hint="$num($totals['leave_year']).' so far in '.$year.($totals['leave_pending'] > 0 ? ' · '.$num($totals['leave_pending']).' requested' : '')" />
            <x-kpi-card label="Overtime" :value="$num($totals['overtime']).' h'" icon="ti-clock" :tone="$totals['overtime'] > 0 ? 'amber' : 'teal'" :hint="$topOvertime ? 'by '.$totals['overtime_people'].' '.Str::plural('person', $totals['overtime_people']).' · most: '.Str::before($topOvertime['employee']->name, ' ').' '.$num($topOvertime['overtime']).' h' : ($totals['overtime_pending'] > 0 ? $num($totals['overtime_pending']).' h waiting for approval' : 'No approved overtime')" />
        </div>

        @if ($rows->isEmpty())
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="ti ti-users font-24 d-block mb-2"></i>
                    <div class="fw-semibold text-body mb-1">Nobody to report on</div>
                    <span class="font-13"><a href="{{ $pageUrl(['scope' => null]) }}">Include everyone below you</a></span>
                </div>
            </div>
        @else
            <div class="card list-view">
                <div class="list-toolbar"><span class="list-toolbar-title">Attendance · {{ $start->format('F Y') }}</span></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Team member</th>
                                <th style="min-width: 190px">Days worked</th>
                                <th class="text-end">From home</th>
                                <th class="text-end">Half days</th>
                                <th class="text-end">On leave</th>
                                <th class="text-end">Absent</th>
                                <th class="text-end">Not marked</th>
                                <th class="text-end">Late</th>
                                <th class="text-end">Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $r)
                                <tr>
                                    <td>
                                        <a href="{{ $teamLink($r['employee']) }}" class="list-user text-body" title="See {{ $r['employee']->name }}'s days on Team Attendance">
                                            <x-avatar :person="$r['employee']" />
                                            <div class="list-user-text">
                                                <div class="list-user-name">{{ $r['employee']->name }}</div>
                                                <div class="list-user-meta">{{ collect([$r['employee']->designation ?: $r['employee']->department?->name, $r['employee']->is_direct ? null : 'indirect'])->filter()->implode(' · ') ?: $r['employee']->employee_code }}</div>
                                            </div>
                                        </a>
                                    </td>
                                    <td>
                                        @if ($r['rate'] !== null)
                                            <div class="mini-progress tone-{{ $rateTone($r['rate']) }}">
                                                <div class="mini-progress-bar is-toned"><span style="width: {{ min(100, $r['rate']) }}%"></span></div>
                                                <span class="mini-progress-label">{{ $r['rate'] }}%</span>
                                            </div>
                                            <div class="font-12 text-muted">{{ $num($r['worked']) }} of {{ $r['working'] }} working days</div>
                                        @else
                                            <span class="text-muted">No working days</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $r['wfh'] ?: '—' }}</td>
                                    <td class="text-end">{{ $r['half'] ?: '—' }}</td>
                                    <td class="text-end">{{ $r['leave'] ?: '—' }}</td>
                                    <td class="text-end {{ $r['absent'] ? 'text-danger fw-semibold' : '' }}">{{ $r['absent'] ?: '—' }}</td>
                                    <td class="text-end {{ $r['missing'] ? 'text-danger fw-semibold' : '' }}">{{ $r['missing'] ?: '—' }}</td>
                                    <td class="text-end" @if ($r['late']) title="{{ $r['late_minutes'] }} minutes in total, after grace" @endif><span class="{{ $r['late'] ? 'text-warning fw-semibold' : '' }}">{{ $r['late'] ?: '—' }}</span></td>
                                    <td class="text-end text-nowrap">{{ $r['hours'] > 0 ? $num($r['hours']) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if ($rows->count() > 1)
                            <tfoot>
                                <tr class="report-total">
                                    <td>Team total</td>
                                    <td>{{ $totals['rate'] !== null ? $totals['rate'].'% · '.$num($totals['worked']).' of '.$totals['working'] : '—' }}</td>
                                    <td class="text-end">{{ $totals['wfh'] }}</td>
                                    <td class="text-end">{{ $totals['half'] }}</td>
                                    <td class="text-end">{{ $totals['leave'] }}</td>
                                    <td class="text-end">{{ $totals['absent'] }}</td>
                                    <td class="text-end">{{ $totals['missing'] }}</td>
                                    <td class="text-end">{{ $totals['late'] }}</td>
                                    <td class="text-end">{{ $num($totals['hours']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <div class="card list-view">
                <div class="list-toolbar"><span class="list-toolbar-title">Leave · {{ $year }}</span></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Team member</th>
                                <th class="text-end">Taken in {{ $start->format('M') }}</th>
                                <th class="text-end">Taken in {{ $year }}</th>
                                @foreach ($leaveTypes as $type)
                                    <th class="text-end">{{ $type }}</th>
                                @endforeach
                                <th class="text-end">Requested</th>
                                <th class="text-end">Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $r)
                                <tr>
                                    <td><div class="list-user"><x-avatar :person="$r['employee']" /><div class="list-user-text"><div class="list-user-name">{{ $r['employee']->name }}</div></div></div></td>
                                    <td class="text-end">{{ $r['leave_month'] > 0 ? $num($r['leave_month']) : '—' }}</td>
                                    <td class="text-end fw-semibold">{{ $r['leave_year'] > 0 ? $num($r['leave_year']) : '—' }}</td>
                                    @foreach ($leaveTypes as $type)
                                        <td class="text-end text-muted">{{ ($r['leave_types'][$type] ?? 0) > 0 ? $num($r['leave_types'][$type]) : '—' }}</td>
                                    @endforeach
                                    <td class="text-end {{ $r['leave_pending'] > 0 ? 'text-warning' : '' }}">{{ $r['leave_pending'] > 0 ? $num($r['leave_pending']) : '—' }}</td>
                                    <td class="text-end {{ $r['leave_left'] <= 0 ? 'text-danger' : '' }}">{{ $num($r['leave_left']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if ($rows->count() > 1)
                            <tfoot>
                                <tr class="report-total">
                                    <td>Team total</td>
                                    <td class="text-end">{{ $num($totals['leave_month']) }}</td>
                                    <td class="text-end">{{ $num($totals['leave_year']) }}</td>
                                    @foreach ($leaveTypes as $type)
                                        <td class="text-end">{{ $num($rows->sum(fn ($r) => $r['leave_types'][$type] ?? 0)) }}</td>
                                    @endforeach
                                    <td class="text-end">{{ $num($totals['leave_pending']) }}</td>
                                    <td class="text-end">{{ $num($totals['leave_left']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                <div class="list-subbar"><span class="font-12 text-muted"><i class="ti ti-info-circle"></i> Approved leave, in days, by the date it starts. "Left" is what remains of this year's allowance across all leave types.</span></div>
            </div>

            <div class="card list-view">
                <div class="list-toolbar"><span class="list-toolbar-title">Overtime, expenses and travel · {{ $start->format('F Y') }}</span></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Team member</th>
                                <th style="min-width: 190px">Overtime</th>
                                <th style="min-width: 200px">Expenses claimed</th>
                                <th class="text-end">Trips</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $r)
                                @php $spend = $r['expenses'] + $r['expenses_pending']; @endphp
                                <tr>
                                    <td><div class="list-user"><x-avatar :person="$r['employee']" /><div class="list-user-text"><div class="list-user-name">{{ $r['employee']->name }}</div></div></div></td>
                                    <td>
                                        @if ($r['overtime'] > 0 || $r['overtime_pending'] > 0)
                                            <div class="mini-progress tone-amber">
                                                <div class="mini-progress-bar is-toned"><span style="width: {{ round($r['overtime'] / $maxOvertime * 100) }}%"></span></div>
                                                <span class="mini-progress-label">{{ $num($r['overtime']) }} h</span>
                                            </div>
                                            @if ($r['overtime_pending'] > 0)
                                                <div class="font-12 text-warning">{{ $num($r['overtime_pending']) }} h waiting for approval</div>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($spend > 0)
                                            <div class="mini-progress tone-pink">
                                                <div class="mini-progress-bar is-toned"><span style="width: {{ round($spend / $maxSpend * 100) }}%"></span></div>
                                                <span class="mini-progress-label">{{ $money($r['expenses']) }}</span>
                                            </div>
                                            <div class="font-12 {{ $r['expenses_pending'] > 0 ? 'text-warning' : 'text-muted' }}">{{ $r['claims'] }} {{ Str::plural('claim', $r['claims']) }}{{ $r['expenses_pending'] > 0 ? ' · '.$money($r['expenses_pending']).' waiting for approval' : ' approved' }}</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $r['trips'] ? $r['trips'].' · '.$r['trip_days'].' '.Str::plural('day', $r['trip_days']) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if ($rows->count() > 1)
                            <tfoot>
                                <tr class="report-total">
                                    <td>Team total</td>
                                    <td>{{ $num($totals['overtime']) }} h{{ $totals['overtime_pending'] > 0 ? ' · '.$num($totals['overtime_pending']).' h pending' : '' }}</td>
                                    <td>{{ $money($totals['expenses']) }}{{ $totals['expenses_pending'] > 0 ? ' · '.$money($totals['expenses_pending']).' pending' : '' }}</td>
                                    <td class="text-end">{{ $totals['trips'] ? $totals['trips'].' · '.$totals['trip_days'].' '.Str::plural('day', $totals['trip_days']) : '—' }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                <div class="list-subbar"><span class="font-12 text-muted"><i class="ti ti-info-circle"></i> Overtime and expenses are counted in the month they were worked or spent; rejected ones are left out. Trips are approved ones that touch this month.</span></div>
            </div>
        @endif
    @endunless
@endsection
