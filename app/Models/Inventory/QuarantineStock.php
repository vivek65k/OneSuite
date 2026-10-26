<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuarantineStock extends Model
{
    protected $table = 'inv_quarantine_stock';

    protected $fillable = ['company_id', 'warehouse_id', 'item_id', 'grn_line_id', 'quantity'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function grnLine(): BelongsTo
    {
        return $this->belongsTo(GrnLine::class, 'grn_line_id');
    }
}
