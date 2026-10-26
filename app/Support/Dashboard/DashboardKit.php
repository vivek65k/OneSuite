<?php

namespace App\Support\Dashboard;

use App\Models\LeaveRequest;
use App\Support\LeavePolicy;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pieces shared by the HR, manager and employee home dashboards:
 * greetings, celebrations, upcoming holidays, who is away this week and
 * the attendance-rate formula (kept identical to the attendance report).
 */
class DashboardKit
{
    public const INACTIVE = ['terminated', 'inactive'];

    public const ANNIVERSARY_MILESTONES = [1, 3, 5, 10, 15, 20, 25];

    public static function greeting(): string
    {
        $hour = now()->hour;

        return $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    }

    public static function firstName(?string $name): string
    {
        return $name ? (string) str($name)->trim()->explode(' ')->first() : 'there';
    }

    /** SQL text-format of a date column; DATE_FORMAT on MySQL/MariaDB, strftime on SQLite. */
    public static function formatDateSql(string $column, string $format): string
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? "DATE_FORMAT({$column}, '{$format}')"
            : "strftime('{$format}', {$column})";
    }

    public static function days(float $days): string
    {
        return rtrim(rtrim(number_format($days, 1), '0'), '.');
    }

    /**
     * Birthdays and work anniversaries in the next $days days, plus how
     * many years each anniversary marks (only 1+ years count).
     *
     * @return Collection<int, array{employee: mixed, kind: string, date: Carbon, years: ?int, milestone: bool}>
     */
    public static function celebrations(Builder $base, int $days = 14): Collection
    {
        $today = today();
        $window = collect(range(0, $days - 1))->map(fn ($i) => $today->copy()->addDays($i)->format('m-d'))->all();
        $rows = collect();

        $birthdays = (clone $base)->whereNotNull('date_of_birth')
            ->whereIn(DB::raw(self::formatDateSql('date_of_birth', '%m-%d')), $window)
            ->get();
        foreach ($birthdays as $employee) {
            $rows->push(['employee' => $employee, 'kind' => 'birthday', 'date' => self::nextOccurrence($employee->date_of_birth), 'years' => null, 'milestone' => false]);
        }

        $anniversaries = (clone $base)->whereNotNull('joining_date')
            ->where('joining_date', '<', $today->toDateString())
            ->whereIn(DB::raw(self::formatDateSql('joining_date', '%m-%d')), $window)
            ->get();
        foreach ($anniversaries as $employee) {
            $next = self::nextOccurrence($employee->joining_date);
            $years = $next->year - $employee->joining_date->year;
            if ($years >= 1) {
                $rows->push(['employee' => $employee, 'kind' => 'anniversary', 'date' => $next, 'years' => $years, 'milestone' => in_array($years, self::ANNIVERSARY_MILESTONES, true) && $years > 1]);
            }
        }

        return $rows->sortBy(fn ($r) => $r['date']->timestamp)->values();
    }

    /** This year's (or next year's, if already passed) occurrence of a month/day. */
    public static function nextOccurrence(Carbon $date): Carbon
    {
        $today = today();
        $day = min($date->day, Carbon::create($today->year, $date->month, 1)->daysInMonth);
        $next = Carbon::create($today->year, $date->month, $day)->startOfDay();
        if ($next->lt($today)) {
            $day = min($date->day, Carbon::create($today->year + 1, $date->month, 1)->daysInMonth);
            $next = Carbon::create($today->year + 1, $date->month, $day)->startOfDay();
        }

        return $next;
    }

    /**
     * Next holidays from today (yearly ones repeat), with weekend and
     * long-weekend flags.
     *
     * @return Collection<int, array{name: string, date: Carbon, on_weekend: bool, long_weekend: bool}>
     */
    public static function upcomingHolidays(LeavePolicy $policy, int $max = 5, int $horizonDays = 120): Collection
    {
        $out = collect();
        for ($day = today(); $out->count() < $max && $day->lte(today()->addDays($horizonDays)); $day = $day->copy()->addDay()) {
            $name = $policy->holidayOn($day);
            if (! $name) {
                continue;
            }
            $weekend = $policy->isWeekend($day);
            $out->push([
                'name' => $name,
                'date' => $day->copy(),
                'on_weekend' => $weekend,
                'long_weekend' => ! $weekend && ($policy->isWeekend($day->copy()->subDay()) || $policy->isWeekend($day->copy()->addDay())),
            ]);
        }

        return $out;
    }

    /** Approved or pending leave overlapping a date range, for employees in $employees. */
    public static function leaveBetween(Builder $employees, Carbon $from, Carbon $to): Collection
    {
        return LeaveRequest::query()
            ->whereIn('employee_id', (clone $employees)->select('employees.id'))
            ->whereIn('status', ['approved', 'pending'])
            ->where('start_date', '<=', $to->toDateString())
            ->where('end_date', '>=', $from->toDateString())
            ->with(['employee:id,name,employee_code,avatar,department_id', 'leaveType:id,name,code'])
            ->get(['id', 'employee_id', 'leave_type_id', 'start_date', 'end_date', 'days', 'half_day', 'status']);
    }

    /**
     * Attendance rate for a set of status counts, the same formula as the
     * attendance report: present + WFH + half of half days, out of every
     * marked outcome. Null when nothing was marked.
     */
    public static function attendanceRate(Collection $counts): ?float
    {
        $present = (int) $counts->get('present', 0) + (int) $counts->get('wfh', 0);
        $half = (int) $counts->get('half_day', 0);
        $marked = $present + $half + (int) $counts->get('absent', 0) + (int) $counts->get('on_leave', 0);

        return $marked ? round(($present + $half * 0.5) / $marked * 100, 1) : null;
    }

    public static function percent(?float $value): string
    {
        return $value === null ? '—' : rtrim(rtrim(number_format($value, 1), '0'), '.').'%';
    }

    public static function money(float $amount): string
    {
        return number_format($amount, 0);
    }
}
