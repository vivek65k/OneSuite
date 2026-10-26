<?php

namespace App\Policies;

use App\Models\RegularizationRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegularizationRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_regularization_request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_regularization_request');
    }

    public function update(User $user, RegularizationRequest $regularizationRequest): bool
    {
        return $user->can('update_regularization_request');
    }

    public function delete(User $user, RegularizationRequest $regularizationRequest): bool
    {
        return $user->can('delete_regularization_request');
    }
}
