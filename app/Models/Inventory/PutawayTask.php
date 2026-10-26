<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PutawayTask extends Model
{
    protected $table = 'inv_putaway_tasks';

    public const STATUSES = ['pending' => 'Pending', 'completed' => 'Completed', 'exception' => 'Exception'];

    protected $fillable = [
        'company_id', 'warehouse_id', 'item_id', 'quantity', 'suggested_location_id',
        'confirmed_location_id', 'status', 'source_reference', 'exception_note', 'completed_at',
    ];

    protected $casts = ['completed_at' => 'datetime'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function suggestedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'suggested_location_id');
    }

    public function confirmedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'confirmed_location_id');
    }
}
