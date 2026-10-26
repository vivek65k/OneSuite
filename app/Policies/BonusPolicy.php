<?php

namespace App\Policies;

use App\Models\Bonus;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BonusPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_bonus');
    }

    public function create(User $user): bool
    {
        return $user->can('create_bonus');
    }

    public function update(User $user, Bonus $bonus): bool
    {
        return $user->can('update_bonus');
    }

    public function delete(User $user, Bonus $bonus): bool
    {
        return $user->can('delete_bonus');
    }
}
