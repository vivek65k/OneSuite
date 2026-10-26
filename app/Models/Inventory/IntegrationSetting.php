<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    protected $table = 'inv_integration_settings';

    public const KEYS = [
        'sales' => 'Sales', 'purchase' => 'Purchase', 'production' => 'Production',
        'finance' => 'Finance', 'barcode_scanner' => 'Barcode Scanner Devices',
    ];

    protected $fillable = ['company_id', 'integration_key', 'is_enabled', 'endpoint_url', 'api_key', 'notes'];

    protected $casts = ['is_enabled' => 'boolean'];
}
