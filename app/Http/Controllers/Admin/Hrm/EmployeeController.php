<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Scopes\EmployeeDataScope;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\ReportingChain;
use App\Support\EmploymentType;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['name', 'employee_code', 'designation', 'status', 'joining_date', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Employee::class);

        $state = $this->resolveTableState($request, 'employees', [
            'sort' => 'name', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'name', self::SORTABLE, true) ? $state['sort'] : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $employees = $this->filteredQuery($state)
            ->with(['department', 'branch'])
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        // Status-tab counts ignore the status filter itself (so every tab
        // shows what clicking it would return) but respect the rest.
        $statusCounts = $this->filteredQuery(array_diff_key($state, ['status' => true]))
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        // Headline KPIs are company/branch-wide, unaffected by list filters.
        $kpis = $this->filteredQuery([])
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active")
            ->selectRaw("SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) as on_leave")
            ->selectRaw('SUM(CASE WHEN joining_date >= ? THEN 1 ELSE 0 END) as joined_this_month', [now()->startOfMonth()->toDateString()])
            ->first();

        return view('admin.hrm.employees.index', [
            'kpis' => array_map('intval', (array) $kpis),
            'employees' => $employees,
            'departments' => $this->departmentOptions(),
            'branches' => $this->branchOptions(),
            'employmentTypes' => collect($this->employmentTypeOptions())->map(fn ($option) => $option['label']),
            'statusCounts' => $statusCounts->map(fn ($count) => (int) $count)->put('all', (int) $statusCounts->sum()),
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Employee::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'name';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $employees = $this->filteredQuery($state)->with(['department', 'branch'])->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'employees-'.now()->format('Y-m-d-His').'.csv',
            ['Employee Code', 'Name', 'Email', 'Phone', 'Designation', 'Department', 'Branch', 'Status', 'Joining Date'],
            (function () use ($employees) {
                foreach ($employees as $employee) {
                    yield [
                        $employee->employee_code, $employee->name, $employee->email, $employee->phone,
                        $employee->designation, $employee->department?->name, $employee->branch?->name,
                        $employee->status, optional($employee->joining_date)->format('Y-m-d'),
                    ];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($state['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->where('department_id', $departmentId))
            ->when($state['branch_id'] ?? null, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($state['employment_type'] ?? null, fn ($q, $type) => $q->whereIn(
                'employment_type', $this->employmentTypeOptions()[$type]['values'] ?? [$type]
            ))
            ->when($state['joined_from'] ?? null, fn ($q, $date) => $q->whereDate('joining_date', '>=', $date))
            ->when($state['joined_until'] ?? null, fn ($q, $date) => $q->whereDate('joining_date', '<=', $date));
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('admin.hrm.employees.create', [
            'employee' => new Employee(),
            'departments' => $this->departmentOptions(),
            'branches' => $this->branchOptions(),
            'managers' => $this->managerOptions(),
            'users' => $this->userOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        $employee = Employee::create($data);

        return redirect()->route('admin.employees.show', $employee)->with('status', 'Employee added.');
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load(['department', 'branch', 'manager']);

        // The line above them, top first. Loaded without the data-access
        // filter on purpose: "who is my manager's manager" is not private,
        // and the people above someone are by definition outside their team.
        $aboveIds = ReportingChain::above((int) $employee->id, (int) $employee->company_id);
        $above = Employee::withoutGlobalScope(EmployeeDataScope::class)
            ->whereIn('id', $aboveIds)->get(['id', 'name', 'designation', 'employee_code', 'avatar'])
            ->sortBy(fn (Employee $manager) => -array_search($manager->id, $aboveIds, true))->values();

        return view('admin.hrm.employees.show', [
            'employee' => $employee,
            'reportingLine' => $above,
            'directReports' => $employee->directReports()->whereNotIn('status', ['inactive', 'terminated'])->orderBy('name')
                ->get(['id', 'name', 'designation', 'employee_code', 'avatar']),
            'teamSize' => count(ReportingChain::below((int) $employee->id, (int) $employee->company_id)),
        ]);
    }

    public function timeline(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load(['promotions', 'transfers', 'salaries']);

        return view('admin.hrm.employees.timeline', [
            'employee' => $employee,
            'timeline' => $this->buildTimeline($employee),
        ]);
    }

    public function history(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load(['promotions', 'transfers', 'salaries']);

        return view('admin.hrm.employees.history', [
            'employee' => $employee,
            'timeline' => $this->buildTimeline($employee),
        ]);
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        return view('admin.hrm.employees.edit', [
            'employee' => $employee,
            'departments' => $this->departmentOptions(),
            'branches' => $this->branchOptions(),
            'managers' => $this->managerOptions($employee),
            'users' => $this->userOptions($employee),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        $employee->update($this->validated($request, $employee));

        return redirect()->route('admin.employees.show', $employee)->with('status', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $employee->delete();

        return redirect()->route('admin.employees.index')->with('status', 'Employee deleted.');
    }

    /**
     * Combine every dated event on the employee's record into one
     * chronological feed for the Timeline / History tabs.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function buildTimeline(Employee $employee)
    {
        $events = collect();

        if ($employee->joining_date) {
            $events->push([
                'date' => $employee->joining_date,
                'type' => 'Joined',
                'icon' => 'ti-login',
                'description' => $employee->designation
                    ? "Joined as {$employee->designation}"
                    : 'Joined the company',
            ]);
        }

        foreach ($employee->promotions as $promotion) {
            $events->push([
                'date' => $promotion->promotion_date,
                'type' => 'Promotion',
                'icon' => 'ti-arrow-up-circle',
                'description' => $promotion->from_designation
                    ? "Promoted from {$promotion->from_designation} to {$promotion->to_designation}"
                    : "Promoted to {$promotion->to_designation}",
            ]);
        }

        foreach ($employee->transfers as $transfer) {
            $events->push([
                'date' => $transfer->transfer_date,
                'type' => 'Transfer',
                'icon' => 'ti-exchange',
                'description' => 'Transferred'
                    .($transfer->toDepartment ? " to {$transfer->toDepartment->name}" : '')
                    .($transfer->toBranch ? " ({$transfer->toBranch->name})" : ''),
            ]);
        }

        foreach ($employee->salaries as $salary) {
            $events->push([
                'date' => $salary->effective_date,
                'type' => 'Salary change',
                'icon' => 'ti-cash',
                'description' => 'Salary set to '.number_format((float) $salary->amount, 2).' '.($salary->currency ?? ''),
            ]);
        }

        return $events->sortByDesc('date')->values();
    }

    /** @return array<string, array{label: string, values: list<string>}> */
    protected function employmentTypeOptions(): array
    {
        return $this->employmentTypeOptionsCache ??= EmploymentType::inUse(
            Employee::query()->where('company_id', Company::current()->id)
        );
    }

    protected ?array $employmentTypeOptionsCache = null;

    protected function departmentOptions()
    {
        return Department::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Branches this user is allowed to file an employee under - the same
     * set the sidebar switcher offers them (their own assignments, or every
     * company branch for a super admin, who has none of their own).
     */
    protected function branchOptions()
    {
        return BranchContext::switchableBranches()->pluck('name', 'id');
    }

    /**
     * Who this employee may report to: anyone in the company except
     * themselves and everyone below them (reporting to your own report
     * would close a loop). Labelled with the designation, since names
     * alone don't tell two people apart.
     */
    protected function managerOptions(?Employee $except = null)
    {
        $companyId = Company::current()->id;
        $excluded = $except?->exists
            ? array_merge([(int) $except->getKey()], ReportingChain::below((int) $except->getKey(), (int) $companyId))
            : [];

        return Employee::query()
            ->where('company_id', $companyId)
            ->when($excluded, fn ($q) => $q->whereNotIn('id', $excluded))
            ->orderBy('name')
            ->get(['id', 'name', 'designation'])
            ->mapWithKeys(fn (Employee $manager) => [$manager->id => $manager->name.($manager->designation ? ' - '.$manager->designation : '')]);
    }

    /**
     * Logins available to link for Employee Self Service: any user not
     * already linked to a different employee, plus this employee's own
     * current login (so it still shows up as selected on the edit form).
     */
    protected function userOptions(?Employee $employee = null)
    {
        return User::query()
            ->where('company_id', Company::current()->id)
            ->where(fn ($q) => $q->doesntHave('employee')
                ->when($employee?->user_id, fn ($q2, $userId) => $q2->orWhere('id', $userId)))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function validated(Request $request, ?Employee $employee = null): array
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_code' => [
                'required', 'string', 'max:50',
                Rule::unique('employees', 'employee_code')->where('company_id', $companyId)->ignore($employee?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            // A branch-restricted admin may only file an employee under one of
            // their own assigned branches (and that branch's own departments).
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
                BranchContext::isRestricted() ? Rule::in($this->departmentOptions()->keys()) : 'nullable',
            ],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
                BranchContext::isRestricted() ? Rule::in($this->branchOptions()->keys()) : 'nullable',
            ],
            'manager_id' => [
                'nullable',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
                // Only someone the form itself offered: never the employee
                // themselves or anyone below them (no loops), and under
                // team/own data access only people the role reaches. Any
                // branch is fine - a branch head reports to head office.
                Rule::in($this->managerOptions($employee)->keys()),
            ],
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('company_id', $companyId),
                Rule::unique('employees', 'user_id')->ignore($employee?->id),
            ],
            'designation' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'joining_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(Employee::STATUSES)],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        return $data;
    }
}
