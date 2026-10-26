<?php

namespace App\Policies;

use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SmsTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_sms_template');
    }

    public function create(User $user): bool
    {
        return $user->can('create_sms_template');
    }

    public function update(User $user, SmsTemplate $smsTemplate): bool
    {
        return $user->can('update_sms_template');
    }

    public function delete(User $user, SmsTemplate $smsTemplate): bool
    {
        return $user->can('delete_sms_template');
    }
}
