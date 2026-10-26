<?php

namespace App\Policies;

use App\Models\CompOff;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompOffPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_comp_off');
    }

    public function create(User $user): bool
    {
        return $user->can('create_comp_off');
    }

    public function update(User $user, CompOff $compOff): bool
    {
        return $user->can('update_comp_off');
    }

    public function delete(User $user, CompOff $compOff): bool
    {
        return $user->can('delete_comp_off');
    }
}
