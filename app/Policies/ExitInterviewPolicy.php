<?php

namespace App\Policies;

use App\Models\ExitInterview;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExitInterviewPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_exit_interview');
    }

    public function create(User $user): bool
    {
        return $user->can('create_exit_interview');
    }

    public function update(User $user, ExitInterview $exitInterview): bool
    {
        return $user->can('update_exit_interview');
    }

    public function delete(User $user, ExitInterview $exitInterview): bool
    {
        return $user->can('delete_exit_interview');
    }
}
