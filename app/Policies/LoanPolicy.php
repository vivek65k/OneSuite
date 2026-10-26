<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LoanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_loan');
    }

    public function create(User $user): bool
    {
        return $user->can('create_loan');
    }

    public function update(User $user, Loan $loan): bool
    {
        return $user->can('update_loan');
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $user->can('delete_loan');
    }
}
