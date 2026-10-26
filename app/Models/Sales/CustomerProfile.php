<?php

namespace App\Models\Sales;

use App\Models\Crm\Account;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    protected $table = 'sales_customer_profiles';

    public const STATUSES = ['active' => 'Active', 'on_hold' => 'On Hold', 'blocked' => 'Blocked'];

    protected $fillable = [
        'company_id', 'account_id', 'credit_limit', 'payment_terms_days', 'price_list_id',
        'salesperson_employee_id', 'status', 'outstanding_balance', 'notes',
    ];

    protected $casts = ['credit_limit' => 'decimal:2', 'outstanding_balance' => 'decimal:2'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'salesperson_employee_id');
    }

    public function getAvailableCreditAttribute(): float
    {
        return (float) $this->credit_limit - (float) $this->outstanding_balance;
    }

    public function getIsOverLimitAttribute(): bool
    {
        return $this->credit_limit > 0 && $this->outstanding_balance > $this->credit_limit;
    }

    public static function forAccount(int $companyId, int $accountId): self
    {
        return self::firstOrCreate(['account_id' => $accountId], ['company_id' => $companyId, 'status' => 'active']);
    }
}
