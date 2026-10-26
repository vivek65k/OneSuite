<?php

namespace App\Policies;

use App\Models\Resignation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResignationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_resignation');
    }

    public function create(User $user): bool
    {
        return $user->can('create_resignation');
    }

    public function update(User $user, Resignation $resignation): bool
    {
        return $user->can('update_resignation');
    }

    public function delete(User $user, Resignation $resignation): bool
    {
        return $user->can('delete_resignation');
    }
}
