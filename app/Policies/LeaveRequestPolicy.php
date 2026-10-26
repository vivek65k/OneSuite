<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaveRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_leave_request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_leave_request');
    }

    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('update_leave_request');
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('delete_leave_request');
    }
}
