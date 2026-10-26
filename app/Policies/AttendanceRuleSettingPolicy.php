<?php

namespace App\Policies;

use App\Models\AttendanceRuleSetting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceRuleSettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_attendance_rule_setting');
    }

    public function update(User $user, AttendanceRuleSetting $attendanceRuleSetting): bool
    {
        return $user->can('update_attendance_rule_setting');
    }
}
