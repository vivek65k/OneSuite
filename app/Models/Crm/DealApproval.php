<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealApproval extends Model
{
    protected $table = 'crm_deal_approvals';

    public const STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    protected $fillable = [
        'company_id', 'opportunity_id', 'rule_id', 'approver_employee_id', 'requested_by_employee_id',
        'status', 'reason', 'requested_at', 'decided_at',
    ];

    protected $casts = ['requested_at' => 'datetime', 'decided_at' => 'datetime'];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(DealApprovalRule::class, 'rule_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by_employee_id');
    }
}
