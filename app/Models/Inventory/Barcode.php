<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Barcode extends Model
{
    protected $table = 'inv_barcodes';

    public const TYPES = ['CODE128' => 'Code 128', 'EAN13' => 'EAN-13', 'QR' => 'QR Code'];

    protected $fillable = ['company_id', 'item_id', 'barcode', 'type', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
