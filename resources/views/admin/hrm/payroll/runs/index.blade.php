@extends('layouts.admin')

@section('title', 'Payroll Run')

@php
    use App\Models\PayrollRun;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pageUrl = fn (array $overrides = []) => route('admin.payroll-runs.index', array_filter(
        array_merge($state, ['period' => $period->format('Y-m') === now()->format('Y-m') ? null : $period->format('Y-m')], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All runs', 'tone' => 'indigo', 'count' => $counts['all']],
        'processed' => ['label' => 'Processed, not paid', 'tone' => 'amber', 'count' => $counts['processed']],
        'paid' => ['label' => 'Paid', 'tone' => 'success', 'count' => $counts['paid']],
    ];
    $latest = $kpis['latest'];
    $previous = $kpis['previous'];
    $change = $latest && $previous && (float) $previous->total_net > 0 ? round(((float) $latest->total_net - (float) $previous->total_net) / (float) $previous->total_net * 100, 1) : null;
    // Periods offered in the run panel: this month and the five before it.
    $periods = collect(range(0, 5))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
@endphp

@section('content')
    <x-page-title title="Payroll Run" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card :label="$latest ? 'Latest run · '.$latest->monthLabel() : 'Latest run'" :value="$latest ? $money($latest->total_net) : '—'" icon="ti-cash" tone="success"
                    :hint="$latest ? ($change !== null ? ($change >= 0 ? '+' : '').$change.'% net vs '.$previous->monthLabel() : $latest->employee_count.' payslips') : 'No payroll run yet'"
                    :href="$latest ? route('admin.payroll-runs.show', $latest) : null" />
        <x-kpi-card label="Waiting to be paid" :value="$kpis['unpaid']->count()" icon="ti-clock" :tone="$kpis['unpaid']->count() ? 'amber' : 'success'"
                    :hint="$kpis['unpaid']->count() ? $money($kpis['unpaid']->sum('total_net')).' net across '.$kpis['unpaid']->map->monthLabel()->take(2)->implode(', ') : 'Every run is paid'"
                    :href="$pageUrl(['status' => 'processed'])" />
        <x-kpi-card label="Paid in {{ now()->year }}" :value="$kpis['ytd_runs'] ? $money($kpis['ytd_net']) : '—'" icon="ti-calendar-stats" tone="teal" :hint="$kpis['ytd_runs'].' '.Str::plural('run', $kpis['ytd_runs']).' this year'" />
        <x-kpi-card label="Payroll runs" :value="$counts['all']" icon="ti-history" tone="indigo" :hint="$counts['paid'].' paid · '.$counts['processed'].' processed'" />
    </div>

    {{-- Run payroll --}}
    @if ($preview)
        @php
            $blocked = $existing || $preview['included']->isEmpty();
            $netEstimate = max(0, $preview['gross'] - $preview['deductions'] - $preview['loan_emi']);
        @endphp
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <div class="card form-section tone-indigo mb-0 h-100">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-player-play"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Run payroll</h5>
                            <p class="form-section-sub">Pick the month. Nothing is saved until you press Run.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="run-periods" role="group" aria-label="Period">
                            @foreach ($periods as $p)
                                @php $ran = in_array($p->format('Y-m'), $ranPeriods, true); @endphp
                                <a href="{{ route('admin.payroll-runs.index', ['period' => $p->format('Y-m')]) }}" class="run-period {{ $p->isSameMonth($period) ? 'is-active' : '' }} {{ $ran ? 'is-done' : '' }}">
                                    <span class="run-period-month">{{ $p->format('M') }}</span>
                                    <span class="run-period-year">{{ $p->format('Y') }}</span>
                                    <span class="run-period-state"><i class="ti {{ $ran ? 'ti-circle-check' : 'ti-circle-dashed' }}"></i> {{ $ran ? 'Done' : 'Not run' }}</span>
                                </a>
                            @endforeach
                        </div>

                        @if ($existing)
                            <div class="run-note tone-success mt-3">
                                <i class="ti ti-circle-check"></i>
                                <div class="flex-grow-1">Payroll for <strong>{{ $period->format('F Y') }}</strong> has already been run ({{ PayrollRun::STATUSES[$existing->status] ?? $existing->status }}).</div>
                                <a href="{{ route('admin.payroll-runs.show', $existing) }}" class="btn btn-sm btn-light">Open run <i class="ti ti-arrow-right"></i></a>
                            </div>
                        @elseif ($preview['included']->isEmpty())
                            <div class="run-note tone-danger mt-3">
                                <i class="ti ti-alert-triangle"></i>
                                <div class="flex-grow-1">Nobody has a salary structure yet, so there is nothing to pay for <strong>{{ $period->format('F Y') }}</strong>.</div>
                                <a href="{{ route('admin.salary-structures.index', ['tab' => 'missing']) }}" class="btn btn-sm btn-light">Set up structures <i class="ti ti-arrow-right"></i></a>
                            </div>
                        @else
                            <div class="policy-card-stats mt-3">
                                <div><strong>{{ $preview['included']->count() }}</strong><span>{{ Str::plural('Payslip', $preview['included']->count()) }}</span></div>
                                <div><strong>{{ $money($preview['gross']) }}</strong><span>Gross (before statutory)</span></div>
                                <div><strong>≈ {{ $money($netEstimate) }}</strong><span>Before PF, ESI and tax</span></div>
                            </div>
                            <div class="run-lines">
                                <div><span>Salary structures</span><strong>{{ $money($preview['structures']) }}</strong></div>
                                <div><span>Bonuses & incentives for {{ $period->format('F') }} <em>({{ $preview['bonus_count'] }})</em></span><strong>+ {{ $money($preview['bonuses']) }}</strong></div>
                                <div><span>Deductions for {{ $period->format('F') }} <em>({{ $preview['deduction_count'] }})</em></span><strong>− {{ $money($preview['deductions']) }}</strong></div>
                                <div><span>Loan instalments <em>({{ $preview['loan_count'] }})</em></span><strong>− {{ $money($preview['loan_emi']) }}</strong></div>
                            </div>
                            <form method="POST" action="{{ route('admin.payroll-runs.store') }}" class="mt-3 d-flex align-items-center flex-wrap gap-2"
                                  onsubmit="return confirm({{ json_encode('Run payroll for '.$period->format('F Y').'? This creates '.$preview['included']->count().' payslips'.($preview['skipped']->count() ? ' and skips '.$preview['skipped']->count().' people with no salary structure. A month can only be run once, so they cannot be added later.' : '.')) }});">
                                @csrf
                                <input type="hidden" name="month" value="{{ $period->month }}">
                                <input type="hidden" name="year" value="{{ $period->year }}">
                                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-player-play font-14 me-1"></i> Run payroll for {{ $period->format('F Y') }}</button>
                                <span class="list-subtext">PF, ESI and income tax are worked out for each person when it runs.</span>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card side-list tone-{{ $preview['skipped']->isEmpty() ? 'success' : 'rose' }} h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-user-x"></i></span>
                        <span class="fw-semibold">Won't be paid in {{ $period->format('F') }}</span>
                        @if ($preview['skipped']->isNotEmpty())<span class="side-list-count">{{ $preview['skipped']->count() }}</span>@endif
                    </div>
                    @forelse ($preview['skipped']->take(6) as $person)
                        <a href="{{ route('admin.salary-structures.edit', $person) }}" class="side-list-item py-2">
                            <x-avatar :person="$person" />
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold font-13 text-truncate">{{ $person->name }}</div>
                                <div class="list-subtext">No salary structure</div>
                            </div>
                            <span class="tag tone-rose">Set up <i class="ti ti-arrow-right"></i></span>
                        </a>
                    @empty
                        <div class="side-list-empty"><i class="ti ti-circle-check"></i> Everyone on payroll has a salary structure.</div>
                    @endforelse
                    @if ($preview['skipped']->count() > 6)
                        <a href="{{ route('admin.salary-structures.index', ['tab' => 'missing']) }}" class="side-list-foot">+{{ $preview['skipped']->count() - 6 }} more</a>
                    @endif
                    @if ($preview['skipped']->isNotEmpty() && ! $existing)
                        <div class="side-list-foot"><i class="ti ti-info-circle"></i> A month can only be run once. Set these up first if they should be paid.</div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- History --}}
    <div class="card list-view">
        <div class="list-toolbar">
            <nav class="list-tabs" aria-label="Run status">
                @foreach ($tabs as $value => $tab)
                    <a href="{{ $pageUrl(['status' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['status'] === $value ? 'active' : '' }}">
                        {{ $tab['label'] }}
                        <span class="list-tab-count">{{ $tab['count'] }}</span>
                    </a>
                @endforeach
            </nav>
            @if ($years->count() > 1)
                <div class="list-toolbar-actions">
                    <div class="segmented" role="group" aria-label="Year">
                        <a href="{{ $pageUrl(['year' => null]) }}" class="segmented-item {{ $state['year'] ? '' : 'active' }}">All years</a>
                        @foreach ($years as $year)
                            <a href="{{ $pageUrl(['year' => $year]) }}" class="segmented-item {{ $state['year'] === $year ? 'active' : '' }}">{{ $year }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Period</th>
                        <th class="text-center">Payslips</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Deductions</th>
                        <th class="text-end">Net pay</th>
                        <th>Status</th>
                        <th>Run on</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($runs as $run)
                        <tr class="{{ $run->status === 'paid' ? '' : 'is-pending-row' }}">
                            <td>
                                <div class="date-range-cell">
                                    <span class="when-block tone-{{ $run->status === 'paid' ? 'success' : 'amber' }}"><span class="when-block-month">{{ $run->year }}</span><span class="when-block-day">{{ Str::limit(PayrollRun::MONTHS[$run->month] ?? $run->month, 3, '') }}</span></span>
                                    <a href="{{ route('admin.payroll-runs.show', $run) }}" class="fw-semibold">{{ $run->monthLabel() }}</a>
                                </div>
                            </td>
                            <td class="text-center">{{ $run->employee_count }}</td>
                            <td class="text-end">{{ $money($run->total_gross) }}</td>
                            <td class="text-end text-muted">{{ $money($run->total_deductions) }}</td>
                            <td class="text-end"><span class="salary-figure">{{ $money($run->total_net) }}</span></td>
                            <td><span class="tag tone-{{ $run->status === 'paid' ? 'success' : 'amber' }}"><i class="ti {{ $run->status === 'paid' ? 'ti-circle-check' : 'ti-clock' }}"></i>{{ PayrollRun::STATUSES[$run->status] ?? $run->status }}</span></td>
                            <td class="text-nowrap">{{ $run->run_date?->format('M j, Y') }}</td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($run->status !== 'paid')
                                        @can('update', $run)
                                            <form action="{{ route('admin.payroll-runs.mark-paid', $run) }}" method="POST" class="d-inline" onsubmit="return confirm('Mark {{ $run->monthLabel() }} as paid? All {{ $run->employee_count }} payslips will be marked paid.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-cash"></i> Mark paid</button>
                                            </form>
                                        @endcan
                                    @endif
                                    <a href="{{ route('admin.payroll-runs.show', $run) }}" class="btn-icon btn-icon-accent" title="Open run"><i class="ti ti-arrow-right font-16"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="ti ti-report-money font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">{{ $counts['all'] ? 'No runs match' : 'No payroll runs yet' }}</div>
                                @if ($counts['all'])
                                    <span class="font-13"><a href="{{ route('admin.payroll-runs.index') }}">Show all runs</a></span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($runs->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $runs->links() }}</div></div>
        @endif
    </div>
@endsection
