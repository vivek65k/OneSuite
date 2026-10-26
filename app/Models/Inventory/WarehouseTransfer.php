<?php

namespace App\Models\Inventory;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseTransfer extends Model
{
    protected $table = 'inv_warehouse_transfers';

    public const STATUSES = [
        'requested' => 'Requested',
        'approved' => 'Approved',
        'picked' => 'Picked',
        'dispatched' => 'Dispatched',
        'in_transit' => 'In Transit',
        'received' => 'Received',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'company_id', 'transfer_number', 'source_warehouse_id', 'dest_warehouse_id', 'status',
        'requested_by_employee_id', 'approved_by_employee_id', 'notes',
        'requested_at', 'dispatched_at', 'received_at',
    ];

    protected $casts = ['requested_at' => 'datetime', 'dispatched_at' => 'datetime', 'received_at' => 'datetime'];

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'dest_warehouse_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by_employee_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_employee_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(WarehouseTransferLine::class, 'transfer_id');
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'transfer', 'TRF', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'TRF-'.now()->format('Y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        });
    }
}
