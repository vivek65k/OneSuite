<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\ApprovalRule;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApprovalRuleController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['name', 'module', 'level', 'is_active', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', ApprovalRule::class);

        $state = $this->resolveTableState($request, 'approval-rules', [
            'sort' => 'level', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'level', self::SORTABLE, true) ? $state['sort'] : 'level';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $rules = $this->filteredQuery($state)
            ->with('department')
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.settings.workflow-approvals.approval-rules.index', [
            'rules' => $rules,
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', ApprovalRule::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'level';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'level';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $rules = $this->filteredQuery($state)->with('department')->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'approval-rules-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Module', 'Department', 'Min Amount', 'Max Amount', 'Approver Role', 'Level', 'Active'],
            (function () use ($rules) {
                foreach ($rules as $rule) {
                    yield [
                        $rule->name, $rule->module, $rule->department?->name,
                        $rule->min_amount, $rule->max_amount, $rule->approver_role,
                        $rule->level, $rule->is_active ? 'Yes' : 'No',
                    ];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return ApprovalRule::query()
            ->where('company_id', Company::current()->id)
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('module', 'like', "%{$search}%");
            }));
    }

    public function create(): View
    {
        $this->authorize('create', ApprovalRule::class);

        return view('admin.settings.workflow-approvals.approval-rules.create', [
            'rule' => new ApprovalRule(),
            'departments' => $this->departmentOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ApprovalRule::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        ApprovalRule::create($data);

        return redirect()->route('admin.approval-rules.index')->with('status', 'Approval rule created.');
    }

    public function edit(ApprovalRule $approvalRule): View
    {
        $this->authorize('update', $approvalRule);

        return view('admin.settings.workflow-approvals.approval-rules.edit', [
            'rule' => $approvalRule,
            'departments' => $this->departmentOptions(),
        ]);
    }

    public function update(Request $request, ApprovalRule $approvalRule): RedirectResponse
    {
        $this->authorize('update', $approvalRule);

        $approvalRule->update($this->validated($request));

        return redirect()->route('admin.approval-rules.index')->with('status', 'Approval rule updated.');
    }

    public function destroy(ApprovalRule $approvalRule): RedirectResponse
    {
        $this->authorize('delete', $approvalRule);

        $approvalRule->delete();

        return redirect()->route('admin.approval-rules.index')->with('status', 'Approval rule deleted.');
    }

    protected function departmentOptions()
    {
        return Department::query()->where('company_id', Company::current()->id)->orderBy('name')->pluck('name', 'id');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'module' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'approver_role' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
