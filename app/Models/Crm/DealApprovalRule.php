<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealApprovalRule extends Model
{
    protected $table = 'crm_deal_approval_rules';

    protected $fillable = ['company_id', 'name', 'level', 'min_amount', 'max_amount', 'approver_employee_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id');
    }

    public function matches(float $amount): bool
    {
        if ($this->min_amount !== null && $amount < $this->min_amount) {
            return false;
        }
        if ($this->max_amount !== null && $amount > $this->max_amount) {
            return false;
        }

        return true;
    }
}
