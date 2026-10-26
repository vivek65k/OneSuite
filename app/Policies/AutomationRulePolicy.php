<?php

namespace App\Policies;

use App\Models\AutomationRule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AutomationRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_automation_rule');
    }

    public function create(User $user): bool
    {
        return $user->can('create_automation_rule');
    }

    public function update(User $user, AutomationRule $automationRule): bool
    {
        return $user->can('update_automation_rule');
    }

    public function delete(User $user, AutomationRule $automationRule): bool
    {
        return $user->can('delete_automation_rule');
    }
}
