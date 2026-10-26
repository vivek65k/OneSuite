<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebForm extends Model
{
    protected $table = 'crm_web_forms';

    protected $fillable = [
        'company_id', 'campaign_id', 'name', 'slug', 'fields_json', 'redirect_url',
        'is_active', 'submissions_count',
    ];

    protected $casts = [
        'fields_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(WebFormSubmission::class, 'web_form_id');
    }
}
