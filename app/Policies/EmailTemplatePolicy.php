<?php

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmailTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_email_template');
    }

    public function create(User $user): bool
    {
        return $user->can('create_email_template');
    }

    public function update(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->can('update_email_template');
    }

    public function delete(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->can('delete_email_template');
    }
}
