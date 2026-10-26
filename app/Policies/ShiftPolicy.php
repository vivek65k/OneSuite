<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShiftPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_shift');
    }

    public function create(User $user): bool
    {
        return $user->can('create_shift');
    }

    public function update(User $user, Shift $shift): bool
    {
        return $user->can('update_shift');
    }

    public function delete(User $user, Shift $shift): bool
    {
        return $user->can('delete_shift');
    }
}
