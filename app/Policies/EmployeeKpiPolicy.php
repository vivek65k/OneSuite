<?php

namespace App\Policies;

use App\Models\EmployeeKpi;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeKpiPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_employee_kpi');
    }

    public function create(User $user): bool
    {
        return $user->can('create_employee_kpi');
    }

    public function update(User $user, EmployeeKpi $employeeKpi): bool
    {
        return $user->can('update_employee_kpi');
    }

    public function delete(User $user, EmployeeKpi $employeeKpi): bool
    {
        return $user->can('delete_employee_kpi');
    }
}
