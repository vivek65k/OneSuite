<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Everything a new client needs to be usable, in one transaction: the
 * company (Company::booted() adds its "Head Office" branch), its own
 * super_admin role with every permission, the ready-made HR Manager /
 * Manager / Employee roles, and the first admin login assigned to that
 * branch. Used by Platform > Clients > New client and by
 * `php artisan onesuite:install` on a fresh system.
 */
class ClientProvisioner
{
    /**
     * @param  array<string, mixed>  $company  Company attributes (name, email, phone, app_access, max_users)
     * @param  string|null  $adminPassword  null = an unusable random one, to be replaced through an invite link
     * @return array{0: Company, 1: User}
     */
    public static function create(array $company, string $adminName, string $adminEmail, ?string $adminPassword = null): array
    {
        return DB::transaction(function () use ($company, $adminName, $adminEmail, $adminPassword) {
            $client = Company::create($company + ['is_active' => true]);

            // Roles belong to exactly one client (Spatie "teams") - everything
            // from here on must be scoped to THIS new client, not to whoever
            // is running this (a platform admin belongs to no company at all).
            setPermissionsTeamId($client->id);

            $adminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'web', 'data_scope' => DataScope::ALL]);
            $adminRole->syncPermissions(Permission::pluck('name'));

            DefaultRoles::createFor($client->id);

            $admin = User::create([
                'company_id' => $client->id,
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => $adminPassword ?? Str::random(40),
                'is_active' => true,
                'is_super_admin' => true,
            ]);

            // A password chosen here (installer) is ready to use - there is no invite to accept.
            if ($adminPassword !== null) {
                $admin->forceFill(['invitation_accepted_at' => now(), 'email_verified_at' => now()])->save();
            }

            // Without the branch assignment every branch-scoped screen (starting
            // with the Users list itself) would filter the admin out of their own data.
            $admin->branches()->sync($client->branches()->pluck('branches.id'));

            return [$client, $admin];
        });
    }
}
