<?php

namespace App\Support;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The three roles every client starts with, one per kind of data access,
 * so a new client's admin has something sensible to assign on day one
 * instead of an empty Roles screen:
 *
 *   hr_manager - all employees, every permission except user/role
 *                administration (that stays with the client's super admin)
 *   manager    - their team (the whole reporting chain below them)
 *   employee   - only themselves
 *
 * Manager and Employee start without admin permissions on purpose: the
 * Team and My self-service screens already cover them and need none. The
 * data access is what makes it safe for the client to add permissions to
 * those roles later - whatever they tick stays within the team / the person.
 */
class DefaultRoles
{
    public const ROLES = [
        'hr_manager' => DataScope::ALL,
        'manager' => DataScope::TEAM,
        'employee' => DataScope::OWN,
    ];

    /**
     * Idempotent - a role the client already has (and may have changed) is
     * left exactly as it is.
     */
    public static function createFor(int $companyId): void
    {
        foreach (self::ROLES as $name => $dataScope) {
            // Looked up by company explicitly: role names repeat across clients.
            if (Role::query()->where('company_id', $companyId)->where('name', $name)->where('guard_name', 'web')->exists()) {
                continue;
            }

            $role = Role::create(['name' => $name, 'guard_name' => 'web', 'company_id' => $companyId, 'data_scope' => $dataScope]);

            if ($name === 'hr_manager') {
                $role->syncPermissions(
                    Permission::where('guard_name', 'web')->get()
                        ->reject(fn (Permission $permission) => Str::endsWith($permission->name, ['_user', '_role']))
                );
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
