<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingEnrollment extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = [
        'assigned' => 'Assigned',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'dropped' => 'Dropped',
    ];

    protected $fillable = [
        'company_id', 'training_session_id', 'employee_id', 'status', 'assigned_date', 'completed_date',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'completed_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
