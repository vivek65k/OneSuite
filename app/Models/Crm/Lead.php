<?php

namespace App\Models\Crm;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $table = 'crm_leads';

    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'qualified' => 'Qualified',
        'unqualified' => 'Unqualified',
        'converted' => 'Converted',
    ];

    public const TIMELINES = [
        'immediate' => 'Immediate',
        'this_quarter' => 'This Quarter',
        'next_quarter' => 'Next Quarter',
        'no_timeline' => 'No Timeline',
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'team_id', 'name', 'email', 'phone', 'company_name', 'lead_source_id', 'campaign_id',
        'status', 'score', 'assigned_to_employee_id', 'is_duplicate_of_lead_id',
        'notes', 'converted_at', 'converted_contact_id',
        'budget', 'has_need', 'has_authority', 'timeline',
        'is_recycled', 'recycled_at', 'recycle_reason',
    ];

    protected function casts(): array
    {
        return [
            'converted_at' => 'datetime',
            'has_need' => 'boolean',
            'has_authority' => 'boolean',
            'is_recycled' => 'boolean',
            'recycled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to_employee_id');
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'is_duplicate_of_lead_id');
    }

    public function getBantCompleteAttribute(): int
    {
        return (! empty($this->budget) ? 1 : 0) + ($this->has_need ? 1 : 0) + ($this->has_authority ? 1 : 0) + (! empty($this->timeline) ? 1 : 0);
    }

    public function convertedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'converted_contact_id');
    }
}
