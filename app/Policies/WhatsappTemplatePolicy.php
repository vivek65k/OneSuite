<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;

class WhatsappTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_whatsapp_template');
    }

    public function create(User $user): bool
    {
        return $user->can('create_whatsapp_template');
    }

    public function update(User $user, WhatsappTemplate $whatsappTemplate): bool
    {
        return $user->can('update_whatsapp_template');
    }

    public function delete(User $user, WhatsappTemplate $whatsappTemplate): bool
    {
        return $user->can('delete_whatsapp_template');
    }
}
