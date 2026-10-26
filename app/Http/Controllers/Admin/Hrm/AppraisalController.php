<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeKpi;
use App\Models\Goal;
use App\Models\Increment;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppraisalController extends Controller
{
    protected const TABS = ['self_pending', 'manager_pending', 'ready', 'completed'];

    /** People who have left don't get a new appraisal. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Appraisal::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'cycle_id' => $id('cycle_id') ?? $id('appraisal_cycle_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $companyId = Company::current()->id;
        $filtered = fn () => $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['cycle_id'], fn ($q, $cycleId) => $q->where('appraisal_cycle_id', $cycleId))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->whereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like)));
            });
        // Each appraisal sits in exactly one stage, so the tab counts add up to the total.
        $tabbed = fn (Builder $q, string $tab) => match ($tab) {
            'self_pending' => $q->where('status', 'draft')->whereNull('manager_review_rating')->whereNull('self_review_rating'),
            'manager_pending' => $q->where('status', 'draft')->whereNull('manager_review_rating')->whereNotNull('self_review_rating'),
            'ready' => $q->where('status', 'draft')->whereNotNull('manager_review_rating'),
            'completed' => $q->where('status', 'completed'),
            default => $q,
        };

        $appraisals = $tabbed($filtered(), $state['tab'])
            ->with(['employee.department:id,name', 'appraisalCycle', 'managerReviewedBy:id,name'])
            ->orderByRaw("status = 'completed'")
            ->orderBy(Employee::query()->select('name')->whereColumn('employees.id', 'appraisals.employee_id'))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'employee_id', 'appraisal_cycle_id', 'status', 'self_review_rating', 'manager_review_rating', 'overall_rating']);
        $draft = $all->where('status', 'draft');
        $completed = $all->where('status', 'completed');
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'status']);
        $cycles = AppraisalCycle::query()->where('company_id', $companyId)->orderByRaw("status = 'closed'")->orderByDesc('period_start')->get();
        $pageEmployees = $appraisals->pluck('employee_id')->unique();
        $pageCycles = $appraisals->pluck('appraisal_cycle_id')->unique();

        return view('admin.hrm.performance.appraisals.index', [
            'appraisals' => $appraisals,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'payable' => $people->whereNotIn('status', self::INACTIVE)->values(),
            'departments' => Department::query()->where('company_id', $companyId)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'cycles' => $cycles,
            // Per cycle: how many appraisals this login can see, and how many are finished.
            'cycleStats' => $all->groupBy('appraisal_cycle_id')->map(fn ($group) => ['total' => $group->count(), 'completed' => $group->where('status', 'completed')->count()]),
            // A cycle with goals or KPIs hanging off it (even in another branch) must not be deleted.
            'cycleInUse' => $cycles->mapWithKeys(fn (AppraisalCycle $c) => [$c->id => $this->cycleInUse($c)]),
            // Who is already in each cycle, so the start form can show it ("cycle" => [employee ids]).
            'enrolled' => $all->groupBy('appraisal_cycle_id')->map(fn ($group) => $group->pluck('employee_id')->values()),
            'goalProgress' => Goal::query()->whereIn('employee_id', $pageEmployees)->whereIn('appraisal_cycle_id', $pageCycles)
                ->selectRaw('employee_id, appraisal_cycle_id, COUNT(*) as goals, AVG(progress_percent) as progress')
                ->groupBy('employee_id', 'appraisal_cycle_id')->get()->keyBy(fn ($row) => $row->employee_id.':'.$row->appraisal_cycle_id),
            'kpiAchievement' => EmployeeKpi::query()->whereIn('employee_id', $pageEmployees)->whereIn('appraisal_cycle_id', $pageCycles)
                ->get()->groupBy(fn (EmployeeKpi $k) => $k->employee_id.':'.$k->appraisal_cycle_id)
                ->map(fn ($group) => ['kpis' => $group->count(), 'achievement' => $group->map->achievementPercent()->filter(fn ($v) => $v !== null)->avg()]),
            'rewarded' => Increment::query()->whereIn('appraisal_id', $appraisals->pluck('id'))->pluck('appraisal_id')->flip(),
            'counts' => [
                'all' => $filtered()->count(),
                'self_pending' => $tabbed($filtered(), 'self_pending')->count(),
                'manager_pending' => $tabbed($filtered(), 'manager_pending')->count(),
                'ready' => $tabbed($filtered(), 'ready')->count(),
                'completed' => $tabbed($filtered(), 'completed')->count(),
            ],
            'kpis' => [
                'open' => $draft->count(),
                'self_pending' => $draft->whereNull('manager_review_rating')->whereNull('self_review_rating')->count(),
                'manager_pending' => $draft->whereNull('manager_review_rating')->whereNotNull('self_review_rating')->count(),
                'ready' => $draft->whereNotNull('manager_review_rating')->count(),
                'completed' => $completed->count(),
                'average' => $completed->whereNotNull('overall_rating')->isNotEmpty() ? round((float) $completed->whereNotNull('overall_rating')->avg('overall_rating'), 1) : null,
                'active_cycle' => $cycles->firstWhere('status', 'active'),
            ],
            'canCreate' => $request->user()->can('create', Appraisal::class),
            'canUpdate' => $request->user()->can('update_appraisal'),
            'canDelete' => $request->user()->can('delete_appraisal'),
        ]);
    }

    public function storeCycle(Request $request): RedirectResponse
    {
        $this->authorize('create', Appraisal::class);

        $data = $this->validatedCycle($request);

        AppraisalCycle::create($data + ['company_id' => Company::current()->id, 'status' => 'active']);

        return back()->with('status', 'Appraisal cycle "'.$data['name'].'" created.');
    }

    public function updateCycle(Request $request, AppraisalCycle $appraisalCycle): RedirectResponse
    {
        $this->ensureOwnCycle($appraisalCycle);
        abort_unless($request->user()->can('update_appraisal'), 403);

        // The close/reopen button sends only a status; the edit dialog sends the details.
        if (! $request->has('name')) {
            $data = $request->validate(['status' => ['required', Rule::in(array_keys(AppraisalCycle::STATUSES))]]);
            $appraisalCycle->update(['status' => $data['status']]);

            return back()->with('status', '"'.$appraisalCycle->name.'" is now '.($data['status'] === 'closed' ? 'closed. No new appraisals can be started in it' : 'open again').'.');
        }

        $appraisalCycle->update($this->validatedCycle($request, $appraisalCycle));

        return back()->with('status', 'Appraisal cycle updated.');
    }

    public function destroyCycle(Request $request, AppraisalCycle $appraisalCycle): RedirectResponse
    {
        $this->ensureOwnCycle($appraisalCycle);
        abort_unless($request->user()->can('delete_appraisal'), 403);

        // Deleting a cycle would delete its appraisals and unlink its goals and KPIs.
        if ($this->cycleInUse($appraisalCycle)) {
            return back()->withErrors(['cycle' => '"'.$appraisalCycle->name.'" already has appraisals, goals or KPIs, so it can\'t be deleted. Close it instead.']);
        }

        $appraisalCycle->delete();

        return back()->with('status', 'Appraisal cycle removed.');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Appraisal::class);

        $companyId = Company::current()->id;

        // A cycle is usually started for many people at once; the old single employee_id still works.
        if (! $request->has('employee_ids') && $request->filled('employee_id')) {
            $request->merge(['employee_ids' => [$request->input('employee_id')]]);
        }

        $data = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1', 'max:500'],
            'employee_ids.*' => ['integer', 'distinct'],
            'appraisal_cycle_id' => ['required', Rule::exists('appraisal_cycles', 'id')->where('company_id', $companyId)],
        ], [
            'employee_ids.required' => 'Pick at least one employee.',
        ]);

        $cycle = AppraisalCycle::findOrFail($data['appraisal_cycle_id']);

        if ($cycle->status !== 'active') {
            throw ValidationException::withMessages(['appraisal_cycle_id' => '"'.$cycle->name.'" is closed. Reopen it before starting new appraisals in it.']);
        }

        $employees = $this->employeeQuery()->whereNotIn('status', self::INACTIVE)->whereIn('id', $data['employee_ids'])->get(['id', 'name']);

        if ($employees->count() !== count($data['employee_ids'])) {
            throw ValidationException::withMessages(['employee_ids' => 'One or more of the selected employees can\'t be appraised (not found, or no longer with the company).']);
        }

        $already = Appraisal::query()->where('appraisal_cycle_id', $cycle->id)->whereIn('employee_id', $employees->pluck('id'))->pluck('employee_id')->flip();
        $fresh = $employees->reject(fn (Employee $e) => $already->has($e->id))->values();

        if ($fresh->isEmpty()) {
            throw ValidationException::withMessages(['employee_ids' => ($employees->count() === 1 ? $employees->first()->name.' already has' : 'Everyone selected already has').' an appraisal in "'.$cycle->name.'".']);
        }

        DB::transaction(fn () => $fresh->each(fn (Employee $e) => Appraisal::create([
            'company_id' => $companyId,
            'employee_id' => $e->id,
            'appraisal_cycle_id' => $cycle->id,
            'status' => 'draft',
        ])));

        $skipped = $employees->count() - $fresh->count();

        return redirect()->route('admin.appraisals.index')->with('status', ($fresh->count() === 1 ? 'Appraisal started for '.$fresh->first()->name : $fresh->count().' appraisals started').' in "'.$cycle->name.'".'.($skipped ? ' Skipped '.$skipped.' who already had one.' : ''));
    }

    public function complete(Appraisal $appraisal): RedirectResponse
    {
        $this->ensureOwn($appraisal);
        $this->authorize('update', $appraisal);

        if ($appraisal->status === 'completed') {
            return back()->withErrors(['appraisal' => 'This appraisal is already completed.']);
        }

        // The overall rating comes from the manager's review, falling back to the self review.
        $overall = $appraisal->manager_review_rating ?? $appraisal->self_review_rating;

        if ($overall === null) {
            return back()->withErrors(['appraisal' => 'Nobody has reviewed '.($appraisal->employee?->name ?? 'this employee').' yet, so there is no rating to complete the appraisal with. Record a self review or a manager review first.']);
        }

        $appraisal->update(['status' => 'completed', 'overall_rating' => $overall, 'completed_at' => now()]);

        return back()->with('status', 'Appraisal completed for '.($appraisal->employee?->name ?? 'the employee').' with an overall rating of '.$overall.'.');
    }

    /** Put a completed appraisal back to draft so a review can be corrected. */
    public function reopen(Appraisal $appraisal): RedirectResponse
    {
        $this->ensureOwn($appraisal);
        $this->authorize('update', $appraisal);

        if ($appraisal->status !== 'completed') {
            return back()->withErrors(['appraisal' => 'Only a completed appraisal can be reopened.']);
        }

        if (Increment::query()->where('appraisal_id', $appraisal->id)->exists()) {
            return back()->withErrors(['appraisal' => 'An increment has already been given for this appraisal, so it can\'t be reopened. Undo the increment first.']);
        }

        $appraisal->update(['status' => 'draft', 'overall_rating' => null, 'completed_at' => null]);

        return back()->with('status', 'Appraisal reopened. The reviews are kept and can be changed.');
    }

    public function destroy(Appraisal $appraisal): RedirectResponse
    {
        $this->ensureOwn($appraisal);
        $this->authorize('delete', $appraisal);

        // A finished appraisal is a record of someone's rating; removing it would also orphan any increment given for it.
        if ($appraisal->status === 'completed') {
            return back()->withErrors(['appraisal' => 'A completed appraisal can\'t be removed. Reopen it first if it was completed by mistake.']);
        }

        $appraisal->delete();

        return back()->with('status', 'Appraisal removed.');
    }

    protected function validatedCycle(Request $request, ?AppraisalCycle $cycle = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('appraisal_cycles', 'name')->where('company_id', Company::current()->id)->ignore($cycle?->id)],
            'period_start' => ['required', 'date', 'after_or_equal:2000-01-01'],
            'period_end' => ['required', 'date', 'after:period_start', 'before_or_equal:'.today()->addYears(5)->toDateString()],
        ], [
            'name.unique' => 'An appraisal cycle with that name already exists.',
            'period_end.after' => 'The cycle must end after it starts.',
        ]);

        if (Carbon::parse($data['period_start'])->diffInDays(Carbon::parse($data['period_end'])) > 366 * 2) {
            throw ValidationException::withMessages(['period_end' => 'A cycle can cover at most two years.']);
        }

        return ['name' => trim($data['name']), 'period_start' => $data['period_start'], 'period_end' => $data['period_end']];
    }

    protected function cycleInUse(AppraisalCycle $cycle): bool
    {
        return Appraisal::query()->where('appraisal_cycle_id', $cycle->id)->exists()
            || Goal::query()->where('appraisal_cycle_id', $cycle->id)->exists()
            || EmployeeKpi::query()->where('appraisal_cycle_id', $cycle->id)->exists();
    }

    /** 404 (not 403) for another company's cycle, so the URL doesn't confirm it exists. */
    protected function ensureOwnCycle(AppraisalCycle $cycle): void
    {
        abort_unless((int) $cycle->company_id === (int) Company::current()->id, 404);
    }

    /** 404 for another company's or branch's appraisal. */
    protected function ensureOwn(Appraisal $appraisal): void
    {
        abort_unless($this->scoped()->whereKey($appraisal->id)->exists(), 404);
    }

    protected function scoped(): Builder
    {
        return Appraisal::query()
            ->where('appraisals.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
