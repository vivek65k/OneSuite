<?php

namespace App\Models\Sales;

use App\Models\Crm\Account;
use App\Models\Inventory\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPrice extends Model
{
    protected $table = 'sales_customer_prices';

    protected $fillable = ['company_id', 'account_id', 'item_id', 'unit_price'];

    protected $casts = ['unit_price' => 'decimal:2'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
