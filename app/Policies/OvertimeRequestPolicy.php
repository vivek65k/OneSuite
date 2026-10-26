<?php

namespace App\Policies;

use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OvertimeRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_overtime_request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_overtime_request');
    }

    public function update(User $user, OvertimeRequest $overtimeRequest): bool
    {
        return $user->can('update_overtime_request');
    }

    public function delete(User $user, OvertimeRequest $overtimeRequest): bool
    {
        return $user->can('delete_overtime_request');
    }
}
