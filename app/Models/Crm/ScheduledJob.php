<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class ScheduledJob extends Model
{
    protected $table = 'crm_scheduled_jobs';

    public const JOB_TYPES = [
        'recalculate_campaign_counts' => 'Recalculate Campaign Counts',
        'close_stale_leads' => 'Close Stale Unassigned Leads',
        'sync_case_sla' => 'Flag Overdue SLA Cases',
    ];

    public const FREQUENCIES = ['hourly' => 'Hourly', 'daily' => 'Daily', 'weekly' => 'Weekly'];

    protected $fillable = [
        'company_id', 'name', 'job_type', 'frequency', 'is_active',
        'last_run_at', 'next_run_at', 'last_result',
    ];

    protected $casts = ['is_active' => 'boolean', 'last_run_at' => 'datetime', 'next_run_at' => 'datetime'];

    public function computeNextRun(): \Illuminate\Support\Carbon
    {
        return match ($this->frequency) {
            'hourly' => now()->addHour(),
            'weekly' => now()->addWeek(),
            default => now()->addDay(),
        };
    }
}
