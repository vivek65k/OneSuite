<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Serial extends Model
{
    protected $table = 'inv_serials';

    public const STATUSES = ['in_stock' => 'In Stock', 'sold' => 'Sold', 'returned' => 'Returned'];

    protected $fillable = ['company_id', 'item_id', 'warehouse_id', 'serial_number', 'status', 'warranty_expiry'];

    protected $casts = ['warranty_expiry' => 'date'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
