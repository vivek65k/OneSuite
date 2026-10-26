<?php

namespace App\Policies;

use App\Models\Kpi;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class KpiPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_kpi');
    }

    public function create(User $user): bool
    {
        return $user->can('create_kpi');
    }

    public function update(User $user, Kpi $kpi): bool
    {
        return $user->can('update_kpi');
    }

    public function delete(User $user, Kpi $kpi): bool
    {
        return $user->can('delete_kpi');
    }
}
