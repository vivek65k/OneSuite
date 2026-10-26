<?php

namespace App\Models\Crm;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseModel extends Model
{
    protected $table = 'crm_cases';

    public const TYPES = [
        'case' => 'Case',
        'complaint' => 'Complaint',
        'service_request' => 'Service Request',
    ];

    public const STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'on_hold' => 'On Hold',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    public const PRIORITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];

    protected $fillable = [
        'company_id', 'branch_id', 'case_number', 'type', 'subject', 'description', 'status', 'priority', 'source',
        'account_id', 'contact_id', 'sales_order_id', 'assigned_to_employee_id', 'sla_policy_id',
        'response_due_at', 'resolution_due_at', 'first_responded_at', 'resolved_at',
        'is_escalated', 'escalated_to_employee_id', 'escalated_at', 'escalation_reason', 'csat_rating',
    ];

    protected $casts = [
        'response_due_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'first_responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'escalated_at' => 'datetime',
        'is_escalated' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to_employee_id');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'escalated_to_employee_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(CustomerFeedback::class, 'case_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        return ! in_array($this->status, ['resolved', 'closed'])
            && $this->resolution_due_at
            && $this->resolution_due_at->isPast();
    }

    public static function nextCaseNumber(int $companyId): string
    {
        $count = self::where('company_id', $companyId)->count() + 1;

        return 'CASE-'.now()->format('Y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
