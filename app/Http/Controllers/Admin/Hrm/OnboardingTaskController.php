<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\OnboardingTask;
use App\Support\BranchContext;
use App\Support\Onboarding;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingTaskController extends Controller
{
    use PersistsTableState;

    /**
     * /hrm/onboarding - overview across every category: headline KPIs,
     * per-category progress, per-new-hire progress, overdue tasks and
     * upcoming probation reviews.
     */
    public function overview(): View
    {
        $this->authorize('viewAny', OnboardingTask::class);

        $today = now()->toDateString();

        $byCategory = Onboarding::taskQuery()
            ->toBase()
            ->selectRaw('category, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as done')
            ->selectRaw('SUM(CASE WHEN is_completed = 0 AND due_date < ? THEN 1 ELSE 0 END) as overdue', [$today])
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        $kpis = [
            'open' => Onboarding::taskQuery()->where('is_completed', false)->count(),
            'overdue' => Onboarding::taskQuery()->where('is_completed', false)->whereDate('due_date', '<', $today)->count(),
            'done_this_week' => Onboarding::taskQuery()->where('is_completed', true)->where('completed_at', '>=', now()->startOfWeek())->count(),
            'new_hires' => Onboarding::taskQuery()->where('is_completed', false)->distinct()->count('employee_id'),
            'reviews_due' => Onboarding::reviewsDueQuery(now()->addDays(30))->count(),
        ];

        // Every employee with onboarding tasks, most work outstanding first.
        $hires = Employee::query()
            ->whereIn('id', Onboarding::taskQuery()->select('employee_id'))
            ->with(['department', 'onboardingTasks' => fn ($q) => $q->orderBy('due_date')])
            ->get()
            ->map(function (Employee $employee) {
                $tasks = $employee->onboardingTasks;
                $done = $tasks->where('is_completed', true)->count();

                return (object) [
                    'employee' => $employee,
                    'total' => $tasks->count(),
                    'done' => $done,
                    'percent' => $tasks->count() ? (int) round($done / $tasks->count() * 100) : 0,
                    'overdue' => $tasks->filter(fn ($t) => ! $t->is_completed && $t->due_date?->isPast() && ! $t->due_date->isToday())->count(),
                    'next' => $tasks->first(fn ($t) => ! $t->is_completed),
                    'categories' => collect(OnboardingTask::CATEGORIES)->map(function ($label, $key) use ($tasks) {
                        $inCategory = $tasks->where('category', $key);

                        return $inCategory->isEmpty() ? null : ($inCategory->every('is_completed') ? 'done' : 'open');
                    }),
                ];
            })
            ->sortBy([['percent', 'asc'], ['overdue', 'desc']])
            ->values();

        return view('admin.hrm.onboarding.overview', [
            'kpis' => $kpis,
            'byCategory' => $byCategory,
            'hires' => $hires,
            'overdueTasks' => Onboarding::taskQuery()
                ->with(['employee', 'assignedTo'])
                ->where('is_completed', false)
                ->whereDate('due_date', '<', $today)
                ->orderBy('due_date')
                ->limit(6)
                ->get(),
            'reviews' => Onboarding::reviewsDueQuery(now()->addDays(30))
                ->with('employee')
                ->get()
                ->sortBy(fn ($record) => Onboarding::probationDueDate($record))
                ->take(6),
        ]);
    }

    public function index(Request $request, string $category): View|RedirectResponse
    {
        $this->authorize('viewAny', OnboardingTask::class);

        abort_unless(array_key_exists($category, OnboardingTask::CATEGORIES), 404);

        $state = $this->resolveTableState($request, "onboarding-{$category}", [
            'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $tasks = $this->filteredQuery($state, $category)
            ->with(['employee.department', 'assignedTo', 'completedBy'])
            ->orderBy('is_completed')
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.hrm.onboarding.tasks.index', [
            'mode' => 'category',
            'category' => $category,
            'title' => OnboardingTask::CATEGORIES[$category],
            'tasks' => $tasks,
            'state' => $state,
            'statusCounts' => $this->statusCounts($this->filteredQuery(array_diff_key($state, ['status' => true]), $category)),
            'progress' => $this->progress(Onboarding::taskQuery()->where('category', $category)),
            'employees' => $this->employeeOptions(),
            'users' => Onboarding::assignableUsers(),
        ]);
    }

    public function store(Request $request, string $category): RedirectResponse
    {
        $this->authorize('create', OnboardingTask::class);

        abort_unless(array_key_exists($category, OnboardingTask::CATEGORIES), 404);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'assigned_role' => ['nullable', Rule::in(array_keys(OnboardingTask::ROLES))],
            // Only this company's own users can be assignees.
            'assigned_to_user_id' => ['nullable', Rule::exists('users', 'id')->where('company_id', $companyId)],
            'due_date' => ['nullable', 'date'],
        ]);

        $data['company_id'] = $companyId;
        $data['category'] = $category;

        OnboardingTask::create($data);

        return redirect()->route('admin.onboarding.category.index', $category)->with('status', 'Task added.');
    }

    public function toggle(OnboardingTask $onboardingTask): RedirectResponse
    {
        $this->ensureOwnTask($onboardingTask);

        $user = Auth::user();

        abort_unless(
            $onboardingTask->assigned_to_user_id === $user->id || $user->can('update_onboarding_task'),
            403
        );

        $onboardingTask->is_completed
            ? $onboardingTask->update(['is_completed' => false, 'completed_at' => null, 'completed_by_user_id' => null])
            : $onboardingTask->update(['is_completed' => true, 'completed_at' => now(), 'completed_by_user_id' => $user->id]);

        return back()->with('status', $onboardingTask->is_completed ? 'Task marked complete.' : 'Task reopened.');
    }

    public function destroy(OnboardingTask $onboardingTask): RedirectResponse
    {
        $this->authorize('delete', $onboardingTask);
        $this->ensureOwnTask($onboardingTask);

        $category = $onboardingTask->category;
        $onboardingTask->delete();

        return back(fallback: route('admin.onboarding.category.index', $category))->with('status', 'Task removed.');
    }

    public function managerTasks(Request $request): View
    {
        return $this->myTasks($request, 'manager', 'Manager Tasks');
    }

    public function hrTasks(Request $request): View
    {
        return $this->myTasks($request, 'hr', 'HR Tasks');
    }

    protected function myTasks(Request $request, string $role, string $title): View
    {
        $this->authorize('viewAny', OnboardingTask::class);

        $state = $request->only(['status', 'search', 'category']);
        $base = fn (array $state) => $this->filteredQuery($state)
            ->where('assigned_role', $role)
            ->where('assigned_to_user_id', Auth::id());

        $tasks = $base($state)
            ->with(['employee.department', 'completedBy'])
            ->orderBy('is_completed')
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get();

        return view('admin.hrm.onboarding.tasks.index', [
            'mode' => 'my-tasks',
            'role' => $role,
            'title' => $title,
            'tasks' => $tasks,
            'state' => $state,
            'statusCounts' => $this->statusCounts($base(array_diff_key($state, ['status' => true]))),
            'progress' => $this->progress($base([])),
        ]);
    }

    /**
     * Shared filters: search (task title / employee), employee, assignee,
     * status (pending / overdue / due_week / completed).
     */
    protected function filteredQuery(array $state, ?string $category = null): Builder
    {
        $today = now()->toDateString();

        return Onboarding::taskQuery()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($state['category'] ?? null, fn ($q, $cat) => $q->where('category', $cat))
            ->when($state['employee_id'] ?? null, fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['assignee'] ?? null, fn ($q, $userId) => $q->where('assigned_to_user_id', $userId))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('title', 'like', "%{$search}%")
                    ->orWhereHas('employee', fn ($q3) => $q3->where('name', 'like', "%{$search}%"));
            }))
            ->when($state['status'] ?? null, fn ($q, $status) => match ($status) {
                'completed' => $q->where('is_completed', true),
                'pending' => $q->where('is_completed', false),
                'overdue' => $q->where('is_completed', false)->whereDate('due_date', '<', $today),
                'due_week' => $q->where('is_completed', false)->whereBetween('due_date', [$today, now()->addDays(7)->toDateString()]),
                default => $q,
            });
    }

    /** @return array{all: int, pending: int, overdue: int, due_week: int, completed: int} */
    protected function statusCounts(Builder $query): array
    {
        $today = now()->toDateString();

        $row = $query->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed')
            ->selectRaw('SUM(CASE WHEN is_completed = 0 AND due_date < ? THEN 1 ELSE 0 END) as overdue', [$today])
            ->selectRaw('SUM(CASE WHEN is_completed = 0 AND due_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as due_week', [$today, now()->addDays(7)->toDateString()])
            ->first();

        return [
            'all' => (int) $row->total,
            'pending' => (int) $row->total - (int) $row->completed,
            'overdue' => (int) $row->overdue,
            'due_week' => (int) $row->due_week,
            'completed' => (int) $row->completed,
        ];
    }

    /** @return array{total: int, done: int, percent: int} */
    protected function progress(Builder $query): array
    {
        $row = $query->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as done')
            ->first();

        $total = (int) $row->total;
        $done = (int) $row->done;

        return ['total' => $total, 'done' => $done, 'percent' => $total ? (int) round($done / $total * 100) : 0];
    }

    /**
     * The task policy only checks permissions - also make sure the task
     * belongs to the current company (and branch) before touching it.
     */
    protected function ensureOwnTask(OnboardingTask $task): void
    {
        abort_unless((int) $task->company_id === (int) Company::current()->id, 404);
        abort_if(
            BranchContext::isRestricted() && (int) $task->employee?->branch_id !== (int) BranchContext::currentBranchId(),
            404
        );
    }

    protected function employeeOptions()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
