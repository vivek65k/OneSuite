<?php

namespace App\Policies;

use App\Models\Trainer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_trainer');
    }

    public function create(User $user): bool
    {
        return $user->can('create_trainer');
    }

    public function update(User $user, Trainer $trainer): bool
    {
        return $user->can('update_trainer');
    }

    public function delete(User $user, Trainer $trainer): bool
    {
        return $user->can('delete_trainer');
    }
}
