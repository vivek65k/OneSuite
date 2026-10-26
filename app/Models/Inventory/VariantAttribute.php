<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VariantAttribute extends Model
{
    protected $table = 'inv_variant_attributes';

    protected $fillable = ['company_id', 'name'];

    public function values(): HasMany
    {
        return $this->hasMany(VariantValue::class, 'attribute_id');
    }
}
