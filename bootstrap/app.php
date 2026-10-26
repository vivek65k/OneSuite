<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // No URL prefix here: each app group inside routes/admin.php sets
            // its own URI prefix (settings/hrm/crm/...) via ['app' => '{key}',
            // 'prefix' => '{key}']. The "admin." route-NAME prefix stays fixed
            // so every route()/redirect()->route() call in the app is unaffected
            // by URL/app-folder changes — only the "app" action tag and prefix
            // decide the URL and sidebar, per App\Support\AppLauncher.
            Route::middleware(['web', 'auth', 'active', 'app_access'])
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            // Separate from every tenant's own app routes above - manages
            // the client roster itself, not any one client's data, so it
            // skips 'app_access' entirely (a tenant app concept, meaningless
            // here) and gates on 'platform_admin' instead.
            Route::middleware(['web', 'auth', 'active', 'platform_admin'])
                ->prefix('platform')
                ->name('platform.')
                ->group(base_path('routes/platform.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'app_access' => \App\Http\Middleware\EnsureUserHasAppAccess::class,
            'platform_admin' => \App\Http\Middleware\EnsurePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
