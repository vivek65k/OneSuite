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
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * HR's view of self reviews: who has submitted one, and a way to record or
 * correct one on an employee's behalf. Employees submit their own from
 * Employee Self Service (My Performance).
 */
class SelfReviewController extends Controller
{
    protected const TABS = ['pending', 'submitted', 'locked'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Appraisal::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            // The old "pending only" checkbox still works as a link.
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : ($text('pending_only') === '1' ? 'pending' : ''),
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'cycle_id' => $id('cycle_id'),
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
        // Each appraisal is in exactly one of the three, so the counts add up to the total.
        $tabbed = fn (Builder $q, string $tab) => match ($tab) {
            'pending' => $q->where('status', 'draft')->whereNull('self_review_rating'),
            'submitted' => $q->where('status', 'draft')->whereNotNull('self_review_rating'),
            'locked' => $q->where('status', 'completed'),
            default => $q,
        };

        $appraisals = $tabbed($filtered(), $state['tab'])
            ->with(['employee.department:id,name', 'appraisalCycle:id,name,period_end', 'managerReviewedBy:id,name'])
            // Missing self reviews first; finished appraisals last.
            ->orderByRaw("status = 'completed'")
            ->orderByRaw('self_review_rating IS NOT NULL')
            ->orderBy(Employee::query()->select('name')->whereColumn('employees.id', 'appraisals.employee_id'))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'status', 'self_review_rating', 'manager_review_rating']);
        $open = $all->where('status', 'draft');
        $rated = $all->whereNotNull('self_review_rating');
        $both = $all->filter(fn (Appraisal $a) => $a->manager_review_rating !== null && $a->self_review_rating !== null);
        $people = $this->employeeQuery()->orderBy('name')->get(['id', 'name', 'department_id']);
        $pageEmployees = $appraisals->pluck('employee_id')->unique();
        $pageCycles = $appraisals->pluck('appraisal_cycle_id')->unique();

        return view('admin.hrm.performance.self-reviews.index', [
            'appraisals' => $appraisals,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', $companyId)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'cycles' => AppraisalCycle::query()->where('company_id', $companyId)->orderByDesc('period_start')->pluck('name', 'id'),
            'counts' => [
                'all' => $filtered()->count(),
                'pending' => $tabbed($filtered(), 'pending')->count(),
                'submitted' => $tabbed($filtered(), 'submitted')->count(),
                'locked' => $tabbed($filtered(), 'locked')->count(),
            ],
            'kpis' => [
                'pending' => $open->whereNull('self_review_rating')->count(),
                // Their manager has already reviewed them, so the self review is overdue.
                'overtaken' => $open->whereNull('self_review_rating')->whereNotNull('manager_review_rating')->count(),
                'submitted' => $rated->count(),
                'total' => $all->count(),
                'average' => $rated->isNotEmpty() ? round((float) $rated->avg('self_review_rating'), 1) : null,
                // How people see themselves against how their manager sees them (positive = they rate themselves higher).
                'gap' => $both->isNotEmpty() ? round((float) $both->avg(fn (Appraisal $a) => $a->self_review_rating - $a->manager_review_rating), 1) : null,
            ],
            'goalProgress' => Goal::query()->whereIn('employee_id', $pageEmployees)->whereIn('appraisal_cycle_id', $pageCycles)
                ->selectRaw('employee_id, appraisal_cycle_id, COUNT(*) as goals, AVG(progress_percent) as progress')
                ->groupBy('employee_id', 'appraisal_cycle_id')->get()->keyBy(fn ($row) => $row->employee_id.':'.$row->appraisal_cycle_id),
            'kpiAchievement' => EmployeeKpi::query()->whereIn('employee_id', $pageEmployees)->whereIn('appraisal_cycle_id', $pageCycles)
                ->get()->groupBy(fn (EmployeeKpi $k) => $k->employee_id.':'.$k->appraisal_cycle_id)
                ->map(fn ($group) => ['kpis' => $group->count(), 'achievement' => $group->map->achievementPercent()->filter(fn ($v) => $v !== null)->avg()]),
        ]);
    }

    public function update(Request $request, Appraisal $appraisal): RedirectResponse
    {
        abort_unless($this->scoped()->whereKey($appraisal->id)->exists(), 404);
        $this->authorize('update', $appraisal);

        $name = $appraisal->employee?->name ?? 'this employee';

        // Once the appraisal is signed off, the reviews behind its rating are part of the record.
        if ($appraisal->status === 'completed') {
            return back()->withErrors(['review' => $name.'\'s appraisal is already completed, so the self review can\'t be changed. Reopen the appraisal first.']);
        }

        $data = $request->validate([
            'self_review_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'self_review_comments' => ['nullable', 'string', 'max:2000'],
        ], [
            'self_review_rating.required' => 'Pick a rating from 1 to 5.',
        ]);

        $first = $appraisal->self_review_rating === null;

        $appraisal->update([
            'self_review_rating' => (int) $data['self_review_rating'],
            'self_review_comments' => filled($data['self_review_comments'] ?? null) ? trim($data['self_review_comments']) : null,
            // Keep the date it was first handed in; a later correction doesn't make it look freshly submitted.
            'self_review_submitted_at' => $appraisal->self_review_submitted_at ?? now(),
        ]);

        return back()->with('status', 'Self review '.($first ? 'recorded' : 'updated').' for '.$name.': '.$data['self_review_rating'].' of 5.');
    }

    /** Appraisals this login may see: its own company and, when tied to a branch, that branch's employees. */
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
