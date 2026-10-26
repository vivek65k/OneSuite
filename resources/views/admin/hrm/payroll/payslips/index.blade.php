@extends('layouts.admin')

@section('title', 'Payslips')

@php
    use App\Models\Payslip;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pageUrl = fn (array $overrides = []) => route('admin.payslips.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All payslips', 'tone' => 'indigo', 'count' => $counts['all']],
        'generated' => ['label' => 'Waiting to be paid', 'tone' => 'amber', 'count' => $counts['generated']],
        'paid' => ['label' => 'Paid', 'tone' => 'success', 'count' => $counts['paid']],
    ];
    $filterValues = [
        'run' => $runs[$state['run']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['run' => 'Period', 'department_id' => 'Department', 'employee_id' => 'Employee', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $scopeLabel = $filterValues['run'] ?? 'all periods';
    $deductionParts = fn ($p) => collect([
        'PF' => $p->pf_employee_amount, 'ESI' => $p->esi_employee_amount, 'Tax' => $p->tax_amount,
        'Loan' => $p->loan_deduction_amount, 'Other' => $p->other_deduction_amount,
    ])->filter(fn ($v) => (float) $v > 0);
@endphp

@section('content')
    <x-page-title title="Payslips" />

    <div class="kpi-strip">
        <x-kpi-card label="Payslips" :value="$counts['all']" icon="ti-file-invoice" tone="indigo" :hint="$counts['all'] ? Str::ucfirst($scopeLabel) : 'Run payroll to generate payslips'" />
        <x-kpi-card label="Net pay" :value="$counts['all'] ? $money($kpis['net']) : '—'" icon="ti-cash" tone="success" :hint="$counts['all'] ? 'Average '.$money($kpis['net'] / $counts['all']).' a payslip' : null" />
        <x-kpi-card label="Waiting to be paid" :value="$counts['generated']" icon="ti-clock" :tone="$counts['generated'] ? 'amber' : 'success'"
                    :hint="$counts['generated'] ? $money($kpis['unpaid_net']).' net still to pay' : ($counts['all'] ? 'Every payslip is paid' : null)" :href="$pageUrl(['status' => 'generated'])" />
        <x-kpi-card label="Deductions" :value="$counts['all'] ? $money($kpis['deductions']) : '—'" icon="ti-trending-down" tone="rose"
                    :hint="$kpis['gross'] > 0 ? round($kpis['deductions'] / $kpis['gross'] * 100).'% of '.$money($kpis['gross']).' gross' : null" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.payslips.index') }}">
            @if ($state['status'] !== '')
                <input type="hidden" name="status" value="{{ $state['status'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Payslip status">
                    @foreach ($tabs as $value => $tab)
                        <a href="{{ $pageUrl(['status' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['status'] === $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or code" aria-label="Search payslips">
                    </div>
                    <select name="run" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Pay period">
                        <option value="">All periods</option>
                        @foreach ($runs as $id => $label)
                            <option value="{{ $id }}" @selected($state['run'] === $id)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($departments->count() > 1)
                        <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected($state['department_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Employee">
                        <option value="">All employees</option>
                        @foreach ($employees as $id => $name)
                            <option value="{{ $id }}" @selected($state['employee_id'] === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $pageUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                    @if ($activeFilters->count() > 1)
                        <a href="{{ route('admin.payslips.index', array_filter(['status' => $state['status']])) }}" class="font-12 ms-1">Clear all</a>
                    @endif
                </div>
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Period</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Deductions</th>
                        <th class="text-end">Net pay</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Open</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payslips as $payslip)
                        @php
                            $paid = $payslip->status === 'paid';
                            $parts = $deductionParts($payslip);
                        @endphp
                        <tr class="{{ $paid ? '' : 'is-pending-row' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$payslip->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ route('admin.payslips.show', $payslip) }}">{{ $payslip->employee?->name ?? 'Removed employee' }}</a></div>
                                        <div class="list-user-meta">{{ collect([$payslip->employee?->employee_code, $payslip->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                @if ($payslip->payrollRun)
                                    @can('viewAny', \App\Models\PayrollRun::class)
                                        <a href="{{ route('admin.payroll-runs.show', $payslip->payrollRun) }}" class="text-body" title="Open this payroll run">{{ $payslip->payrollRun->monthLabel() }}</a>
                                    @else
                                        {{ $payslip->payrollRun->monthLabel() }}
                                    @endcan
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $money($payslip->gross_earnings) }}</td>
                            <td class="text-end">
                                @if ($parts->isNotEmpty())
                                    <div>{{ $money($payslip->total_deductions) }}</div>
                                    <div class="payslip-parts">{{ $parts->map(fn ($v, $k) => $k.' '.$money($v))->implode(' · ') }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end"><span class="salary-figure">{{ $money($payslip->net_pay) }}</span></td>
                            <td>
                                <span class="tag tone-{{ $paid ? 'success' : 'amber' }}"><i class="ti {{ $paid ? 'ti-circle-check' : 'ti-clock' }}"></i>{{ $paid ? 'Paid' : 'Not paid yet' }}</span>
                                @if ($paid && $payslip->paid_at)
                                    <div class="payslip-parts">{{ $payslip->paid_at->format('M j, Y') }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.payslips.show', $payslip) }}" class="btn-icon btn-icon-accent" title="View payslip"><i class="ti ti-eye font-16"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-file-invoice font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty())
                                    <div class="fw-semibold text-body mb-1">No payslips match</div>
                                    <span class="font-13"><a href="{{ route('admin.payslips.index') }}">Show all payslips</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No payslips yet</div>
                                    <span class="font-13">Payslips are created when a month's payroll is run.
                                        @can('viewAny', \App\Models\PayrollRun::class)
                                            <a href="{{ route('admin.payroll-runs.index') }}">Go to Payroll Run</a>
                                        @endcan
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($totals['slips'] > 0)
                    <tfoot>
                        <tr class="report-total">
                            <td class="fw-semibold" colspan="2">Total · {{ $totals['slips'] }} {{ Str::plural('payslip', $totals['slips']) }}</td>
                            <td class="text-end fw-semibold">{{ $money($totals['gross']) }}</td>
                            <td class="text-end">{{ $money($totals['deductions']) }}</td>
                            <td class="text-end fw-bold">{{ $money($totals['net']) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @if ($payslips->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $payslips->links() }}</div></div>
        @endif
    </div>
@endsection
