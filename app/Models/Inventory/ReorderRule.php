<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderRule extends Model
{
    protected $table = 'inv_reorder_rules';

    protected $fillable = [
        'company_id', 'warehouse_id', 'item_id', 'reorder_point', 'target_stock_level',
        'lead_time_days', 'safety_stock', 'auto_create_po', 'is_active',
    ];

    protected $casts = [
        'reorder_point' => 'decimal:2', 'target_stock_level' => 'decimal:2', 'safety_stock' => 'decimal:2',
        'auto_create_po' => 'boolean', 'is_active' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
