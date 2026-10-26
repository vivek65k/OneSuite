<?php

namespace App\Policies;

use App\Models\OvertimeRule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OvertimeRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_overtime_rule');
    }

    public function create(User $user): bool
    {
        return $user->can('create_overtime_rule');
    }

    public function update(User $user, OvertimeRule $overtimeRule): bool
    {
        return $user->can('update_overtime_rule');
    }

    public function delete(User $user, OvertimeRule $overtimeRule): bool
    {
        return $user->can('delete_overtime_rule');
    }
}
