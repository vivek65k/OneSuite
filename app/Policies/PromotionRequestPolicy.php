<?php

namespace App\Policies;

use App\Models\PromotionRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PromotionRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_promotion_request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_promotion_request');
    }

    public function update(User $user, PromotionRequest $promotionRequest): bool
    {
        return $user->can('update_promotion_request');
    }

    public function delete(User $user, PromotionRequest $promotionRequest): bool
    {
        return $user->can('delete_promotion_request');
    }
}
