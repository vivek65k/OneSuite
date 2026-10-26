<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandForecast extends Model
{
    protected $table = 'inv_demand_forecasts';

    protected $fillable = [
        'company_id', 'warehouse_id', 'item_id', 'period_label', 'historical_avg_qty', 'forecast_qty', 'method', 'generated_at',
    ];

    protected $casts = ['historical_avg_qty' => 'decimal:2', 'forecast_qty' => 'decimal:2', 'generated_at' => 'datetime'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
