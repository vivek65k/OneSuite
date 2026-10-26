<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    protected $table = 'crm_custom_fields';

    public const ENTITIES = ['lead' => 'Lead', 'account' => 'Account', 'contact' => 'Contact', 'opportunity' => 'Opportunity', 'case' => 'Case'];

    public const FIELD_TYPES = ['text' => 'Text', 'number' => 'Number', 'date' => 'Date', 'select' => 'Dropdown', 'checkbox' => 'Checkbox'];

    protected $fillable = ['company_id', 'entity', 'field_name', 'field_label', 'field_type', 'options', 'is_required', 'is_active'];

    protected $casts = ['options' => 'array', 'is_required' => 'boolean', 'is_active' => 'boolean'];
}
