<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkflowPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_workflow');
    }

    public function create(User $user): bool
    {
        return $user->can('create_workflow');
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $user->can('update_workflow');
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->can('delete_workflow');
    }
}
