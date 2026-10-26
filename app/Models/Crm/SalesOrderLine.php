<?php

namespace App\Models\Crm;

use App\Models\Inventory\StockReservation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderLine extends Model
{
    protected $table = 'crm_sales_order_lines';

    protected $fillable = ['sales_order_id', 'product_id', 'description', 'quantity', 'unit_price', 'line_total', 'fulfilled_qty', 'reservation_id'];

    protected $casts = ['fulfilled_qty' => 'decimal:2'];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(StockReservation::class, 'reservation_id');
    }

    public function getBackorderQtyAttribute(): float
    {
        return max(0, (float) $this->quantity - (float) $this->fulfilled_qty);
    }
}
