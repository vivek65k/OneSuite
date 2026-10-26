<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProbationRecord extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = [
        'on_probation' => 'On Probation',
        'extended' => 'Extended',
        'confirmed' => 'Confirmed',
        'terminated' => 'Terminated',
    ];

    protected $fillable = [
        'company_id', 'employee_id', 'start_date', 'end_date', 'status',
        'extended_to', 'remarks', 'reviewed_by_user_id', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'extended_to' => 'date',
            'reviewed_at' => 'datetime',
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

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
