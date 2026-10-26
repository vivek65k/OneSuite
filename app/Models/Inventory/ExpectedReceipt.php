<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpectedReceipt extends Model
{
    protected $table = 'inv_expected_receipts';

    public const STATUSES = ['pending' => 'Pending', 'received' => 'Received', 'cancelled' => 'Cancelled'];

    protected $fillable = [
        'company_id', 'warehouse_id', 'supplier_id', 'reference_number', 'po_reference',
        'expected_date', 'vehicle_number', 'tracking_number', 'is_asn', 'status',
    ];

    protected $casts = ['expected_date' => 'date', 'is_asn' => 'boolean'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ExpectedReceiptLine::class, 'receipt_id');
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'expected_receipt', 'ASN', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'ASN-'.now()->format('Y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        });
    }
}
