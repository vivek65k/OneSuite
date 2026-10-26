<?php

namespace App\Policies;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TravelRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_travel_request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_travel_request');
    }

    public function update(User $user, TravelRequest $travelRequest): bool
    {
        return $user->can('update_travel_request');
    }

    public function delete(User $user, TravelRequest $travelRequest): bool
    {
        return $user->can('delete_travel_request');
    }
}
