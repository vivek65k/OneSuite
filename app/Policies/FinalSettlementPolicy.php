<?php

namespace App\Policies;

use App\Models\FinalSettlement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinalSettlementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_final_settlement');
    }

    public function create(User $user): bool
    {
        return $user->can('create_final_settlement');
    }

    public function update(User $user, FinalSettlement $finalSettlement): bool
    {
        return $user->can('update_final_settlement');
    }

    public function delete(User $user, FinalSettlement $finalSettlement): bool
    {
        return $user->can('delete_final_settlement');
    }
}
