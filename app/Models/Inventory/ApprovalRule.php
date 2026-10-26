<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class ApprovalRule extends Model
{
    protected $table = 'inv_approval_rules';

    public const ACTION_TYPES = [
        'adjustment' => 'Stock Adjustment', 'transfer' => 'Warehouse Transfer',
        'disposal' => 'Batch Disposal', 'customer_return' => 'Customer Return',
    ];

    protected $fillable = ['company_id', 'action_type', 'threshold_qty', 'threshold_value', 'approver_role', 'is_active'];

    protected $casts = ['threshold_qty' => 'decimal:2', 'threshold_value' => 'decimal:2', 'is_active' => 'boolean'];
}
