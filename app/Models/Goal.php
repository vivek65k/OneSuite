<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
    ];

    protected $fillable = [
        'company_id', 'employee_id', 'appraisal_cycle_id', 'title', 'description',
        'weight', 'target_date', 'status', 'progress_percent',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'target_date' => 'date',
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

    public function appraisalCycle(): BelongsTo
    {
        return $this->belongsTo(AppraisalCycle::class);
    }
}
