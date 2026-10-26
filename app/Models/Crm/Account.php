<?php

namespace App\Models\Crm;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $table = 'crm_accounts';

    protected $fillable = [
        'company_id', 'branch_id', 'name', 'industry', 'website', 'phone', 'email', 'address',
        'billing_address', 'shipping_address',
        'parent_account_id', 'owner_employee_id', 'customer_segment_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_account_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_account_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'customer_segment_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(AccountShare::class, 'account_id');
    }

    /**
     * The Sales app's commercial layer on top of this shared customer
     * record (credit limit, payment terms, assigned price list) - see
     * App\Models\Sales\CustomerProfile.
     */
    public function salesProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Sales\CustomerProfile::class, 'account_id');
    }
}
