<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftRoster extends Model
{
    use HasFactory, ScopedByEmployee;

    protected $fillable = ['company_id', 'employee_id', 'shift_id', 'date'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
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

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Find-or-create the one roster row for this employee/date. See
     * AttendanceRecord::upsertForDate() for why whereDate() (not an exact
     * 'date' match) is required to find the existing row.
     */
    public static function upsertForDate(int $employeeId, int $companyId, string $date, int $shiftId): self
    {
        $roster = static::where('employee_id', $employeeId)->whereDate('date', $date)->first();

        if ($roster) {
            $roster->update(['shift_id' => $shiftId]);

            return $roster;
        }

        return static::create([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'shift_id' => $shiftId,
            'date' => $date,
        ]);
    }

    public static function clearForDate(int $employeeId, string $date): void
    {
        static::where('employee_id', $employeeId)->whereDate('date', $date)->delete();
    }
}
