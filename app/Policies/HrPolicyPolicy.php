<?php

namespace App\Policies;

use App\Models\HrPolicy;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HrPolicyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_hr_policy');
    }

    public function create(User $user): bool
    {
        return $user->can('create_hr_policy');
    }

    public function update(User $user, HrPolicy $hrPolicy): bool
    {
        return $user->can('update_hr_policy');
    }

    public function delete(User $user, HrPolicy $hrPolicy): bool
    {
        return $user->can('delete_hr_policy');
    }
}
