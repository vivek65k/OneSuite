<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CandidatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_candidate');
    }

    public function view(User $user, Candidate $candidate): bool
    {
        return $user->can('view_candidate');
    }

    public function create(User $user): bool
    {
        return $user->can('create_candidate');
    }

    public function update(User $user, Candidate $candidate): bool
    {
        return $user->can('update_candidate');
    }

    public function delete(User $user, Candidate $candidate): bool
    {
        return $user->can('delete_candidate');
    }
}
