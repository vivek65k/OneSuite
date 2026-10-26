<?php

namespace App\Policies;

use App\Models\ShiftRoster;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShiftRosterPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_shift_roster');
    }

    public function update(User $user, ShiftRoster $shiftRoster): bool
    {
        return $user->can('update_shift_roster');
    }
}
