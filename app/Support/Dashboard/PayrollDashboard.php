<?php

namespace App\Support\Dashboard;

use App\Models\Bonus;
use App\Models\Deduction;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EsiSetting;
use App\Models\Incentive;
use App\Models\Increment;
use App\Models\Loan;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PfSetting;
use App\Models\TaxDeclaration;
use App\Models\User;
use App\Support\DataScope;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything the payroll dashboard shows: this month's run and whether
 * payroll is ready to run, cost trends, the latest run's breakdown, and
 * the inputs (bonuses, deductions, loans) feeding the next run. Company
 * and branch are resolved once; each widget needs its module permission.
 */
class PayrollDashboard
{
    protected Carbon $today;

    protected ?int $headcountNow = null;

    public function __construct(
        protected User $user,
        protected int $companyId,
        protected ?int $branchId,
    ) {
        $this->today = today();
    }

    public function build(): array
    {
        $runs = $this->runs()->orderByDesc('year')->orderByDesc('month')->limit(12)->get();
        $current = $runs->first(fn ($r) => (int) $r->month === $this->today->month && (int) $r->year === $this->today->year);
        $latest = $runs->first();

        $data = [
            'current' => $current,
            'latest' => $latest,
            'runs' => $runs,
            'days_left' => max(0, (int) $this->today->diffInDays($this->today->copy()->endOfMonth())),
            'headcount' => $this->headcount(),
            'ytd' => $this->ytd(),
            'trend' => $this->trend($runs),
            'breakdown' => $latest ? $this->breakdown($latest) : null,
            'departments' => $latest ? $this->departments($latest) : null,
            'structures' => $this->can('view_any_salary_structure') ? $this->structures() : null,
            'inputs' => $this->inputs(),
            'loans' => $this->can('view_any_loan') ? $this->loans() : null,
            'statutory' => $this->statutory($latest),
            'revisions' => $this->can('view_any_increment') ? $this->revisions() : null,
        ];
        $data['checklist'] = $this->checklist($data);

        return $data;
    }

    // ---------------------------------------------------------------- scopes

    protected function can(string $permission): bool
    {
        return $this->user->can($permission);
    }

