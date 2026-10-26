<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory, ScopedByEmployee;

    public const STATUSES = [
        'present' => 'Present',
        'absent' => 'Absent',
        'half_day' => 'Half Day',
        'on_leave' => 'On Leave',
        'wfh' => 'Work From Home',
        'holiday' => 'Holiday',
        'weekend' => 'Weekend',
    ];

    public const SOURCES = [
        'manual' => 'Manual',
        'biometric' => 'Biometric',
        'geo' => 'Geo Check-in',
    ];

    protected $fillable = [
        'company_id', 'employee_id', 'date', 'check_in_time', 'check_out_time',
        'status', 'source', 'work_hours',
        'check_in_lat', 'check_in_lng', 'check_out_lat', 'check_out_lng', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'work_hours' => 'decimal:2',
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

    /**
     * Find-or-create the one record for this employee/date and apply
     * $attributes. Deliberately not a plain updateOrCreate(): the 'date'
     * cast serializes to a full 'Y-m-d H:i:s' value on save (Eloquent uses
     * the connection's date format for storage regardless of the cast's
     * own display format), so an exact-match WHERE against a plain 'Y-m-d'
     * string from a request/CSV never finds the existing row — it always
     * inserts a duplicate. whereDate() compares only the date part, so it
     * finds the row correctly regardless of what the stored value looks like.
     */
    public static function upsertForDate(int $employeeId, int $companyId, string $date, array $attributes): self
    {
        $record = static::where('employee_id', $employeeId)->whereDate('date', $date)->first();

        if ($record) {
            $record->update($attributes);

            return $record;
        }

        return static::create(array_merge($attributes, [
            'employee_id' => $employeeId,
            'company_id' => $companyId,
            'date' => $date,
        ]));
    }
}
