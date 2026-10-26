<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaPolicy extends Model
{
    protected $table = 'crm_sla_policies';

    protected $fillable = ['company_id', 'name', 'priority', 'response_hours', 'resolution_hours', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function cases(): HasMany
    {
        return $this->hasMany(CaseModel::class, 'sla_policy_id');
    }
}
