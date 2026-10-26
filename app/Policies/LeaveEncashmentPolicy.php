<?php

namespace App\Policies;

use App\Models\LeaveEncashment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaveEncashmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_leave_encashment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_leave_encashment');
    }

    public function update(User $user, LeaveEncashment $leaveEncashment): bool
    {
        return $user->can('update_leave_encashment');
    }

    public function delete(User $user, LeaveEncashment $leaveEncashment): bool
    {
        return $user->can('delete_leave_encashment');
    }
}
