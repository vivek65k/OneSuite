<?php

namespace App\Models\Crm;

use App\Models\Employee;
use App\Models\Inventory\NumberingSeries;
use App\Models\Inventory\StockReservation;
use App\Models\Inventory\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    protected $table = 'crm_sales_orders';

    public const STATUSES = [
        'draft' => 'Draft',
        'pending' => 'Pending Approval',
        'confirmed' => 'Confirmed',
        'partially_fulfilled' => 'Partially Fulfilled',
        'fulfilled' => 'Fulfilled',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'company_id', 'order_number', 'quotation_id', 'account_id', 'warehouse_id', 'status', 'order_date', 'total',
        'owner_employee_id', 'cancelled_reason', 'cancelled_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return ['order_date' => 'date', 'cancelled_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SalesOrderEvent::class)->orderByDesc('created_at');
    }

    public function invoiceRequests(): HasMany
    {
        return $this->hasMany(\App\Models\Sales\InvoiceRequest::class);
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'sales_order', 'SO', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'SO-'.now()->format('Y').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Logs every status transition to the timeline automatically,
     * regardless of which controller (CRM or Sales) made the change -
     * this is what the module's audit-trail requirement reads.
     */
    protected static function booted(): void
    {
        static::updated(function (self $order) {
            if ($order->wasChanged('status')) {
                $from = self::STATUSES[$order->getOriginal('status')] ?? $order->getOriginal('status');
                $to = self::STATUSES[$order->status] ?? $order->status;
                SalesOrderEvent::log($order->id, 'status_change', "Status changed from {$from} to {$to}.", auth()->user()?->employee?->id);
            }
        });

        static::created(function (self $order) {
            SalesOrderEvent::log($order->id, 'status_change', 'Order created.', auth()->user()?->employee?->id);
        });
    }

    public function getIsFullyFulfilledAttribute(): bool
    {
        return $this->lines->isNotEmpty() && $this->lines->every(fn ($line) => $line->fulfilled_qty >= $line->quantity);
    }

    public function getIsPartiallyFulfilledAttribute(): bool
    {
        return $this->lines->sum('fulfilled_qty') > 0 && ! $this->is_fully_fulfilled;
    }

    /**
     * Rolls the order's own status up from its lines' fulfilled_qty -
     * confirmed stays confirmed until something ships, then becomes
     * partially_fulfilled or fulfilled depending on how much shipped.
     */
    public function refreshFulfilmentStatus(): void
    {
        if (! in_array($this->status, ['confirmed', 'partially_fulfilled', 'fulfilled'], true)) {
            return;
        }

        $this->load('lines');
        $newStatus = $this->is_fully_fulfilled ? 'fulfilled' : ($this->is_partially_fulfilled ? 'partially_fulfilled' : 'confirmed');

        if ($newStatus !== $this->status) {
            $this->update(['status' => $newStatus]);
        }
    }

    /**
     * Reserves Inventory stock for every line whose product is linked to
     * an Inventory item (via Product::inventory_item_id) - lines without
     * that link, or without enough available stock, are simply skipped
     * and reported back so the caller can show what still needs sourcing.
     *
     * @return array{reserved: int, shortages: array<int, array{line: SalesOrderLine, available: float}>}
     */
    public function reserveStock(): array
    {
        abort_unless($this->warehouse_id, 422, 'Set a fulfilment warehouse on this order before reserving stock.');

        $this->load('lines.product');
        $reserved = 0;
        $shortages = [];

        foreach ($this->lines as $line) {
            $itemId = $line->product?->inventory_item_id;
            if (! $itemId || $line->reservation_id) {
                continue;
            }

            $stock = \App\Models\Inventory\Stock::firstOrCreate(
                ['warehouse_id' => $this->warehouse_id, 'item_id' => $itemId],
                ['company_id' => $this->company_id, 'quantity' => 0]
            );

            $reservation = StockReservation::reserve($this->company_id, $this->warehouse_id, $itemId, (float) $line->quantity, $this->order_number);

            if ($reservation) {
                $line->update(['reservation_id' => $reservation->id]);
                $reserved++;
            } else {
                $shortages[] = ['line' => $line, 'available' => $stock->available_quantity];
            }
        }

        return ['reserved' => $reserved, 'shortages' => $shortages];
    }

    /**
     * Releases every still-active reservation this order holds - used
     * when the order is cancelled, so the stock becomes available to
     * other orders again.
     */
    public function releaseReservations(): void
    {
        foreach ($this->lines as $line) {
            $line->reservation?->releaseReservation();
        }
    }
}
