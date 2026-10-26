<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EscalationRule;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EscalationRuleController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['name', 'module', 'after_hours', 'is_active', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', EscalationRule::class);

        $state = $this->resolveTableState($request, 'escalation-rules', [
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

        return view('admin.settings.workflow-approvals.escalation-rules.index', [
            'rules' => $rules,
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', EscalationRule::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'name';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $rules = $this->filteredQuery($state)->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'escalation-rules-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Module', 'After (hours)', 'Escalate To', 'Active'],
            (function () use ($rules) {
                foreach ($rules as $rule) {
                    yield [$rule->name, $rule->module, $rule->after_hours, $rule->escalate_to_role, $rule->is_active ? 'Yes' : 'No'];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return EscalationRule::query()
            ->where('company_id', Company::current()->id)
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('module', 'like', "%{$search}%");
            }));
    }

    public function create(): View
    {
        $this->authorize('create', EscalationRule::class);

        return view('admin.settings.workflow-approvals.escalation-rules.create', ['rule' => new EscalationRule()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EscalationRule::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        EscalationRule::create($data);

        return redirect()->route('admin.escalation-rules.index')->with('status', 'Escalation rule created.');
    }

    public function edit(EscalationRule $escalationRule): View
    {
        $this->authorize('update', $escalationRule);

        return view('admin.settings.workflow-approvals.escalation-rules.edit', ['rule' => $escalationRule]);
    }

    public function update(Request $request, EscalationRule $escalationRule): RedirectResponse
    {
        $this->authorize('update', $escalationRule);

        $escalationRule->update($this->validated($request));

        return redirect()->route('admin.escalation-rules.index')->with('status', 'Escalation rule updated.');
    }

    public function destroy(EscalationRule $escalationRule): RedirectResponse
    {
        $this->authorize('delete', $escalationRule);

        $escalationRule->delete();

        return redirect()->route('admin.escalation-rules.index')->with('status', 'Escalation rule deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'module' => ['nullable', 'string', 'max:255'],
            'after_hours' => ['required', 'integer', 'min:1'],
            'escalate_to_role' => ['required', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
