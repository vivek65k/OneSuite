<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollReportController extends Controller
{
    use ExportsCsv;

    /** Money columns summed for every total on the page and in the export. */
    protected const SUMS = [
        'gross' => 'gross_earnings', 'pf' => 'pf_employee_amount', 'esi' => 'esi_employee_amount', 'tax' => 'tax_amount',
        'loan' => 'loan_deduction_amount', 'other' => 'other_deduction_amount', 'deductions' => 'total_deductions', 'net' => 'net_pay',
        'pf_employer' => 'pf_employer_amount', 'esi_employer' => 'esi_employer_amount',
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payslip::class);

        $runs = $this->runs();
        $period = $this->period($request, $runs);
        $departmentId = ctype_digit((string) (is_scalar($request->query('department_id')) ? $request->query('department_id') : '')) ? (int) $request->query('department_id') : null;

        $all = $this->payslips($period)->get();
        $payslips = $departmentId ? $all->filter(fn (Payslip $p) => (int) $p->employee?->department_id === $departmentId)->values() : $all;
        $totals = $this->totals($payslips);
        $trend = $this->trend($period, $departmentId);
        $previous = $trend->get($period->copy()->subMonth()->format('Y-m'));

        return view('admin.hrm.payroll.reports', [
            'period' => $period,
            'departmentId' => $departmentId,
            'payslips' => $payslips,
            'totals' => $totals,
            'previous' => $previous && $previous['slips'] > 0 ? $previous : null,
            'trend' => $trend,
            'run' => $runs->first(fn (PayrollRun $r) => (int) $r->year === $period->year && (int) $r->month === $period->month),
            'periods' => $runs->map(fn (PayrollRun $r) => sprintf('%04d-%02d', $r->year, $r->month))->unique()->values(),
            'departments' => $all->map(fn (Payslip $p) => $p->employee?->department)->filter()->unique('id')->sortBy('name')->pluck('name', 'id'),
            'byDepartment' => $all->groupBy(fn (Payslip $p) => $p->employee?->department?->name ?? 'No department')
                ->map(fn (Collection $group, string $name) => ['name' => $name, 'id' => $group->first()->employee?->department_id] + $this->totals($group))
                ->sortByDesc('gross')->values(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Payslip::class);

        $period = $this->period($request, $this->runs());
        $departmentId = ctype_digit((string) (is_scalar($request->query('department_id')) ? $request->query('department_id') : '')) ? (int) $request->query('department_id') : null;
        $payslips = $this->payslips($period)->get()
            ->when($departmentId, fn (Collection $c) => $c->filter(fn (Payslip $p) => (int) $p->employee?->department_id === $departmentId)->values());
        $totals = $this->totals($payslips);
        // A cell starting with = + - @ would run as a formula when the file is opened in a spreadsheet.
        $safe = fn (?string $text) => preg_match('/^[=+\-@\t\r]/', (string) $text) ? "'".$text : (string) $text;
        $num = fn ($n) => number_format((float) $n, 2, '.', '');

        return $this->streamCsv(
            'payroll-report-'.$period->format('Y-m').'.csv',
            ['Employee Code', 'Employee', 'Department', 'Gross Earnings', 'PF (Employee)', 'ESI (Employee)', 'Tax', 'Loan EMI', 'Other Deductions', 'Total Deductions', 'Net Pay', 'PF (Employer)', 'ESI (Employer)', 'Status', 'Paid On'],
            (function () use ($payslips, $totals, $safe, $num) {
                foreach ($payslips as $p) {
                    yield [
                        $safe($p->employee?->employee_code), $safe($p->employee?->name ?? 'Removed employee'), $safe($p->employee?->department?->name),
                        $num($p->gross_earnings), $num($p->pf_employee_amount), $num($p->esi_employee_amount), $num($p->tax_amount), $num($p->loan_deduction_amount),
                        $num($p->other_deduction_amount), $num($p->total_deductions), $num($p->net_pay), $num($p->pf_employer_amount), $num($p->esi_employer_amount),
                        Payslip::STATUSES[$p->status] ?? $p->status, $p->paid_at?->format('Y-m-d') ?? '',
                    ];
                }

                if ($payslips->isNotEmpty()) {
                    yield [
                        '', 'Total ('.$payslips->count().')', '', $num($totals['gross']), $num($totals['pf']), $num($totals['esi']), $num($totals['tax']), $num($totals['loan']),
                        $num($totals['other']), $num($totals['deductions']), $num($totals['net']), $num($totals['pf_employer']), $num($totals['esi_employer']), '', '',
                    ];
                }
            })()
        );
    }

    /** The month being reported: ?period=YYYY-MM, the older ?month=&year=, else the latest month payroll was run. */
    protected function period(Request $request, Collection $runs): Carbon
    {
        $q = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';

        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $q('period'), $m) && (int) $m[1] >= 2000 && (int) $m[1] <= 2100) {
            return Carbon::create((int) $m[1], (int) $m[2], 1);
        }

        if (ctype_digit($q('month')) && ctype_digit($q('year')) && (int) $q('month') >= 1 && (int) $q('month') <= 12 && (int) $q('year') >= 2000 && (int) $q('year') <= 2100) {
            return Carbon::create((int) $q('year'), (int) $q('month'), 1);
        }

        $latest = $runs->first();

        return $latest ? Carbon::create((int) $latest->year, (int) $latest->month, 1) : today()->startOfMonth();
    }

    /** Payroll runs this login may see, newest first. */
    protected function runs(): Collection
    {
        return PayrollRun::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')))
            ->orderByDesc('year')->orderByDesc('month')
            ->get();
    }

    /**
     * Payslips this login may see: its own company, and when the login is tied
     * to a branch, only that branch's payroll runs (or, for a company-wide run,
     * only that branch's employees). Same rule as the Payslips page.
     */
    protected function scoped(): Builder
    {
        $branchId = BranchContext::currentBranchId();

        return Payslip::query()
            ->where('payslips.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('payrollRun', fn ($r) => $r->where('branch_id', $branchId))
                ->orWhere(fn ($o) => $o
                    ->whereHas('payrollRun', fn ($r) => $r->whereNull('branch_id'))
                    ->whereHas('employee', fn ($e) => $e->where('branch_id', $branchId)))));
    }

    protected function payslips(Carbon $period): Builder
    {
        return $this->scoped()
            ->whereHas('payrollRun', fn ($q) => $q->where('month', $period->month)->where('year', $period->year))
            ->with('employee.department:id,name')
            ->orderBy(\App\Models\Employee::query()->select('name')->whereColumn('employees.id', 'payslips.employee_id'))
            ->orderBy('id');
    }

    protected function totals(Collection $payslips): array
    {
        $totals = ['slips' => $payslips->count(), 'paid' => $payslips->where('status', 'paid')->count()];

        foreach (self::SUMS as $key => $column) {
            $totals[$key] = round((float) $payslips->sum(fn (Payslip $p) => (float) $p->{$column}), 2);
        }

        // What the month really cost the company: gross pay plus its own PF and ESI share.
        $totals['employer_cost'] = round($totals['gross'] + $totals['pf_employer'] + $totals['esi_employer'], 2);

        return $totals;
    }

    /** Totals for the twelve months ending with $period, keyed "YYYY-MM"; months without payroll are zeros. */
    protected function trend(Carbon $period, ?int $departmentId): Collection
    {
        $from = $period->copy()->subMonths(11);
        $key = fn ($year, $month) => (int) $year * 100 + (int) $month;

        $rows = $this->scoped()
            ->whereHas('payrollRun', fn ($q) => $q->whereRaw('`year` * 100 + `month` BETWEEN ? AND ?', [$key($from->year, $from->month), $key($period->year, $period->month)]))
            ->when($departmentId, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->with('payrollRun:id,month,year')
            ->get()
            ->groupBy(fn (Payslip $p) => sprintf('%04d-%02d', $p->payrollRun->year, $p->payrollRun->month));

        return collect(range(0, 11))->mapWithKeys(function (int $i) use ($from, $rows) {
            $month = $from->copy()->addMonths($i);

            return [$month->format('Y-m') => ['label' => $month->format('M y')] + $this->totals($rows->get($month->format('Y-m'), collect()))];
        });
    }
}
