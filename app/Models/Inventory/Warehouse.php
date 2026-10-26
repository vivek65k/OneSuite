<?php

namespace App\Models\Inventory;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $table = 'inv_warehouses';

    public const TYPES = ['storage' => 'Storage', 'production' => 'Production', 'transit' => 'Transit', 'returns' => 'Returns'];

    public const PICKING_STRATEGIES = [
        'fifo' => 'FIFO - First In, First Out',
        'fefo' => 'FEFO - First Expiry, First Out',
        'nearest_bin' => 'Nearest-Bin Picking',
        'batch' => 'Batch Picking',
        'zone' => 'Zone Picking',
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'manager_employee_id', 'name', 'code', 'address',
        'operating_hours', 'type', 'picking_strategy', 'is_active',
        'default_receiving_zone_id', 'default_dispatch_zone_id',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class, 'warehouse_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'warehouse_id');
    }

    public function receivingZone(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_receiving_zone_id');
    }

    public function dispatchZone(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_dispatch_zone_id');
    }
}
