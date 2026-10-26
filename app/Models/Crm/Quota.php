<?php

namespace App\Models\Crm;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quota extends Model
{
    protected $table = 'crm_quotas';

    public const PERIODS = ['month' => 'Monthly', 'quarter' => 'Quarterly', 'year' => 'Yearly'];

    protected $fillable = ['company_id', 'employee_id', 'team_id', 'period', 'period_start', 'target_amount', 'incentive_percent'];

    protected $casts = ['period_start' => 'date', 'target_amount' => 'decimal:2', 'incentive_percent' => 'decimal:2'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function periodEnd(): \Illuminate\Support\Carbon
    {
        return match ($this->period) {
            'quarter' => $this->period_start->copy()->addMonths(3)->subDay(),
            'year' => $this->period_start->copy()->addYear()->subDay(),
            default => $this->period_start->copy()->endOfMonth(),
        };
    }

    public function achievedAmount(): float
    {
        $query = Opportunity::where('company_id', $this->company_id)->where('status', 'won')
            ->whereBetween('closed_at', [$this->period_start, $this->periodEnd()]);

        if ($this->employee_id) {
            $query->where('owner_employee_id', $this->employee_id);
        } elseif ($this->team_id) {
            $memberIds = Team::find($this->team_id)?->members->pluck('id') ?? collect();
            $query->whereIn('owner_employee_id', $memberIds);
        }

        return (float) $query->sum('amount');
    }

    /**
     * The Sales-side view of achievement: actual booked revenue (Sales
     * Order totals, excluding cancelled orders) rather than CRM's won
     * Opportunity amount - this is what the Sales app's Team & Targets
     * screen and incentive calculation use; achievedAmount() above stays
     * as-is for the existing CRM Quota screen.
     */
    public function achievedSalesAmount(): float
    {
        $query = SalesOrder::where('company_id', $this->company_id)->where('status', '!=', 'cancelled')
            ->whereBetween('order_date', [$this->period_start, $this->periodEnd()]);

        if ($this->employee_id) {
            $query->where('owner_employee_id', $this->employee_id);
        } elseif ($this->team_id) {
            $memberIds = Team::find($this->team_id)?->members->pluck('id') ?? collect();
            $query->whereIn('owner_employee_id', $memberIds);
        }

        return (float) $query->sum('total');
    }

    public function commissionAmount(): float
    {
        return $this->achievedSalesAmount() * (float) $this->incentive_percent / 100;
    }
}
