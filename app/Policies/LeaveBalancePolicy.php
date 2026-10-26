<?php

namespace App\Policies;

use App\Models\LeaveBalance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaveBalancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_leave_balance');
    }

    public function update(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->can('update_leave_balance');
    }
}
