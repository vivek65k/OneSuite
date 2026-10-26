<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_attendance_record');
    }

    public function create(User $user): bool
    {
        return $user->can('create_attendance_record');
    }

    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->can('update_attendance_record');
    }

    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->can('delete_attendance_record');
    }
}
