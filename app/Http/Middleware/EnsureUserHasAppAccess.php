<?php

namespace App\Http\Middleware;

use App\Support\AppLauncher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAppAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Untagged routes (e.g. the branch switcher, which every app's
        // sidebar can trigger) aren't owned by any single app - only
        // routes explicitly tagged ['app' => '{key}'] are gated.
        $appKey = $request->route()?->getAction('app');

        if ($user && is_string($appKey) && ! AppLauncher::hasAccess($user, $appKey)) {
            return redirect()->route(AppLauncher::defaultRouteName($user))
                ->with('status', "You don't have access to that app.");
        }

        return $next($request);
    }
}
