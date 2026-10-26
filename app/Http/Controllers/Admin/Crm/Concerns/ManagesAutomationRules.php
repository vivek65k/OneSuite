<?php

namespace App\Http\Controllers\Admin\Crm\Concerns;

use App\Models\Company;
use App\Models\Crm\AutomationRule;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

trait ManagesAutomationRules
{
    abstract protected function category(): string;

    abstract protected function viewPrefix(): string;

    abstract protected function indexRoute(): string;

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view($this->viewPrefix().'.index', [
            'rules' => AutomationRule::where('company_id', $companyId)->where('category', $this->category())
                ->with('actionEmployee')->orderByDesc('created_at')->get(),
            'employees' => Employee::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'entities' => AutomationRule::ENTITIES,
            'triggerEvents' => AutomationRule::TRIGGER_EVENTS,
            'actionTypes' => AutomationRule::ACTION_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'entity' => ['nullable', 'string'],
            'trigger_event' => ['nullable', 'string'],
            'condition_field' => ['nullable', 'string', 'max:100'],
            'condition_operator' => ['nullable', 'string', 'max:10'],
            'condition_value' => ['nullable', 'string', 'max:255'],
            'action_type' => ['nullable', 'string'],
            'action_employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        AutomationRule::create($data + ['company_id' => Company::current()->id, 'category' => $this->category(), 'is_active' => true]);

        return back()->with('status', 'Rule created.');
    }

    public function toggle(AutomationRule $automationRule): RedirectResponse
    {
        abort_unless($automationRule->company_id === Company::current()->id, 404);
        $automationRule->update(['is_active' => ! $automationRule->is_active]);

        return back()->with('status', $automationRule->is_active ? 'Rule activated.' : 'Rule paused.');
    }

    public function destroy(AutomationRule $automationRule): RedirectResponse
    {
        abort_unless($automationRule->company_id === Company::current()->id, 404);
        $automationRule->delete();

        return redirect()->route($this->indexRoute())->with('status', 'Rule removed.');
    }

    public function run(AutomationRule $automationRule): RedirectResponse
    {
        abort_unless($automationRule->company_id === Company::current()->id, 404);
        $matched = $automationRule->execute();

        return back()->with('status', "Rule executed - {$matched} record(s) affected.");
    }
}
