<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Loan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The checks a loan must pass before it is requested or given out, so that
 * payroll can actually recover it. Payroll refuses to run for everyone when
 * one person's deductions exceed their pay, hence the salary limit.
 */
class LoanRules
{
    /** Longest repayment allowed, in monthly instalments. */
    public const MAX_INSTALMENTS = 120;

    /** Employees payroll no longer pays. */
    public const INACTIVE = ['terminated', 'inactive'];

    /** Why this loan can't go ahead, or null when it can. */
    public static function problem(Employee $employee, float $principal, float $instalment): ?string
    {
        if (in_array($employee->status, self::INACTIVE, true)) {
            return $employee->name.' is no longer on payroll, so a loan could not be recovered from salary.';
        }

        if ($principal <= 0 || $instalment <= 0) {
            return 'The loan amount and the monthly instalment must both be more than zero.';
        }

        if ($instalment > $principal) {
            return 'The monthly instalment can\'t be more than the loan itself.';
        }

        if (ceil($principal / $instalment) > self::MAX_INSTALMENTS) {
            return 'At '.number_format($instalment, 2).' a month this loan would take more than 10 years to repay. Raise the instalment.';
        }

        $salary = self::salaries(collect([$employee->id]))->get($employee->id);
        $existing = self::instalments(collect([$employee->id]))->get($employee->id, 0.0);

        if ($salary !== null && $existing + $instalment > (float) $salary) {
            return $employee->name.'\'s loan instalments would come to '.number_format($existing + $instalment, 2).' a month, more than the '.number_format((float) $salary, 2).' they earn. Payroll would refuse to run. Lower the instalment.';
        }

        return null;
    }

    /** Monthly gross from each person's current salary structure, keyed by employee. People without one are absent. */
    public static function salaries(Collection $employeeIds): Collection
    {
        return DB::table('salary_structures')
            ->join('salary_structure_items', 'salary_structure_items.salary_structure_id', '=', 'salary_structures.id')
            ->whereIn('salary_structures.employee_id', $employeeIds)
            ->where('salary_structures.is_current', true)
            ->groupBy('salary_structures.employee_id')
            ->selectRaw('salary_structures.employee_id, SUM(salary_structure_items.amount) as total')
            ->pluck('total', 'employee_id')
            ->map(fn ($total) => (float) $total);
    }

    /** What each person already repays a month across their open loans, keyed by employee. */
    public static function instalments(Collection $employeeIds): Collection
    {
        return Loan::query()->whereIn('employee_id', $employeeIds)->where('status', 'active')
            ->get(['employee_id', 'monthly_installment', 'outstanding_amount'])
            ->groupBy('employee_id')
            ->map(fn (Collection $loans) => (float) $loans->sum(fn (Loan $l) => min((float) $l->monthly_installment, (float) $l->outstanding_amount)));
    }
}
