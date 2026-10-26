<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickList extends Model
{
    protected $table = 'inv_pick_lists';

    public const STATUSES = ['pending' => 'Pending', 'picking' => 'Picking', 'picked' => 'Picked', 'cancelled' => 'Cancelled'];

    protected $fillable = ['company_id', 'warehouse_id', 'picker_employee_id', 'pick_list_number', 'strategy', 'status'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PickListLine::class, 'pick_list_id');
    }

    public function package(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InventoryPackage::class, 'pick_list_id');
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'pick_list', 'PICK', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'PICK-'.now()->format('Y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        });
    }
}
