<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Batch extends Model
{
    protected $table = 'inv_batches';

    protected $fillable = [
        'company_id', 'item_id', 'warehouse_id', 'batch_number',
        'manufacture_date', 'expiry_date', 'supplier_lot', 'quantity', 'warranty_months',
        'unit_cost', 'status',
    ];

    protected $casts = ['manufacture_date' => 'date', 'expiry_date' => 'date', 'unit_cost' => 'decimal:2'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->expiry_date && ! $this->is_expired && $this->expiry_date->diffInDays(now()) <= 30;
    }
}
