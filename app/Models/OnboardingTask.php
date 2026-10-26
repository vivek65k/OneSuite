<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTask extends Model
{
    use HasFactory, ScopedByEmployee;

    public const CATEGORIES = [
        'welcome-checklist' => 'Welcome Checklist',
        'document-verification' => 'Document Verification',
        'asset-allocation' => 'Asset Allocation',
        'it-account-creation' => 'IT Account Creation',
        'orientation' => 'Orientation',
    ];

    public const ROLES = [
        'manager' => 'Manager',
        'hr' => 'HR',
    ];

    protected $fillable = [
        'company_id', 'employee_id', 'category', 'title', 'description',
        'assigned_role', 'assigned_to_user_id', 'due_date',
        'is_completed', 'completed_at', 'completed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_completed' => 'boolean',
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

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
