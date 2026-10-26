<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'inv_categories';

    protected $fillable = ['company_id', 'parent_id', 'name', 'default_storage_type', 'handling_rules'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id');
    }

    public function getFullPathAttribute(): string
    {
        return $this->parent ? $this->parent->full_path.' / '.$this->name : $this->name;
    }
}
