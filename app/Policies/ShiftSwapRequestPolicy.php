<?php

namespace App\Policies;

use App\Models\ShiftSwapRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShiftSwapRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_shift_swap_request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_shift_swap_request');
    }

    public function update(User $user, ShiftSwapRequest $shiftSwapRequest): bool
    {
        return $user->can('update_shift_swap_request');
    }

    public function delete(User $user, ShiftSwapRequest $shiftSwapRequest): bool
    {
        return $user->can('delete_shift_swap_request');
    }
}
