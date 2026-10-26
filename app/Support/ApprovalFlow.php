<?php

namespace App\Support;

use App\Models\ApprovalLevelSetting;
use App\Models\ApprovalStep;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\RegularizationRequest;
use App\Models\Scopes\EmployeeDataScope;
use App\Models\TravelRequest;
use App\Models\User;
use App\Models\WfhRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Multi-level approval along the reporting chain.
 *
 * Every employee has one "Reports to"; the levels follow from it - level 1
 * is their manager, level 2 that manager's manager, and so on
 * (App\Support\ReportingChain). A client chooses, per request type, how
 * many of those levels a request must pass (HRM > Settings > Approval
 * Levels; 1 by default, which is exactly the single approval the app
 * always had).
 *
 * The rules, all in forward():
 *   - a manager approves for their own level; a manager further up may
 *     approve early, which also covers the levels below them;
 *   - someone outside the employee's chain who is allowed to approve at
 *     all (HR with "all" data access, the super admin) settles the whole
 *     request in one go - HR can always unblock a stuck request;
 *   - a chain shorter than the configured levels simply ends sooner: an
 *     employee with one manager above them needs that one approval;
 *   - a rejection at any level is final.
 *
 * The request's own status stays "pending" until the last level approves;
 * only then does the caller run its normal approval code (balances,
 * attendance, ...), so none of that had to change.
 */
class ApprovalFlow
{
    public const MAX_LEVELS = 3;

    /** The request types that follow the chain - the ones managers act on from Team Approvals. */
    public const TYPES = [
        'leave_request' => ['label' => 'Leave requests', 'icon' => 'ti-calendar-event', 'model' => LeaveRequest::class],
        'regularization_request' => ['label' => 'Attendance regularization', 'icon' => 'ti-clock', 'model' => RegularizationRequest::class],
        'wfh_request' => ['label' => 'Work from home requests', 'icon' => 'ti-home', 'model' => WfhRequest::class],
        'overtime_request' => ['label' => 'Overtime requests', 'icon' => 'ti-clock', 'model' => OvertimeRequest::class],
        'travel_request' => ['label' => 'Travel requests', 'icon' => 'ti-plane', 'model' => TravelRequest::class],
        'expense_claim' => ['label' => 'Expense claims', 'icon' => 'ti-receipt-2', 'model' => ExpenseClaim::class],
    ];

    public static function typeOf(Model $record): ?string
    {
        foreach (self::TYPES as $type => $definition) {
            if ($record instanceof $definition['model']) {
                return $type;
            }
        }

        return null;
    }

    /** How many levels the client asked for on this request type (1 when never set). */
    public static function configuredLevels(int $companyId, string $type): int
    {
        $cache = request()->attributes;
        $key = "approval_levels.{$companyId}";

        if (! $cache->has($key)) {
            $cache->set($key, ApprovalLevelSetting::where('company_id', $companyId)->pluck('levels', 'request_type')->all());
        }

        return max(1, min(self::MAX_LEVELS, (int) ($cache->get($key)[$type] ?? 1)));
    }

    /**
     * The managers this request has to pass, keyed by level (1 = the
     * employee's own manager). Empty when the employee reports to nobody.
     *
     * @return Collection<int, Employee>
     */
    public static function approvers(Model $record): Collection
    {
        $type = self::typeOf($record);

        if (! $type || ! $record->employee_id) {
            return collect();
        }

        $chain = array_slice(
            ReportingChain::above((int) $record->employee_id, (int) $record->company_id),
            0,
            self::configuredLevels((int) $record->company_id, $type),
        );

        $people = Employee::withoutGlobalScope(EmployeeDataScope::class)->whereIn('id', $chain)->get(['id', 'name', 'user_id', 'designation'])->keyBy('id');

        return collect($chain)->values()
            ->mapWithKeys(fn (int $employeeId, int $index) => [$index + 1 => $people->get($employeeId)])
            ->filter();
    }

