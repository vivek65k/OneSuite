<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReturnLine extends Model
{
    protected $table = 'inv_customer_return_lines';

    protected $fillable = ['return_id', 'item_id', 'quantity', 'batch_number'];

    protected $casts = ['quantity' => 'decimal:2'];

    public function customerReturn(): BelongsTo
    {
        return $this->belongsTo(CustomerReturn::class, 'return_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
