<?php

namespace App\Policies;

use App\Models\OnboardingTask;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OnboardingTaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_onboarding_task');
    }

    public function create(User $user): bool
    {
        return $user->can('create_onboarding_task');
    }

    public function update(User $user, OnboardingTask $onboardingTask): bool
    {
        return $user->can('update_onboarding_task');
    }

    public function delete(User $user, OnboardingTask $onboardingTask): bool
    {
        return $user->can('delete_onboarding_task');
    }
}
