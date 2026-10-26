<?php

namespace App\Policies;

use App\Models\AssessmentResult;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssessmentResultPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_assessment_result');
    }

    public function create(User $user): bool
    {
        return $user->can('create_assessment_result');
    }

    public function update(User $user, AssessmentResult $assessmentResult): bool
    {
        return $user->can('update_assessment_result');
    }

    public function delete(User $user, AssessmentResult $assessmentResult): bool
    {
        return $user->can('delete_assessment_result');
    }
}
