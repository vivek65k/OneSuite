<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory, ScopedByEmployee;

    protected $fillable = [
        'company_id', 'employee_id', 'leave_type_id', 'year',
        'allocated', 'carried_forward', 'adjusted', 'used', 'encashed',
    ];

    protected function casts(): array
    {
        return [
            'allocated' => 'decimal:2',
            'carried_forward' => 'decimal:2',
            'adjusted' => 'decimal:2',
            'used' => 'decimal:2',
            'encashed' => 'decimal:2',
        ];
    }

    protected function remaining(): Attribute
    {
        return Attribute::get(fn () => round(
            $this->allocated + $this->carried_forward + $this->adjusted - $this->used - $this->encashed,
            2
        ));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Find-or-create the one balance row for this employee/type/year, then
     * apply a delta to $column (e.g. +1 to "used" when a leave is approved).
     * Centralized here so LeaveRequest/CompOff/LeaveEncashment approvals
     * all adjust balances the same way instead of three separate
     * find-or-create-then-increment blocks scattered across controllers.
     */
    public static function adjust(int $employeeId, int $companyId, int $leaveTypeId, int $year, string $column, float $delta): self
    {
        $balance = static::firstOrCreate(
            ['employee_id' => $employeeId, 'leave_type_id' => $leaveTypeId, 'year' => $year],
            ['company_id' => $companyId]
        );

        $balance->increment($column, $delta);

        return $balance;
    }
}
