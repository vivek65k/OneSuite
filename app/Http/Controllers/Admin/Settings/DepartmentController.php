<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Support\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['name', 'code', 'is_active', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Department::class);

        $state = $this->resolveTableState($request, 'departments', [
            'sort' => 'name', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'name', self::SORTABLE, true) ? $state['sort'] : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $departments = $this->filteredQuery($state)
            ->with(['parent', 'branch'])
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.settings.organization.departments.index', [
            'departments' => $departments,
            'branches' => $this->branchOptions(),
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Department::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'name';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $departments = $this->filteredQuery($state)->with(['parent', 'branch'])->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'departments-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Code', 'Branch', 'Parent', 'Active'],
            (function () use ($departments) {
                foreach ($departments as $department) {
                    yield [
                        $department->name,
                        $department->code,
                        $department->branch?->name,
                        $department->parent?->name,
                        $department->is_active ? 'Yes' : 'No',
                    ];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return Department::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->when(! BranchContext::isRestricted() && ($state['branch_id'] ?? null), fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            }))
            ->when(($state['is_active'] ?? '') !== '', fn ($q) => $q->where('is_active', $state['is_active']));
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);

        return view('admin.settings.organization.departments.create', [
            'department' => new Department(),
            'parents' => $this->parentOptions(),
            'branches' => $this->branchOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Department::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        Department::create($data);

        return redirect()->route('admin.departments.index')->with('status', 'Department created.');
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        return view('admin.settings.organization.departments.edit', [
            'department' => $department,
            'parents' => $this->parentOptions($department),
            'branches' => $this->branchOptions(),
        ]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $department->update($this->validated($request, $department));

        return redirect()->route('admin.departments.index')->with('status', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $department->delete();

        return redirect()->route('admin.departments.index')->with('status', 'Department deleted.');
    }

    protected function parentOptions(?Department $except = null)
    {
        return Department::query()
            ->where('company_id', Company::current()->id)
            ->when($except?->exists, fn ($q) => $q->whereKeyNot($except->getKey()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Branches this user is allowed to file a department under - the same
     * set the sidebar switcher offers them (their own assignments, or every
     * company branch for a super admin, who has none of their own).
     */
    protected function branchOptions()
    {
        return BranchContext::switchableBranches()->pluck('name', 'id');
    }

    protected function validated(Request $request, ?Department $department = null): array
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            // A branch-restricted admin may only file a department under one
            // of their OWN assigned branches, never an arbitrary company branch.
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
                BranchContext::isRestricted() ? Rule::in($this->branchOptions()->keys()) : 'nullable',
            ],
            'parent_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
                $department?->exists ? Rule::notIn([$department->id]) : 'nullable',
            ],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
