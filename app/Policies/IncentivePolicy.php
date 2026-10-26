<?php

namespace App\Policies;

use App\Models\Incentive;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IncentivePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_incentive');
    }

    public function create(User $user): bool
    {
        return $user->can('create_incentive');
    }

    public function update(User $user, Incentive $incentive): bool
    {
        return $user->can('update_incentive');
    }

    public function delete(User $user, Incentive $incentive): bool
    {
        return $user->can('delete_incentive');
    }
}
