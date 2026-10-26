<?php

namespace App\Policies;

use App\Models\TravelSettlement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TravelSettlementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_travel_settlement');
    }

    public function update(User $user, TravelSettlement $travelSettlement): bool
    {
        return $user->can('update_travel_settlement');
    }
}
