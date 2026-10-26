<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class InventorySetting extends Model
{
    protected $table = 'inv_settings';

    protected $fillable = [
        'company_id', 'allow_negative_stock', 'allow_backorder', 'require_batch_number_validation',
        'require_serial_number_validation', 'default_costing_method', 'landed_cost_overhead_percent',
    ];

    protected $casts = [
        'allow_negative_stock' => 'boolean', 'allow_backorder' => 'boolean',
        'require_batch_number_validation' => 'boolean', 'require_serial_number_validation' => 'boolean',
        'landed_cost_overhead_percent' => 'decimal:2',
    ];

    public static function forCompany(int $companyId): self
    {
        return self::firstOrCreate(['company_id' => $companyId]);
    }
}
