<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentResult extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = [
        'pass' => 'Pass',
        'fail' => 'Fail',
    ];

    protected $fillable = ['company_id', 'assessment_id', 'employee_id', 'score', 'status', 'attempted_date'];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'attempted_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
