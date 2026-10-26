<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Models\Company;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AutomationRuleController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['name', 'trigger_event', 'action', 'is_active', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', AutomationRule::class);

        $state = $this->resolveTableState($request, 'automation-rules', [
            'sort' => 'name', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'name', self::SORTABLE, true) ? $state['sort'] : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $rules = $this->filteredQuery($state)
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.settings.workflow-approvals.automation-rules.index', [
            'rules' => $rules,
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', AutomationRule::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'name';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $rules = $this->filteredQuery($state)->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'automation-rules-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Trigger', 'Action', 'Action target', 'Active'],
            (function () use ($rules) {
                foreach ($rules as $rule) {
                    yield [$rule->name, $rule->trigger_event, $rule->action, $rule->action_target, $rule->is_active ? 'Yes' : 'No'];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return AutomationRule::query()
            ->where('company_id', Company::current()->id)
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('trigger_event', 'like', "%{$search}%");
            }));
    }

    public function create(): View
    {
        $this->authorize('create', AutomationRule::class);

        return view('admin.settings.workflow-approvals.automation-rules.create', ['rule' => new AutomationRule()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', AutomationRule::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        AutomationRule::create($data);

        return redirect()->route('admin.automation-rules.index')->with('status', 'Automation rule created.');
    }

    public function edit(AutomationRule $automationRule): View
    {
        $this->authorize('update', $automationRule);

        return view('admin.settings.workflow-approvals.automation-rules.edit', ['rule' => $automationRule]);
    }

    public function update(Request $request, AutomationRule $automationRule): RedirectResponse
    {
        $this->authorize('update', $automationRule);

        $automationRule->update($this->validated($request));

        return redirect()->route('admin.automation-rules.index')->with('status', 'Automation rule updated.');
    }

    public function destroy(AutomationRule $automationRule): RedirectResponse
    {
        $this->authorize('delete', $automationRule);

        $automationRule->delete();

        return redirect()->route('admin.automation-rules.index')->with('status', 'Automation rule deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_event' => ['required', 'string', 'max:255'],
            'action' => ['required', 'string', 'max:255'],
            'action_target' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
