<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountSheetLine extends Model
{
    protected $table = 'inv_count_sheet_lines';

    public const STATUSES = ['pending' => 'Pending', 'counted' => 'Counted', 'recount_requested' => 'Recount Requested', 'approved' => 'Approved'];

    protected $fillable = ['sheet_id', 'item_id', 'location_id', 'system_qty', 'counted_qty', 'variance', 'status'];

    protected $casts = ['system_qty' => 'decimal:2', 'counted_qty' => 'decimal:2', 'variance' => 'decimal:2'];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(CountSheet::class, 'sheet_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
