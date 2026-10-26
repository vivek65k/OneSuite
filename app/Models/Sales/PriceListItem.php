<?php

namespace App\Models\Sales;

use App\Models\Inventory\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceListItem extends Model
{
    protected $table = 'sales_price_list_items';

    protected $fillable = ['price_list_id', 'item_id', 'unit_price', 'min_qty'];

    protected $casts = ['unit_price' => 'decimal:2', 'min_qty' => 'decimal:2'];

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
