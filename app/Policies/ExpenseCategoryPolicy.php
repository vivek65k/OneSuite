<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpenseCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_expense_category');
    }

    public function create(User $user): bool
    {
        return $user->can('create_expense_category');
    }

    public function update(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->can('update_expense_category');
    }

    public function delete(User $user, ExpenseCategory $expenseCategory): bool
    {
        return $user->can('delete_expense_category');
    }
}
