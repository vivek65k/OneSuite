<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispatch extends Model
{
    protected $table = 'inv_dispatches';

    public const STATUSES = ['dispatched' => 'Dispatched', 'delivered' => 'Delivered'];

    protected $fillable = [
        'company_id', 'warehouse_id', 'package_id', 'dispatch_number', 'carrier', 'tracking_number',
        'status', 'dispatched_at', 'delivered_at', 'received_by', 'signature_name',
    ];

    protected $casts = ['dispatched_at' => 'datetime', 'delivered_at' => 'datetime'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(InventoryPackage::class, 'package_id');
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'dispatch', 'DSP', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'DSP-'.now()->format('Y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        });
    }
}
