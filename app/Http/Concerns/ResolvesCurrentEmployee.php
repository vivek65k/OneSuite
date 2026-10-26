<?php

namespace App\Http\Concerns;

use App\Models\Employee;
use App\Support\ReportingChain;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait ResolvesCurrentEmployee
{
    /**
     * The employee record behind the logged-in login. Employee Self Service
     * has no admin permission of its own to gate this on - if your login
     * isn't linked to an employee (see Employee::user_id), there is simply
     * no "my data" to show.
     */
    protected function currentEmployee(): Employee
    {
        $employee = Auth::user()->employee;

        abort_unless($employee, 403, 'Your login is not linked to an employee profile. Ask HR to link it from Employees → Edit.');

        return $employee;
    }

    /**
     * Guard against one ESS screen reading/acting on someone else's record
     * via a guessed/shared URL. 404 (not 403) so it doesn't confirm the
     * record exists, matching the ownership-check convention already used
     * for cross-tenant guards elsewhere (e.g. ExpenseReimbursementController).
     */
    protected function ownedByCurrentEmployee(object $record, string $column = 'employee_id'): void
    {
        abort_unless($record->{$column} === $this->currentEmployee()->id, 404);
    }

    /**
     * IDs of everyone below the current employee in the reporting chain -
     * their direct reports, those people's reports, and so on (the same
     * "team" a role's Team data access means; see App\Support\ReportingChain).
     * Manager Self Service scopes every "team" query to this list rather
     * than a Spatie permission - a manager sees their own chain the same
     * way an employee sees their own record, no admin grant required.
     *
     * @return array<int, int>
     */
    protected function teamEmployeeIds(): array
    {
        $employee = $this->currentEmployee();

        return ReportingChain::below((int) $employee->id, (int) $employee->company_id);
    }

    /**
     * id => name of the same people, for the "Employee" filter on team screens.
     */
    protected function teamEmployeeOptions(): Collection
    {
        return Employee::query()->whereIn('id', $this->teamEmployeeIds())->orderBy('name')->pluck('name', 'id');
    }

    /**
     * Same ownership guard as ownedByCurrentEmployee(), but for "is this
     * record someone's in my chain" instead of "is this mine."
     */
    protected function belongsToMyTeam(object $record, string $column = 'employee_id'): void
    {
        abort_unless(in_array($record->{$column}, $this->teamEmployeeIds(), true), 404);
    }
}
