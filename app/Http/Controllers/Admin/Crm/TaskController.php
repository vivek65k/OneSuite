<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\Crm\ResolvesRelatable;
use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Activity;
use App\Models\Crm\Team;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    use ResolvesCurrentEmployee, ResolvesRelatable;

    protected const SORTABLE = ['subject', 'due_at', 'priority', 'created_at'];

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'pending');
        $scope = (string) $request->query('scope', 'all');
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'due_at';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query = $this->companyQuery()->with('owner');
        $teamId = $request->query('team_id');

        if ($scope === 'mine') {
            $query->where('owner_employee_id', optional(auth()->user()->employee)->id);
        } elseif ($scope === 'team') {
            $myTeamIds = Team::where('company_id', $companyId)
                ->whereHas('members', fn ($q) => $q->where('employee_id', optional(auth()->user()->employee)->id))
                ->pluck('id');
            $employeeIds = Team::whereIn('id', $teamId ? [$teamId] : $myTeamIds)->get()
                ->flatMap(fn ($team) => $team->members->pluck('id'));
            $query->whereIn('owner_employee_id', $employeeIds);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where('subject', 'like', "%{$search}%");
        }

        $tasks = $query->orderBy($sort, $direction)->paginate(25)->withQueryString();

        return view('admin.crm.tasks.index', [
            'tasks' => $tasks->through(fn ($task) => tap($task, fn ($t) => $t->relatedLabel = $this->relatedLabel($t->related_type, $t->related_id))),
            'search' => $search,
            'status' => $status,
            'scope' => $scope,
            'teams' => Team::where('company_id', $companyId)->orderBy('name')->get(),
            'teamId' => $teamId,
            'sort' => $sort,
            'direction' => $direction,
            'total' => $this->companyQuery()->where('status', 'pending')->count(),
            'overdueCount' => $this->companyQuery()->where('status', 'pending')->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'dueTodayCount' => $this->companyQuery()->where('status', 'pending')->whereDate('due_at', now()->toDateString())->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.tasks.form', ['task' => new Activity, 'relatable' => $this->relatableOptions(), 'employees' => $this->employeeOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Activity::create($this->validated($request) + ['company_id' => Company::current()->id, 'type' => 'task', 'status' => 'pending']);

        return redirect()->route('admin.crm.tasks.index')->with('status', 'Task created.');
    }

    public function edit(Activity $task): View
    {
        $this->ownCompany($task);

        return view('admin.crm.tasks.form', [
            'task' => $task,
            'relatable' => $this->relatableOptions(),
            'employees' => $this->employeeOptions(),
            'currentRelatable' => $this->relatableValue($task->related_type, $task->related_id),
        ]);
    }

    public function update(Request $request, Activity $task): RedirectResponse
    {
        $this->ownCompany($task);
        $task->update($this->validated($request));

        return redirect()->route('admin.crm.tasks.index')->with('status', 'Task updated.');
    }

    public function destroy(Activity $task): RedirectResponse
    {
        $this->ownCompany($task);
        $task->delete();

        return back()->with('status', 'Task deleted.');
    }

    public function complete(Activity $task): RedirectResponse
    {
        $this->ownCompany($task);
        $task->update(['status' => 'completed', 'completed_at' => now()]);

        if ($task->is_recurring) {
            Activity::create([
                'company_id' => $task->company_id, 'type' => 'task', 'subject' => $task->subject,
                'description' => $task->description, 'related_type' => $task->related_type, 'related_id' => $task->related_id,
                'due_at' => $task->computeNextOccurrence(), 'status' => 'pending', 'priority' => $task->priority,
                'owner_employee_id' => $task->owner_employee_id, 'is_recurring' => true,
                'recurrence_frequency' => $task->recurrence_frequency, 'recurrence_parent_id' => $task->recurrence_parent_id ?? $task->id,
            ]);
        }

        return back()->with('status', 'Task marked complete.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['required', 'in:'.implode(',', array_keys(Activity::PRIORITIES))],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
            'reminder_at' => ['nullable', 'date'],
            'relatable' => ['nullable', 'string'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_frequency' => ['nullable', 'in:'.implode(',', array_keys(Activity::RECURRENCE_FREQUENCIES))],
        ]);

        $data['is_recurring'] = $request->boolean('is_recurring');

        $relatable = $data['relatable'] ?? null;
        unset($data['relatable']);
        $data['related_type'] = $relatable ? $this->relatedTypeFromKey($relatable) : null;
        $data['related_id'] = $relatable ? $this->relatedIdFromKey($relatable) : null;

        return $data;
    }

    protected function employeeOptions()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->where('status', 'active')
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function companyQuery()
    {
        return Activity::query()->where('company_id', Company::current()->id)->where('type', 'task');
    }

    protected function ownCompany(Activity $task): void
    {
        abort_unless($task->company_id === Company::current()->id && $task->type === 'task', 404);
    }
}
