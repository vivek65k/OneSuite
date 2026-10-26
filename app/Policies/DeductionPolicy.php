<?php

namespace App\Policies;

use App\Models\Deduction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeductionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_deduction');
    }

    public function create(User $user): bool
    {
        return $user->can('create_deduction');
    }

    public function update(User $user, Deduction $deduction): bool
    {
        return $user->can('update_deduction');
    }

    public function delete(User $user, Deduction $deduction): bool
    {
        return $user->can('delete_deduction');
    }
}
