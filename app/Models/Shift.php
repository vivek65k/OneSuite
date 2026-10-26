<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'branch_id', 'name', 'code', 'start_time', 'end_time',
        'break_minutes', 'grace_minutes', 'half_day_hours', 'full_day_hours',
        'is_night_shift', 'is_flexible', 'flexible_window_minutes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'half_day_hours' => 'decimal:2',
            'full_day_hours' => 'decimal:2',
            'is_night_shift' => 'boolean',
            'is_flexible' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(ShiftRoster::class);
    }

    public function overtimeRules(): HasMany
    {
        return $this->hasMany(OvertimeRule::class);
    }
}
