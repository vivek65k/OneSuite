<?php

namespace App\Policies;

use App\Models\EsiSetting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EsiSettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_esi_setting');
    }

    public function update(User $user, EsiSetting $esiSetting): bool
    {
        return $user->can('update_esi_setting');
    }
}
