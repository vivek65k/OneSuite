@extends('layouts.admin')

@section('title', 'Payroll Reports')

@php
    use App\Models\Payslip;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $dash = fn ($n) => (float) $n > 0 ? $money($n) : '—';
    $key = $period->format('Y-m');
    $pageUrl = fn (array $overrides = []) => route('admin.payroll-reports.index', array_filter(
        array_merge(['period' => $key, 'department_id' => $departmentId], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $has = $totals['slips'] > 0;
    $gross = $totals['gross'];
    // "+4.2% vs Sep" style comparison against the month before, when it has payroll.
    $versus = function (string $field) use ($totals, $previous, $period) {
        if (! $previous || (float) $previous[$field] <= 0) {
            return null;
        }
        $pct = round(($totals[$field] - $previous[$field]) / $previous[$field] * 100, 1);

        return ($pct > 0 ? '+' : '').$pct.'% vs '.$period->copy()->subMonth()->format('M');
    };
    $segments = collect([
        ['label' => 'Net pay', 'tone' => 'success', 'amount' => $totals['net']],
        ['label' => 'PF', 'tone' => 'indigo', 'amount' => $totals['pf']],
        ['label' => 'ESI', 'tone' => 'sky', 'amount' => $totals['esi']],
        ['label' => 'Income tax', 'tone' => 'rose', 'amount' => $totals['tax']],
        ['label' => 'Loan recovery', 'tone' => 'amber', 'amount' => $totals['loan']],
        ['label' => 'Other', 'tone' => 'slate', 'amount' => $totals['other']],
    ])->filter(fn ($s) => $s['amount'] > 0)->values();
    $statutory = [
        ['label' => 'Provident Fund', 'tone' => 'indigo', 'employee' => $totals['pf'], 'employer' => $totals['pf_employer']],
        ['label' => 'ESI', 'tone' => 'sky', 'employee' => $totals['esi'], 'employer' => $totals['esi_employer']],
        ['label' => 'Income tax (TDS)', 'tone' => 'rose', 'employee' => $totals['tax'], 'employer' => 0],
    ];
    $statutoryTotal = collect($statutory)->sum(fn ($s) => $s['employee'] + $s['employer']);
    $choices = $periods->contains($key) ? $periods : $periods->concat([$key])->sortDesc()->values();
    $isLatest = $period->gte(now()->startOfMonth());
    $trendHasData = $trend->sum('slips') > 0;
    $deptMax = max(1, (float) $byDepartment->max('gross'));
    $canSeeRuns = auth()->user()->can('viewAny', \App\Models\PayrollRun::class);
@endphp

@section('content')
    <x-page-title title="Payroll Reports" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $has ? 'indigo' : 'slate' }}" aria-hidden="true"><i class="ti ti-report-money font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $period->format('F Y') }}{{ $departmentId && isset($departments[$departmentId]) ? ' · '.$departments[$departmentId] : '' }}</div>
            <div class="record-header-meta">
                @if ($run)
                    <span class="status-dot status-dot-{{ $totals['paid'] === $totals['slips'] && $has ? 'success' : 'warning' }}">{{ $totals['paid'] }}/{{ $totals['slips'] }} payslips paid</span>
                    <span>Run on {{ $run->run_date?->format('M j, Y') }}</span>
                @else
                    <span class="status-dot status-dot-warning">Payroll has not been run for this month</span>
                @endif
            </div>
        </div>
        <div class="record-header-actions">
            <form method="GET" action="{{ route('admin.payroll-reports.index') }}" class="d-flex align-items-center gap-2">
                <a href="{{ $pageUrl(['period' => $period->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-light btn-sm px-2" title="Previous month"><i class="ti ti-chevron-left font-14"></i></a>
                <select name="period" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Month">
                    @foreach ($choices as $choice)
                        <option value="{{ $choice }}" @selected($choice === $key)>{{ Carbon::createFromFormat('!Y-m', $choice)->format('F Y') }}{{ $periods->contains($choice) ? '' : ' (not run)' }}</option>
                    @endforeach
                </select>
                @if ($isLatest)
                    <span class="btn btn-light btn-sm px-2 disabled" aria-disabled="true"><i class="ti ti-chevron-right font-14"></i></span>
                @else
                    <a href="{{ $pageUrl(['period' => $period->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-light btn-sm px-2" title="Next month"><i class="ti ti-chevron-right font-14"></i></a>
                @endif
                @if ($departments->count() > 1)
                    <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                        <option value="">All departments</option>
                        @foreach ($departments as $id => $name)
                            <option value="{{ $id }}" @selected($departmentId === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                @endif
            </form>
            @if ($has)
                <a href="{{ route('admin.payroll-reports.export', array_filter(['period' => $key, 'department_id' => $departmentId])) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-download font-14 me-1"></i> Export CSV</a>
            @endif
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Gross pay" :value="$has ? $money($gross) : '—'" icon="ti-trending-up" tone="teal" :hint="$has ? ($versus('gross') ?? $totals['slips'].' '.Str::plural('payslip', $totals['slips'])) : 'No payroll this month'" />
        <x-kpi-card label="Deductions" :value="$has ? $money($totals['deductions']) : '—'" icon="ti-trending-down" tone="rose" :hint="$has && $gross > 0 ? round($totals['deductions'] / $gross * 100).'% of gross' : null" />
        <x-kpi-card label="Net pay" :value="$has ? $money($totals['net']) : '—'" icon="ti-cash" tone="success" :hint="$has ? ($versus('net') ?? 'Average '.$money($totals['net'] / $totals['slips'])) : null" />
        <x-kpi-card label="Total cost to company" :value="$has ? $money($totals['employer_cost']) : '—'" icon="ti-building" tone="indigo" :hint="$has ? 'Gross + '.$money($totals['pf_employer'] + $totals['esi_employer']).' employer PF/ESI' : null" />
    </div>

    @if ($has || $trendHasData)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <div class="card side-list tone-indigo mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-chart-bar"></i></span>
                        <span class="fw-semibold">Last 12 months</span>
                        <span class="ms-auto font-12 text-muted">Net pay + deductions = gross</span>
                    </div>
                    <div class="card-body pt-2"><div class="chart-box" id="payroll-report-trend"></div></div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-rose mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-building-bank"></i></span>
                        <span class="fw-semibold">Statutory dues for {{ $period->format('F') }}</span>
                        <span class="side-list-count">{{ $money($statutoryTotal) }}</span>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm report-mini mb-0">
                            <thead>
                                <tr><th></th><th class="text-end">Employee</th><th class="text-end">Employer</th><th class="text-end">To deposit</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($statutory as $s)
                                    <tr>
                                        <td><i class="legend-dot tone-{{ $s['tone'] }}"></i> {{ $s['label'] }}</td>
                                        <td class="text-end">{{ $dash($s['employee']) }}</td>
                                        <td class="text-end">{{ $dash($s['employer']) }}</td>
                                        <td class="text-end fw-semibold">{{ $dash($s['employee'] + $s['employer']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if ($segments->isNotEmpty() && $gross > 0)
                            <div class="mix-row-title mt-3">Where the gross pay went</div>
                            <div class="stack-bar stack-bar-lg">
                                @foreach ($segments as $s)
                                    <span class="tone-{{ $s['tone'] }}" style="flex: {{ $s['amount'] }}" title="{{ $s['label'] }}: {{ $money($s['amount']) }}"></span>
                                @endforeach
                            </div>
                            <div class="stack-legend">
                                @foreach ($segments as $s)
                                    <span><i class="legend-dot tone-{{ $s['tone'] }}"></i>{{ $s['label'] }} <strong>{{ $money($s['amount']) }}</strong> <span class="text-muted">· {{ round($s['amount'] / $gross * 100) }}%</span></span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

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
                            <th style="min-width: 200px">Share of gross</th>
                            <th class="text-end">Gross</th>
                            <th class="text-end">Deductions</th>
                            <th class="text-end">Net pay</th>
                            <th class="text-end">Cost to company</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($byDepartment as $row)
                            <tr class="{{ $departmentId && $row['id'] === $departmentId ? 'table-active' : '' }}">
                                <td>
                                    @if ($row['id'])
                                        <a href="{{ $pageUrl(['department_id' => $departmentId === $row['id'] ? null : $row['id']]) }}" class="fw-semibold" title="Show only this department">{{ $row['name'] }}</a>
                                    @else
                                        <span class="fw-semibold">{{ $row['name'] }}</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $row['slips'] }}</td>
                                <td>
                                    <div class="mini-progress tone-indigo">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ round($row['gross'] / $deptMax * 100) }}%"></span></div>
                                        <span class="mini-progress-label">{{ $byDepartment->sum('gross') > 0 ? round($row['gross'] / $byDepartment->sum('gross') * 100) : 0 }}%</span>
                                    </div>
                                </td>
                                <td class="text-end">{{ $money($row['gross']) }}</td>
                                <td class="text-end text-muted">{{ $money($row['deductions']) }}</td>
                                <td class="text-end"><span class="salary-figure">{{ $money($row['net']) }}</span></td>
                                <td class="text-end">{{ $money($row['employer_cost']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-file-invoice"></i> Salary register <span class="list-tab-count">{{ $totals['slips'] }}</span></div>
            @if ($has)
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" id="register-search" class="form-control form-control-sm" placeholder="Search employee" aria-label="Search the register">
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
            <table class="table table-hover list-table mb-0" id="register-table">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">PF</th>
                        <th class="text-end">ESI</th>
                        <th class="text-end">Tax</th>
                        <th class="text-end">Loan</th>
                        <th class="text-end">Other</th>
                        <th class="text-end">Deductions</th>
                        <th class="text-end">Net pay</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Open</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payslips as $payslip)
                        <tr data-name="{{ mb_strtolower(($payslip->employee?->name ?? '').' '.($payslip->employee?->employee_code ?? '').' '.($payslip->employee?->department?->name ?? '')) }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$payslip->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $payslip->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$payslip->employee?->employee_code, $payslip->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">{{ $money($payslip->gross_earnings) }}</td>
                            <td class="text-end text-muted">{{ $dash($payslip->pf_employee_amount) }}</td>
                            <td class="text-end text-muted">{{ $dash($payslip->esi_employee_amount) }}</td>
                            <td class="text-end text-muted">{{ $dash($payslip->tax_amount) }}</td>
                            <td class="text-end text-muted">{{ $dash($payslip->loan_deduction_amount) }}</td>
                            <td class="text-end text-muted">{{ $dash($payslip->other_deduction_amount) }}</td>
                            <td class="text-end">{{ $dash($payslip->total_deductions) }}</td>
                            <td class="text-end"><span class="salary-figure">{{ $money($payslip->net_pay) }}</span></td>
                            <td><span class="tag tone-{{ $payslip->status === 'paid' ? 'success' : 'amber' }}">{{ $payslip->status === 'paid' ? 'Paid' : 'Not paid yet' }}</span></td>
                            <td class="text-end"><a href="{{ route('admin.payslips.show', $payslip) }}" class="btn-icon btn-icon-accent" title="View payslip"><i class="ti ti-eye font-16"></i></a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                <i class="ti ti-report font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No payroll for {{ $period->format('F Y') }}{{ $departmentId ? ' in this department' : '' }}</div>
                                <span class="font-13">
                                    @if ($departmentId)
                                        <a href="{{ $pageUrl(['department_id' => null]) }}">Show all departments</a>
                                    @elseif ($periods->isNotEmpty())
                                        Latest payroll is <a href="{{ $pageUrl(['period' => $periods->first()]) }}">{{ Carbon::createFromFormat('!Y-m', $periods->first())->format('F Y') }}</a>.
                                    @elseif ($canSeeRuns)
                                        Run payroll first from the <a href="{{ route('admin.payroll-runs.index') }}">Payroll Run</a> screen.
                                    @else
                                        Payroll has not been run yet.
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($has)
                    <tfoot>
                        <tr class="report-total">
                            <td class="fw-semibold">Total · {{ $totals['slips'] }} {{ Str::plural('payslip', $totals['slips']) }}</td>
                            <td class="text-end fw-semibold">{{ $money($totals['gross']) }}</td>
                            <td class="text-end">{{ $dash($totals['pf']) }}</td>
                            <td class="text-end">{{ $dash($totals['esi']) }}</td>
                            <td class="text-end">{{ $dash($totals['tax']) }}</td>
                            <td class="text-end">{{ $dash($totals['loan']) }}</td>
                            <td class="text-end">{{ $dash($totals['other']) }}</td>
                            <td class="text-end fw-semibold">{{ $dash($totals['deductions']) }}</td>
                            <td class="text-end fw-bold">{{ $money($totals['net']) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection

@push('styles')
    <link href="{{ asset('vendor/metrica/assets/libs/apexcharts/apexcharts.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('vendor/metrica/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        (function () {
            var search = document.getElementById('register-search');
            if (search) {
                var rows = document.querySelectorAll('#register-table tbody tr[data-name]');
                search.addEventListener('input', function () {
                    var q = search.value.trim().toLowerCase();
                    rows.forEach(function (row) { row.hidden = q && row.dataset.name.indexOf(q) === -1; });
                });
            }

            var el = document.getElementById('payroll-report-trend');
            if (!el || typeof ApexCharts === 'undefined') return;
            var trend = @json($trend->values());
            var keys = @json($trend->keys());
            var current = @json($key);
            var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 0 }); };
            var compact = function (n) { return n >= 1e6 ? (n / 1e6).toFixed(1) + 'M' : (n >= 1e3 ? Math.round(n / 1e3) + 'k' : n); };

            new ApexCharts(el, {
                chart: {
                    type: 'bar', height: 260, stacked: true, toolbar: { show: false }, fontFamily: 'inherit',
                    events: {
                        // Clicking a month opens that month's report.
                        dataPointSelection: function (event, chart, config) {
                            var k = keys[config.dataPointIndex];
                            if (k && k !== current && trend[config.dataPointIndex].slips > 0) {
                                var url = new URL(window.location.href);
                                url.searchParams.set('period', k);
                                window.location.href = url.toString();
                            }
                        }
                    }
                },
                series: [
                    { name: 'Net pay', data: trend.map(function (t) { return t.net; }) },
                    { name: 'Deductions', data: trend.map(function (t) { return t.deductions; }) }
                ],
                colors: ['#0f9d70', '#f59e0b'],
                plotOptions: { bar: { columnWidth: '52%', borderRadius: 3 } },
                dataLabels: { enabled: false },
                xaxis: { categories: trend.map(function (t) { return t.label; }), axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { formatter: compact } },
                grid: { borderColor: 'rgba(120, 130, 150, 0.15)', strokeDashArray: 4 },
                legend: { position: 'top', horizontalAlign: 'right' },
                tooltip: { shared: true, intersect: false, y: { formatter: fmt } }
            }).render();
        })();
    </script>
@endpush
