<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Resolves branch-level data scoping for the currently authenticated user.
 *
 * Every authenticated user is always scoped to exactly one branch - there is
 * no "see every branch at once" mode, including for the super admin. A user
 * assigned to one or more branches picks among those; a super admin (who has
 * no assignments of their own) picks among every branch in the company. In
 * both cases the switcher persists the choice in session and defaults to the
 * first available branch when nothing has been chosen yet.
 */
class BranchContext
{
    protected const SESSION_KEY = 'current_branch_id';

    public static function assignedBranches(): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return $user->branches()->orderBy('name')->get();
    }

    /**
     * Branches the switcher offers, and the universe currentBranchId() picks
     * from. A restricted user only ever picks among their own assignments; a
     * super admin (who has none) picks among every branch in the company.
     * Anyone else with no assignment is held to one branch - the one their
     * own employee record belongs to, else the company's default branch -
     * otherwise they'd have nothing to scope to, isRestricted() would be
     * false, and every branch filter would be skipped (seeing ALL branches).
     */
    public static function switchableBranches(): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        // Every scoped query asks for this; resolve it once per request and user.
        $key = 'onesuite.branches.'.$user->getKey();
        $attributes = app()->bound('request') ? request()->attributes : null;
        if ($attributes?->has($key)) {
            return $attributes->get($key);
        }

        $branches = static::resolveSwitchableBranches($user);
        $attributes?->set($key, $branches);

        return $branches;
    }

    protected static function resolveSwitchableBranches(User $user): Collection
    {
        $assigned = static::assignedBranches();

        if ($assigned->isNotEmpty()) {
            return $assigned;
        }

        if ($user->is_super_admin) {
            return Branch::query()->where('company_id', Company::current()->id)->orderBy('name')->get();
        }

        // company_id, not Company::current() - that aborts for platform
        // admins, who have no company and so no branch at all.
        if (! $user->company_id) {
            return collect();
        }

        return collect([static::fallbackBranchFor($user)])->filter()->values();
    }

    /**
     * The single branch an unassigned, non-super-admin user is held to.
     */
    public static function fallbackBranchFor(User $user): ?Branch
    {
        $employeeBranchId = $user->employee?->branch_id;

        if ($employeeBranchId) {
            $branch = Branch::query()
                ->where('company_id', $user->company_id)
                ->find($employeeBranchId);

            if ($branch) {
                return $branch;
            }
        }

        return static::defaultBranchFor($user->company_id);
    }

    /**
     * Whether $user counts as belonging to $branchId - the exact mirror of
     * switchableBranches(): assigned users belong to their assigned
     * branches, an unassigned super admin to every branch, and anyone else
     * unassigned to their fallback branch only. Used wherever "who is in
     * this branch" matters (Users list, UserPolicy, Notifier) so that it
     * always agrees with what each user can actually see.
     */
    public static function userInBranch(User $user, int $branchId): bool
    {
        if ($user->branches()->exists()) {
            return $user->branches()->where('branches.id', $branchId)->exists();
        }

        if ($user->is_super_admin) {
            return true;
        }

        return static::fallbackBranchFor($user)?->id === $branchId;
    }

    /**
     * Query form of userInBranch(), for filtering a users query in SQL.
     * $companyId is the company the branch (and the users) belong to.
     */
    public static function scopeUsersToBranch(Builder $query, int $branchId, int $companyId): Builder
    {
        $companyBranchIds = Branch::query()->where('company_id', $companyId)->select('id');
        $isDefaultBranch = static::defaultBranchFor($companyId)?->id === $branchId;

        return $query->where(fn ($q) => $q
            // Assigned to this branch.
            ->whereHas('branches', fn ($b) => $b->where('branches.id', $branchId))
            // Unassigned super admin - every branch.
            ->orWhere(fn ($q) => $q->whereDoesntHave('branches')->where('is_super_admin', true))
            // Unassigned anyone else - their fallback branch: their employee
            // record's branch, else (no usable one) the default branch.
            ->orWhere(fn ($q) => $q->whereDoesntHave('branches')->where('is_super_admin', false)
                ->where(fn ($q) => $q
                    ->whereHas('employee', fn ($e) => $e->where('branch_id', $branchId))
                    ->when($isDefaultBranch, fn ($q) => $q->orWhereDoesntHave(
                        'employee', fn ($e) => $e->whereIn('branch_id', $companyBranchIds)
                    )))));
    }

    /**
     * The company's default branch (is_default - Branch keeps exactly one),
     * falling back to its oldest branch if none is flagged.
     */
    public static function defaultBranchFor(int $companyId): ?Branch
    {
        return Branch::query()
            ->where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->oldest('id')
            ->first();
    }

    /**
     * True whenever there's a branch to scope to - i.e. for every signed-in
     * user, as long as the company has at least one branch. The only false
     * case left is a company with zero branches defined yet.
     */
    public static function isRestricted(): bool
    {
        return Auth::check() && static::switchableBranches()->isNotEmpty();
    }

    public static function currentBranchId(): ?int
    {
        if (! static::isRestricted()) {
            return null;
        }

        $switchable = static::switchableBranches();
        $stored = Session::get(self::SESSION_KEY);

        if ($stored && $switchable->contains('id', $stored)) {
            return (int) $stored;
        }

        return $switchable->first()?->id;
    }

    public static function currentBranch(): ?Branch
    {
        $id = static::currentBranchId();

        return $id ? static::switchableBranches()->firstWhere('id', $id) : null;
    }

    public static function setCurrentBranch(int $branchId): void
    {
        if (static::switchableBranches()->contains('id', $branchId)) {
            Session::put(self::SESSION_KEY, $branchId);
        }
    }
}
