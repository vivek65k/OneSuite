<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    protected $table = 'inv_stock_adjustments';

    public const STATUSES = ['pending_approval' => 'Pending Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public const REASON_CODES = [
        'shortage' => 'Shortage', 'excess' => 'Excess', 'damage' => 'Damage', 'expiry' => 'Expiry', 'loss' => 'Loss',
    ];

    protected $fillable = [
        'company_id', 'warehouse_id', 'item_id', 'adjustment_number', 'system_qty', 'physical_qty', 'adjustment_qty',
        'reason_code', 'reason_notes', 'supporting_document', 'status',
        'requested_by_employee_id', 'approved_by_employee_id', 'approved_at', 'rejected_reason',
    ];

    protected $casts = [
        'system_qty' => 'decimal:2', 'physical_qty' => 'decimal:2', 'adjustment_qty' => 'decimal:2', 'approved_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'adjustment', 'ADJ', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'ADJ-'.now()->format('Y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        });
    }
}
