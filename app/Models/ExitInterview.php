<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExitInterview extends Model
{
    use HasFactory, ScopedByEmployee;

    protected $fillable = [
        'company_id', 'resignation_id', 'employee_id', 'interview_date',
        'conducted_by_user_id', 'feedback', 'rating', 'would_rehire',
    ];

    protected function casts(): array
    {
        return [
            'interview_date' => 'date',
            'would_rehire' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function resignation(): BelongsTo
    {
        return $this->belongsTo(Resignation::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function conductedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by_user_id');
    }
}
