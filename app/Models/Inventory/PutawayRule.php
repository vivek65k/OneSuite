<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PutawayRule extends Model
{
    protected $table = 'inv_putaway_rules';

    public const STRATEGIES = ['fixed' => 'Fixed Bin', 'flexible' => 'Flexible (System Suggests)'];

    protected $fillable = [
        'company_id', 'warehouse_id', 'category_id', 'strategy', 'fixed_location_id',
        'is_hazardous', 'is_temp_controlled', 'is_active',
    ];

    protected $casts = ['is_hazardous' => 'boolean', 'is_temp_controlled' => 'boolean', 'is_active' => 'boolean'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function fixedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'fixed_location_id');
    }
}