    /**
     * Where a request stands: how many levels it needs, how many it has
     * passed, and who it is waiting for now (null once nobody in the chain
     * is left, or when the employee has no manager and it is HR's to settle).
     *
     * @return array{required: int, done: int, approvers: Collection, waiting: ?Employee, steps: Collection}
     */
    public static function state(Model $record): array
    {
        $approvers = self::approvers($record);
        $required = max(1, $approvers->count());

        $steps = $required > 1
            ? ApprovalStep::where('approvable_type', $record->getMorphClass())->where('approvable_id', $record->getKey())->with('actedBy:id,name')->orderBy('level')->get()
            : collect();

        $done = (int) $steps->where('status', 'approved')->max('level');

        return [
            'required' => $required,
            'done' => min($done, $required),
            'approvers' => $approvers,
            'waiting' => $approvers->get(min($done, $required) + 1),
            'steps' => $steps,
        ];
    }

    /** This user's level above the request's employee (1 = their manager), or null when they aren't in that chain. */
    public static function actorLevel(User $user, Model $record): ?int
    {
        $employeeId = DataScope::employeeIdOf($user);

        if ($employeeId === null || ! $record->employee_id) {
            return null;
        }

        $position = array_search($employeeId, ReportingChain::above((int) $record->employee_id, (int) $record->company_id), true);

        return $position === false ? null : $position + 1;
    }

    /** Whether approving now would add anything - false for a manager whose own level is already done. */
    public static function awaits(User $user, Model $record, ?array $state = null): bool
    {
        $state ??= self::state($record);
        $level = self::actorLevel($user, $record);

        return $level === null || $level > $state['done'];
    }

    /**
     * Record the signed-in user's approval. Returns null when that settles
     * the request - the caller then carries on with its normal approval -
     * or a message for the user when it has only moved up a level, in which
     * case the caller must stop and leave the request pending.
     */
    public static function forward(Model $record): ?string
    {
        $user = Auth::user();

        if (! $user || ! self::typeOf($record) || $record->status !== 'pending' || (int) $record->company_id !== (int) $user->company_id) {
            return null;
        }

        $state = self::state($record);

        // One level (the default, or nobody above the first manager): exactly the old single approval.
        if ($state['required'] <= 1) {
            return null;
        }

        $level = self::actorLevel($user, $record);

        if ($level !== null && $level <= $state['done']) {
            $waiting = $state['waiting']?->name ?? 'the next approver';

            abort(409, "You have already approved this request. It is now waiting for {$waiting}.");
        }

        // Outside the chain (HR, super admin): settle every level still open.
        $upTo = $level === null ? $state['required'] : min($level, $state['required']);

        for ($step = $state['done'] + 1; $step <= $upTo; $step++) {
            ApprovalStep::create([
                'company_id' => $record->company_id,
                'approvable_type' => $record->getMorphClass(),
                'approvable_id' => $record->getKey(),
                'level' => $step,
                'status' => 'approved',
                'acted_by_user_id' => $user->id,
                // Their own level only when they are that level's manager.
                'on_behalf' => $level !== $step,
            ]);
        }

        if ($upTo >= $state['required']) {
            return null;
        }

        $next = $state['approvers']->get($upTo + 1);

        if ($next?->user_id) {
            Notifier::notifyUser(
                User::find($next->user_id),
                'Approval needed',
                "{$record->employee?->name}'s request has passed level {$upTo} and is waiting for you.",
                route('admin.team-approvals.index'),
                'ti ti-checkbox',
            );
        }

        return "Approved at level {$upTo} of {$state['required']}. It now goes to ".($next?->name ?? 'the next approver').' (level '.($upTo + 1).').';
    }

    /** Keep the trail complete when a multi-level request is turned down part-way. */
    public static function rejected(Model $record, ?string $remarks = null): void
    {
        $user = Auth::user();

        if (! $user || ! self::typeOf($record) || $record->status !== 'pending' || (int) $record->company_id !== (int) $user->company_id) {
            return;
        }

        $state = self::state($record);

        if ($state['required'] <= 1) {
            return;
        }

        $level = min($state['done'] + 1, $state['required']);

        ApprovalStep::create([
            'company_id' => $record->company_id,
            'approvable_type' => $record->getMorphClass(),
            'approvable_id' => $record->getKey(),
            'level' => $level,
            'status' => 'rejected',
            'acted_by_user_id' => $user->id,
            'on_behalf' => self::actorLevel($user, $record) !== $level,
            'remarks' => $remarks,
        ]);
    }
}
