<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesSetting extends Model
{
    protected $table = 'sales_settings';

    protected $fillable = [
        'company_id', 'discount_approval_threshold_percent', 'default_payment_terms_days',
        'require_credit_check', 'block_order_over_credit_limit', 'default_price_list_id',
    ];

    protected $casts = ['require_credit_check' => 'boolean', 'block_order_over_credit_limit' => 'boolean'];

    public function defaultPriceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'default_price_list_id');
    }

    public static function forCompany(int $companyId): self
    {
        return self::firstOrCreate(['company_id' => $companyId]);
    }
}