    protected function runs(): Builder
    {
        return PayrollRun::query()->where('company_id', $this->companyId)
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId));
    }

    /** People payroll should cover: not terminated/inactive and already joined. */
    protected function payable(): Builder
    {
        return Employee::query()->where('employees.company_id', $this->companyId)
            ->when($this->branchId, fn ($q) => $q->where('employees.branch_id', $this->branchId))
            ->whereNotIn('employees.status', DashboardKit::INACTIVE)
            ->where(fn ($q) => $q->whereNull('employees.joining_date')->orWhereDate('employees.joining_date', '<=', $this->today));
    }

    protected function headcount(): int
    {
        return $this->headcountNow ??= $this->payable()->count();
    }

    protected function ownedBy(string $model): Builder
    {
        return $model::query()->where('company_id', $this->companyId)
            ->when($this->branchId, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', $this->branchId)));
    }

    // --------------------------------------------------------------- widgets

    protected function ytd(): array
    {
        $row = $this->runs()->where('year', $this->today->year)->toBase()
            ->selectRaw('COUNT(*) as runs, COALESCE(SUM(total_gross),0) as gross, COALESCE(SUM(total_deductions),0) as deductions, COALESCE(SUM(total_net),0) as net')
            ->first();

        return ['runs' => (int) $row->runs, 'gross' => (float) $row->gross, 'deductions' => (float) $row->deductions, 'net' => (float) $row->net];
    }

    /** Oldest to newest, for the cost chart. */
    protected function trend(Collection $runs): array
    {
        $ordered = $runs->reverse()->values();

        return [
            'labels' => $ordered->map(fn ($r) => Carbon::create($r->year, $r->month, 1)->format('M y'))->all(),
            'gross' => $ordered->map(fn ($r) => round((float) $r->total_gross, 2))->all(),
            'net' => $ordered->map(fn ($r) => round((float) $r->total_net, 2))->all(),
            'deductions' => $ordered->map(fn ($r) => round((float) $r->total_deductions, 2))->all(),
            'empty' => $ordered->isEmpty(),
        ];
    }

    /** Where the latest run's money went: net pay, statutory, tax, loans, other. */
    protected function breakdown(PayrollRun $run): array
    {
        $row = Payslip::query()->where('payroll_run_id', $run->id)->toBase()
            ->selectRaw('COUNT(*) as slips, COALESCE(SUM(gross_earnings),0) as gross, COALESCE(SUM(net_pay),0) as net')
            ->selectRaw('COALESCE(SUM(pf_employee_amount),0) as pf_emp, COALESCE(SUM(pf_employer_amount),0) as pf_er')
            ->selectRaw('COALESCE(SUM(esi_employee_amount),0) as esi_emp, COALESCE(SUM(esi_employer_amount),0) as esi_er')
            ->selectRaw('COALESCE(SUM(tax_amount),0) as tax, COALESCE(SUM(loan_deduction_amount),0) as loan, COALESCE(SUM(other_deduction_amount),0) as other')
            ->selectRaw("SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid")
            ->first();

        $gross = (float) $row->gross;

        return [
            'slips' => (int) $row->slips,
            'paid' => (int) $row->paid,
            'gross' => $gross,
            'net' => (float) $row->net,
            'employer_cost' => $gross + (float) $row->pf_er + (float) $row->esi_er,
            'employer_pf' => (float) $row->pf_er,
            'employer_esi' => (float) $row->esi_er,
            'average_net' => (int) $row->slips ? (float) $row->net / (int) $row->slips : 0.0,
            'segments' => collect([
                ['label' => 'Net pay', 'tone' => 'success', 'amount' => (float) $row->net],
                ['label' => 'PF (employee)', 'tone' => 'indigo', 'amount' => (float) $row->pf_emp],
                ['label' => 'ESI (employee)', 'tone' => 'sky', 'amount' => (float) $row->esi_emp],
                ['label' => 'Income tax', 'tone' => 'rose', 'amount' => (float) $row->tax],
                ['label' => 'Loan recovery', 'tone' => 'amber', 'amount' => (float) $row->loan],
                ['label' => 'Other deductions', 'tone' => 'slate', 'amount' => (float) $row->other],
            ])->filter(fn ($s) => $s['amount'] > 0)->values(),
        ];
    }

    protected function departments(PayrollRun $run): Collection
    {
        $rows = DB::table('payslips')->join('employees', 'employees.id', '=', 'payslips.employee_id')
            ->where('payslips.payroll_run_id', $run->id)
            // Raw query, so the role's data access (team / own) has to be applied by hand here.
            ->when(DataScope::activeEmployeeIds() !== null, fn ($q) => $q->whereIn('payslips.employee_id', DataScope::activeEmployeeIds()))
            ->groupBy('employees.department_id')
            ->selectRaw('employees.department_id, COUNT(*) as people, SUM(payslips.gross_earnings) as gross')
            ->get();
        $names = Department::whereIn('id', $rows->pluck('department_id')->filter())->pluck('name', 'id');

        return $rows->map(fn ($r) => ['name' => $names[$r->department_id] ?? 'Unassigned', 'people' => (int) $r->people, 'gross' => (float) $r->gross])
            ->sortByDesc('gross')->values()->take(6);
    }

    protected function structures(): array
    {
        $missing = $this->payable()->whereDoesntHave('salaryStructures', fn ($q) => $q->where('is_current', true)->has('items'));

        return [
            'total' => $this->headcount(),
            'missing' => (clone $missing)->count(),
            'people' => (clone $missing)->orderBy('name')->limit(5)->get(['employees.id', 'employees.name', 'employees.employee_code', 'employees.avatar', 'employees.designation']),
        ];
    }

    /** Pending bonuses, incentives and deductions: this period vs older backlog. */
    protected function inputs(): Collection
    {
        $types = [
            ['key' => 'bonus', 'label' => 'Bonuses', 'model' => Bonus::class, 'perm' => 'view_any_bonus', 'icon' => 'ti-gift', 'tone' => 'success', 'route' => 'admin.bonuses.index'],
            ['key' => 'incentive', 'label' => 'Incentives', 'model' => Incentive::class, 'perm' => 'view_any_incentive', 'icon' => 'ti-trending-up', 'tone' => 'teal', 'route' => 'admin.incentives.index'],
            ['key' => 'deduction', 'label' => 'Deductions', 'model' => Deduction::class, 'perm' => 'view_any_deduction', 'icon' => 'ti-trending-down', 'tone' => 'rose', 'route' => 'admin.deductions.index'],
        ];

        return collect($types)->filter(fn ($t) => $this->can($t['perm']))->map(function ($t) {
            $row = $this->ownedBy($t['model'])->where('status', 'pending')->toBase()
                ->selectRaw('COUNT(*) as total, COALESCE(SUM(amount),0) as amount')
                ->selectRaw('SUM(CASE WHEN period_month = ? AND period_year = ? THEN 1 ELSE 0 END) as this_period', [$this->today->month, $this->today->year])
                ->selectRaw('SUM(CASE WHEN (period_year < ?) OR (period_year = ? AND period_month < ?) THEN 1 ELSE 0 END) as older', [$this->today->year, $this->today->year, $this->today->month])
                ->first();
            unset($t['model'], $t['perm']);

            return $t + ['count' => (int) $row->total, 'amount' => (float) $row->amount, 'this_period' => (int) $row->this_period, 'older' => (int) $row->older];
        })->values();
    }

    protected function loans(): array
    {
        $active = $this->ownedBy(Loan::class)->where('status', 'active');
        $row = (clone $active)->toBase()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(outstanding_amount),0) as outstanding, COALESCE(SUM(principal_amount),0) as principal, COALESCE(SUM(monthly_installment),0) as emi')
            ->first();

        return [
            'count' => (int) $row->total,
            'outstanding' => (float) $row->outstanding,
            'principal' => (float) $row->principal,
            'emi' => (float) $row->emi,
            'rows' => (clone $active)->with('employee:id,name,employee_code,avatar')->orderByDesc('outstanding_amount')->limit(4)->get(),
        ];
    }

    protected function statutory(?PayrollRun $latest): array
    {
        $setting = fn (string $model) => $model::query()->where('company_id', $this->companyId)
            ->where(fn ($q) => $q->where('branch_id', $this->branchId)->orWhereNull('branch_id'))
            ->orderByRaw('branch_id IS NULL')->first();

        $out = [];
        if ($this->can('view_any_pf_setting')) {
            $out['pf'] = $setting(PfSetting::class);
        }
        if ($this->can('view_any_esi_setting')) {
            $out['esi'] = $setting(EsiSetting::class);
        }
        if ($this->can('view_any_tax_declaration')) {
            // Indian financial year: April to March.
            $fyStart = $this->today->month >= 4 ? $this->today->year : $this->today->year - 1;
            $fy = $fyStart.'-'.substr((string) ($fyStart + 1), -2);
            $declared = $this->ownedBy(TaxDeclaration::class)
                ->where(fn ($q) => $q->where('financial_year', $fy)->orWhere('financial_year', $fyStart.'-'.($fyStart + 1)))
                ->distinct()->count('employee_id');
            $out['tax'] = ['fy' => $fy, 'declared' => $declared, 'total' => $this->headcount()];
        }

        return $out;
    }

    protected function revisions(): array
    {
        $recent = $this->ownedBy(Increment::class)->whereDate('effective_date', '>=', $this->today->copy()->subDays(90));

        return [
            'count' => (clone $recent)->count(),
            'upcoming' => $this->ownedBy(Increment::class)->whereDate('effective_date', '>', $this->today)->count(),
            'rows' => (clone $recent)->with('employee:id,name,employee_code,avatar')->orderByDesc('effective_date')->limit(4)->get(),
        ];
    }

    /** "Ready to run?" steps for this month's payroll. */
    protected function checklist(array $data): array
    {
        $steps = [];
        if ($data['structures']) {
            $missing = $data['structures']['missing'];
            $steps[] = ['done' => ! $missing && $data['structures']['total'] > 0, 'label' => $data['structures']['total'] === 0 ? 'No employees to pay yet'
                : ($missing ? $missing.' of '.$data['structures']['total'].' people have no salary structure' : 'Everyone has a salary structure'),
                'route' => 'admin.salary-structures.index', 'blocking' => true];
        }
        $pendingNow = $data['inputs']->sum('this_period');
        if ($data['inputs']->isNotEmpty()) {
            $steps[] = ['done' => ! $pendingNow, 'label' => $pendingNow ? $pendingNow.' bonuses, incentives or deductions pending for '.$this->today->format('F') : 'No pending pay adjustments for '.$this->today->format('F'),
                'route' => $data['inputs']->sortByDesc('this_period')->first()['route'], 'blocking' => false];
        }
        if (isset($data['statutory']['pf'], $data['statutory']['esi']) || isset($data['statutory']['pf']) || isset($data['statutory']['esi'])) {
            $pf = $data['statutory']['pf'] ?? null;
            $esi = $data['statutory']['esi'] ?? null;
            $configured = ($pf || $esi);
            $steps[] = ['done' => (bool) $configured, 'label' => $configured
                ? 'PF '.($pf?->is_enabled ? 'on' : 'off').' · ESI '.($esi?->is_enabled ? 'on' : 'off')
                : 'PF and ESI are not set up for this branch', 'route' => 'admin.pf.index', 'blocking' => false];
        }
        $unpaid = $data['runs']->first(fn ($r) => $r->status !== 'paid' && ! ((int) $r->month === $this->today->month && (int) $r->year === $this->today->year));
        if ($unpaid) {
            $steps[] = ['done' => false, 'label' => $unpaid->monthLabel().' is processed but not marked paid', 'route' => 'admin.payroll-runs.index', 'blocking' => false];
        }

        return ['steps' => $steps, 'done' => collect($steps)->where('done', true)->count(), 'blocked' => collect($steps)->contains(fn ($s) => $s['blocking'] && ! $s['done'])];
    }
}
