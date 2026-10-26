<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Increment extends Model
{
    use HasFactory, ScopedByEmployee;

    protected $fillable = [
        'company_id', 'employee_id', 'appraisal_id', 'previous_amount',
        'increment_percent', 'new_amount', 'effective_date', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'previous_amount' => 'decimal:2',
            'increment_percent' => 'decimal:2',
            'new_amount' => 'decimal:2',
            'effective_date' => 'date',
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

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class);
    }
}
