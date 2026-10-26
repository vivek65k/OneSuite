<?php

namespace App\Policies;

use App\Models\TransferRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransferRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_transfer_request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_transfer_request');
    }

    public function update(User $user, TransferRequest $transferRequest): bool
    {
        return $user->can('update_transfer_request');
    }

    public function delete(User $user, TransferRequest $transferRequest): bool
    {
        return $user->can('delete_transfer_request');
    }
}
