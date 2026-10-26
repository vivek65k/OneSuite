<?php

namespace App\Models\Crm;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PriceBook extends Model
{
    protected $table = 'crm_price_books';

    protected $fillable = ['company_id', 'name', 'currency', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'crm_price_book_entries')->withPivot('price', 'id')->withTimestamps();
    }
}
