<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierReturn extends Model
{
    protected $table = 'inv_supplier_returns';

    public const STATUSES = ['draft' => 'Draft', 'approved' => 'Approved', 'dispatched' => 'Dispatched'];

    protected $fillable = [
        'company_id', 'supplier_id', 'warehouse_id', 'return_number', 'reason',
        'status', 'credit_note_reference', 'tracking_number', 'dispatched_at',
    ];

    protected $casts = ['dispatched_at' => 'datetime'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SupplierReturnLine::class, 'return_id');
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'supplier_return', 'RTN', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'RTN-'.now()->format('Y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        });
    }
}
