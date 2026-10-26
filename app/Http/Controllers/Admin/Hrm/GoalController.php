<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\AppraisalCycle;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Goal;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GoalController extends Controller
{
    protected const TABS = ['not_started', 'in_progress', 'completed', 'overdue'];

    /** People who have left can't be given new goals. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Goal::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'cycle_id' => $id('cycle_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['cycle_id'], fn ($q, $cycleId) => $q->where('appraisal_cycle_id', $cycleId))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('title', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });
        $tabbed = fn (Builder $q, string $tab) => match ($tab) {
            'overdue' => $q->where('status', '!=', 'completed')->whereDate('target_date', '<', today()),
            '' => $q,
            default => $q->where('status', $tab),
        };

        $goals = $tabbed($filtered(), $state['tab'])
            ->with(['employee.department:id,name', 'appraisalCycle:id,name,period_end'])
            // Open goals first, the most urgent on top; finished ones sink to the bottom.
            ->orderByRaw("status = 'completed'")
            ->orderByRaw('target_date IS NULL')->orderBy('target_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'employee_id', 'appraisal_cycle_id', 'weight', 'target_date', 'status', 'progress_percent']);
        $open = $all->where('status', '!=', 'completed');
        $canCreate = $request->user()->can('create', Goal::class);
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'status']);
        $cycles = AppraisalCycle::query()->where('company_id', Company::current()->id)->orderByDesc('period_start')->get(['id', 'name', 'period_start', 'period_end', 'status']);

        return view('admin.hrm.performance.goals.index', [
            'goals' => $goals,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'payable' => $people->whereNotIn('status', self::INACTIVE)->values(),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'cycles' => $cycles,
            'counts' => [
                'all' => $filtered()->count(),
                'not_started' => $tabbed($filtered(), 'not_started')->count(),
                'in_progress' => $tabbed($filtered(), 'in_progress')->count(),
                'completed' => $tabbed($filtered(), 'completed')->count(),
                'overdue' => $tabbed($filtered(), 'overdue')->count(),
            ],
            'kpis' => [
                'total' => $all->count(),
                'open' => $open->count(),
                'people' => $open->pluck('employee_id')->unique()->count(),
                'average' => $all->isNotEmpty() ? (int) round((float) $all->avg('progress_percent')) : null,
                'completed' => $all->where('status', 'completed')->count(),
                'overdue' => $open->filter(fn (Goal $g) => $g->target_date && $g->target_date->lt(today()))->count(),
                'due_soon' => $open->filter(fn (Goal $g) => $g->target_date && $g->target_date->betweenIncluded(today(), today()->addDays(14)))->count(),
                'without' => $people->whereNotIn('status', self::INACTIVE)->whereNotIn('id', $open->pluck('employee_id')->unique())->count(),
            ],
            'canCreate' => $canCreate,
            // Weight already handed out per person per cycle ("employee:cycle" => total), for the form's budget meter.
            'weights' => $all->groupBy(fn (Goal $g) => $g->employee_id.':'.($g->appraisal_cycle_id ?? 0))
                ->map(fn ($group) => ['weight' => round((float) $group->sum(fn (Goal $g) => (float) $g->weight), 2), 'goals' => $group->count()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Goal::class);

        $data = $this->validated($request);
        $employee = Employee::findOrFail($data['employee_id']);

        if (in_array($employee->status, self::INACTIVE, true)) {
            throw ValidationException::withMessages(['employee_id' => $employee->name.' is no longer with the company, so they can\'t be given a goal.']);
        }

        $this->guardWeight($employee->id, $data['appraisal_cycle_id'], $data['weight'], null, $employee->name);

        Goal::create($data + ['company_id' => Company::current()->id, 'status' => 'not_started', 'progress_percent' => 0]);

        return redirect()->route('admin.goals.index')->with('status', 'Goal set for '.$employee->name.'.');
    }

    public function update(Request $request, Goal $goal): RedirectResponse
    {
        $this->ensureOwn($goal);
        $this->authorize('update', $goal);

        // Two kinds of save land here: a quick progress update, and a full edit of the goal's details.
        $changes = [];

        if ($request->has('title')) {
            $details = $this->validated($request, $goal);
            $this->guardWeight($goal->employee_id, $details['appraisal_cycle_id'], $details['weight'], $goal, $goal->employee?->name ?? 'This employee');
            unset($details['employee_id']);
            $changes = $details;
        }

        if ($request->has('progress_percent') || $request->has('status')) {
            $progress = $request->validate([
                'status' => ['nullable', Rule::in(array_keys(Goal::STATUSES))],
                'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            ]);
            $changes += $this->progressState((int) $progress['progress_percent'], $progress['status'] ?? null);
        }

        if ($changes === []) {
            return back()->withErrors(['goal' => 'Nothing to update.']);
        }

        $goal->update($changes);

        return back()->with('status', $goal->status === 'completed' ? '"'.$goal->title.'" marked as completed.' : 'Goal updated: "'.$goal->title.'" is at '.$goal->progress_percent.'%.');
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        $this->ensureOwn($goal);
        $this->authorize('delete', $goal);

        $goal->delete();

        return back()->with('status', 'Goal removed.');
    }

    /**
     * Status and progress must tell the same story: 100% is completed and
     * completed is 100%; any progress means the goal has started.
     */
    protected function progressState(int $progress, ?string $status): array
    {
        if ($status === 'completed' || $progress === 100) {
            return ['status' => 'completed', 'progress_percent' => 100];
        }

        if ($status === 'not_started' && $progress === 0) {
            return ['status' => 'not_started', 'progress_percent' => 0];
        }

        return ['status' => $progress > 0 || $status === 'in_progress' ? 'in_progress' : 'not_started', 'progress_percent' => $progress];
    }

    protected function validated(Request $request, ?Goal $goal = null): array
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => [$goal ? 'nullable' : 'required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'appraisal_cycle_id' => ['nullable', Rule::exists('appraisal_cycles', 'id')->where('company_id', $companyId)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'target_date' => ['nullable', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:'.today()->addYears(5)->toDateString()],
        ], [
            'target_date.before_or_equal' => 'The target date can be at most five years ahead.',
        ]);

        return [
            'employee_id' => $goal ? $goal->employee_id : (int) $data['employee_id'],
            'appraisal_cycle_id' => filled($data['appraisal_cycle_id'] ?? null) ? (int) $data['appraisal_cycle_id'] : null,
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'weight' => filled($data['weight'] ?? null) ? round((float) $data['weight'], 2) : null,
            'target_date' => filled($data['target_date'] ?? null) ? $data['target_date'] : null,
        ];
    }

    /** One person's goal weights in one cycle can't add up to more than 100%. */
    protected function guardWeight(int $employeeId, ?int $cycleId, ?float $weight, ?Goal $ignore, string $name): void
    {
        if (! $weight) {
            return;
        }

        $used = (float) Goal::query()->where('employee_id', $employeeId)
            ->when($cycleId, fn ($q) => $q->where('appraisal_cycle_id', $cycleId), fn ($q) => $q->whereNull('appraisal_cycle_id'))
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))
            ->sum('weight');

        if ($used + $weight > 100.001) {
            $left = max(0, 100 - $used);

            throw ValidationException::withMessages(['weight' => $name.'\'s other goals '.($cycleId ? 'in this cycle' : 'outside a cycle').' already carry '.rtrim(rtrim(number_format($used, 2), '0'), '.').'% of the weight, so at most '.rtrim(rtrim(number_format($left, 2), '0'), '.').'% is left.']);
        }
    }

    /** 404 (not 403) for another company's or branch's goal, so the URL doesn't confirm it exists. */
    protected function ensureOwn(Goal $goal): void
    {
        abort_unless($this->scoped()->whereKey($goal->id)->exists(), 404);
    }

    protected function scoped(): Builder
    {
        return Goal::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
