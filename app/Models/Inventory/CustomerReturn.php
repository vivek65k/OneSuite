<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerReturn extends Model
{
    protected $table = 'inv_customer_returns';

    public const STATUSES = ['draft' => 'Draft', 'approved' => 'Approved', 'received' => 'Received'];

    protected $fillable = [
        'company_id', 'warehouse_id', 'sales_order_id', 'replacement_order_id', 'dispatch_id',
        'return_number', 'reason', 'status', 'received_at',
    ];

    protected $casts = ['received_at' => 'datetime'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class, 'dispatch_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Crm\SalesOrder::class, 'sales_order_id');
    }

    public function replacementOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Crm\SalesOrder::class, 'replacement_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CustomerReturnLine::class, 'return_id');
    }

    /**
     * Creates a new draft Sales Order carrying the same items/quantities
     * as this return, for the "replacement" half of return/replace/issue
     * tracking - distinct from the refund-style stock-only return that
     * CustomerReturnController::receive() already handles.
     */
    public function createReplacementOrder(string $orderNumber): \App\Models\Crm\SalesOrder
    {
        abort_if($this->replacement_order_id, 422, 'A replacement order already exists for this return.');

        $accountId = $this->salesOrder?->account_id;

        $order = \App\Models\Crm\SalesOrder::create([
            'company_id' => $this->company_id,
            'order_number' => $orderNumber,
            'account_id' => $accountId,
            'status' => 'draft',
            'order_date' => now(),
            'total' => 0,
            'owner_employee_id' => $this->salesOrder?->owner_employee_id,
        ]);

        $total = 0;
        foreach ($this->lines as $line) {
            $unitPrice = (float) ($line->item?->sale_price ?? 0);
            $lineTotal = $unitPrice * (float) $line->quantity;
            $total += $lineTotal;

            $order->lines()->create([
                'description' => "Replacement - {$line->item?->name}",
                'quantity' => $line->quantity, 'unit_price' => $unitPrice, 'line_total' => $lineTotal,
            ]);
        }
        $order->update(['total' => $total]);

        $this->update(['replacement_order_id' => $order->id]);

        return $order;
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'customer_return', 'CRTN', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'CRTN-'.now()->format('Y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        });
    }
}
