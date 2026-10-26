<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomFieldDefinition extends Model
{
    protected $table = 'inv_custom_field_definitions';

    public const ENTITY_TYPES = ['item' => 'Product'];

    public const FIELD_TYPES = ['text' => 'Text', 'number' => 'Number', 'date' => 'Date', 'select' => 'Select'];

    protected $fillable = ['company_id', 'entity_type', 'field_key', 'label', 'field_type', 'options', 'is_required', 'is_active'];

    protected $casts = ['is_required' => 'boolean', 'is_active' => 'boolean'];

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'field_id');
    }

    public function getOptionListAttribute(): array
    {
        return $this->options ? array_map('trim', explode(',', $this->options)) : [];
    }
}
