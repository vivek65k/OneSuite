<?php

namespace App\Policies;

use App\Models\Increment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IncrementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_increment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_increment');
    }

    public function delete(User $user, Increment $increment): bool
    {
        return $user->can('delete_increment');
    }
}
