<?php

namespace App\Policies;

use App\Models\PfSetting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PfSettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_pf_setting');
    }

    public function update(User $user, PfSetting $pfSetting): bool
    {
        return $user->can('update_pf_setting');
    }
}
