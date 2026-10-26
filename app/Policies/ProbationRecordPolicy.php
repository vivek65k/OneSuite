<?php

namespace App\Policies;

use App\Models\ProbationRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProbationRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_probation_record');
    }

    public function create(User $user): bool
    {
        return $user->can('create_probation_record');
    }

    public function update(User $user, ProbationRecord $probationRecord): bool
    {
        return $user->can('update_probation_record');
    }

    public function delete(User $user, ProbationRecord $probationRecord): bool
    {
        return $user->can('delete_probation_record');
    }
}
