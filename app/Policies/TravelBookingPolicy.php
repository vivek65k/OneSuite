<?php

namespace App\Policies;

use App\Models\TravelBooking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TravelBookingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_travel_booking');
    }

    public function create(User $user): bool
    {
        return $user->can('create_travel_booking');
    }

    public function update(User $user, TravelBooking $travelBooking): bool
    {
        return $user->can('update_travel_booking');
    }

    public function delete(User $user, TravelBooking $travelBooking): bool
    {
        return $user->can('delete_travel_booking');
    }
}
