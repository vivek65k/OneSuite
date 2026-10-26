<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryPackageLine extends Model
{
    protected $table = 'inv_package_lines';

    protected $fillable = ['package_id', 'item_id', 'quantity'];

    protected $casts = ['quantity' => 'decimal:2'];

    public function package(): BelongsTo
    {
        return $this->belongsTo(InventoryPackage::class, 'package_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
