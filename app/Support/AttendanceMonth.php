<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRuleSetting;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\RegularizationRequest;
use App\Models\ShiftRoster;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * One person's attendance, day by day: what was recorded, and what each day
 * was (worked, leave, holiday, weekly off) or should have been (a working day
 * with nothing marked). My Attendance and Team Attendance both read it from
 * here, so an employee and their manager always see the same picture.
 */
class AttendanceMonth
{
    /** Day kinds that count as having worked. */
    public const WORKED = ['present', 'wfh', 'half_day'];

    /** @var array<string, LeavePolicy> */
    protected static array $calendars = [];

    /** @var array<string, ?AttendanceRuleSetting> */
    protected static array $rules = [];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function days(Employee $employee, Carbon $start, Carbon $end): Collection
    {
        // Read by employee id, not through the viewer's role: who may look is decided by the page calling this.
        $mine = fn (string $model) => $model::withoutGlobalScopes()->where('employee_id', $employee->id);
        // whereDate, not whereBetween: stored dates carry a time part (see AttendanceRecord::upsertForDate()).
        $inRange = fn ($q, string $column = 'date') => $q->whereDate($column, '>=', $start)->whereDate($column, '<=', $end);

        $records = $inRange($mine(AttendanceRecord::class))->orderBy('id')->get()->keyBy(fn ($r) => $r->date->toDateString());
        $rosters = $inRange($mine(ShiftRoster::class))->with('shift')->get()->keyBy(fn ($r) => Carbon::parse($r->date)->toDateString());
        $requests = $inRange($mine(RegularizationRequest::class))->orderBy('id')->get()->keyBy(fn ($r) => Carbon::parse($r->date)->toDateString());
        $leaves = $mine(LeaveRequest::class)->whereIn('status', ['approved', 'pending'])
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->with('leaveType:id,name')
            // An approved leave wins over a pending one on the same day.
            ->orderByRaw("status = 'approved'")->orderBy('id')->get();
        $calendar = self::calendar($employee);
        $rule = self::rule($employee);
        $joined = $employee->joining_date?->copy()->startOfDay();

        $days = collect();
        for ($date = $start->copy()->startOfDay(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $record = $records[$key] ?? null;
            $shift = ($rosters[$key] ?? null)?->shift;
            $holiday = $calendar->holidayOn($date);
            $leave = $leaves->last(fn ($l) => Carbon::parse($l->start_date)->toDateString() <= $key && Carbon::parse($l->end_date)->toDateString() >= $key);
            $request = $requests[$key] ?? null;
            $employed = ! $joined || $date->gte($joined);
            // A day someone is rostered on is a working day even on a weekly off or holiday.
            $weeklyOff = $calendar->isWeekend($date);
            $working = $employed && ($shift || (! $weeklyOff && ! $holiday));
            $isPast = $date->lt(today());

            $kind = match (true) {
                (bool) $record => $record->status,
                ! $employed => 'before',
                $leave?->status === 'approved' && $working => 'on_leave',
                (bool) $holiday && ! $shift => 'holiday',
                $weeklyOff && ! $shift => 'weekend',
                $isPast => 'missing',
                default => 'upcoming',
            };

            $in = $record?->check_in_time;
            $out = $record?->check_out_time;
            $hours = $record?->work_hours !== null ? (float) $record->work_hours
                : ($in && $out ? round(max(0, ShiftTime::spanMinutes($in, $out) - (int) ($shift?->break_minutes ?? 0)) / 60, 2) : null);
            $late = 0;
            if ($in && $shift && ! $shift->is_flexible && in_array($kind, self::WORKED, true)) {
                $grace = (int) ($shift->grace_minutes ?: ($rule?->late_grace_minutes ?? 0));
                $late = max(0, ShiftTime::toMinutes($in) - ShiftTime::toMinutes($shift->start_time) - $grace);
                // More than half a day "late" is someone on the other side of an overnight shift, not a late arrival.
                $late = $late > 720 ? 0 : $late;
            }

            // What someone may want to act on.
            $attention = match (true) {
                $request?->status === 'pending' => null,
                $kind === 'missing' => 'Nothing was marked for this working day',
                $kind === 'absent' => 'Marked absent',
                in_array($kind, self::WORKED, true) && $kind !== 'wfh' && $in && ! $out && $isPast => 'No check-out recorded',
                in_array($kind, self::WORKED, true) && $kind !== 'wfh' && ! $in && $isPast && $record?->source !== 'manual' => 'No check-in recorded',
                $late > 0 => 'Arrived '.$late.' min late',
                default => null,
            };

            $days->push([
                'date' => $date->copy(),
                'record' => $record,
                'kind' => $kind,
                'working' => $working && $kind !== 'on_leave',
                'shift' => $shift,
                'holiday' => $holiday,
                'weekly_off' => $weeklyOff,
                'leave' => $leave,
                'request' => $request,
                'in' => $in,
                'out' => $out,
                'overnight' => $in && $out && ShiftTime::crossesMidnight($in, $out),
                'hours' => $hours,
                'late' => $late,
                'attention' => $attention,
            ]);
        }

        return $days;
    }

    /**
     * The month so far in numbers, from days().
     *
     * @return array{worked: float, working: int, wfh: int, half: int, absent: int, missing: int, late: int, late_minutes: int, hours: float, average: ?float, leave: int, attention: int}
     */
    public static function summary(Collection $days): array
    {
        $past = $days->filter(fn ($d) => $d['date']->lte(today()));
        $worked = $past->whereIn('kind', self::WORKED);
        $hours = (float) $past->sum(fn ($d) => (float) $d['hours']);
        $withHours = $past->whereNotNull('hours')->count();

        return [
            'worked' => (float) $worked->sum(fn ($d) => $d['kind'] === 'half_day' ? 0.5 : 1),
            'working' => $past->where('working', true)->count(),
            'wfh' => $worked->where('kind', 'wfh')->count(),
            'half' => $worked->where('kind', 'half_day')->count(),
            'absent' => $past->where('kind', 'absent')->count(),
            'missing' => $past->where('kind', 'missing')->count(),
            'late' => $past->where('late', '>', 0)->count(),
            'late_minutes' => (int) $past->sum('late'),
            'hours' => round($hours, 1),
            'average' => $withHours ? round($hours / $withHours, 1) : null,
            'leave' => $past->where('kind', 'on_leave')->count(),
            'attention' => $past->whereNotNull('attention')->count(),
        ];
    }

    /** Weekly offs and holidays come from the same calendar the leave policy counts with. */
    protected static function calendar(Employee $employee): LeavePolicy
    {
        return self::$calendars[$employee->company_id.':'.$employee->branch_id] ??= LeavePolicy::for((int) $employee->company_id, $employee->branch_id);
    }

    protected static function rule(Employee $employee): ?AttendanceRuleSetting
    {
        $key = $employee->company_id.':'.$employee->branch_id;

        if (! array_key_exists($key, self::$rules)) {
            self::$rules[$key] = AttendanceRuleSetting::query()->where('company_id', $employee->company_id)
                ->where(fn ($q) => $q->where('branch_id', $employee->branch_id)->orWhereNull('branch_id'))->orderByRaw('branch_id is null')->first();
        }

        return self::$rules[$key];
    }
}
