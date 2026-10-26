<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Department;
use App\Models\JobPosition;
use App\Support\BranchContext;
use App\Support\EmploymentType;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobPositionController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['title', 'status', 'headcount', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', JobPosition::class);

        $state = $this->resolveTableState($request, 'job-positions', [
            'sort' => 'title', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'title', self::SORTABLE, true) ? $state['sort'] : 'title';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $positions = $this->filteredQuery($state)
            ->with(['department', 'branch'])
            ->withCount('candidates')
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        // Status-tab counts ignore the status filter itself (so every tab
        // shows what clicking it would return) but respect the rest.
        $statusCounts = $this->filteredQuery(array_diff_key($state, ['status' => true]))
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count);

        // Headline KPIs are company/branch-wide, unaffected by list filters.
        $kpis = $this->filteredQuery([])
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open")
            ->selectRaw("SUM(CASE WHEN status = 'open' THEN headcount ELSE 0 END) as openings")
            ->first();

        return view('admin.hrm.recruitment.job-positions.index', [
            'positions' => $positions,
            'kpis' => array_map('intval', (array) $kpis) + [
                'candidates' => Candidate::query()->whereIn('job_position_id', $this->filteredQuery([])->select('id'))->count(),
            ],
            'statusCounts' => $statusCounts->put('all', (int) $statusCounts->sum()),
            'departments' => $this->departmentOptions(),
            'branches' => $this->branchOptions(),
            'employmentTypes' => collect($this->employmentTypeOptions())->map(fn ($option) => $option['label']),
            'currency' => Company::current()->currency,
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function openJobs(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', JobPosition::class);

        $state = $this->resolveTableState($request, 'open-jobs', [
            'sort' => 'title', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'title', self::SORTABLE, true) ? $state['sort'] : 'title';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $positions = $this->filteredQuery($state)
            ->where('status', 'open')
            ->withCount([
                'candidates',
                'candidates as hired_count' => fn ($q) => $q->where('stage', 'hired'),
                'candidates as in_pipeline_count' => fn ($q) => $q->whereNotIn('stage', ['hired', 'rejected']),
            ])
            ->with(['department', 'branch'])
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        $openIds = $this->filteredQuery([])->where('status', 'open')->select('id');
        $kpis = [
            'open' => $this->filteredQuery([])->where('status', 'open')->count(),
            'openings' => (int) $this->filteredQuery([])->where('status', 'open')->sum('headcount'),
            'applicants' => Candidate::query()->whereIn('job_position_id', $openIds)->count(),
            'in_pipeline' => Candidate::query()->whereIn('job_position_id', $openIds)->whereNotIn('stage', ['hired', 'rejected'])->count(),
            'hired' => Candidate::query()->whereIn('job_position_id', $openIds)->where('stage', 'hired')->count(),
        ];

        return view('admin.hrm.recruitment.job-positions.open', [
            'positions' => $positions,
            'kpis' => $kpis,
            'departments' => $this->departmentOptions(),
            'branches' => $this->branchOptions(),
            'employmentTypes' => collect($this->employmentTypeOptions())->map(fn ($option) => $option['label']),
            'currency' => Company::current()->currency,
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', JobPosition::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'title';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'title';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $positions = $this->filteredQuery($state)->with(['department', 'branch'])->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'job-positions-'.now()->format('Y-m-d-His').'.csv',
            ['Title', 'Department', 'Branch', 'Employment Type', 'Headcount', 'Status', 'Min Salary', 'Max Salary'],
            (function () use ($positions) {
                foreach ($positions as $position) {
                    yield [
                        $position->title, $position->department?->name, $position->branch?->name,
                        $position->employment_type, $position->headcount, $position->status,
                        $position->min_salary, $position->max_salary,
                    ];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return JobPosition::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($state['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->where('department_id', $departmentId))
            ->when($state['branch_id'] ?? null, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($state['employment_type'] ?? null, fn ($q, $type) => $q->whereIn(
                'employment_type', $this->employmentTypeOptions()[$type]['values'] ?? [$type]
            ));
    }

    /** @return array<string, array{label: string, values: list<string>}> */
    protected function employmentTypeOptions(): array
    {
        return $this->employmentTypeOptionsCache ??= EmploymentType::inUse(
            JobPosition::query()->where('company_id', Company::current()->id)
        );
    }

    protected ?array $employmentTypeOptionsCache = null;

    public function create(): View
    {
        $this->authorize('create', JobPosition::class);

        return view('admin.hrm.recruitment.job-positions.create', [
            'position' => new JobPosition(),
            'departments' => $this->departmentOptions(),
            'branches' => $this->branchOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', JobPosition::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        JobPosition::create($data);

        return redirect()->route('admin.job-positions.index')->with('status', 'Job position created.');
    }

    public function edit(JobPosition $jobPosition): View
    {
        $this->authorize('update', $jobPosition);

        return view('admin.hrm.recruitment.job-positions.edit', [
            'position' => $jobPosition,
            'departments' => $this->departmentOptions(),
            'branches' => $this->branchOptions(),
        ]);
    }

    public function update(Request $request, JobPosition $jobPosition): RedirectResponse
    {
        $this->authorize('update', $jobPosition);

        $jobPosition->update($this->validated($request));

        return redirect()->route('admin.job-positions.index')->with('status', 'Job position updated.');
    }

    public function destroy(JobPosition $jobPosition): RedirectResponse
    {
        $this->authorize('delete', $jobPosition);

        $jobPosition->delete();

        return redirect()->route('admin.job-positions.index')->with('status', 'Job position deleted.');
    }

    protected function departmentOptions()
    {
        return Department::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function branchOptions()
    {
        return BranchContext::switchableBranches()->pluck('name', 'id');
    }

    protected function validated(Request $request): array
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
                BranchContext::isRestricted() ? Rule::in($this->branchOptions()->keys()) : 'nullable',
            ],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'headcount' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(JobPosition::STATUSES)],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'gte:min_salary'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        return $data;
    }
}
