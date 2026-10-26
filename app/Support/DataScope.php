<?php

namespace App\Support;

use App\Models\BusinessUnit;
use App\Models\CostCenter;
use App\Models\Employee;
use App\Models\Feedback360;
use App\Models\User;
use ArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * "Whose data" - the second half of a role, next to its permissions.
 *
 * A permission says WHAT someone may do (open Leave Requests, approve one);
 * the role's data access (roles.data_scope) says for WHOM:
 *
 *   all  - everyone in the branch they are working in (HR, administrators)
 *   team - themselves and everyone below them in the reporting chain
 *   own  - only their own records
 *
 * Someone holding several roles gets, for each permission, the widest
 * access among the roles that actually grant that permission - an "HR
 * Executive" role (all, recruitment) plus a "Manager" role (team, leave)
 * means all candidates but only the team's leave.
 *
 * Enforcement is central rather than per controller:
 *
 *   - Controller::authorize() calls enterScreen(), which fixes the access
 *     for the rest of the request from the permission just checked;
 *   - every employee-linked model (ScopedByEmployee) then filters its own
 *     queries and refuses writes outside that access;
 *   - a Gate::before in AppServiceProvider refuses single records outside
 *     it (so @can('update', $record) hides the buttons too).
 *
 * The My/Team self-service screens never call authorize(), so nothing here
 * touches them - they keep their own "my record" / "my chain" filters.
 */
class DataScope
{
    public const ALL = 'all';

    public const TEAM = 'team';

    public const OWN = 'own';

    /** Shown on the role form, widest first. */
    public const OPTIONS = [
        self::ALL => [
            'label' => 'All employees',
            'icon' => 'ti ti-building',
            'description' => 'Everyone in the branch they are working in. For HR and administrators.',
        ],
        self::TEAM => [
            'label' => 'Their team',
            'icon' => 'ti ti-users',
            'description' => 'Themselves and everyone below them in the reporting chain. For managers.',
        ],
        self::OWN => [
            'label' => 'Only themselves',
            'icon' => 'ti ti-user',
            'description' => 'Just their own records. For employees.',
        ],
    ];

    protected const RANK = [self::OWN => 1, self::TEAM => 2, self::ALL => 3];

    /** Policy ability => permission-name prefix (the Filament Shield naming every policy here uses). */
    protected const ABILITIES = [
        'viewAny' => 'view_any_',
        'view' => 'view_',
        'create' => 'create_',
        'update' => 'update_',
        'delete' => 'delete_',
        'deleteAny' => 'delete_any_',
        'restore' => 'restore_',
        'restoreAny' => 'restore_any_',
        'forceDelete' => 'force_delete_',
        'forceDeleteAny' => 'force_delete_any_',
        'replicate' => 'replicate_',
        'reorder' => 'reorder_',
    ];

    /** Models whose permission key isn't simply the snake_case class name. */
    protected const RESOURCE_KEYS = [
        BusinessUnit::class => 'business::unit',
        CostCenter::class => 'cost::center',
        Feedback360::class => 'feedback_360',
    ];

