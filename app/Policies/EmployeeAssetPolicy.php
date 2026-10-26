<?php

namespace App\Policies;

use App\Models\EmployeeAsset;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeAssetPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_employee_asset');
    }

    public function create(User $user): bool
    {
        return $user->can('create_employee_asset');
    }

    public function update(User $user, EmployeeAsset $employeeAsset): bool
    {
        return $user->can('update_employee_asset');
    }

    public function delete(User $user, EmployeeAsset $employeeAsset): bool
    {
        return $user->can('delete_employee_asset');
    }
}
