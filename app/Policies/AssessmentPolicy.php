<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_assessment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_assessment');
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $user->can('update_assessment');
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->can('delete_assessment');
    }
}