    /**
     * The permission a policy ability checks for a model, e.g.
     * ('update', LeaveRequest) => 'update_leave_request'.
     */
    public static function permissionFor(string $ability, mixed $model): ?string
    {
        $prefix = self::ABILITIES[$ability] ?? null;
        $class = is_object($model) ? $model::class : $model;

        if (! $prefix || ! is_string($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        return $prefix.(self::RESOURCE_KEYS[$class] ?? Str::snake(class_basename($class)));
    }

    /**
     * How far $permission reaches for this user: the widest data access
     * among their roles that grant it. No role granting it (a permission
     * given some other way, or not held at all) is the narrowest, 'own'.
     */
    public static function scopeFor(User $user, string $permission): string
    {
        if ($user->is_super_admin) {
            return self::ALL;
        }

        $memo = self::bag();
        $key = "scope.{$user->id}.{$permission}";

        if (! isset($memo[$key])) {
            $scopes = $user->roles()
                ->whereHas('permissions', fn ($q) => $q->where('permissions.name', $permission))
                ->pluck('roles.data_scope');

            $memo[$key] = $scopes->reduce(
                fn (string $widest, $scope) => (self::RANK[$scope] ?? 0) > self::RANK[$widest] ? $scope : $widest,
                self::OWN,
            );
        }

        return $memo[$key];
    }

    /**
     * The employees a data access covers for this user - null means
     * "no limit" (all). An empty list is a real answer: a team/own role on
     * a login that isn't linked to an employee record covers nobody.
     *
     * @return array<int, int>|null
     */
    public static function employeeIdsFor(User $user, string $scope): ?array
    {
        if ($scope === self::ALL) {
            return null;
        }

        $memo = self::bag();
        $key = "ids.{$user->id}.{$scope}";

        if (! isset($memo[$key])) {
            $own = self::employeeIdOf($user);

            $memo[$key] = match (true) {
                $own === null => [],
                $scope === self::TEAM => array_merge([$own], ReportingChain::below($own, (int) $user->company_id)),
                default => [$own],
            };
        }

        return $memo[$key];
    }

    /**
     * The user's own employee record at their own company - looked up with
     * the query builder so it is never filtered by the very access it is
     * being used to work out.
     */
    public static function employeeIdOf(User $user): ?int
    {
        if (! $user->company_id) {
            return null;
        }

        $memo = self::bag();
        $key = "employee.{$user->id}";

        if (! $memo->offsetExists($key)) {
            $id = DB::table('employees')->where('user_id', $user->id)->where('company_id', $user->company_id)->value('id');
            $memo[$key] = $id === null ? null : (int) $id;
        }

        return $memo[$key];
    }

    /** Whether $permission, as this user holds it, reaches the given employee. */
    public static function covers(User $user, string $permission, ?int $employeeId): bool
    {
        $ids = self::employeeIdsFor($user, self::scopeFor($user, $permission));

        return $ids === null || ($employeeId !== null && in_array($employeeId, $ids, true));
    }

    /** covers() for a record of any employee-linked model. */
    public static function permits(User $user, string $permission, Model $record): bool
    {
        if (self::scopeFor($user, $permission) === self::ALL) {
            return true;
        }

        return self::covers($user, $permission, $record->dataScopeEmployeeId());
    }

    /**
     * Called from Controller::authorize() once the check has passed: the
     * rest of this request (lists, dropdowns, totals, exports, saves) now
     * works within the access of the permission that let the user in.
     */
    public static function enterScreen(mixed $ability, mixed $arguments): void
    {
        $user = Auth::user();
        $subject = is_array($arguments) ? ($arguments[0] ?? null) : $arguments;
        $permission = is_string($ability) && $subject ? self::permissionFor($ability, $subject) : null;

        if (! $user || ! $permission) {
            return;
        }

        self::bag()['screen'] = self::employeeIdsFor($user, self::scopeFor($user, $permission)) ?? false;

        self::checkRouteRecords();
    }

    /**
     * The employees the current screen is limited to, or null when it
     * isn't limited: no screen entered (self-service, console, jobs) or
     * the permission behind it has "all" access.
     *
     * @return array<int, int>|null
     */
    public static function activeEmployeeIds(): ?array
    {
        $screen = self::bag()['screen'] ?? false;

        return $screen === false ? null : $screen;
    }

    /**
     * Run $callback with the screen's limit lifted - for the rare step that
     * must legitimately touch someone outside it (e.g. the other side of an
     * approved shift swap), after the action itself has been authorised.
     */
    public static function unrestricted(callable $callback): mixed
    {
        $memo = self::bag();
        $screen = $memo['screen'] ?? false;
        $memo['screen'] = false;

        try {
            return $callback();
        } finally {
            $memo['screen'] = $screen;
        }
    }

    /**
     * Refuse creating, changing or deleting an employee-linked record for
     * someone outside the current screen's access - what stops a tampered
     * employee_id on a form from reaching past the role.
     */
    public static function guardWrite(Model $record): void
    {
        $ids = self::activeEmployeeIds();

        if ($ids === null) {
            return;
        }

        // A brand-new employee has no id yet: they count as "in the team"
        // when the person they will report to is.
        $employeeId = $record instanceof Employee && ! $record->exists
            ? ($record->manager_id === null ? null : (int) $record->manager_id)
            : $record->dataScopeEmployeeId();

        abort_unless(
            $employeeId !== null && in_array($employeeId, $ids, true),
            403,
            'This record is outside the data your role can access.',
        );
    }

    /**
     * Route-bound records are loaded before the controller runs, i.e.
     * before any screen is entered, so they bypass the query filter - check
     * them here. 404 rather than 403, matching the app's convention of not
     * confirming that a record you can't see exists.
     */
    protected static function checkRouteRecords(): void
    {
        $ids = self::activeEmployeeIds();

        if ($ids === null) {
            return;
        }

        foreach (request()->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model && $parameter->exists && method_exists($parameter, 'dataScopeEmployeeId')) {
                $employeeId = $parameter->dataScopeEmployeeId();

                abort_unless($employeeId !== null && in_array($employeeId, $ids, true), 404);
            }
        }
    }

    /**
     * Per-request memory, kept on the request itself so it can never leak
     * from one request (or one test) into the next.
     */
    protected static function bag(): ArrayObject
    {
        $attributes = request()->attributes;

        if (! $attributes->has('data_scope')) {
            $attributes->set('data_scope', new ArrayObject());
        }

        return $attributes->get('data_scope');
    }
}
