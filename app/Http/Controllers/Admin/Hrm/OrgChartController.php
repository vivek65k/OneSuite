<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Support\BranchContext;
use App\Support\ReportingChain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The reporting structure as one picture: who reports to whom (employees
 * .manager_id, "Reports to"), editable in place. The same structure drives
 * the Team self-service screens and the "Their team" data access on roles
 * (App\Support\ReportingChain), so this is where a client actually builds
 * and checks it instead of opening employees one by one.
 */
class OrgChartController extends Controller
{
    /** People who have left don't appear on the chart (their reports surface as "manager not active"). */
    protected const HIDDEN_STATUSES = ['inactive', 'terminated'];

    public function index(): View
    {
        $this->authorize('viewAny', Employee::class);

        $people = Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->whereNotIn('status', self::HIDDEN_STATUSES)
            ->with(['department:id,name', 'manager:id,name,status,branch_id', 'manager.branch:id,name'])
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $childrenOf = $people->filter(fn (Employee $e) => $e->manager_id && $people->has($e->manager_id))->groupBy('manager_id');

        // Top of the picture: no manager, or a manager who isn't on it
        // (another branch, outside the viewer's data access, or has left).
        $roots = $people->reject(fn (Employee $e) => $e->manager_id && $people->has($e->manager_id));

        $seen = [];
        $tree = $roots->map(fn (Employee $e) => $this->node($e, $childrenOf, $seen))->values();

        // Anyone still unplaced is caught in a loop (A reports to B reports
        // to A) from before loops were refused - show them rather than lose them.
        foreach ($people as $person) {
            if (! isset($seen[$person->id])) {
                $tree->push($this->node($person, $childrenOf, $seen) + ['loop' => true]);
            }
        }

        return view('admin.hrm.employees.org-chart', [
            'tree' => $tree,
            'stats' => [
                'people' => $people->count(),
                'managers' => $childrenOf->count(),
                'unassigned' => $people->whereNull('manager_id')->count(),
                'levels' => (int) $tree->max('depth'),
            ],
            // Whose manager has left or been deactivated - their line needs a new owner.
            'orphaned' => $people->filter(fn (Employee $e) => $e->manager && in_array($e->manager->status, self::HIDDEN_STATUSES, true))->values(),
            'managerOptions' => $this->managerOptions(),
            // Everyone who currently has people reporting to them here - including
            // managers who are not on the chart themselves - for "Reassign a team".
            'teamLeads' => $people->whereNotNull('manager_id')->groupBy('manager_id')
                ->map(fn (Collection $reports, $managerId) => [
                    'id' => (int) $managerId,
                    'name' => $people->get($managerId)?->name ?? $reports->first()->manager?->name ?? 'Unknown',
                    'count' => $reports->count(),
                    'left' => in_array($reports->first()->manager?->status, self::HIDDEN_STATUSES, true),
                ])->sortBy('name')->values(),
            'movedId' => (int) session('org_moved'),
        ]);
    }

    /**
     * Set (or clear) who one employee reports to - from the "Change
     * manager" dialog or a drag-and-drop on the chart.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $companyId = Company::current()->id;
        abort_unless((int) $employee->company_id === (int) $companyId, 404);

        $managerId = $this->validatedManagerId($request, 'manager_id');

        if ($managerId) {
            $this->ensureNoLoop($employee, $managerId, 'manager_id');
        }

        $employee->update(['manager_id' => $managerId]);
        ReportingChain::forget($companyId);

        $manager = $managerId ? Employee::query()->find($managerId) : null;

        return redirect()->route('admin.employees.org-chart')
            ->with('status', $manager ? "{$employee->name} now reports to {$manager->name}." : "{$employee->name} no longer reports to anyone.")
            ->with('org_moved', $employee->id);
    }

    /**
     * Hand one manager's whole team to someone else in one go - what you
     * need when a manager leaves, moves or is promoted. The new manager
     * themselves is skipped if they were part of that team.
     */
    public function reassign(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Employee::class);

        $companyId = Company::current()->id;

        $fromId = (int) $request->validate([
            'from_manager_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('company_id', $companyId)],
        ])['from_manager_id'];
        $toId = $this->validatedManagerId($request, 'to_manager_id');

        if ($toId === $fromId) {
            throw ValidationException::withMessages(['to_manager_id' => 'Choose a different person to take over the team.']);
        }

        $reports = Employee::query()->where('company_id', $companyId)->where('manager_id', $fromId)->orderBy('name')->get();

        // Check everything first, so a team is moved as a whole or not at all.
        foreach ($reports as $report) {
            if ($report->id !== $toId && $toId) {
                $this->ensureNoLoop($report, $toId, 'to_manager_id');
            }
        }

        $moved = 0;
        $skipped = 0;

        foreach ($reports as $report) {
            if ($report->id === $toId) {
                continue;
            }

            if (! $request->user()->can('update', $report)) {
                $skipped++;

                continue;
            }

            $report->update(['manager_id' => $toId]);
            $moved++;
        }

        ReportingChain::forget($companyId);

        $to = $toId ? Employee::query()->find($toId)?->name : null;
        $message = $moved
            ? $moved.' '.str('person')->plural($moved).($to ? ' now '.($moved === 1 ? 'reports' : 'report')." to {$to}." : ' moved to the top level.')
            : 'Nobody was moved - that person has no one reporting to them here.';

        return redirect()->route('admin.employees.org-chart')
            ->with('status', $message.($skipped ? " {$skipped} could not be changed with your access." : ''));
    }

    /**
     * One person and everyone under them, with the two counts shown on
     * their card. $seen guards against a loop in old data.
     *
     * @param  array<int, bool>  $seen
     * @return array{employee: Employee, children: Collection, direct: int, total: int, depth: int}
     */
    protected function node(Employee $employee, Collection $childrenOf, array &$seen): array
    {
        $seen[$employee->id] = true;

        $children = collect($childrenOf->get($employee->id, []))
            ->reject(fn (Employee $child) => isset($seen[$child->id]))
            ->map(fn (Employee $child) => $this->node($child, $childrenOf, $seen))
            ->values();

        return [
            'employee' => $employee,
            'children' => $children,
            'direct' => $children->count(),
            'total' => $children->count() + (int) $children->sum('total'),
            'depth' => 1 + (int) $children->max('depth'),
        ];
    }

    /**
     * Anyone active in the company can be a manager - a branch head
     * usually reports to someone at head office - so this list is not
     * limited to the current branch. (A role's data access still limits it.)
     */
    protected function managerOptions(): Collection
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->whereNotIn('status', self::HIDDEN_STATUSES)
            ->with('branch:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'designation', 'branch_id', 'avatar']);
    }

    protected function validatedManagerId(Request $request, string $field): ?int
    {
        $managerId = $request->validate([
            $field => ['nullable', 'integer', Rule::in($this->managerOptions()->pluck('id'))],
        ], [
            "{$field}.in" => 'Choose an active employee from the list.',
        ])[$field] ?? null;

        return $managerId ? (int) $managerId : null;
    }

    protected function ensureNoLoop(Employee $employee, int $managerId, string $field): void
    {
        if (ReportingChain::wouldCreateLoop((int) $employee->id, $managerId, (int) $employee->company_id)) {
            $manager = Employee::query()->find($managerId);

            throw ValidationException::withMessages([
                $field => $managerId === (int) $employee->id
                    ? "{$employee->name} can't report to themselves."
                    : "{$manager?->name} is already under {$employee->name} in the reporting line, so {$employee->name} can't report to them. Move {$manager?->name} first.",
            ]);
        }
    }
}
