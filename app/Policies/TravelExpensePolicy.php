<?php

namespace App\Policies;

use App\Models\TravelExpense;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TravelExpensePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_travel_expense');
    }

    public function create(User $user): bool
    {
        return $user->can('create_travel_expense');
    }

    public function update(User $user, TravelExpense $travelExpense): bool
    {
        return $user->can('update_travel_expense');
    }

    public function delete(User $user, TravelExpense $travelExpense): bool
    {
        return $user->can('delete_travel_expense');
    }
}
