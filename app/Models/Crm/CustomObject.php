<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class CustomObject extends Model
{
    protected $table = 'crm_custom_objects';

    protected $fillable = ['company_id', 'name', 'label', 'description', 'fields_json', 'is_active'];

    protected $casts = ['fields_json' => 'array', 'is_active' => 'boolean'];
}
