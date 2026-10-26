<?php

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollRunPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payroll_run');
    }

    public function create(User $user): bool
    {
        return $user->can('create_payroll_run');
    }

    public function update(User $user, PayrollRun $payrollRun): bool
    {
        return $user->can('update_payroll_run');
    }
}
