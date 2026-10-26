<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $table = 'crm_integrations';

    public const TYPES = ['email' => 'Email', 'sms' => 'SMS', 'payment' => 'Payment', 'calendar' => 'Calendar', 'webhook' => 'Webhook'];

    protected $fillable = ['company_id', 'name', 'type', 'config', 'is_connected'];

    protected $casts = ['config' => 'array', 'is_connected' => 'boolean'];
}
