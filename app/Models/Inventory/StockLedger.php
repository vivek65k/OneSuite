<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    protected $table = 'inv_stock_ledger';

    public const TYPES = [
        'opening' => 'Opening', 'receipt' => 'Receipt', 'issue' => 'Issue',
        'transfer_out' => 'Transfer Out', 'transfer_in' => 'Transfer In',
        'adjustment' => 'Adjustment', 'count_adjustment' => 'Count Adjustment',
        'return_in' => 'Return In', 'return_out' => 'Return Out',
    ];

    protected $fillable = [
        'company_id', 'warehouse_id', 'item_id', 'location_id', 'batch_number', 'serial_number',
        'transaction_type', 'quantity_before', 'quantity_change', 'quantity_after',
        'source_type', 'source_reference', 'employee_id', 'reason',
    ];

    protected $casts = [
        'quantity_before' => 'decimal:2', 'quantity_change' => 'decimal:2', 'quantity_after' => 'decimal:2',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Employee::class, 'employee_id');
    }

    /**
     * Single entry point every stock-mutating controller calls so the
     * ledger always reflects quantity_before/after around the live
     * inv_stock row, regardless of which transaction caused the change.
     */
    public static function record(array $attributes): self
    {
        return self::create($attributes);
    }
}
