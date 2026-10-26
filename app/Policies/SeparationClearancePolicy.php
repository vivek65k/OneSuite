<?php

namespace App\Policies;

use App\Models\SeparationClearance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SeparationClearancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_separation_clearance');
    }

    public function create(User $user): bool
    {
        return $user->can('create_separation_clearance');
    }

    public function update(User $user, SeparationClearance $separationClearance): bool
    {
        return $user->can('update_separation_clearance');
    }

    public function delete(User $user, SeparationClearance $separationClearance): bool
    {
        return $user->can('delete_separation_clearance');
    }
}
