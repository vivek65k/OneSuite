<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appraisal extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = [
        'draft' => 'Draft',
        'completed' => 'Completed',
    ];

    protected $fillable = [
        'company_id', 'employee_id', 'appraisal_cycle_id', 'status',
        'self_review_rating', 'self_review_comments', 'self_review_submitted_at',
        'manager_review_rating', 'manager_review_comments', 'manager_reviewed_by_user_id', 'manager_review_submitted_at',
        'overall_rating', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'self_review_submitted_at' => 'datetime',
            'manager_review_submitted_at' => 'datetime',
            'overall_rating' => 'decimal:2',
            'completed_at' => 'datetime',
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

    public function managerReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_reviewed_by_user_id');
    }

    public function feedback360s(): HasMany
    {
        return $this->hasMany(Feedback360::class);
    }

    public function increments(): HasMany
    {
        return $this->hasMany(Increment::class);
    }
}
