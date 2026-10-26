<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeKpi;
use App\Models\Feedback360;
use App\Models\Goal;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ManagerReviewController extends Controller
{
    protected const TABS = ['pending', 'reviewed', 'locked'];

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
            'pending' => $q->where('status', 'draft')->whereNull('manager_review_rating'),
            'reviewed' => $q->where('status', 'draft')->whereNotNull('manager_review_rating'),
            'locked' => $q->where('status', 'completed'),
            default => $q,
        };

        $appraisals = $tabbed($filtered(), $state['tab'])
            ->with(['employee.department:id,name', 'employee.manager:id,name', 'appraisalCycle:id,name', 'managerReviewedBy:id,name'])
            // What still needs a review comes first, and among those the ones whose self review is already in.
            ->orderByRaw("status = 'completed'")
            ->orderByRaw('manager_review_rating IS NOT NULL')
            ->orderByRaw('self_review_rating IS NULL')
            ->orderBy(Employee::query()->select('name')->whereColumn('employees.id', 'appraisals.employee_id'))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'status', 'self_review_rating', 'manager_review_rating']);
        $open = $all->where('status', 'draft');
        $rated = $all->whereNotNull('manager_review_rating');
        $both = $all->filter(fn (Appraisal $a) => $a->manager_review_rating !== null && $a->self_review_rating !== null);
        $people = $this->employeeQuery()->orderBy('name')->get(['id', 'name', 'department_id']);
        $pageEmployees = $appraisals->pluck('employee_id')->unique();
        $pageCycles = $appraisals->pluck('appraisal_cycle_id')->unique();

        return view('admin.hrm.performance.manager-reviews.index', [
            'appraisals' => $appraisals,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', $companyId)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'cycles' => AppraisalCycle::query()->where('company_id', $companyId)->orderByDesc('period_start')->pluck('name', 'id'),
            'counts' => [
                'all' => $filtered()->count(),
                'pending' => $tabbed($filtered(), 'pending')->count(),
                'reviewed' => $tabbed($filtered(), 'reviewed')->count(),
                'locked' => $tabbed($filtered(), 'locked')->count(),
            ],
            'kpis' => [
                'pending' => $open->whereNull('manager_review_rating')->count(),
                'self_in' => $open->whereNull('manager_review_rating')->whereNotNull('self_review_rating')->count(),
                'reviewed' => $rated->count(),
                'total' => $all->count(),
                'average' => $rated->isNotEmpty() ? round((float) $rated->avg('manager_review_rating'), 1) : null,
                // How far managers sit from people's own view of themselves, on average (negative = managers rate lower).
                'gap' => $both->isNotEmpty() ? round((float) $both->avg(fn (Appraisal $a) => $a->manager_review_rating - $a->self_review_rating), 1) : null,
                'compared' => $both->count(),
            ],
            // What the reviewer should have in front of them: goals, KPIs and 360 feedback for the same cycle.
            'goalProgress' => Goal::query()->whereIn('employee_id', $pageEmployees)->whereIn('appraisal_cycle_id', $pageCycles)
                ->selectRaw('employee_id, appraisal_cycle_id, COUNT(*) as goals, AVG(progress_percent) as progress')
                ->groupBy('employee_id', 'appraisal_cycle_id')->get()->keyBy(fn ($row) => $row->employee_id.':'.$row->appraisal_cycle_id),
            'kpiAchievement' => EmployeeKpi::query()->whereIn('employee_id', $pageEmployees)->whereIn('appraisal_cycle_id', $pageCycles)
                ->get()->groupBy(fn (EmployeeKpi $k) => $k->employee_id.':'.$k->appraisal_cycle_id)
                ->map(fn ($group) => ['kpis' => $group->count(), 'achievement' => $group->map->achievementPercent()->filter(fn ($v) => $v !== null)->avg()]),
            'feedback' => Feedback360::query()->whereIn('appraisal_id', $appraisals->pluck('id'))->where('relationship', '!=', 'self')
                ->selectRaw('appraisal_id, COUNT(*) as entries, AVG(rating) as rating')->groupBy('appraisal_id')->get()->keyBy('appraisal_id'),
            'myEmployeeId' => Auth::user()->is_super_admin ? null : Employee::query()->where('user_id', Auth::id())->value('id'),
        ]);
    }

    public function update(Request $request, Appraisal $appraisal): RedirectResponse
    {
        abort_unless($this->scoped()->whereKey($appraisal->id)->exists(), 404);
        $this->authorize('update', $appraisal);

        $name = $appraisal->employee?->name ?? 'this employee';

        // Once the appraisal is signed off its rating is final; changing the review would leave the two out of step.
        if ($appraisal->status === 'completed') {
            return back()->withErrors(['review' => $name.'\'s appraisal is already completed, so the manager review can\'t be changed. Reopen the appraisal first.']);
        }

        // Nobody reviews themselves as their own manager. A super admin is the exception, or a one-person company could never finish.
        abort_if(! Auth::user()->is_super_admin && $appraisal->employee && (int) $appraisal->employee->user_id === (int) Auth::id(), 403, 'You can\'t write the manager review for your own appraisal.');

        $data = $request->validate([
            'manager_review_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'manager_review_comments' => ['nullable', 'string', 'max:2000'],
        ], [
            'manager_review_rating.required' => 'Pick a rating from 1 to 5.',
        ]);

        $first = $appraisal->manager_review_rating === null;

        $appraisal->update([
            'manager_review_rating' => (int) $data['manager_review_rating'],
            'manager_review_comments' => filled($data['manager_review_comments'] ?? null) ? trim($data['manager_review_comments']) : null,
            'manager_review_submitted_at' => now(),
            'manager_reviewed_by_user_id' => Auth::id(),
        ]);

        return back()->with('status', 'Manager review '.($first ? 'submitted' : 'updated').' for '.$name.': '.$data['manager_review_rating'].' of 5.');
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
