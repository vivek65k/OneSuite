<?php

namespace App\Policies;

use App\Models\ApprovalRule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApprovalRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_approval_rule');
    }

    public function create(User $user): bool
    {
        return $user->can('create_approval_rule');
    }

    public function update(User $user, ApprovalRule $approvalRule): bool
    {
        return $user->can('update_approval_rule');
    }

    public function delete(User $user, ApprovalRule $approvalRule): bool
    {
        return $user->can('delete_approval_rule');
    }
}
