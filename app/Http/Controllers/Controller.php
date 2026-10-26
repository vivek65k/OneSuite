<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Support\DataScope;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    use AuthorizesRequests {
        authorize as protected authorizeAtGate;
    }

    /**
     * The usual policy check, plus one thing: once it passes, the rest of
     * the request works within the data access (all / team / own) of the
     * permission that was just checked - see App\Support\DataScope. Every
     * admin screen starts with an authorize() call, so this one hook is
     * what limits its lists, dropdowns, totals, exports and saves.
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return \Illuminate\Auth\Access\Response
     */
    public function authorize($ability, $arguments = [])
    {
        $response = $this->authorizeAtGate($ability, $arguments);

        DataScope::enterScreen($ability, $arguments);

        return $response;
    }

    /**
     * Nobody approves, rejects or pays out their own request, however far
     * their role reaches - with "own" or "team" access a person's own
     * requests sit in the same list as the ones they may act on. The super
     * admin is exempt, matching the checks already on loan and promotion
     * requests (a one-person company still has to be able to function).
     */
    protected function denyOwnRequest(?Employee $employee): void
    {
        $user = Auth::user();

        abort_if(
            $user && ! $user->is_super_admin && $employee && (int) $employee->user_id === (int) $user->id,
            403,
            "You can't review your own request - it has to be handled by someone else.",
        );
    }
}
