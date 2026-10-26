<?php

namespace App\Models\Sales;

use App\Models\Crm\Contract;
use App\Models\Crm\SalesOrder;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceRequest extends Model
{
    protected $table = 'sales_invoice_requests';

    public const STATUSES = [
        'requested' => 'Requested',
        'invoiced' => 'Invoiced',
        'partially_paid' => 'Partially Paid',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'company_id', 'sales_order_id', 'contract_id', 'status', 'requested_amount', 'amount_paid',
        'invoice_reference', 'due_date', 'requested_by_employee_id', 'notes',
    ];

    protected $casts = ['requested_amount' => 'decimal:2', 'amount_paid' => 'decimal:2', 'due_date' => 'date'];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by_employee_id');
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0, (float) $this->requested_amount - (float) $this->amount_paid);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && ! in_array($this->status, ['paid', 'cancelled'], true);
    }
}
