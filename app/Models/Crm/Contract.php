<?php

namespace App\Models\Crm;

use App\Models\Employee;
use App\Models\Inventory\NumberingSeries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Contract extends Model
{
    protected $table = 'crm_contracts';

    public const STATUSES = [
        'draft' => 'Draft',
        'active' => 'Active',
        'expired' => 'Expired',
        'terminated' => 'Terminated',
    ];

    public const BILLING_FREQUENCIES = [
        'one_time' => 'One-time',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'annually' => 'Annually',
    ];

    protected $fillable = [
        'company_id', 'contract_number', 'title', 'account_id', 'sales_order_id', 'status',
        'start_date', 'end_date', 'value', 'owner_employee_id', 'auto_renew', 'renewed_from_contract_id',
        'billing_frequency', 'next_billing_date',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'auto_renew' => 'boolean', 'next_billing_date' => 'date'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_contract_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'renewed_from_contract_id');
    }

    public function signatureRequests(): MorphMany
    {
        return $this->morphMany(ESignatureRequest::class, 'signable');
    }

    public static function nextNumber(int $companyId): string
    {
        return NumberingSeries::next($companyId, 'contract', 'C', function () use ($companyId) {
            $count = self::where('company_id', $companyId)->count() + 1;

            return 'C-'.now()->format('Y').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Creates the next contract in the renewal chain as a draft for
     * review - carrying over the account, value and terms with the
     * period shifted forward by this contract's own duration. The
     * original contract's own status is left untouched; expiring it is a
     * separate, explicit step. Shared by both the CRM Renewal Management
     * screen and the Sales app's Contracts screen.
     */
    public function renew(string $contractNumber): self
    {
        $durationDays = $this->start_date && $this->end_date ? $this->start_date->diffInDays($this->end_date) : 365;
        $newStart = $this->end_date ? $this->end_date->copy()->addDay() : now();

        return self::create([
            'company_id' => $this->company_id,
            'contract_number' => $contractNumber,
            'title' => $this->title,
            'account_id' => $this->account_id,
            'sales_order_id' => $this->sales_order_id,
            'status' => 'draft',
            'start_date' => $newStart,
            'end_date' => $newStart->copy()->addDays($durationDays),
            'value' => $this->value,
            'owner_employee_id' => $this->owner_employee_id,
            'auto_renew' => $this->auto_renew,
            'renewed_from_contract_id' => $this->id,
            'billing_frequency' => $this->billing_frequency,
            'next_billing_date' => $this->billing_frequency !== 'one_time' ? $newStart : null,
        ]);
    }

    /**
     * Creates a draft Sales Order for the next billing cycle and advances
     * next_billing_date - this is the "recurring business" the module
     * spec calls for, triggered manually here (a scheduled job would call
     * the same method on a timer).
     */
    public function generateRecurringOrder(string $orderNumber): SalesOrder
    {
        abort_if($this->billing_frequency === 'one_time', 422, 'This contract is not on a recurring billing frequency.');

        $order = SalesOrder::create([
            'company_id' => $this->company_id,
            'order_number' => $orderNumber,
            'account_id' => $this->account_id,
            'status' => 'draft',
            'order_date' => now(),
            'total' => $this->value,
            'owner_employee_id' => $this->owner_employee_id,
        ]);

        $order->lines()->create([
            'description' => "{$this->title} - {$this->billing_frequency} billing",
            'quantity' => 1,
            'unit_price' => $this->value,
            'line_total' => $this->value,
        ]);

        $this->update(['next_billing_date' => $this->nextBillingDateAfter($this->next_billing_date ?? now())]);

        return $order;
    }

    protected function nextBillingDateAfter(\Carbon\Carbon $date): \Carbon\Carbon
    {
        return match ($this->billing_frequency) {
            'monthly' => $date->copy()->addMonth(),
            'quarterly' => $date->copy()->addMonths(3),
            'annually' => $date->copy()->addYear(),
            default => $date,
        };
    }
}
