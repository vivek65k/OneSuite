<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class CrmLayout extends Model
{
    protected $table = 'crm_layouts';

    public const ENTITIES = ['lead' => 'Lead', 'account' => 'Account', 'contact' => 'Contact', 'opportunity' => 'Opportunity'];

    public const DEFAULT_FIELDS = [
        'lead' => ['name', 'email', 'phone', 'company_name', 'lead_source_id', 'status'],
        'account' => ['name', 'industry', 'phone', 'website'],
        'contact' => ['name', 'email', 'phone', 'job_title', 'account_id'],
        'opportunity' => ['name', 'account_id', 'amount', 'sales_stage_id', 'expected_close_date'],
    ];

    protected $fillable = ['company_id', 'entity', 'fields_json'];

    protected $casts = ['fields_json' => 'array'];
}
