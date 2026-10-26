<?php

namespace App\Models\Crm;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $table = 'crm_campaigns';

    public const TYPES = [
        'email' => 'Email',
        'sms' => 'SMS',
        'whatsapp' => 'WhatsApp',
        'event' => 'Event',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'active' => 'Active',
        'paused' => 'Paused',
        'completed' => 'Completed',
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'name', 'type', 'status', 'subject', 'message_body',
        'start_date', 'end_date', 'budget', 'actual_cost', 'owner_employee_id',
        'sent_count', 'delivered_count', 'opened_count', 'clicked_count', 'responded_count',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CampaignMember::class, 'campaign_id');
    }

    public function webForms(): HasMany
    {
        return $this->hasMany(WebForm::class, 'campaign_id');
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class, 'campaign_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'campaign_id');
    }

    public function getOpenRateAttribute(): float
    {
        return $this->delivered_count > 0 ? round($this->opened_count / $this->delivered_count * 100, 1) : 0.0;
    }

    public function getClickRateAttribute(): float
    {
        return $this->opened_count > 0 ? round($this->clicked_count / $this->opened_count * 100, 1) : 0.0;
    }

    public function getResponseRateAttribute(): float
    {
        return $this->sent_count > 0 ? round($this->responded_count / $this->sent_count * 100, 1) : 0.0;
    }

    public function getMembersCountAttribute(): int
    {
        return $this->members()->count();
    }

    public function getConvertedCountAttribute(): int
    {
        return $this->members()->where('status', 'converted')->count();
    }

    public function recalculateCounts(): void
    {
        $counts = $this->members()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $sent = $counts->only(['sent', 'opened', 'clicked', 'responded', 'converted'])->sum();
        $opened = $counts->only(['opened', 'clicked', 'responded', 'converted'])->sum();
        $clicked = $counts->only(['clicked', 'responded', 'converted'])->sum();
        $responded = $counts->only(['responded', 'converted'])->sum();

        $this->update([
            'sent_count' => $sent,
            'delivered_count' => $sent,
            'opened_count' => $opened,
            'clicked_count' => $clicked,
            'responded_count' => $responded,
        ]);
    }
}
