@extends('layouts.admin')

@section('title', 'My Payslips')

@php
    use App\Http\Controllers\Admin\Ess\MyPayslipController as Payslips;
    use App\Models\PayrollRun;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pageUrl = fn (array $overrides = []) => route('admin.my-payslips.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All of '.$year, 'tone' => 'indigo', 'count' => $counts['all']],
        'paid' => ['label' => 'Paid', 'tone' => 'success', 'count' => $counts['paid']],
        'unpaid' => ['label' => 'Not paid yet', 'tone' => 'amber', 'count' => $counts['unpaid']],
    ];
    // Change in take-home against the payslip before, as "+1,200 (4%)".
    $change = function ($now, $before) use ($money) {
        if ($before === null || (float) $before <= 0) {
            return null;
        }
        $diff = (float) $now - (float) $before;

        return abs($diff) < 0.5 ? ['tone' => 'muted', 'icon' => 'ti-minus', 'text' => 'same as the previous payslip']
            : ['tone' => $diff > 0 ? 'success' : 'danger', 'icon' => $diff > 0 ? 'ti-trending-up' : 'ti-trending-down', 'text' => ($diff > 0 ? '+' : '−').$money(abs($diff)).' ('.round(abs($diff) / (float) $before * 100).'%) on the previous payslip'];
    };
    $latestChange = $latest ? $change($latest->net_pay, $beforeLatest?->net_pay) : null;
    $latestPaid = $latest?->status === 'paid';
    $partsHint = collect($totals['parts'])->map(fn ($amount, $column) => Payslips::DEDUCTIONS[$column].' '.$money($amount))->implode(' · ');
    $takeHome = $totals['gross'] > 0 ? round($totals['net'] / $totals['gross'] * 100) : null;
@endphp

@section('content')
    <x-page-title title="My Payslips" />

    @if ($latest)
        <div class="kpi-strip">
            <x-kpi-card :label="'Latest · '.($latest->payrollRun?->monthLabel() ?? 'payslip')" :value="$money($latest->net_pay)" icon="ti-cash" :tone="$latestPaid ? 'success' : 'amber'"
                        :hint="($latestPaid ? 'Paid'.($latest->paid_at ? ' on '.$latest->paid_at->format('M j') : '') : 'Not paid yet').($latestChange && $latestChange['tone'] !== 'muted' ? ' · '.Str::before($latestChange['text'], ' on the') : '')" :href="route('admin.my-payslips.show', $latest)" />
            <x-kpi-card label="Earned in {{ $year }}" :value="$money($totals['gross'])" icon="ti-trending-up" tone="teal" :hint="'Gross, across '.$counts['all'].' '.Str::plural('payslip', $counts['all'])" />
            <x-kpi-card label="Deducted in {{ $year }}" :value="$money($totals['deductions'])" icon="ti-trending-down" tone="rose" :hint="$partsHint ?: 'Nothing deducted'" />
            <x-kpi-card label="Take-home in {{ $year }}" :value="$money($totals['net'])" icon="ti-wallet" tone="indigo" :hint="($takeHome !== null ? $takeHome.'% of gross' : 'No pay yet').($totals['net'] - $totals['paid'] > 0.5 ? ' · '.$money($totals['net'] - $totals['paid']).' not paid yet' : '')" />
        </div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <nav class="list-tabs" aria-label="Payment status">
                @foreach ($tabs as $value => $tab)
                    @if ($value === '' || $tab['count'] || $state['tab'] === $value)
                        <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
            <div class="list-toolbar-actions">
                @if ($years->count() > 1)
                    <form method="GET" action="{{ route('admin.my-payslips.index') }}">
                        <select name="year" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Year">
                            @foreach ($years as $option)
                                <option value="{{ $option }}" @selected($option === $year)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                @if ($counts['all'])
                    <a href="{{ route('admin.my-payslips.export', ['year' => $year]) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> {{ $year }} summary</a>
                @endif
            </div>
        </div>
        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Pay period</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Deductions</th>
                        <th style="min-width: 220px">Net pay</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Open</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payslips as $payslip)
                        @php
                            $paid = $payslip->status === 'paid';
                            $parts = collect(Payslips::DEDUCTIONS)->filter(fn ($label, $column) => (float) $payslip->{$column} > 0)->map(fn ($label, $column) => $label.' '.$money($payslip->{$column}))->implode(' · ');
                            $delta = $change($payslip->net_pay, $previous[$payslip->id] ?? null);
                        @endphp
                        <tr>
                            <td>
                                <div class="date-range-cell">
                                    <span class="when-block tone-{{ $paid ? 'success' : 'amber' }}"><span class="when-block-month">{{ $payslip->payrollRun?->year }}</span><span class="when-block-day">{{ Str::limit(PayrollRun::MONTHS[$payslip->payrollRun?->month] ?? '—', 3, '') }}</span></span>
                                    <div>
                                        <a href="{{ route('admin.my-payslips.show', $payslip) }}" class="fw-semibold">{{ $payslip->payrollRun?->monthLabel() ?? 'Payslip' }}</a>
                                        <div class="payslip-parts">PS-{{ str_pad((string) $payslip->id, 6, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">{{ $money($payslip->gross_earnings) }}</td>
                            <td class="text-end">
                                <span class="text-muted">{{ $money($payslip->total_deductions) }}</span>
                                @if ($parts)
                                    <div class="payslip-parts">{{ $parts }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="mini-progress tone-{{ $paid ? 'success' : 'amber' }}">
                                    <div class="mini-progress-bar is-toned"><span style="width: {{ $maxNet > 0 ? round((float) $payslip->net_pay / $maxNet * 100) : 0 }}%"></span></div>
                                    <span class="mini-progress-label salary-figure">{{ $money($payslip->net_pay) }}</span>
                                </div>
                                @if ($delta)
                                    <div class="payslip-parts text-{{ $delta['tone'] }}"><i class="ti {{ $delta['icon'] }}"></i> {{ $delta['text'] }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $paid ? 'success' : 'amber' }}"><i class="ti {{ $paid ? 'ti-circle-check' : 'ti-clock' }}"></i>{{ $paid ? 'Paid' : 'Not paid yet' }}</span>
                                @if ($paid && $payslip->paid_at)
                                    <div class="payslip-parts">{{ $payslip->paid_at->format('M j, Y') }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    <a href="{{ route('admin.my-payslips.show', $payslip) }}" class="btn-icon btn-icon-accent" title="View payslip"><i class="ti ti-eye font-16"></i></a>
                                    <a href="{{ route('admin.my-payslips.show', ['payslip' => $payslip, 'print' => 1]) }}" class="btn-icon" title="Print or save as PDF"><i class="ti ti-printer font-16"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-file-invoice font-24 d-block mb-2"></i>
                                @if ($state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No payslips like this in {{ $year }}</div>
                                    <span class="font-13"><a href="{{ $pageUrl(['tab' => null]) }}">Show all of {{ $year }}</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No payslips yet</div>
                                    <span class="font-13">Your payslip appears here once payroll is run for the month.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($payslips->count() > 1)
                    <tfoot>
                        <tr class="report-total">
                            <td>{{ $state['tab'] === '' ? 'Total for '.$year : 'Total shown' }}</td>
                            <td class="text-end">{{ $money($payslips->sum('gross_earnings')) }}</td>
                            <td class="text-end">{{ $money($payslips->sum('total_deductions')) }}</td>
                            <td><span class="salary-figure">{{ $money($payslips->sum('net_pay')) }}</span></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
