<?php

namespace App\Policies;

use App\Models\NotificationRule;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_notification_rule');
    }

    public function create(User $user): bool
    {
        return $user->can('create_notification_rule');
    }

    public function update(User $user, NotificationRule $notificationRule): bool
    {
        return $user->can('update_notification_rule');
    }

    public function delete(User $user, NotificationRule $notificationRule): bool
    {
        return $user->can('delete_notification_rule');
    }
}
