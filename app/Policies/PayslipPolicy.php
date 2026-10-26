<?php

namespace App\Policies;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayslipPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payslip');
    }

    public function update(User $user, Payslip $payslip): bool
    {
        return $user->can('update_payslip');
    }
}
