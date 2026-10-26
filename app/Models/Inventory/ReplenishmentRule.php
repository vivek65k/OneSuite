<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplenishmentRule extends Model
{
    protected $table = 'inv_replenishment_rules';

    protected $fillable = ['company_id', 'warehouse_id', 'item_id', 'bin_location_id', 'source_location_id', 'min_qty', 'max_qty', 'is_active'];

    protected $casts = ['min_qty' => 'decimal:2', 'max_qty' => 'decimal:2', 'is_active' => 'boolean'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function binLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'bin_location_id');
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'source_location_id');
    }
}
