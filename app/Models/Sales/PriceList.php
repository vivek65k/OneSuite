<?php

namespace App\Models\Sales;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    protected $table = 'sales_price_lists';

    protected $fillable = ['company_id', 'name', 'currency', 'is_default', 'valid_from', 'valid_to', 'is_active'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean', 'valid_from' => 'date', 'valid_to' => 'date'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class, 'price_list_id');
    }
}
