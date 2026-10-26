<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Who reports to whom, read from employees.manager_id ("Reports to" on the
 * employee form). "Team" everywhere in the app - the Team self-service
 * screens and the Team data access on roles - means the whole chain below
 * a manager, not just the people reporting to them directly: a department
 * head also sees their managers' teams.
 */
class ReportingChain
{
    /**
     * IDs of every employee below $employeeId in the chain (direct reports,
     * their reports, and so on) - never including $employeeId itself.
     *
     * @return array<int, int>
     */
    public static function below(int $employeeId, int $companyId): array
    {
        $children = static::childrenByManager($companyId);

        $found = [];
        $queue = $children[$employeeId] ?? [];

        // $found doubles as the visited set, so a mistaken loop in the data
        // (A reports to B, B reports to A) ends instead of spinning forever.
        while ($queue) {
            $id = array_shift($queue);

            if ($id === $employeeId || isset($found[$id])) {
                continue;
            }

            $found[$id] = true;

            foreach ($children[$id] ?? [] as $childId) {
                $queue[] = $childId;
            }
        }

        return array_keys($found);
    }

    public static function hasReports(int $employeeId, int $companyId): bool
    {
        return ! empty(static::childrenByManager($companyId)[$employeeId]);
    }

    /**
     * IDs of everyone above $employeeId, nearest first: their manager, that
     * manager's manager, and so on up to the top.
     *
     * @return array<int, int>
     */
    public static function above(int $employeeId, int $companyId): array
    {
        $managerOf = [];

        foreach (static::childrenByManager($companyId) as $managerId => $reportIds) {
            foreach ($reportIds as $reportId) {
                $managerOf[$reportId] = $managerId;
            }
        }

        $chain = [];
        $current = $employeeId;

        // Stops at the top, or - should the data ever contain a loop - when it comes back round.
        while (isset($managerOf[$current]) && ! in_array($managerOf[$current], $chain, true) && $managerOf[$current] !== $employeeId) {
            $current = $managerOf[$current];
            $chain[] = $current;
        }

        return $chain;
    }

    /**
     * Whether making $managerId the manager of $employeeId would close a
     * loop: nobody reports to themselves, or to anyone who (directly or
     * through others) already reports to them.
     */
    public static function wouldCreateLoop(int $employeeId, int $managerId, int $companyId): bool
    {
        return $managerId === $employeeId || in_array($managerId, static::below($employeeId, $companyId), true);
    }

    /** Drop the per-request cache after a reporting line changes. */
    public static function forget(int $companyId): void
    {
        request()->attributes->remove("reporting_chain.{$companyId}");
    }

    /**
     * manager id => ids of the employees reporting directly to them. Read
     * once per request with the query builder, not Eloquent, so it is never
     * itself filtered by the data access it is used to work out.
     *
     * @return array<int, array<int, int>>
     */
    protected static function childrenByManager(int $companyId): array
    {
        $cache = request()->attributes;
        $key = "reporting_chain.{$companyId}";

        if (! $cache->has($key)) {
            $children = [];

            $rows = DB::table('employees')
                ->where('company_id', $companyId)
                ->whereNotNull('manager_id')
                ->pluck('manager_id', 'id');

            foreach ($rows as $id => $managerId) {
                $children[(int) $managerId][] = (int) $id;
            }

            $cache->set($key, $children);
        }

        return $cache->get($key);
    }
}
