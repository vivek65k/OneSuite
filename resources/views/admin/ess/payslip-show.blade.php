@extends('layouts.admin')

@section('title', 'My Payslip')

@section('content')
    <div class="no-print">
        <x-page-title title="Payslip" :breadcrumbs="['My Payslips' => route('admin.my-payslips.index')]" />

        <div class="record-header">
            <a href="{{ route('admin.my-payslips.index', array_filter(['year' => $payslip->payrollRun?->year])) }}" class="btn-icon record-back" title="Back to my payslips"><i class="ti ti-arrow-left font-18"></i></a>
            <span class="list-avatar list-avatar-square record-avatar tone-{{ $payslip->status === 'paid' ? 'success' : 'amber' }}" aria-hidden="true"><i class="ti ti-file-invoice font-20"></i></span>
            <div class="record-header-text">
                <div class="record-header-title">{{ $payslip->payrollRun?->monthLabel() ?? 'Payslip' }}</div>
                <div class="record-header-meta">
                    <span class="status-dot status-dot-{{ $payslip->status === 'paid' ? 'success' : 'warning' }}">{{ $payslip->status === 'paid' ? 'Paid'.($payslip->paid_at ? ' on '.$payslip->paid_at->format('M j, Y') : '') : 'Not paid yet' }}</span>
                    <span>Net pay {{ number_format((float) $payslip->net_pay, 2) }}</span>
                </div>
            </div>
            <div class="record-header-actions">
                @if ($older)
                    <a href="{{ route('admin.my-payslips.show', $older) }}" class="btn-icon" title="{{ $older->payrollRun?->monthLabel() ?? 'Earlier payslip' }}"><i class="ti ti-chevron-left font-18"></i></a>
                @else
                    <span class="btn-icon disabled opacity-50" title="This is your first payslip" aria-disabled="true"><i class="ti ti-chevron-left font-18"></i></span>
                @endif
                @if ($newer)
                    <a href="{{ route('admin.my-payslips.show', $newer) }}" class="btn-icon" title="{{ $newer->payrollRun?->monthLabel() ?? 'Later payslip' }}"><i class="ti ti-chevron-right font-18"></i></a>
                @else
                    <span class="btn-icon disabled opacity-50" title="This is your latest payslip" aria-disabled="true"><i class="ti ti-chevron-right font-18"></i></span>
                @endif
                <button type="button" class="btn btn-de-primary btn-sm text-light px-3" onclick="window.print()"><i class="ti ti-printer font-14 me-1"></i> Print / save PDF</button>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-9">
            @include('admin.hrm.payroll.payslips._document', ['payslip' => $payslip, 'showEmployer' => false])
        </div>
    </div>
@endsection

@if (request()->boolean('print'))
    @push('scripts')
        <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
    @endpush
@endif
