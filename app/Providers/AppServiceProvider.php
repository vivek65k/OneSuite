<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\RolePolicy;
use App\Support\DataScope;
use App\Support\Mail\OAuthMailTransport;
use App\Support\SidebarAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.bootstrap-5-custom');

        Gate::before(fn (User $user) => $user->is_super_admin ? true : null);

        // Data access (all / team / own - see App\Support\DataScope): any
        // check against one saved employee-linked record - authorize('update',
        // $leaveRequest), @can('delete', $payslip) - is refused when that
        // record's employee is outside what the role granting the permission
        // reaches. Registered after the super admin rule above so it never
        // applies to them; a blank record (how "may I do this at all?" is
        // asked for the sidebar) has nobody to check and is left to the policy.
        Gate::before(function (User $user, string $ability, array $arguments = []) {
            $record = $arguments[0] ?? null;

            if (! $record instanceof Model || ! $record->exists || ! method_exists($record, 'dataScopeEmployeeId')) {
                return null;
            }

            $permission = DataScope::permissionFor($ability, $record);

            return $permission === null || DataScope::permits($user, $permission, $record) ? null : false;
        });

        Gate::policy(Role::class, RolePolicy::class);

        // Built per client by App\Support\Mail\CompanyMailer.
        Mail::extend('oauth-mail', fn (array $config) => new OAuthMailTransport($config['account']));

        // @navcan('admin.employees.index') ... @endnavcan - sidebar links only for screens the user may open.
        Blade::if('navcan', fn (string $routeName) => SidebarAccess::allows($routeName));
    }
}
