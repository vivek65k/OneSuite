<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'branch_id', 'name', 'shift_id', 'daily_threshold_hours', 'multiplier', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'daily_threshold_hours' => 'decimal:2',
            'multiplier' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
