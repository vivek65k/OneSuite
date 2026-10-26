<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grn extends Model
{
    protected $table = 'inv_grns';

    public const STATUSES = ['draft' => 'Draft', 'posted' => 'Posted'];

    protected $fillable = ['company_id', 'warehouse_id', 'expected_receipt_id', 'supplier_id', 'grn_number', 'status', 'posted_by_employee_id', 'posted_at'];

    protected $casts = ['posted_at' => 'datetime'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function expectedReceipt(): BelongsTo
    {
        return $this->belongsTo(ExpectedReceipt::class, 'expected_receipt_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GrnLine::class, 'grn_id');
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'grn', 'GRN', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'GRN-'.now()->format('Y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        });
    }
}
