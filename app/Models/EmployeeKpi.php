<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeKpi extends Model
{
    use HasFactory, ScopedByEmployee;

    protected $fillable = [
        'company_id', 'employee_id', 'kpi_id', 'appraisal_cycle_id', 'target_value', 'actual_value', 'weight',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'actual_value' => 'decimal:2',
            'weight' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }

    public function appraisalCycle(): BelongsTo
    {
        return $this->belongsTo(AppraisalCycle::class);
    }

    public function achievementPercent(): ?float
    {
        if ((float) $this->target_value <= 0 || $this->actual_value === null) {
            return null;
        }

        return round((float) $this->actual_value / (float) $this->target_value * 100, 1);
    }
}
