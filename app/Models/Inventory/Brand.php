<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $table = 'inv_brands';

    protected $fillable = ['company_id', 'name', 'manufacturer'];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'brand_id');
    }
}
