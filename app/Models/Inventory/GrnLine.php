<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnLine extends Model
{
    protected $table = 'inv_grn_lines';

    public const INSPECTION_STATUSES = [
        'not_required' => 'Not Required',
        'pending' => 'Pending Inspection',
        'passed' => 'Passed',
        'failed' => 'Failed',
    ];

    protected $fillable = [
        'grn_id', 'item_id', 'received_qty', 'damaged_qty', 'batch_number',
        'expiry_date', 'inspection_required', 'inspection_status',
    ];

    protected $casts = ['expiry_date' => 'date', 'inspection_required' => 'boolean'];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(Grn::class, 'grn_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function getGoodQtyAttribute(): float
    {
        return max(0, (float) $this->received_qty - (float) $this->damaged_qty);
    }
}
