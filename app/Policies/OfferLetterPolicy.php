<?php

namespace App\Policies;

use App\Models\OfferLetter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfferLetterPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_offer_letter');
    }

    public function create(User $user): bool
    {
        return $user->can('create_offer_letter');
    }

    public function update(User $user, OfferLetter $offerLetter): bool
    {
        return $user->can('update_offer_letter');
    }

    public function delete(User $user, OfferLetter $offerLetter): bool
    {
        return $user->can('delete_offer_letter');
    }
}
