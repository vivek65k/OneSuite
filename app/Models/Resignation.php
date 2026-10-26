<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Resignation extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = [
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'withdrawn' => 'Withdrawn',
        'completed' => 'Completed',
    ];

    protected $fillable = [
        'company_id', 'employee_id', 'resignation_date', 'notice_period_days', 'last_working_day',
        'reason', 'status', 'reviewed_by_user_id', 'reviewed_at', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'resignation_date' => 'date',
            'last_working_day' => 'date',
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

    public function clearances(): HasMany
    {
        return $this->hasMany(SeparationClearance::class);
    }

    public function exitInterview(): HasOne
    {
        return $this->hasOne(ExitInterview::class);
    }

    public function finalSettlement(): HasOne
    {
        return $this->hasOne(FinalSettlement::class);
    }
}
