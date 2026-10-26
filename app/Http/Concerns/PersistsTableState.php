<?php

namespace App\Http\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait PersistsTableState
{
    /**
     * Resolve the current index-page filter/sort state, persisting it in the
     * session (mirroring Filament's persistFiltersInSession()/persistSortInSession()).
     *
     * If the request carries no query string at all, we fall back to whatever
     * was last stored for this module and redirect so the URL reflects it.
     * If the request does carry query params, they become the new stored state.
     *
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>|RedirectResponse
     */
    protected function resolveTableState(Request $request, string $module, array $defaults = []): array|RedirectResponse
    {
        $sessionKey = "admin.table.{$module}";

        if ($request->query() === []) {
            $stored = session($sessionKey);

            if ($stored) {
                return redirect()->to($request->url().'?'.http_build_query($stored));
            }

            return $defaults;
        }

        $state = array_merge($defaults, $request->query());
        session([$sessionKey => $state]);

        return $state;
    }

    protected function clampPerPage(mixed $value, int $default = 25): int
    {
        $value = (int) $value;

        return in_array($value, [10, 25, 50, 100], true) ? $value : $default;
    }
}
