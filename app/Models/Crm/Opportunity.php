<?php

namespace App\Models\Crm;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    protected $table = 'crm_opportunities';

    public const STATUSES = [
        'open' => 'Open',
        'won' => 'Won',
        'lost' => 'Lost',
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'team_id', 'name', 'account_id', 'contact_id', 'sales_stage_id', 'amount', 'probability',
        'expected_close_date', 'owner_employee_id', 'status', 'lost_reason', 'lead_id', 'notes', 'closed_at',
        'risks_blockers',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expected_close_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(SalesStage::class, 'sales_stage_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(OpportunityProduct::class);
    }

    public function competitors(): BelongsToMany
    {
        return $this->belongsToMany(Competitor::class, 'crm_opportunity_competitor')->withPivot('outcome')->withTimestamps();
    }

    /**
     * Effective win probability - the opportunity's own override if set,
     * otherwise whatever its current stage implies. Forecast and pipeline
     * totals both read this instead of the raw column so a stage move
     * updates the weighted value even when no override was ever set.
     */
    public function getEffectiveProbabilityAttribute(): int
    {
        return $this->probability ?? $this->stage?->win_probability ?? 0;
    }

    public function getWeightedAmountAttribute(): float
    {
        return (float) $this->amount * $this->effective_probability / 100;
    }
}
