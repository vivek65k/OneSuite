<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $table = 'inv_items';

    public const UNITS = ['pcs' => 'Pieces', 'kg' => 'Kilograms', 'ltr' => 'Litres', 'box' => 'Box', 'm' => 'Meters'];

    public const TAX_CATEGORIES = ['standard' => 'Standard', 'zero' => 'Zero-rated', 'exempt' => 'Exempt'];

    public const COSTING_METHODS = ['moving_avg' => 'Moving Weighted Average', 'fifo' => 'FIFO'];

    protected $fillable = [
        'company_id', 'sku', 'name', 'description', 'category', 'unit',
        'cost_price', 'sale_price', 'reorder_level', 'is_active',
        'category_id', 'brand_id', 'purchase_uom_id', 'stock_uom_id', 'sales_uom_id',
        'tax_category', 'track_stock', 'track_batch', 'track_serial', 'track_expiry',
        'default_warehouse_id', 'parent_item_id', 'is_variant',
        'costing_method', 'abc_class', 'xyz_class',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
        'track_batch' => 'boolean',
        'track_serial' => 'boolean',
        'track_expiry' => 'boolean',
        'is_variant' => 'boolean',
    ];

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class, 'item_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function purchaseUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'purchase_uom_id');
    }

    public function stockUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'stock_uom_id');
    }

    public function salesUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'sales_uom_id');
    }

    public function uomConversions(): HasMany
    {
        return $this->hasMany(UomConversion::class, 'item_id');
    }

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    public function parentItem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_item_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_item_id');
    }

    public function variantValues(): BelongsToMany
    {
        return $this->belongsToMany(VariantValue::class, 'inv_item_variant_values', 'item_id', 'variant_value_id');
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class, 'item_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'item_id');
    }

    public function serials(): HasMany
    {
        return $this->hasMany(Serial::class, 'item_id');
    }

    public function supplierProducts(): HasMany
    {
        return $this->hasMany(SupplierProduct::class, 'item_id');
    }

    public function getVariantLabelAttribute(): string
    {
        return $this->variantValues->pluck('value')->implode(' / ');
    }

    public function getTotalQuantityAttribute(): float
    {
        return (float) $this->stock->sum('quantity');
    }

    /**
     * FIFO values remaining stock off its still-active batch cost layers;
     * everything else (including FIFO items with no batches costed yet)
     * falls back to quantity x the item's moving weighted average cost.
     */
    public function getStockValueAttribute(): float
    {
        if ($this->costing_method === 'fifo' && $this->track_batch) {
            $batchValue = $this->batches()->where('status', 'active')->whereNotNull('unit_cost')->get()
                ->sum(fn ($batch) => (float) $batch->quantity * (float) $batch->unit_cost);

            if ($batchValue > 0) {
                return $batchValue;
            }
        }

        return $this->total_quantity * (float) $this->cost_price;
    }
}
