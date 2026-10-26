<?php

namespace App\Policies;

use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalaryComponentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_salary_component');
    }

    public function create(User $user): bool
    {
        return $user->can('create_salary_component');
    }

    public function update(User $user, SalaryComponent $salaryComponent): bool
    {
        return $user->can('update_salary_component');
    }

    public function delete(User $user, SalaryComponent $salaryComponent): bool
    {
        return $user->can('delete_salary_component');
    }
}
