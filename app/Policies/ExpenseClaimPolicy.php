<?php

namespace App\Policies;

use App\Models\ExpenseClaim;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpenseClaimPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_expense_claim');
    }

    public function create(User $user): bool
    {
        return $user->can('create_expense_claim');
    }

    public function update(User $user, ExpenseClaim $expenseClaim): bool
    {
        return $user->can('update_expense_claim');
    }

    public function delete(User $user, ExpenseClaim $expenseClaim): bool
    {
        return $user->can('delete_expense_claim');
    }
}
