<?php

namespace App\Policies;

use App\Models\JobPosition;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobPositionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_job_position');
    }

    public function create(User $user): bool
    {
        return $user->can('create_job_position');
    }

    public function update(User $user, JobPosition $jobPosition): bool
    {
        return $user->can('update_job_position');
    }

    public function delete(User $user, JobPosition $jobPosition): bool
    {
        return $user->can('delete_job_position');
    }
}
