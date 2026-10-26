<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Bonus;
use App\Models\Company;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\EsiSetting;
use App\Models\Incentive;
use App\Models\Loan;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PfSetting;
use App\Models\TaxDeclaration;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayrollRunController extends Controller
{
    /** Statuses that are no longer on payroll. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PayrollRun::class);

        $companyId = Company::current()->id;
        $branchId = BranchContext::currentBranchId();
        $state = [
            'status' => in_array($request->query('status'), array_keys(PayrollRun::STATUSES), true) ? $request->query('status') : '',
            'year' => ctype_digit((string) $request->query('year')) ? (int) $request->query('year') : null,
        ];

        $all = $this->scoped()->orderByDesc('year')->orderByDesc('month')->get();
        $runs = $this->scoped()
            ->when($state['status'], fn ($q, $status) => $q->where('status', $status))
            ->when($state['year'], fn ($q, $year) => $q->where('year', $year))
            ->orderByDesc('year')->orderByDesc('month')
            ->paginate(25)->withQueryString();

        // The period the "Run payroll" panel previews: ?period=YYYY-MM, else this month.
        $period = preg_match('/^(\d{4})-(\d{2})$/', (string) $request->query('period'), $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12
            ? Carbon::create((int) $m[1], (int) $m[2], 1)
            : now()->startOfMonth();
        if ($period->gt(now()->startOfMonth())) {
            $period = now()->startOfMonth();
        }

        return view('admin.hrm.payroll.runs.index', [
            'runs' => $runs,
            'state' => $state,
            'period' => $period,
            'preview' => $request->user()->can('create', PayrollRun::class) ? $this->preview($companyId, $branchId, $period) : null,
            'existing' => $all->first(fn ($r) => (int) $r->month === $period->month && (int) $r->year === $period->year),
            'ranPeriods' => $all->map(fn ($r) => sprintf('%04d-%02d', $r->year, $r->month))->all(),
            'counts' => ['all' => $all->count(), 'processed' => $all->where('status', 'processed')->count(), 'paid' => $all->where('status', 'paid')->count()],
            'years' => $all->pluck('year')->unique()->sortDesc()->values(),
            'kpis' => [
                'latest' => $all->first(),
                'previous' => $all->get(1),
                'unpaid' => $all->where('status', 'processed'),
                'ytd_net' => (float) $all->where('year', now()->year)->sum('total_net'),
                'ytd_runs' => $all->where('year', now()->year)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PayrollRun::class);

        $companyId = Company::current()->id;
        $branchId = BranchContext::currentBranchId();

        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $period = Carbon::create((int) $data['year'], (int) $data['month'], 1);
        $back = fn (string $message) => redirect()->route('admin.payroll-runs.index', ['period' => $period->format('Y-m')])->withErrors(['month' => $message]);

        if ($period->gt(now()->startOfMonth())) {
            return $back('Payroll can\'t be run for a future month.');
        }
        if ($this->scoped()->where('month', $data['month'])->where('year', $data['year'])->exists()) {
            return $back('Payroll has already been run for '.$period->format('F Y').'.');
        }
        // A run with no payslips would lock the month for good - refuse it.
        if (! $this->preview($companyId, $branchId, $period)['included']->count()) {
            return $back('Nobody has a salary structure yet, so there is nothing to pay for '.$period->format('F Y').'. Set up salary structures first.');
        }

        try {
            $run = $this->process($companyId, $branchId, $data, $period);
        } catch (\DomainException $e) {
            return $back($e->getMessage());
        }

        return redirect()->route('admin.payroll-runs.show', $run)->with('status', 'Payroll run completed for '.$run->monthLabel().': '.$run->employee_count.' '.($run->employee_count === 1 ? 'payslip' : 'payslips').' generated.');
    }

    /** Company/branch-scoped runs. */
    protected function scoped()
    {
        return PayrollRun::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    protected function ensureOwn(PayrollRun $run): void
    {
        abort_unless((int) $run->company_id === (int) Company::current()->id, 404);
        abort_if(BranchContext::isRestricted() && (int) $run->branch_id !== (int) BranchContext::currentBranchId(), 404);
    }

    /** Everyone payroll should cover for a period: still employed and joined by the period's end. */
    protected function payableEmployees(int $companyId, ?int $branchId, Carbon $period)
    {
        return Employee::query()
            ->where('company_id', $companyId)
            ->whereNotIn('status', self::INACTIVE)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', $branchId))
            ->where(fn ($q) => $q->whereNull('joining_date')->orWhereDate('joining_date', '<=', $period->copy()->endOfMonth()));
    }

    /**
     * What running payroll for $period would do, without changing anything:
     * who gets a payslip, who is skipped (no salary structure) and the
     * adjustments and loan recoveries that would be picked up.
     */
    protected function preview(int $companyId, ?int $branchId, Carbon $period): array
    {
        $employees = $this->payableEmployees($companyId, $branchId, $period)
            ->with(['salaryStructures' => fn ($q) => $q->where('is_current', true)->with('items')])
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'avatar', 'designation', 'joining_date']);

        [$included, $skipped] = $employees->partition(fn ($e) => $e->salaryStructures->first()?->items->isNotEmpty());
        $ids = $included->pluck('id');
        $sum = fn (string $model) => (float) $model::query()->whereIn('employee_id', $ids)->where('status', 'pending')
            ->where('period_month', $period->month)->where('period_year', $period->year)->sum('amount');
        $count = fn (string $model) => $model::query()->whereIn('employee_id', $ids)->where('status', 'pending')
            ->where('period_month', $period->month)->where('period_year', $period->year)->count();

        $structures = (float) $included->sum(fn ($e) => (float) $e->salaryStructures->first()->items->sum('amount'));
        $bonuses = $sum(Bonus::class) + $sum(Incentive::class);
        // A loan is only recovered from the month it was given out, not before.
        $loans = Loan::query()->whereIn('employee_id', $ids)->where('status', 'active')->whereDate('disbursed_date', '<=', $period->copy()->endOfMonth())->get(['monthly_installment', 'outstanding_amount']);

        return [
            'included' => $included->values(),
            'skipped' => $skipped->values(),
            'structures' => $structures,
            'bonuses' => $bonuses,
            'bonus_count' => $count(Bonus::class) + $count(Incentive::class),
            'gross' => $structures + $bonuses,
            'deductions' => $sum(Deduction::class),
            'deduction_count' => $count(Deduction::class),
            'loan_emi' => (float) $loans->sum(fn ($l) => min((float) $l->monthly_installment, (float) $l->outstanding_amount)),
            'loan_count' => $loans->count(),
        ];
    }

    /** @throws \DomainException when a payslip can't be produced safely (rolls the whole run back). */
    protected function process(int $companyId, ?int $branchId, array $data, Carbon $period): PayrollRun
    {
        return DB::transaction(function () use ($companyId, $branchId, $data, $period) {
            $run = PayrollRun::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'month' => $data['month'],
                'year' => $data['year'],
                'status' => 'processed',
                'run_date' => now(),
                'processed_by_user_id' => Auth::id(),
            ]);

            $pfSetting = PfSetting::forBranch($companyId, $branchId);
            $esiSetting = EsiSetting::forBranch($companyId, $branchId);
            $financialYear = TaxDeclaration::currentFinancialYear(Carbon::create($data['year'], $data['month'], 1));

            $employees = $this->payableEmployees($companyId, $branchId, $period)->get();

            $totals = ['gross' => 0, 'deductions' => 0, 'net' => 0, 'count' => 0];

            foreach ($employees as $employee) {
                $payslip = $this->processEmployee($employee, $run, $pfSetting, $esiSetting, $financialYear);

                if ($payslip) {
                    $totals['gross'] += (float) $payslip->gross_earnings;
                    $totals['deductions'] += (float) $payslip->total_deductions;
                    $totals['net'] += (float) $payslip->net_pay;
                    $totals['count']++;
                }
            }

            $run->update([
                'total_gross' => $totals['gross'],
                'total_deductions' => $totals['deductions'],
                'total_net' => $totals['net'],
                'employee_count' => $totals['count'],
            ]);

            if (! $totals['count']) {
                throw new \DomainException('No payslips could be generated - nobody has a salary structure.');
            }

            return $run;
        });
    }

    public function show(Request $request, PayrollRun $payrollRun): View
    {
        $this->authorize('viewAny', PayrollRun::class);
        $this->ensureOwn($payrollRun);

        $payrollRun->load(['payslips.employee.department:id,name', 'processedBy:id,name']);
        $payslips = $payrollRun->payslips->sortBy(fn ($p) => $p->employee?->name)->values();

        // People on payroll now who have no payslip in this run (e.g. structure set up afterwards).
        $period = Carbon::create($payrollRun->year, $payrollRun->month, 1);
        $missed = $this->payableEmployees($payrollRun->company_id, $payrollRun->branch_id, $period)
            ->whereNotIn('id', $payslips->pluck('employee_id'))
            ->orderBy('name')->limit(8)->get(['id', 'name', 'employee_code', 'avatar', 'designation']);

        return view('admin.hrm.payroll.runs.show', [
            'run' => $payrollRun,
            'payslips' => $payslips,
            'missed' => $missed,
            'totals' => [
                'pf' => (float) $payslips->sum('pf_employee_amount'),
                'esi' => (float) $payslips->sum('esi_employee_amount'),
                'tax' => (float) $payslips->sum('tax_amount'),
                'loan' => (float) $payslips->sum('loan_deduction_amount'),
                'other' => (float) $payslips->sum('other_deduction_amount'),
                'employer' => (float) $payslips->sum('pf_employer_amount') + (float) $payslips->sum('esi_employer_amount'),
                'paid' => $payslips->where('status', 'paid')->count(),
            ],
        ]);
    }

    public function markPaid(PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorize('update', $payrollRun);
        $this->ensureOwn($payrollRun);

        // Marking twice would overwrite every payslip's paid date.
        abort_if($payrollRun->status === 'paid', 409, 'This payroll run is already marked as paid.');

        DB::transaction(function () use ($payrollRun) {
            $payrollRun->payslips()->where('status', '!=', 'paid')->update(['status' => 'paid', 'paid_at' => now()]);
            $payrollRun->update(['status' => 'paid']);
        });

        return back()->with('status', $payrollRun->monthLabel().' marked as paid - '.$payrollRun->employee_count.' payslips.');
    }

    protected function processEmployee(Employee $employee, PayrollRun $run, PfSetting $pfSetting, EsiSetting $esiSetting, string $financialYear): ?Payslip
    {
        $structure = $employee->salaryStructures()->where('is_current', true)->with('items.component')->first();

        if (! $structure || $structure->items->isEmpty()) {
            return null;
        }

        $items = [];
        $grossEarnings = 0;
        $basicAmount = 0;

        foreach ($structure->items as $item) {
            $amount = (float) $item->amount;
            $grossEarnings += $amount;
            $items[] = ['component_name' => $item->component->name, 'type' => 'earning', 'amount' => $amount];

            if ($item->component->code === 'BASIC') {
                $basicAmount = $amount;
            }
        }

        foreach (Bonus::query()->where('employee_id', $employee->id)->where('status', 'pending')
            ->where('period_month', $run->month)->where('period_year', $run->year)->get() as $bonus) {
            $grossEarnings += (float) $bonus->amount;
            $items[] = ['component_name' => 'Bonus: '.$bonus->title, 'type' => 'earning', 'amount' => (float) $bonus->amount];
            $bonus->update(['status' => 'paid', 'payroll_run_id' => $run->id]);
        }

        foreach (Incentive::query()->where('employee_id', $employee->id)->where('status', 'pending')
            ->where('period_month', $run->month)->where('period_year', $run->year)->get() as $incentive) {
            $grossEarnings += (float) $incentive->amount;
            $items[] = ['component_name' => 'Incentive: '.$incentive->title, 'type' => 'earning', 'amount' => (float) $incentive->amount];
            $incentive->update(['status' => 'paid', 'payroll_run_id' => $run->id]);
        }

        $pfEmployee = 0;
        $pfEmployer = 0;

        if ($pfSetting->is_enabled && $basicAmount > 0) {
            $pfWage = $pfSetting->wage_ceiling ? min($basicAmount, (float) $pfSetting->wage_ceiling) : $basicAmount;
            $pfEmployee = round($pfWage * (float) $pfSetting->employee_contribution_percent / 100, 2);
            $pfEmployer = round($pfWage * (float) $pfSetting->employer_contribution_percent / 100, 2);

            if ($pfEmployee > 0) {
                $items[] = ['component_name' => 'PF (Employee)', 'type' => 'deduction', 'amount' => $pfEmployee];
            }
        }

        $esiEmployee = 0;
        $esiEmployer = 0;

        if ($esiSetting->is_enabled && $grossEarnings > 0 && $grossEarnings <= (float) $esiSetting->wage_ceiling) {
            $esiEmployee = round($grossEarnings * (float) $esiSetting->employee_contribution_percent / 100, 2);
            $esiEmployer = round($grossEarnings * (float) $esiSetting->employer_contribution_percent / 100, 2);

            if ($esiEmployee > 0) {
                $items[] = ['component_name' => 'ESI (Employee)', 'type' => 'deduction', 'amount' => $esiEmployee];
            }
        }

        $taxAmount = 0;
        $declaration = TaxDeclaration::query()->where('employee_id', $employee->id)->where('financial_year', $financialYear)->first();

        if ($declaration && $declaration->monthlyTds() > 0) {
            $taxAmount = $declaration->monthlyTds();
            $items[] = ['component_name' => 'Income Tax (TDS)', 'type' => 'deduction', 'amount' => $taxAmount];
        }

        $loanDeduction = 0;

        // A loan is only recovered from the month it was given out, not before.
        $loanCutoff = Carbon::create((int) $run->year, (int) $run->month, 1)->endOfMonth();

        foreach (Loan::query()->where('employee_id', $employee->id)->where('status', 'active')->whereDate('disbursed_date', '<=', $loanCutoff)->get() as $loan) {
            $installment = min((float) $loan->monthly_installment, (float) $loan->outstanding_amount);

            if ($installment <= 0) {
                continue;
            }

            $loanDeduction += $installment;
            $items[] = ['component_name' => 'Loan EMI: '.$loan->loan_type, 'type' => 'deduction', 'amount' => $installment];

            $newOutstanding = round((float) $loan->outstanding_amount - $installment, 2);
            $loan->update([
                'outstanding_amount' => $newOutstanding,
                'status' => $newOutstanding <= 0 ? 'closed' : 'active',
            ]);
        }

        $otherDeduction = 0;

        foreach (Deduction::query()->where('employee_id', $employee->id)->where('status', 'pending')
            ->where('period_month', $run->month)->where('period_year', $run->year)->get() as $deduction) {
            $otherDeduction += (float) $deduction->amount;
            $items[] = ['component_name' => $deduction->title, 'type' => 'deduction', 'amount' => (float) $deduction->amount];
            $deduction->update(['status' => 'paid', 'payroll_run_id' => $run->id]);
        }

        $totalDeductions = round($pfEmployee + $esiEmployee + $taxAmount + $loanDeduction + $otherDeduction, 2);
        $netPay = round($grossEarnings - $totalDeductions, 2);

        // Never issue a payslip that takes more than the employee earned.
        if ($netPay < 0) {
            throw new \DomainException("{$employee->name}'s deductions (".number_format($totalDeductions, 2).') are more than their gross pay ('.number_format($grossEarnings, 2).'). Reduce the deductions or loan instalment for this month, then run payroll again. Nothing was saved.');
        }

        $payslip = Payslip::create([
            'company_id' => $employee->company_id,
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'gross_earnings' => round($grossEarnings, 2),
            'pf_employee_amount' => $pfEmployee,
            'pf_employer_amount' => $pfEmployer,
            'esi_employee_amount' => $esiEmployee,
            'esi_employer_amount' => $esiEmployer,
            'tax_amount' => $taxAmount,
            'loan_deduction_amount' => round($loanDeduction, 2),
            'other_deduction_amount' => round($otherDeduction, 2),
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'status' => 'generated',
        ]);

        foreach ($items as $item) {
            $payslip->items()->create($item);
        }

        return $payslip;
    }
}
