@extends('layouts.admin')

@section('title', 'Payroll Run — '.$run->monthLabel())

@php
    use App\Models\Payslip;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $isPaid = $run->status === 'paid';
    $gross = (float) $run->total_gross;
    $segments = collect([
        ['label' => 'Net pay', 'tone' => 'success', 'amount' => (float) $run->total_net],
        ['label' => 'PF', 'tone' => 'indigo', 'amount' => $totals['pf']],
        ['label' => 'ESI', 'tone' => 'sky', 'amount' => $totals['esi']],
        ['label' => 'Income tax', 'tone' => 'rose', 'amount' => $totals['tax']],
        ['label' => 'Loan recovery', 'tone' => 'amber', 'amount' => $totals['loan']],
        ['label' => 'Other', 'tone' => 'slate', 'amount' => $totals['other']],
    ])->filter(fn ($s) => $s['amount'] > 0)->values();
    $dash = fn ($n) => (float) $n > 0 ? $money($n) : '—';
@endphp

@section('content')
    <x-page-title title="Payroll Run" :breadcrumbs="['Payroll Run' => route('admin.payroll-runs.index')]" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif

    <div class="record-header">
        <a href="{{ route('admin.payroll-runs.index') }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $isPaid ? 'success' : 'amber' }}" aria-hidden="true"><i class="ti ti-report-money font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $run->monthLabel() }}</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-{{ $isPaid ? 'success' : 'warning' }}">{{ $isPaid ? 'Paid' : 'Processed, not paid yet' }}</span>
                <span>Run on {{ $run->run_date?->format('M j, Y') }}{{ $run->processedBy ? ' by '.$run->processedBy->name : '' }}</span>
                <span>{{ $totals['paid'] }}/{{ $payslips->count() }} payslips paid</span>
            </div>
        </div>
        <div class="record-header-actions">
            <a href="{{ route('admin.payslips.index') }}" class="btn btn-light btn-sm px-3"><i class="ti ti-file-invoice font-14 me-1"></i> All payslips</a>
            @can('update', $run)
                @unless ($isPaid)
                    <form action="{{ route('admin.payroll-runs.mark-paid', $run) }}" method="POST" onsubmit="return confirm('Mark {{ $run->monthLabel() }} as paid? All {{ $payslips->count() }} payslips will be marked paid.');">
                        @csrf
                        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-cash font-14 me-1"></i> Mark run as paid</button>
                    </form>
                @endunless
            @endcan
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Payslips" :value="$payslips->count()" icon="ti-users" tone="indigo" :hint="$missed->isNotEmpty() ? $missed->count().' on payroll not included' : 'Everyone on payroll included'" />
        <x-kpi-card label="Gross" :value="$money($gross)" icon="ti-trending-up" tone="teal" :hint="$payslips->count() ? 'Average '.$money($gross / $payslips->count()) : null" />
        <x-kpi-card label="Deductions" :value="$money($run->total_deductions)" icon="ti-trending-down" tone="rose" :hint="$gross > 0 ? round((float) $run->total_deductions / $gross * 100).'% of gross' : null" />
        <x-kpi-card label="Net pay" :value="$money($run->total_net)" icon="ti-cash" tone="success" :hint="'Employer adds '.$money($totals['employer']).' PF/ESI'" />
    </div>

    @if ($segments->isNotEmpty())
        <div class="card side-list tone-success mb-3">
            <div class="side-list-head">
                <span class="side-list-icon"><i class="ti ti-chart-donut"></i></span>
                <span class="fw-semibold">Where the gross pay went</span>
            </div>
            <div class="card-body">
                <div class="stack-bar stack-bar-lg">
                    @foreach ($segments as $s)
                        <span class="tone-{{ $s['tone'] }}" style="flex: {{ $s['amount'] }}" title="{{ $s['label'] }}: {{ $money($s['amount']) }}"></span>
                    @endforeach
                </div>
                <div class="stack-legend">
                    @foreach ($segments as $s)
                        <span><i class="legend-dot tone-{{ $s['tone'] }}"></i>{{ $s['label'] }} <strong>{{ $money($s['amount']) }}</strong>@if ($gross > 0) <span class="text-muted">· {{ round($s['amount'] / $gross * 100) }}%</span>@endif</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if ($missed->isNotEmpty())
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="ti ti-alert-triangle font-18"></i>
            <div>
                <strong>{{ $missed->count() }} {{ Str::plural('person', $missed->count()) }} on payroll {{ $missed->count() === 1 ? 'has' : 'have' }} no payslip in this run:</strong>
                {{ $missed->pluck('name')->implode(', ') }}.
                <div class="font-13">They had no salary structure when it ran. A month can only be run once, so pay them separately or include the arrears next month.</div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-file-invoice"></i> Payslips <span class="list-tab-count">{{ $payslips->count() }}</span></div>
            <div class="list-toolbar-actions">
                <div class="list-search">
                    <i class="ti ti-search"></i>
                    <input type="search" id="payslip-search" class="form-control form-control-sm" placeholder="Search employee" aria-label="Search payslips">
                </div>
            </div>
        </div>
        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0" id="payslip-table">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">PF</th>
                        <th class="text-end">ESI</th>
                        <th class="text-end">Tax</th>
                        <th class="text-end">Loan</th>
                        <th class="text-end">Other</th>
                        <th class="text-end">Net pay</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Open</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payslips as $payslip)
                        <tr data-name="{{ mb_strtolower(($payslip->employee?->name ?? '').' '.($payslip->employee?->employee_code ?? '')) }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$payslip->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ route('admin.payslips.show', $payslip) }}">{{ $payslip->employee?->name ?? 'Removed employee' }}</a></div>
                                        <div class="list-user-meta">{{ $payslip->employee?->department?->name ?? $payslip->employee?->employee_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">{{ $money($payslip->gross_earnings) }}</td>
                            <td class="text-end text-muted">{{ $dash($payslip->pf_employee_amount) }}</td>
                            <td class="text-end text-muted">{{ $dash($payslip->esi_employee_amount) }}</td>
                            <td class="text-end text-muted">{{ $dash($payslip->tax_amount) }}</td>
                            <td class="text-end text-muted">{{ $dash($payslip->loan_deduction_amount) }}</td>
                            <td class="text-end text-muted">{{ $dash($payslip->other_deduction_amount) }}</td>
                            <td class="text-end"><span class="salary-figure">{{ $money($payslip->net_pay) }}</span></td>
                            <td><span class="tag tone-{{ $payslip->status === 'paid' ? 'success' : 'amber' }}">{{ Payslip::STATUSES[$payslip->status] ?? $payslip->status }}</span></td>
                            <td class="text-end"><a href="{{ route('admin.payslips.show', $payslip) }}" class="btn-icon btn-icon-accent" title="View payslip"><i class="ti ti-eye font-16"></i></a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">No payslips were generated for this run.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($payslips->isNotEmpty())
                    <tfoot>
                        <tr class="report-total">
                            <td class="fw-semibold">Total</td>
                            <td class="text-end fw-semibold">{{ $money($gross) }}</td>
                            <td class="text-end">{{ $dash($totals['pf']) }}</td>
                            <td class="text-end">{{ $dash($totals['esi']) }}</td>
                            <td class="text-end">{{ $dash($totals['tax']) }}</td>
                            <td class="text-end">{{ $dash($totals['loan']) }}</td>
                            <td class="text-end">{{ $dash($totals['other']) }}</td>
                            <td class="text-end fw-bold">{{ $money($run->total_net) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var search = document.getElementById('payslip-search');
            if (!search) return;
            var rows = document.querySelectorAll('#payslip-table tbody tr[data-name]');
            search.addEventListener('input', function () {
                var q = search.value.trim().toLowerCase();
                rows.forEach(function (row) { row.hidden = q && row.dataset.name.indexOf(q) === -1; });
            });
        })();
    </script>
@endpush
