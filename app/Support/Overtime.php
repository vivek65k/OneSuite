<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\OvertimeRule;
use Illuminate\Support\Collection;

/**
 * Overtime helpers: which rule applies to a shift, and how many hours an
 * attendance record shows as worked.
 */
class Overtime
{
    /** Active overtime rules of the current company (and branch). */
    public static function activeRules(): Collection
    {
        return OvertimeRule::query()
            ->where('company_id', Company::current()->id)
            ->where('is_active', true)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->get();
    }

    /** A shift's own rule wins; otherwise the all-shifts rule of the employee's branch (or company-wide). */
    public static function ruleFor(Collection $rules, ?int $shiftId, ?int $branchId): ?OvertimeRule
    {
        $inBranch = $rules->filter(fn ($r) => ! $r->branch_id || ! $branchId || (int) $r->branch_id === (int) $branchId);

        return ($shiftId ? $inBranch->first(fn ($r) => (int) $r->shift_id === $shiftId) : null)
            ?? $inBranch->first(fn ($r) => ! $r->shift_id);
    }

    /** Hours worked per the attendance record, or null when there is no complete punch. */
    public static function worked(?AttendanceRecord $record): ?float
    {
        if (! $record) {
            return null;
        }
        if ($record->work_hours !== null && (float) $record->work_hours > 0) {
            return round((float) $record->work_hours, 2);
        }
        if ($record->check_in_time && $record->check_out_time) {
            return round(ShiftTime::spanMinutes($record->check_in_time, $record->check_out_time) / 60, 2);
        }

        return null;
    }
}
