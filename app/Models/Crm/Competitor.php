<?php

namespace App\Models\Crm;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Competitor extends Model
{
    protected $table = 'crm_competitors';

    protected $fillable = ['company_id', 'name', 'website', 'strengths', 'weaknesses'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class, 'crm_opportunity_competitor')->withPivot('outcome')->withTimestamps();
    }
}
