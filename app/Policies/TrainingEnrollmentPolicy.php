<?php

namespace App\Policies;

use App\Models\TrainingEnrollment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainingEnrollmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_training_enrollment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_training_enrollment');
    }

    public function update(User $user, TrainingEnrollment $trainingEnrollment): bool
    {
        return $user->can('update_training_enrollment');
    }

    public function delete(User $user, TrainingEnrollment $trainingEnrollment): bool
    {
        return $user->can('delete_training_enrollment');
    }
}
