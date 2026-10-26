<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryPackage extends Model
{
    protected $table = 'inv_packages';

    public const STATUSES = ['packed' => 'Packed', 'dispatched' => 'Dispatched'];

    protected $fillable = ['company_id', 'warehouse_id', 'pick_list_id', 'package_number', 'weight', 'box_type', 'status'];

    protected $casts = ['weight' => 'decimal:2'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function pickList(): BelongsTo
    {
        return $this->belongsTo(PickList::class, 'pick_list_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryPackageLine::class, 'package_id');
    }

    public function dispatch(): HasOne
    {
        return $this->hasOne(Dispatch::class, 'package_id');
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'package', 'PKG', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'PKG-'.now()->format('Y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        });
    }
}
