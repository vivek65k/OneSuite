<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplenishmentTask extends Model
{
    protected $table = 'inv_replenishment_tasks';

    public const STATUSES = ['pending' => 'Pending', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

    public const PRIORITIES = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'];

    protected $fillable = [
        'company_id', 'warehouse_id', 'item_id', 'replenishment_rule_id', 'from_location_id', 'to_location_id',
        'suggested_qty', 'priority', 'status', 'completed_at',
    ];

    protected $casts = ['suggested_qty' => 'decimal:2', 'completed_at' => 'datetime'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ReplenishmentRule::class, 'replenishment_rule_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }
}
