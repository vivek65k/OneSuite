<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRule extends Model
{
    protected $table = 'crm_automation_rules';

    public const CATEGORIES = [
        'assignment' => 'Assignment Rule',
        'approval' => 'Approval Rule',
        'escalation' => 'Escalation Rule',
        'follow_up' => 'Follow-up Automation',
        'trigger' => 'Trigger & Action',
    ];

    public const ENTITIES = [
        'lead' => 'Lead', 'case' => 'Case', 'opportunity' => 'Opportunity',
        'quotation' => 'Quotation', 'sales_order' => 'Sales Order',
    ];

    public const TRIGGER_EVENTS = [
        'lead_created' => 'Lead Created',
        'case_created' => 'Case Created',
        'opportunity_won' => 'Opportunity Won',
        'opportunity_stage_changed' => 'Opportunity Stage Changed',
        'no_activity' => 'No Activity For X Days',
        'quotation_stalled' => 'Quotation Sent, No Follow-up For X Days',
        'sales_order_confirmed' => 'Sales Order Confirmed, Unassigned',
    ];

    public const ACTION_TYPES = [
        'assign_to' => 'Assign To',
        'escalate_to' => 'Escalate To',
        'require_approval_from' => 'Require Approval From',
        'create_task' => 'Create Follow-up Task',
        'send_notification' => 'Send Notification',
    ];

    protected $fillable = [
        'company_id', 'category', 'name', 'entity', 'trigger_event',
        'condition_field', 'condition_operator', 'condition_value',
        'action_type', 'action_employee_id', 'is_active', 'run_count', 'last_run_at',
    ];

    protected $casts = ['is_active' => 'boolean', 'last_run_at' => 'datetime'];

    public function actionEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'action_employee_id');
    }

    /**
     * Runs this rule's matching logic now and returns how many records it
     * touched. Shared by every "Run" button across CRM's rule screens
     * (Assignment/Escalation/Follow-up) and the Sales app's Automation
     * screen - manually triggered rather than event-driven, same as every
     * other "Generate"/"Recalculate" action elsewhere in OneSuite.
     */
    public function execute(): int
    {
        $companyId = $this->company_id;
        $matched = 0;

        if ($this->category === 'assignment' && $this->entity === 'lead' && $this->action_employee_id) {
            $leads = Lead::where('company_id', $companyId)->whereNull('assigned_to_employee_id')->get();
            foreach ($leads as $lead) {
                $lead->update(['assigned_to_employee_id' => $this->action_employee_id]);
                $matched++;
            }
        } elseif ($this->category === 'escalation' && $this->entity === 'case' && $this->action_employee_id) {
            $cases = CaseModel::where('company_id', $companyId)->whereNotIn('status', ['resolved', 'closed'])
                ->where('priority', 'urgent')->where('is_escalated', false)->get();
            foreach ($cases as $case) {
                $case->update(['is_escalated' => true, 'escalated_to_employee_id' => $this->action_employee_id, 'escalated_at' => now()]);
                $matched++;
            }
        } elseif ($this->category === 'follow_up' && $this->entity === 'lead') {
            $days = (int) ($this->condition_value ?: 7);
            $leads = Lead::where('company_id', $companyId)->whereNotIn('status', ['converted', 'unqualified'])
                ->where('created_at', '<=', now()->subDays($days))->get();
            foreach ($leads as $lead) {
                $exists = Activity::where('company_id', $companyId)->where('related_type', Lead::class)->where('related_id', $lead->id)
                    ->where('type', 'task')->where('status', 'pending')->exists();
                if (! $exists) {
                    Activity::create([
                        'company_id' => $companyId, 'type' => 'task', 'subject' => 'Follow up with '.$lead->name,
                        'related_type' => Lead::class, 'related_id' => $lead->id, 'status' => 'pending',
                        'priority' => 'medium', 'due_at' => now()->addDay(),
                        'owner_employee_id' => $this->action_employee_id ?: $lead->assigned_to_employee_id,
                    ]);
                    $matched++;
                }
            }
        } elseif ($this->category === 'follow_up' && $this->entity === 'quotation') {
            $days = (int) ($this->condition_value ?: 7);
            $quotations = Quotation::where('company_id', $companyId)->where('status', 'sent')
                ->where('updated_at', '<=', now()->subDays($days))->get();
            foreach ($quotations as $quotation) {
                $exists = Activity::where('company_id', $companyId)->where('related_type', Quotation::class)->where('related_id', $quotation->id)
                    ->where('type', 'task')->where('status', 'pending')->exists();
                if (! $exists) {
                    Activity::create([
                        'company_id' => $companyId, 'type' => 'task', 'subject' => "Follow up on {$quotation->quotation_number}",
                        'related_type' => Quotation::class, 'related_id' => $quotation->id, 'status' => 'pending',
                        'priority' => 'medium', 'due_at' => now()->addDay(),
                        'owner_employee_id' => $this->action_employee_id ?: $quotation->owner_employee_id,
                    ]);
                    $matched++;
                }
            }
        } elseif ($this->category === 'assignment' && $this->entity === 'sales_order' && $this->action_employee_id) {
            $orders = SalesOrder::where('company_id', $companyId)->whereIn('status', ['confirmed', 'partially_fulfilled'])
                ->whereNull('owner_employee_id')->get();
            foreach ($orders as $order) {
                $order->update(['owner_employee_id' => $this->action_employee_id]);
                $matched++;
            }
        }

        $this->update(['run_count' => $this->run_count + $matched, 'last_run_at' => now()]);

        return $matched;
    }
}
