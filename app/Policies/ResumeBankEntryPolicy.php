<?php

namespace App\Policies;

use App\Models\ResumeBankEntry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResumeBankEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_resume_bank_entry');
    }

    public function create(User $user): bool
    {
        return $user->can('create_resume_bank_entry');
    }

    public function update(User $user, ResumeBankEntry $resumeBankEntry): bool
    {
        return $user->can('update_resume_bank_entry');
    }

    public function delete(User $user, ResumeBankEntry $resumeBankEntry): bool
    {
        return $user->can('delete_resume_bank_entry');
    }
}
