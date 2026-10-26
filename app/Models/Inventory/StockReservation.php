<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockReservation extends Model
{
    protected $table = 'inv_stock_reservations';

    public const STATUSES = ['reserved' => 'Reserved', 'released' => 'Released', 'fulfilled' => 'Fulfilled'];

    protected $fillable = ['company_id', 'warehouse_id', 'item_id', 'reservation_number', 'reference', 'quantity', 'status', 'released_at'];

    protected $casts = ['quantity' => 'decimal:2', 'released_at' => 'datetime'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function pickListLines(): HasMany
    {
        return $this->hasMany(PickListLine::class, 'reservation_id');
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'reservation', 'RSV', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'RSV-'.now()->format('Y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Shared reservation logic used by both the Inventory app's own Stock
     * Reservations screen and other apps (e.g. Sales, on order confirm) -
     * reserving only ever reduces AVAILABLE quantity, never on-hand.
     * Returns null (and leaves stock untouched) if not enough is available.
     */
    public static function reserve(int $companyId, int $warehouseId, int $itemId, float $quantity, ?string $reference = null): ?self
    {
        $stock = Stock::firstOrCreate(['warehouse_id' => $warehouseId, 'item_id' => $itemId], ['company_id' => $companyId, 'quantity' => 0]);

        if ($quantity > $stock->available_quantity) {
            return null;
        }

        $stock->update(['reserved_quantity' => $stock->reserved_quantity + $quantity]);

        return self::create([
            'company_id' => $companyId, 'warehouse_id' => $warehouseId, 'item_id' => $itemId,
            'reservation_number' => self::nextNumber($companyId), 'reference' => $reference,
            'quantity' => $quantity, 'status' => 'reserved',
        ]);
    }

    public function releaseReservation(): void
    {
        if ($this->status !== 'reserved') {
            return;
        }

        $stock = Stock::where('warehouse_id', $this->warehouse_id)->where('item_id', $this->item_id)->first();
        if ($stock) {
            $stock->update(['reserved_quantity' => max(0, $stock->reserved_quantity - $this->quantity)]);
        }

        $this->update(['status' => 'released', 'released_at' => now()]);
    }
}
