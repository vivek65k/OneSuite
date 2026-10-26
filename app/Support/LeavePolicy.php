<?php

namespace App\Support;

use App\Models\Holiday;
use App\Models\LeavePolicySetting;
use App\Models\WorkingHour;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * The leave policy in force for a branch: which days in a range count as
 * leave (weekends, holidays, the sandwich rule, half days) and the limits
 * on when leave may be requested.
 *
 * Until a policy has been saved, leave keeps being counted the way it always
 * was - every calendar day - so nothing changes behind anyone's back.
 */
class LeavePolicy
{
    /**
     * @param  array<string, mixed>  $rules
     * @param  array<int, bool>  $workingDays  day of week (0 = Sunday) => working?
     */
    public function __construct(
        public array $rules,
        public bool $saved,
        public array $workingDays,
        public Collection $holidays,
    ) {}

    public static function for(int $companyId, ?int $branchId): self
    {
        // The branch's own policy, else a company-wide one.
        $setting = LeavePolicySetting::where('company_id', $companyId)
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->orderByRaw('branch_id IS NULL')
            ->first();

        $rules = $setting
            ? array_intersect_key($setting->only(array_keys(LeavePolicySetting::LEGACY)), LeavePolicySetting::LEGACY)
            : LeavePolicySetting::LEGACY;

        $hours = WorkingHour::where('company_id', $companyId)
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->orderByRaw('branch_id IS NULL')
            ->get()
            ->unique('day_of_week');
        $workingDays = [];
        foreach (range(0, 6) as $dow) {
            $row = $hours->firstWhere('day_of_week', $dow);
            $workingDays[$dow] = $row ? (bool) $row->is_working_day : ! in_array($dow, [0, 6], true);
        }

        return new self($rules, (bool) $setting, $workingDays, Holiday::where('company_id', $companyId)->get(['name', 'date', 'is_recurring']));
    }

    public function isWeekend(Carbon $day): bool
    {
        return ! ($this->workingDays[$day->dayOfWeek] ?? true);
    }

    /** The holiday on this day, if any (yearly ones repeat from the year they were added). */
    public function holidayOn(Carbon $day): ?string
    {
        foreach ($this->holidays as $holiday) {
            if ($holiday->date->isSameDay($day)
                || ($holiday->is_recurring && $holiday->date->year <= $day->year && $holiday->date->format('m-d') === $day->format('m-d'))) {
                return $holiday->name;
            }
        }

        return null;
    }

    /**
     * How a date range is counted under this policy.
     *
     * @return array{calendar: int, weekends: int, holidays: array<string, string>, sandwiched: int, days: float, dates: array<int, string>}
     */
    public function breakdown(Carbon $start, Carbon $end, ?string $halfDay = null): array
    {
        $kinds = [];
        for ($day = $start->copy()->startOfDay(); $day->lte($end); $day->addDay()) {
            $holiday = $this->holidayOn($day);
            $kinds[$day->toDateString()] = match (true) {
                $holiday && $this->rules['exclude_holidays'] => ['holiday', $holiday],
                $this->isWeekend($day) && $this->rules['exclude_weekends'] => ['weekend', null],
                default => ['work', null],
            };
        }

        $work = array_keys(array_filter($kinds, fn ($k) => $k[0] === 'work'));
        $first = $work[0] ?? null;
        $last = $work ? end($work) : null;
        $counted = [];
        $weekends = $sandwiched = 0;
        $holidays = [];

        foreach ($kinds as $date => [$kind, $name]) {
            if ($kind === 'work') {
                $counted[] = $date;
            } elseif ($this->rules['sandwich_rule'] && $first && $date > $first && $date < $last) {
                // Off-days squeezed between two leave days count as leave too.
                $counted[] = $date;
                $sandwiched++;
            } elseif ($kind === 'weekend') {
                $weekends++;
            } else {
                $holidays[$date] = $name;
            }
        }

        $days = (float) count($counted);
        if ($halfDay && $this->rules['allow_half_day'] && $days === 1.0 && $start->isSameDay($end)) {
            $days = 0.5;
        }

        return [
            'calendar' => count($kinds),
            'weekends' => $weekends,
            'holidays' => $holidays,
            'sandwiched' => $sandwiched,
            'days' => $days,
            'dates' => $counted,
        ];
    }

    /**
     * Check a request against the policy and return its breakdown.
     * Notice and backdating limits only bind employees applying for themselves;
     * HR can still record leave on someone's behalf.
     *
     * @throws ValidationException
     */
    public function check(Carbon $start, Carbon $end, ?string $halfDay, bool $selfService): array
    {
        if ($halfDay) {
            if (! $this->rules['allow_half_day']) {
                throw ValidationException::withMessages(['half_day' => 'Half-day leave isn\'t allowed by the leave policy.']);
            }
            if (! $start->isSameDay($end)) {
                throw ValidationException::withMessages(['half_day' => 'A half day must start and end on the same date.']);
            }
        }

        $breakdown = $this->breakdown($start, $end, $halfDay);

        if ($breakdown['days'] <= 0) {
            throw ValidationException::withMessages(['start_date' => 'Every day in that range is a weekend or holiday - there is nothing to take.']);
        }
        if ($this->rules['max_consecutive_days'] && $breakdown['days'] > $this->rules['max_consecutive_days']) {
            throw ValidationException::withMessages(['end_date' => "The leave policy allows at most {$this->rules['max_consecutive_days']} days in one request; this is {$breakdown['days']}."]);
        }

        if ($selfService) {
            $notice = (int) $this->rules['min_notice_days'];
            if ($notice && $start->copy()->startOfDay()->gte(today()) && $start->lt(today()->addDays($notice))) {
                throw ValidationException::withMessages(['start_date' => "Leave must be requested at least {$notice} ".($notice === 1 ? 'day' : 'days').' in advance.']);
            }
            $back = $this->rules['max_backdated_days'];
            if ($back !== null && $start->lt(today()->subDays($back))) {
                throw ValidationException::withMessages(['start_date' => $back === 0 ? 'Leave can\'t be requested for past dates.' : "Past leave can only be requested up to {$back} days back."]);
            }
        }

        return $breakdown;
    }

    /** What the leave forms' JavaScript needs to count days the same way. */
    public function toClient(): array
    {
        return [
            'rules' => $this->rules,
            'saved' => $this->saved,
            'workingDays' => $this->workingDays,
            'holidays' => $this->holidays->map(fn ($h) => ['name' => $h->name, 'date' => $h->date->toDateString(), 'yearly' => (bool) $h->is_recurring])->values(),
        ];
    }
}
