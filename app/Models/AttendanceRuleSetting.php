<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRuleSetting extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'late_grace_minutes', 'half_day_threshold_hours',
        'full_day_threshold_hours', 'auto_mark_absent_if_no_checkin', 'weekend_overtime_multiplier',
    ];

    protected function casts(): array
    {
        return [
            'half_day_threshold_hours' => 'decimal:2',
            'full_day_threshold_hours' => 'decimal:2',
            'auto_mark_absent_if_no_checkin' => 'boolean',
            'weekend_overtime_multiplier' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function forBranch(int $companyId, ?int $branchId): self
    {
        return static::firstOrCreate(['company_id' => $companyId, 'branch_id' => $branchId]);
    }
}
