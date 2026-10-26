<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_employee');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('view_employee') && $this->inScope($employee);
    }

    public function create(User $user): bool
    {
        return $user->can('create_employee');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('update_employee') && $this->inScope($employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('delete_employee') && $this->inScope($employee);
    }

    /**
     * A branch-restricted admin may only act on employees in their own
     * currently-selected branch, never another branch's employees.
     */
    protected function inScope(Employee $employee): bool
    {
        return ! BranchContext::isRestricted() || $employee->branch_id === BranchContext::currentBranchId();
    }
}
