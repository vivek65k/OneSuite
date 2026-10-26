<?php

namespace App\Policies;

use App\Models\EscalationRule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EscalationRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_escalation_rule');
    }

    public function create(User $user): bool
    {
        return $user->can('create_escalation_rule');
    }

    public function update(User $user, EscalationRule $escalationRule): bool
    {
        return $user->can('update_escalation_rule');
    }

    public function delete(User $user, EscalationRule $escalationRule): bool
    {
        return $user->can('delete_escalation_rule');
    }
}
