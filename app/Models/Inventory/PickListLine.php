<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickListLine extends Model
{
    protected $table = 'inv_pick_list_lines';

    protected $fillable = ['pick_list_id', 'reservation_id', 'item_id', 'requested_qty', 'picked_qty', 'status'];

    protected $casts = ['requested_qty' => 'decimal:2', 'picked_qty' => 'decimal:2'];

    public function pickList(): BelongsTo
    {
        return $this->belongsTo(PickList::class, 'pick_list_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(StockReservation::class, 'reservation_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
