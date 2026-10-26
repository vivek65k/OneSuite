<?php

namespace App\Policies;

use App\Models\Appraisal;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppraisalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_appraisal');
    }

    public function create(User $user): bool
    {
        return $user->can('create_appraisal');
    }

    public function update(User $user, Appraisal $appraisal): bool
    {
        return $user->can('update_appraisal');
    }

    public function delete(User $user, Appraisal $appraisal): bool
    {
        return $user->can('delete_appraisal');
    }
}
