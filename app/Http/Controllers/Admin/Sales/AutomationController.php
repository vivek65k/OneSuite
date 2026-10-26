<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\AutomationRule;
use App\Models\Crm\Quota;
use App\Models\Crm\SalesOrderLine;
use App\Models\Employee;
use App\Models\Sales\CustomerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rules live in crm_automation_rules (shared with CRM's Assignment/
 * Escalation/Follow-up screens - AutomationRule::ENTITIES was extended
 * with quotation/sales_order rather than building a parallel engine).
 * This screen shows all rules targeting those two entities, across any
 * category, plus a set of computed "smart recommendations" from real
 * Sales data - the "AI" here is the same kind of intelligent-query
 * pattern CRM's own Ai* controllers already use, not a trained model.
 */
class AutomationController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.sales.automation.index', [
            'rules' => AutomationRule::where('company_id', $companyId)
                ->whereIn('entity', ['quotation', 'sales_order'])->with('actionEmployee')->orderByDesc('created_at')->get(),
            'employees' => Employee::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'categories' => AutomationRule::CATEGORIES,
            'entities' => ['quotation' => AutomationRule::ENTITIES['quotation'], 'sales_order' => AutomationRule::ENTITIES['sales_order']],
            'triggerEvents' => AutomationRule::TRIGGER_EVENTS,
            'actionTypes' => AutomationRule::ACTION_TYPES,
            'recommendations' => $this->recommendations($companyId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.implode(',', array_keys(AutomationRule::CATEGORIES))],
            'entity' => ['required', 'in:quotation,sales_order'],
            'trigger_event' => ['nullable', 'string'],
            'condition_value' => ['nullable', 'string', 'max:255'],
            'action_type' => ['nullable', 'string'],
            'action_employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        AutomationRule::create($data + ['company_id' => Company::current()->id, 'is_active' => true]);

        return redirect()->route('admin.sales.automation.index')->with('status', 'Rule created.');
    }

    public function toggle(AutomationRule $automationRule): RedirectResponse
    {
        $this->ownCompany($automationRule);
        $automationRule->update(['is_active' => ! $automationRule->is_active]);

        return back()->with('status', $automationRule->is_active ? 'Rule activated.' : 'Rule paused.');
    }

    public function run(AutomationRule $automationRule): RedirectResponse
    {
        $this->ownCompany($automationRule);
        $matched = $automationRule->execute();

        return back()->with('status', "Rule executed - {$matched} record(s) affected.");
    }

    public function destroy(AutomationRule $automationRule): RedirectResponse
    {
        $this->ownCompany($automationRule);
        $automationRule->delete();

        return back()->with('status', 'Rule removed.');
    }

    /**
     * Computed insights from real Sales/Inventory/CRM data - every row
     * links straight to the screen where it can be acted on.
     */
    protected function recommendations(int $companyId): array
    {
        $backorderLines = SalesOrderLine::whereHas('salesOrder', fn ($q) => $q->where('company_id', $companyId)->whereIn('status', ['confirmed', 'partially_fulfilled']))
            ->whereColumn('fulfilled_qty', '<', 'quantity')->with(['salesOrder', 'product'])->get();

        $overLimitCustomers = CustomerProfile::where('company_id', $companyId)
            ->whereColumn('outstanding_balance', '>', 'credit_limit')->where('credit_limit', '>', 0)->with('account')->get();

        $quotasAtRisk = Quota::where('company_id', $companyId)->get()->filter(function ($quota) {
            $elapsedPct = min(1, now()->diffInDays($quota->period_start, true) / max(1, $quota->period_start->diffInDays($quota->periodEnd())));
            $achievedPct = $quota->target_amount > 0 ? $quota->achievedSalesAmount() / $quota->target_amount : 1;

            return now()->between($quota->period_start, $quota->periodEnd()) && $elapsedPct > 0.5 && $achievedPct < $elapsedPct - 0.15;
        })->values();

        return [
            'backorders' => $backorderLines,
            'overLimitCustomers' => $overLimitCustomers,
            'quotasAtRisk' => $quotasAtRisk,
        ];
    }

    protected function ownCompany(AutomationRule $rule): void
    {
        abort_unless($rule->company_id === Company::current()->id, 404);
    }
}
