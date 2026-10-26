<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPage extends Model
{
    protected $table = 'crm_landing_pages';

    public const STATUSES = [
        'draft' => 'Draft',
        'published' => 'Published',
    ];

    protected $fillable = [
        'company_id', 'campaign_id', 'name', 'slug', 'headline', 'content',
        'status', 'views_count', 'conversions_count',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function getConversionRateAttribute(): float
    {
        return $this->views_count > 0 ? round($this->conversions_count / $this->views_count * 100, 1) : 0.0;
    }
}
