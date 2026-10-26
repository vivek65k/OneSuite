<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CampaignMember extends Model
{
    protected $table = 'crm_campaign_members';

    public const STATUSES = [
        'added' => 'Added',
        'sent' => 'Sent',
        'opened' => 'Opened',
        'clicked' => 'Clicked',
        'responded' => 'Responded',
        'converted' => 'Converted',
    ];

    public const MEMBERABLE = [
        'lead' => Lead::class,
        'contact' => Contact::class,
    ];

    protected $fillable = ['company_id', 'campaign_id', 'member_type', 'member_id', 'status', 'added_at'];

    protected $casts = ['added_at' => 'datetime'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function member(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'member_type', 'member_id');
    }

    public function resolveMemberType(string $key): ?string
    {
        return self::MEMBERABLE[$key] ?? null;
    }
}
