<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpectedReceiptLine extends Model
{
    protected $table = 'inv_expected_receipt_lines';

    protected $fillable = ['receipt_id', 'item_id', 'expected_qty'];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(ExpectedReceipt::class, 'receipt_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
