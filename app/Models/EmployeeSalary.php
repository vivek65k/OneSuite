<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalary extends Model
{
    use ScopedByEmployee;

    protected $fillable = ['employee_id', 'amount', 'currency', 'effective_date', 'is_current', 'notes'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'effective_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Mark the right revision as current for one employee: the latest one
     * whose effective date has arrived. Future-dated revisions stay
     * "scheduled" and a back-dated one never overrides a newer salary.
     * Returns true when something changed.
     */
    public static function syncCurrent(int $employeeId): bool
    {
        $rows = static::query()->where('employee_id', $employeeId)->get(['id', 'effective_date', 'is_current']);
        $due = $rows->filter(fn (self $row) => $row->effective_date->lte(today()))
            ->sortBy(fn (self $row) => $row->effective_date->format('Ymd').str_pad((string) $row->id, 12, '0', STR_PAD_LEFT))
            ->last();
        $flagged = $rows->where('is_current', true)->pluck('id')->all();
        $wanted = $due ? [$due->id] : [];

        if ($flagged === $wanted) {
            return false;
        }

        // Query updates on purpose: the saving hook below would fight this.
        static::query()->where('employee_id', $employeeId)->update(['is_current' => false]);

        if ($due) {
            static::query()->whereKey($due->id)->update(['is_current' => true]);
        }

        return true;
    }

    protected static function booted(): void
    {
        static::saving(function (self $salary): void {
            if ($salary->is_current) {
                static::query()
                    ->where('employee_id', $salary->employee_id)
                    ->when($salary->exists, fn ($query) => $query->whereKeyNot($salary->getKey()))
                    ->update(['is_current' => false]);
            }
        });
    }
}
