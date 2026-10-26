{{-- The payslip itself: shared by HR's view and the employee's own "My Payslips". Prints on its own. --}}
@php
    use App\Models\Payslip;

    $company = $company ?? \App\Models\Company::current();
    $showEmployer = $showEmployer ?? false;
    $employee = $payslip->employee;
    $run = $payslip->payrollRun;
    $currency = $company->currency ?: '';
    $amount = fn ($n) => number_format((float) $n, 2);
    $earnings = $payslip->items->where('type', 'earning')->values();
    $deductions = $payslip->items->where('type', 'deduction')->values();
    $isPaid = $payslip->status === 'paid';
    $gross = (float) $payslip->gross_earnings;
    $takeHome = $gross > 0 ? max(0, min(100, round((float) $payslip->net_pay / $gross * 100))) : 0;
    $words = $payslip->netPayInWords($currency);
    $companyAddress = collect([$company->address, $company->city, $company->state, $company->postal_code])->filter()->implode(', ');
    $details = [
        'Employee code' => $employee?->employee_code,
        'Designation' => $employee?->designation,
        'Department' => $employee?->department?->name,
        'Date of joining' => $employee?->joining_date?->format('M j, Y'),
        'Pay period' => $run?->monthLabel(),
        'Pay date' => $payslip->paid_at?->format('M j, Y') ?? 'Not paid yet',
    ];
@endphp

<div class="card payslip-doc">
    <div class="payslip-head">
        <div>
            <div class="payslip-company">{{ $company->legal_name ?: $company->name }}</div>
            @if ($companyAddress)
                <div class="payslip-company-meta">{{ $companyAddress }}</div>
            @endif
            @if ($company->tax_id)
                <div class="payslip-company-meta">Tax ID {{ $company->tax_id }}</div>
            @endif
        </div>
        <div class="payslip-head-side">
            <div class="payslip-title">Payslip</div>
            <div class="payslip-period">{{ $run?->monthLabel() ?? 'Period unavailable' }}</div>
            <span class="tag tone-{{ $isPaid ? 'success' : 'amber' }}"><i class="ti {{ $isPaid ? 'ti-circle-check' : 'ti-clock' }}"></i>{{ $isPaid ? 'Paid' : 'Not paid yet' }}</span>
        </div>
    </div>

    <div class="payslip-person">
        <x-avatar :person="$employee" class="no-print" />
        <div>
            <div class="payslip-person-name">{{ $employee?->name ?? 'Removed employee' }}</div>
            <div class="payslip-company-meta">Payslip no. PS-{{ str_pad((string) $payslip->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>
    </div>

    <dl class="payslip-meta">
        @foreach ($details as $label => $value)
            <div>
                <dt>{{ $label }}</dt>
                <dd>{{ filled($value) ? $value : '—' }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="payslip-cols">
        <div class="payslip-block tone-success">
            <div class="payslip-block-head"><i class="ti ti-trending-up"></i> Earnings <span>{{ $currency }}</span></div>
            @forelse ($earnings as $item)
                <div class="payslip-line"><span>{{ $item->component_name }}</span><strong>{{ $amount($item->amount) }}</strong></div>
            @empty
                <div class="payslip-line is-empty"><span>No earning lines recorded</span></div>
            @endforelse
            <div class="payslip-line is-total"><span>Gross earnings</span><strong>{{ $amount($payslip->gross_earnings) }}</strong></div>
        </div>
        <div class="payslip-block tone-rose">
            <div class="payslip-block-head"><i class="ti ti-trending-down"></i> Deductions <span>{{ $currency }}</span></div>
            @forelse ($deductions as $item)
                <div class="payslip-line"><span>{{ $item->component_name }}</span><strong>{{ $amount($item->amount) }}</strong></div>
            @empty
                <div class="payslip-line is-empty"><span>No deductions this month</span></div>
            @endforelse
            <div class="payslip-line is-total"><span>Total deductions</span><strong>{{ $amount($payslip->total_deductions) }}</strong></div>
        </div>
    </div>

    <div class="payslip-net tone-success">
        <div>
            <div class="payslip-net-label">Net pay</div>
            @if ($words)
                <div class="payslip-net-words">{{ $words }}</div>
            @endif
            <div class="payslip-net-formula">{{ $amount($payslip->gross_earnings) }} earned − {{ $amount($payslip->total_deductions) }} deducted · {{ $takeHome }}% take-home</div>
        </div>
        <div class="payslip-net-amount"><small>{{ $currency }}</small> {{ $amount($payslip->net_pay) }}</div>
    </div>

    @if ($showEmployer && ((float) $payslip->pf_employer_amount > 0 || (float) $payslip->esi_employer_amount > 0))
        <div class="payslip-employer">
            <i class="ti ti-building"></i>
            <div>
                <strong>Paid by the company on top of this salary</strong>
                <span>Employer PF {{ $amount($payslip->pf_employer_amount) }} · Employer ESI {{ $amount($payslip->esi_employer_amount) }}. Not deducted from net pay.</span>
            </div>
        </div>
    @endif

    <div class="payslip-foot">
        This is a computer-generated payslip and does not need a signature.
        @if ($run?->run_date)
            Generated on {{ $run->run_date->format('M j, Y') }}.
        @endif
    </div>
</div>
