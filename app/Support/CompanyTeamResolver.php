<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

/**
 * Ties Spatie's "teams" feature to this app's own tenant concept - a role
 * (and its permission assignments) belongs to exactly one client, keyed
 * by company_id, the same column already used everywhere else. Without
 * this, every client would share and could edit the same global "Roles &
 * Permissions" list.
 *
 * Defaults to the logged-in user's own company_id, so ordinary requests
 * need no extra wiring. setPermissionsTeamId() is still available for the
 * few places that must act on a DIFFERENT company than the current
 * session's - chiefly the platform admin's "create client" flow, which
 * creates that new client's first role/user while the platform admin
 * themselves belongs to no company at all.
 */
class CompanyTeamResolver implements PermissionsTeamResolver
{
    protected int|string|null $teamId = null;

    protected bool $wasSetManually = false;

    public function setPermissionsTeamId($id): void
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        $this->teamId = $id;
        $this->wasSetManually = true;
    }

    public function getPermissionsTeamId(): int|string|null
    {
        if ($this->wasSetManually) {
            return $this->teamId;
        }

        return Auth::user()?->company_id;
    }
}
