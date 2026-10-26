<?php

namespace App\Policies;

use App\Models\LoanRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LoanRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_loan_request');
    }

    public function create(User $user): bool
    {
        return $user->can('create_loan_request');
    }

    public function update(User $user, LoanRequest $loanRequest): bool
    {
        return $user->can('update_loan_request');
    }

    public function delete(User $user, LoanRequest $loanRequest): bool
    {
        return $user->can('delete_loan_request');
    }
}
