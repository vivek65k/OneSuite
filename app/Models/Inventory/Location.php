<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $table = 'inv_locations';

    public const LEVELS = ['zone' => 'Zone', 'aisle' => 'Aisle', 'rack' => 'Rack', 'shelf' => 'Shelf', 'bin' => 'Bin'];

    public const STORAGE_TYPES = ['ambient' => 'Ambient', 'cold' => 'Cold Storage', 'hazardous' => 'Hazardous'];

    protected $fillable = [
        'company_id', 'warehouse_id', 'parent_id', 'level', 'code', 'full_code',
        'max_weight', 'max_volume', 'storage_type', 'is_blocked',
    ];

    protected $casts = ['is_blocked' => 'boolean'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function computeFullCode(): string
    {
        return $this->parent ? $this->parent->computeFullCode().'/'.$this->code : $this->code;
    }
}
