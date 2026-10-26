<?php

namespace App\Http\Controllers\Admin\Ess;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MyPayslipController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['paid', 'unpaid'];

    /** The parts of a payslip's deductions, in the order they are shown. */
    public const DEDUCTIONS = [
        'pf_employee_amount' => 'PF',
        'esi_employee_amount' => 'ESI',
        'tax_amount' => 'Tax',
        'loan_deduction_amount' => 'Loan',
        'other_deduction_amount' => 'Other',
    ];

    public function index(Request $request): View
    {
        $employee = $this->currentEmployee();
        [$state, $year, $years, $all] = $this->resolve($request, $employee);

        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'paid' => $rows->where('status', 'paid'),
            'unpaid' => $rows->where('status', '!=', 'paid'),
            default => $rows,
        };
        // The newest payslip of all, whichever year is on screen, and the one before it to compare with.
        $recent = $this->mine($employee)->with('payrollRun')->get()->sortByDesc(fn (Payslip $p) => $this->period($p))->values();

        return view('admin.ess.payslips', [
            'state' => $state,
            'year' => $year,
            'years' => $years,
            'payslips' => $tabbed($all, $state['tab'])->values(),
            // What each payslip's net pay was the month before, to show the change.
            'previous' => $recent->mapWithKeys(fn (Payslip $p, int $i) => [$p->id => $recent->get($i + 1)?->net_pay]),
            'counts' => ['all' => $all->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($all, $tab)->count()])->all(),
            'latest' => $recent->first(),
            'beforeLatest' => $recent->get(1),
            'totals' => [
                'gross' => (float) $all->sum('gross_earnings'),
                'deductions' => (float) $all->sum('total_deductions'),
                'net' => (float) $all->sum('net_pay'),
                'paid' => (float) $all->where('status', 'paid')->sum('net_pay'),
                'parts' => collect(self::DEDUCTIONS)->map(fn ($label, $column) => (float) $all->sum($column))->filter()->all(),
            ],
            'maxNet' => (float) $all->max('net_pay'),
        ]);
    }

    public function show(int $payslip): View
    {
        $employee = $this->currentEmployee();
        // 404 (not 403) for someone else's payslip, so the id doesn't confirm it exists.
        $record = $this->mine($employee)->whereKey($payslip)->with(['payrollRun', 'items', 'employee.department:id,name'])->firstOrFail();

        $ordered = $this->mine($employee)->with('payrollRun:id,month,year')->get(['id', 'payroll_run_id'])->sortBy(fn (Payslip $p) => $this->period($p))->values();
        $position = $ordered->search(fn (Payslip $p) => $p->id === $record->id);

        return view('admin.ess.payslip-show', [
            'payslip' => $record,
            'older' => $position > 0 ? $ordered->get($position - 1) : null,
            'newer' => $ordered->get($position + 1),
        ]);
    }

    /** The year's payslips as a spreadsheet, for tax filing or a loan application. */
    public function export(Request $request): StreamedResponse
    {
        $employee = $this->currentEmployee();
        [, $year, , $all] = $this->resolve($request, $employee);

        // Stops a spreadsheet from running a cell as a formula.
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;

        return response()->streamDownload(function () use ($all, $safe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['Pay period', 'Gross earnings'], array_values(self::DEDUCTIONS), ['Total deductions', 'Net pay', 'Status', 'Paid on']));
            foreach ($all->sortBy(fn (Payslip $p) => $this->period($p)) as $p) {
                fputcsv($out, array_map($safe, array_merge(
                    [$p->payrollRun?->monthLabel() ?? 'Payslip '.$p->id, $p->gross_earnings],
                    collect(self::DEDUCTIONS)->keys()->map(fn ($column) => $p->{$column})->all(),
                    [$p->total_deductions, $p->net_pay, $p->status === 'paid' ? 'Paid' : 'Not paid yet', $p->paid_at?->format('Y-m-d') ?? '']
                )));
            }
            fputcsv($out, array_merge(
                ['Total', number_format((float) $all->sum('gross_earnings'), 2, '.', '')],
                collect(self::DEDUCTIONS)->keys()->map(fn ($column) => number_format((float) $all->sum($column), 2, '.', ''))->all(),
                [number_format((float) $all->sum('total_deductions'), 2, '.', ''), number_format((float) $all->sum('net_pay'), 2, '.', ''), '', '']
            ));
            fclose($out);
        }, 'my-payslips-'.$year.'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * The filters from the URL and the payslips of the chosen year, newest first.
     *
     * @return array{0: array<string, mixed>, 1: int, 2: Collection, 3: Collection}
     */
    protected function resolve(Request $request, Employee $employee): array
    {
        // Anything that isn't a plain value (e.g. ?year[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $years = PayrollRun::query()->whereIn('id', $this->mine($employee)->select('payroll_run_id'))->distinct()->pluck('year')
            ->map(fn ($y) => (int) $y)->sortDesc()->values();
        $default = $years->first() ?? now()->year;
        $year = ctype_digit($text('year')) && $years->contains((int) $text('year')) ? (int) $text('year') : $default;
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'year' => $year === $default ? null : $year,
        ];
        $all = $this->mine($employee)->whereHas('payrollRun', fn ($q) => $q->where('year', $year))->with('payrollRun')->get()
            ->sortByDesc(fn (Payslip $p) => $this->period($p))->values();

        return [$state, $year, $years, $all];
    }

    /** This employee's own payslips, whatever their role may browse elsewhere. */
    protected function mine(Employee $employee)
    {
        return Payslip::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id);
    }

    /** Sortable pay period: payslips are ordered by the month they pay for, not when the run happened. */
    protected function period(Payslip $payslip): int
    {
        return ((int) $payslip->payrollRun?->year) * 100 + (int) $payslip->payrollRun?->month;
    }
}
