<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class AppLauncher
{
    /**
     * When a user has no explicit default_app, this decides which
     * accessible app they land on instead - HRM first, since it's the
     * one fully-built, daily-use app almost every account belongs in.
     * Settings is an occasional admin/config area, not a sensible "home"
     * for a regular HR admin or employee account, so it only wins when
     * HRM isn't accessible to that particular user. Independent of
     * config('onesuite_apps.apps') key order, which only controls
     * app-launcher display order.
     */
    protected const PREFERRED_HOME_ORDER = ['hrm', 'settings', 'crm', 'inventory', 'sales'];

    public static function apps(): array
    {
        return config('onesuite_apps.apps', []);
    }

    /**
     * Only the apps the current client (Company::current()) has been
     * given - what a client admin may hand out to their own users, and
     * which apps' permissions their roles can use. Offering anything
     * else would be meaningless: hasAccess() below always blocks an app
     * the client doesn't have, whatever the user or role says.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function companyApps(): array
    {
        $company = Company::current();

        return array_filter(
            static::apps(),
            fn ($key) => $company->hasApp($key),
            ARRAY_FILTER_USE_KEY
        );
    }

    public static function currentKey(): string
    {
        $app = Request::route()?->getAction('app');

        if (is_string($app) && array_key_exists($app, static::apps())) {
            return $app;
        }

        return 'settings';
    }

    /**
     * A null app_access means unrestricted (every app) - the default for
     * every existing user, and future-proof against new apps being added
     * later without having to backfill everyone's access list. A super
     * admin bypasses their own USER-level restriction, but never their
     * CLIENT's - a company subscribed to Settings+HRM only doesn't get
     * CRM just because its super admin asks for it. Checked first and
     * unconditionally, so it can't be bypassed by any user-level flag.
     *
     * Platform admins (company_id null) aren't tied to any client and
     * never use a tenant app at all - only the separate /platform area.
     */
    public static function hasAccess(User $user, string $key): bool
    {
        if (! array_key_exists($key, static::apps())) {
            return false;
        }

        if ($user->is_platform_admin || ! $user->company_id) {
            return false;
        }

        if (! Company::find($user->company_id)?->hasApp($key)) {
            return false;
        }

        return $user->is_super_admin || $user->app_access === null || in_array($key, $user->app_access, true);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function accessibleApps(User $user): array
    {
        return array_filter(
            static::apps(),
            fn ($key) => static::hasAccess($user, $key),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Which app this user lands on right after login or when opening the
     * app launcher without picking a specific app - their chosen
     * default_app if it's still one they can access, otherwise the most
     * sensible accessible app per PREFERRED_HOME_ORDER.
     */
    public static function defaultAppKey(User $user): string
    {
        $accessible = static::accessibleApps($user);

        if ($user->default_app && array_key_exists($user->default_app, $accessible)) {
            return $user->default_app;
        }

        return static::preferredKey(array_keys($accessible)) ?? 'settings';
    }

    /**
     * The best "home app" pick from a given set of app keys, per
     * PREFERRED_HOME_ORDER - shared by defaultAppKey() above and by the
     * Users create/edit form, which needs the same preference to choose
     * a sensible initial "Default landing app" selection for a user who
     * hasn't had one explicitly set yet.
     *
     * @param  array<string>  $keys
     */
    public static function preferredKey(array $keys): ?string
    {
        foreach (self::PREFERRED_HOME_ORDER as $key) {
            if (in_array($key, $keys, true)) {
                return $key;
            }
        }

        return $keys[0] ?? null;
    }

    public static function defaultRouteName(User $user): string
    {
        $key = static::defaultAppKey($user);

        return static::apps()[$key]['route'] ?? 'admin.dashboard';
    }

    /**
     * Where a freshly-authenticated user belongs - shared by the login
     * flow and the invitation-accept flow so "where does this user go"
     * has exactly one answer instead of two copies drifting apart.
     */
    public static function homeRoute(User $user): string
    {
        return $user->is_platform_admin
            ? route('platform.clients.index')
            : route(static::defaultRouteName($user));
    }
}
