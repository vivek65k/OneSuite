<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Whether the logged-in user may open the screen behind a sidebar link, so
 * the menu never offers a page that would only answer 403. The permission
 * each route needs lives in config/sidebar_access.php.
 */
class SidebarAccess
{
    public static function allows(string $routeName): bool
    {
        $user = Auth::user();

        // Self service needs no permission, but it only has something to
        // show the right people: "My ..." screens need the login to be
        // linked to an employee record, "Team ..." screens need someone
        // reporting to that employee (anywhere down the chain).
        if (str_starts_with($routeName, 'admin.my-') || str_starts_with($routeName, 'admin.team-')) {
            $employeeId = $user ? DataScope::employeeIdOf($user) : null;

            if ($employeeId === null) {
                return false;
            }

            return str_starts_with($routeName, 'admin.my-') || ReportingChain::hasReports($employeeId, (int) $user->company_id);
        }

        // Route names contain dots, so index the array rather than using config()'s dot syntax.
        $rule = config('sidebar_access')[$routeName] ?? null;

        // Not permission-gated (dashboards): always listed.
        if ($rule === null) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if (is_string($rule)) {
            return $user->can($rule);
        }

        [$ability, $model] = $rule;

        // viewAny / create take the class; anything else is checked against a blank record, as the controllers do.
        return $user->can($ability, in_array($ability, ['viewAny', 'create'], true) ? $model : new $model());
    }
}
