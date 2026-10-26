<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProduct extends Model
{
    protected $table = 'inv_supplier_products';

    protected $fillable = ['company_id', 'supplier_id', 'item_id', 'supplier_sku', 'cost_price', 'lead_time_days', 'is_preferred'];

    protected $casts = ['is_preferred' => 'boolean'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
