<?php

namespace App\Policies;

use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalaryStructurePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_salary_structure');
    }

    public function create(User $user): bool
    {
        return $user->can('create_salary_structure');
    }

    public function update(User $user, SalaryStructure $salaryStructure): bool
    {
        return $user->can('update_salary_structure');
    }

    public function delete(User $user, SalaryStructure $salaryStructure): bool
    {
        return $user->can('delete_salary_structure');
    }
}
