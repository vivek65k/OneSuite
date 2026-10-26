<?php

namespace App\Policies;

use App\Models\Interview;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InterviewPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_interview');
    }

    public function create(User $user): bool
    {
        return $user->can('create_interview');
    }

    public function update(User $user, Interview $interview): bool
    {
        return $user->can('update_interview');
    }

    public function delete(User $user, Interview $interview): bool
    {
        return $user->can('delete_interview');
    }
}
