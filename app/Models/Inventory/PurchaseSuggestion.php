<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseSuggestion extends Model
{
    protected $table = 'inv_purchase_suggestions';

    public const STATUSES = ['suggested' => 'Suggested', 'approved' => 'Approved', 'dismissed' => 'Dismissed'];

    protected $fillable = [
        'company_id', 'warehouse_id', 'item_id', 'reorder_rule_id', 'on_hand_qty', 'reserved_qty',
        'available_qty', 'incoming_qty', 'suggested_qty', 'status', 'approved_by_employee_id', 'approved_at',
    ];

    protected $casts = [
        'on_hand_qty' => 'decimal:2', 'reserved_qty' => 'decimal:2', 'available_qty' => 'decimal:2',
        'incoming_qty' => 'decimal:2', 'suggested_qty' => 'decimal:2', 'approved_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function reorderRule(): BelongsTo
    {
        return $this->belongsTo(ReorderRule::class, 'reorder_rule_id');
    }
}
