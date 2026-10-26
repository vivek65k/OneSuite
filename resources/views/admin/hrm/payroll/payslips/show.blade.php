@extends('layouts.admin')

@section('title', 'Payslip — '.($payslip->employee?->name ?? 'Removed employee'))

@php
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $employee = $payslip->employee;
    $run = $payslip->payrollRun;
    $isPaid = $payslip->status === 'paid';
    $itemTotal = fn (string $type) => round((float) $payslip->items->where('type', $type)->sum('amount'), 2);
    // Lines are a snapshot taken when payroll ran; flag it if they no longer add up to the stored totals.
    $mismatch = abs($itemTotal('earning') - (float) $payslip->gross_earnings) > 0.01
        || abs($itemTotal('deduction') - (float) $payslip->total_deductions) > 0.01;
    $split = collect([
        ['label' => 'Net pay', 'tone' => 'success', 'amount' => (float) $payslip->net_pay],
        ['label' => 'PF', 'tone' => 'indigo', 'amount' => (float) $payslip->pf_employee_amount],
        ['label' => 'ESI', 'tone' => 'sky', 'amount' => (float) $payslip->esi_employee_amount],
        ['label' => 'Income tax', 'tone' => 'rose', 'amount' => (float) $payslip->tax_amount],
        ['label' => 'Loan', 'tone' => 'amber', 'amount' => (float) $payslip->loan_deduction_amount],
        ['label' => 'Other', 'tone' => 'slate', 'amount' => (float) $payslip->other_deduction_amount],
    ])->filter(fn ($s) => $s['amount'] > 0)->values();
    $gross = (float) $payslip->gross_earnings;
@endphp

@section('content')
    <div class="no-print">
        <x-page-title title="Payslip" :breadcrumbs="['Payslips' => route('admin.payslips.index')]" />

        <div class="record-header">
            <a href="{{ route('admin.payslips.index') }}" class="btn-icon record-back" title="Back to payslips"><i class="ti ti-arrow-left font-18"></i></a>
            <x-avatar :person="$employee" class="record-avatar" />
            <div class="record-header-text">
                <div class="record-header-title">{{ $employee?->name ?? 'Removed employee' }}</div>
                <div class="record-header-meta">
                    <span class="status-dot status-dot-{{ $isPaid ? 'success' : 'warning' }}">{{ $isPaid ? 'Paid'.($payslip->paid_at ? ' on '.$payslip->paid_at->format('M j, Y') : '') : 'Not paid yet' }}</span>
                    <span>{{ $run?->monthLabel() ?? 'Period unavailable' }}</span>
                    @if ($employee)
                        <span>{{ collect([$employee->employee_code, $employee->designation])->filter()->implode(' · ') }}</span>
                    @endif
                </div>
            </div>
            <div class="record-header-actions">
                @if ($older)
                    <a href="{{ route('admin.payslips.show', $older) }}" class="btn btn-light btn-sm px-2" title="{{ $older->payrollRun->monthLabel() }}"><i class="ti ti-chevron-left font-14"></i></a>
                @endif
                @if ($newer)
                    <a href="{{ route('admin.payslips.show', $newer) }}" class="btn btn-light btn-sm px-2" title="{{ $newer->payrollRun->monthLabel() }}"><i class="ti ti-chevron-right font-14"></i></a>
                @endif
                @if ($run)
                    @can('viewAny', \App\Models\PayrollRun::class)
                        <a href="{{ route('admin.payroll-runs.show', $run) }}" class="btn btn-light btn-sm px-3"><i class="ti ti-report-money font-14 me-1"></i> Payroll run</a>
                    @endcan
                @endif
                <button type="button" class="btn btn-de-primary btn-sm text-light px-3" onclick="window.print()"><i class="ti ti-printer font-14 me-1"></i> Print / save PDF</button>
            </div>
        </div>

        @if ($mismatch)
            <div class="alert alert-warning d-flex align-items-start gap-2">
                <i class="ti ti-alert-triangle font-18"></i>
                <div><strong>The lines on this payslip don't add up to its totals.</strong> Lines come to {{ number_format($itemTotal('earning'), 2) }} earned and {{ number_format($itemTotal('deduction'), 2) }} deducted. Check it before sharing.</div>
            </div>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            @include('admin.hrm.payroll.payslips._document', ['payslip' => $payslip, 'company' => $company, 'showEmployer' => true])
        </div>

        <div class="col-xl-4 no-print">
            @if ($split->isNotEmpty() && $gross > 0)
                <div class="card side-list tone-success mb-3">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-chart-donut"></i></span>
                        <span class="fw-semibold">Where the gross pay went</span>
                    </div>
                    <div class="card-body">
                        <div class="stack-bar stack-bar-lg">
                            @foreach ($split as $s)
                                <span class="tone-{{ $s['tone'] }}" style="flex: {{ $s['amount'] }}" title="{{ $s['label'] }}: {{ $money($s['amount']) }}"></span>
                            @endforeach
                        </div>
                        <div class="run-lines">
                            @foreach ($split as $s)
                                <div><span><i class="legend-dot tone-{{ $s['tone'] }}"></i> {{ $s['label'] }}</span><span><strong>{{ $money($s['amount']) }}</strong> <em>· {{ round($s['amount'] / $gross * 100) }}%</em></span></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if ($run && $ytd['months'] > 0)
                <div class="card side-list tone-indigo mb-3">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-calendar-stats"></i></span>
                        <span class="fw-semibold">{{ $run->year }} so far</span>
                        <span class="side-list-count">{{ $ytd['months'] }} {{ Str::plural('payslip', $ytd['months']) }}</span>
                    </div>
                    <div class="card-body">
                        <div class="run-lines mt-0">
                            <div><span>Gross earned</span><strong>{{ $money($ytd['gross']) }}</strong></div>
                            <div><span>Deducted</span><strong>{{ $money($ytd['deductions']) }}</strong></div>
                            <div><span>Net paid out</span><strong>{{ $money($ytd['net']) }}</strong></div>
                        </div>
                        <div class="font-12 text-muted mt-2">January to {{ \App\Models\PayrollRun::MONTHS[$run->month] ?? $run->month }} {{ $run->year }}, including this payslip.</div>
                    </div>
                </div>
            @endif

            <div class="card side-list tone-teal mb-0">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-history"></i></span>
                    <span class="fw-semibold">Payslip history</span>
                    <span class="side-list-count">{{ $history->count() }}</span>
                </div>
                @foreach ($history->take(8) as $slip)
                    <a href="{{ route('admin.payslips.show', $slip) }}" class="side-list-item {{ $slip->id === $payslip->id ? 'is-current' : '' }}">
                        <span class="list-avatar list-avatar-square tone-{{ $slip->status === 'paid' ? 'success' : 'amber' }}" aria-hidden="true"><i class="ti {{ $slip->status === 'paid' ? 'ti-circle-check' : 'ti-clock' }}"></i></span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $slip->payrollRun->monthLabel() }}</div>
                            <div class="font-12 text-muted">{{ $slip->status === 'paid' ? 'Paid' : 'Not paid yet' }}{{ $slip->id === $payslip->id ? ' · viewing' : '' }}</div>
                        </div>
                        <span class="salary-figure">{{ $money($slip->net_pay) }}</span>
                    </a>
                @endforeach
                @if ($history->count() > 8 && $employee)
                    <a href="{{ route('admin.payslips.index', ['employee_id' => $employee->id]) }}" class="side-list-foot">See all {{ $history->count() }} payslips</a>
                @endif
            </div>
        </div>
    </div>
@endsection
