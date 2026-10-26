<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountSchedule extends Model
{
    protected $table = 'inv_count_schedules';

    public const TYPES = ['cycle' => 'Cycle Count', 'physical' => 'Physical Stocktake'];

    public const FREQUENCIES = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];

    protected $fillable = ['company_id', 'warehouse_id', 'name', 'type', 'frequency', 'abc_class', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
