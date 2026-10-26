<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\SimpleNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

/**
 * Small wrapper around Laravel's notification system so approval-style
 * controllers (leave requests, expense claims, ...) don't each have to
 * work out "which users can act on this" by hand.
 */
class Notifier
{
    /**
     * Notify every user who holds the given permission (through any
     * role) - a super admin always qualifies regardless of permission.
     * Always limited to the acting user's own company - roles and the
     * super admin flag exist at every client, so without that filter
     * another client's admins would be notified too. When $branchId is
     * given, only holders belonging to that branch qualify (see
     * BranchContext::userInBranch()). When $employeeId is given - whose
     * request this is about - only holders whose role actually reaches
     * that employee are told (a "team" manager isn't notified about
     * someone outside their team), and never the employee themselves,
     * who can't review their own request anyway.
     */
    public static function notifyUsersWithPermission(
        string $permission,
        string $title,
        string $message,
        ?string $url = null,
        string $icon = 'ti ti-bell',
        ?int $branchId = null,
        ?string $app = null,
        ?int $employeeId = null,
    ): void {
        $permissionModel = Permission::where('name', $permission)->where('guard_name', 'web')->first();

        if (! $permissionModel) {
            return;
        }

        $roleIds = $permissionModel->roles()->pluck('roles.id');

        $companyId = Auth::user()?->company_id;

        if (! $companyId) {
            return;
        }

        $users = User::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('is_super_admin', true)->orWhereHas('roles', fn ($rq) => $rq->whereIn('roles.id', $roleIds)))
            ->when($branchId, fn ($q) => BranchContext::scopeUsersToBranch($q, $branchId, $companyId))
            ->get();

        if ($employeeId) {
            $users = $users->filter(fn (User $user) => $user->is_super_admin
                || (DataScope::employeeIdOf($user) !== $employeeId && DataScope::covers($user, $permission, $employeeId)));
        }

        if ($users->isNotEmpty()) {
            Notification::send($users, new SimpleNotification($title, $message, $url, $icon, $app));
        }
    }

    public static function notifyUser(?User $user, string $title, string $message, ?string $url = null, string $icon = 'ti ti-bell', ?string $app = null): void
    {
        $user?->notify(new SimpleNotification($title, $message, $url, $icon, $app));
    }
}
