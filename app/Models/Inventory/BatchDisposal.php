<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchDisposal extends Model
{
    protected $table = 'inv_batch_disposals';

    public const STATUSES = ['pending_approval' => 'Pending Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    protected $fillable = [
        'company_id', 'batch_id', 'item_id', 'warehouse_id', 'quantity', 'reason', 'status',
        'requested_by_employee_id', 'approved_by_employee_id', 'approved_at',
    ];

    protected $casts = ['quantity' => 'decimal:2', 'approved_at' => 'datetime'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
