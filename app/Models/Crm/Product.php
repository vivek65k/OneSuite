<?php

namespace App\Models\Crm;

use App\Models\Company;
use App\Models\Inventory\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $table = 'crm_products';

    protected $fillable = ['company_id', 'name', 'sku', 'description', 'unit_price', 'is_active', 'inventory_item_id'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Links this CRM catalog entry to the shared Inventory item master -
     * only products with this set can be reserved/fulfilled through
     * Inventory & Warehouse when a Sales Order is confirmed.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'inventory_item_id');
    }

    public function priceBooks(): BelongsToMany
    {
        return $this->belongsToMany(PriceBook::class, 'crm_price_book_entries')->withPivot('price')->withTimestamps();
    }

    /**
     * The price for this product in a given price book, falling back to
     * the catalog's own unit_price when the book has no override entry -
     * every product is implicitly priced in every book unless overridden.
     */
    public function priceIn(?PriceBook $priceBook): float
    {
        if (! $priceBook) {
            return (float) $this->unit_price;
        }

        $entry = $this->priceBooks->firstWhere('id', $priceBook->id);

        return $entry ? (float) $entry->pivot->price : (float) $this->unit_price;
    }
}
