<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingHour extends Model
{
    public const DAYS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        0 => 'Sunday',
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'day_of_week', 'is_working_day', 'start_time', 'end_time',
    ];

    protected function casts(): array
    {
        return [
            'is_working_day' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function dayLabel(): string
    {
        return self::DAYS[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Ensure the company has all 7 day rows, creating sensible defaults
     * (Mon-Fri working 09:00-18:00, Sat-Sun off) on first access.
     */
    public static function ensureDefaultsFor(Company $company, ?int $branchId): void
    {
        foreach (array_keys(self::DAYS) as $day) {
            self::firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => $branchId, 'day_of_week' => $day],
                [
                    'is_working_day' => ! in_array($day, [0, 6], true),
                    'start_time' => '09:00:00',
                    'end_time' => '18:00:00',
                ]
            );
        }
    }
}
