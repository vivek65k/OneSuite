<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WfhRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class WfhRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_wfh_request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_wfh_request');
    }

    public function update(User $user, WfhRequest $wfhRequest): bool
    {
        return $user->can('update_wfh_request');
    }

    public function delete(User $user, WfhRequest $wfhRequest): bool
    {
        return $user->can('delete_wfh_request');
    }
}
