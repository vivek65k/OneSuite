<?php

namespace App\Policies;

use App\Models\Feedback360;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class Feedback360Policy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_feedback_360');
    }

    public function create(User $user): bool
    {
        return $user->can('create_feedback_360');
    }

    public function delete(User $user, Feedback360 $feedback360): bool
    {
        return $user->can('delete_feedback_360');
    }
}
