<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Workflow;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkflowController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['name', 'module', 'is_active', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Workflow::class);

        $state = $this->resolveTableState($request, 'workflows', [
            'sort' => 'name', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'name', self::SORTABLE, true) ? $state['sort'] : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $workflows = $this->filteredQuery($state)
            ->withCount('steps')
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.settings.workflow-approvals.workflows.index', [
            'workflows' => $workflows,
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Workflow::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'name';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $workflows = $this->filteredQuery($state)->withCount('steps')->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'workflows-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Module', 'Steps', 'Active'],
            (function () use ($workflows) {
                foreach ($workflows as $workflow) {
                    yield [$workflow->name, $workflow->module, $workflow->steps_count, $workflow->is_active ? 'Yes' : 'No'];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return Workflow::query()
            ->where('company_id', Company::current()->id)
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('module', 'like', "%{$search}%");
            }));
    }

    public function create(): View
    {
        $this->authorize('create', Workflow::class);

        return view('admin.settings.workflow-approvals.workflows.create', ['workflow' => new Workflow()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Workflow::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        $workflow = Workflow::create($data);

        $this->syncSteps($workflow, $request);

        return redirect()->route('admin.workflows.index')->with('status', 'Workflow created.');
    }

    public function edit(Workflow $workflow): View
    {
        $this->authorize('update', $workflow);

        return view('admin.settings.workflow-approvals.workflows.edit', [
            'workflow' => $workflow,
            'steps' => $workflow->steps,
        ]);
    }

    public function update(Request $request, Workflow $workflow): RedirectResponse
    {
        $this->authorize('update', $workflow);

        $workflow->update($this->validated($request));

        $this->syncSteps($workflow, $request);

        return redirect()->route('admin.workflows.index')->with('status', 'Workflow updated.');
    }

    public function destroy(Workflow $workflow): RedirectResponse
    {
        $this->authorize('delete', $workflow);

        $workflow->delete();

        return redirect()->route('admin.workflows.index')->with('status', 'Workflow deleted.');
    }

    protected function syncSteps(Workflow $workflow, Request $request): void
    {
        $steps = $request->input('steps', []);

        $workflow->steps()->delete();

        foreach (array_values($steps) as $index => $step) {
            if (blank($step['name'] ?? null)) {
                continue;
            }

            $workflow->steps()->create([
                'name' => $step['name'],
                'approver_role' => $step['approver_role'] ?? null,
                'sequence' => $index + 1,
            ]);
        }
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'module' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'steps' => ['nullable', 'array'],
            'steps.*.name' => ['nullable', 'string', 'max:255'],
            'steps.*.approver_role' => ['nullable', 'string', 'max:255'],
        ]);

        unset($data['steps']);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
