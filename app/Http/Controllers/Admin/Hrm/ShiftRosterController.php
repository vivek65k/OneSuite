<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftRoster;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShiftRosterController extends Controller
{
    public function planning(Request $request): View
    {
        $this->authorize('viewAny', ShiftRoster::class);

        $start = $this->weekStart($request);
        $end = $start->copy()->addDays(6);
        $dates = $this->weekDates($start);
        $companyId = Company::current()->id;
        $state = $request->only(['search', 'department_id']);

        // Active roster only - terminated/inactive people aren't scheduled.
        $employees = $this->employeeQuery($companyId)
            ->whereNotIn('status', ['terminated', 'inactive'])
            ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->where('department_id', $departmentId))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%")))
            ->with(['department', 'shiftRosters' => fn ($q) => $q->whereDate('date', '>=', $start)->whereDate('date', '<=', $end)])
            ->get();

        $shifts = $this->shiftOptions($companyId);

        // Approved leave and company holidays in the week, so nobody gets
        // scheduled on a day they're off.
        $onLeave = $this->leaveMap($employees->pluck('id'), $start, $end);
        $holidays = $this->holidayMap($companyId, $dates);

        // Coverage + KPIs.
        $cells = $employees->count() * 7;
        $assigned = $employees->sum(fn ($e) => $e->shiftRosters->count());
        $leaveDays = collect($onLeave)->sum(fn ($days) => count($days));
        $rosterShifts = \App\Models\Shift::whereIn('id', $employees->flatMap->shiftRosters->pluck('shift_id')->unique())->get()->keyBy('id');
        $hours = $employees->sum(fn ($e) => $e->shiftRosters->sum(fn ($r) => ($s = $rosterShifts->get($r->shift_id)) ? \App\Support\ShiftTime::netHours($s) : 0));

        return view('admin.hrm.shift.roster.planning', [
            'start' => $start,
            'dates' => $dates,
            'employees' => $employees,
            'shifts' => $shifts,
            'allShifts' => $rosterShifts,
            'onLeave' => $onLeave,
            'holidays' => $holidays,
            'state' => $state,
            'kpis' => [
                'scheduled' => $employees->filter(fn ($e) => $e->shiftRosters->isNotEmpty())->count(),
                'employees' => $employees->count(),
                'open' => max(0, $cells - $assigned - $leaveDays),
                'hours' => $hours,
                'leave_days' => $leaveDays,
            ],
            'canEdit' => $request->user()->can('update_shift_roster'),
            'departments' => \App\Models\Department::query()
                ->where('company_id', $companyId)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ]);
    }

    /**
     * Fill many cells with one shift (or clear them): a whole row, a whole
     * day column, or the whole week, from the planning board's bulk menus.
     */
    public function bulk(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('update_shift_roster'), 403);

        $data = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', $this->employeeExistsRule()],
            'dates' => ['required', 'array', 'min:1', 'max:31'],
            'dates.*' => ['date'],
            'shift_id' => ['nullable', 'integer'],
            'only_empty' => ['nullable', 'boolean'],
        ]);

        $shift = ($data['shift_id'] ?? null) ? $this->usableShift((int) $data['shift_id']) : null;
        $companyId = Company::current()->id;
        $employees = Employee::whereKey($data['employee_ids'])->get(['id', 'branch_id']);
        $changed = 0;
        $skipped = 0;
        $onLeave = 0;

        // Approved leave on the target dates - bulk fills don't schedule over it.
        $leave = $shift ? \App\Models\LeaveRequest::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', max($data['dates']))
            ->whereDate('end_date', '>=', min($data['dates']))
            ->get(['employee_id', 'start_date', 'end_date']) : collect();

        foreach ($employees as $employee) {
            if ($shift && $shift->branch_id && (int) $shift->branch_id !== (int) $employee->branch_id) {
                $skipped += count($data['dates']);

                continue;
            }
            foreach (array_unique($data['dates']) as $date) {
                if (! empty($data['only_empty']) && ShiftRoster::where('employee_id', $employee->id)->whereDate('date', $date)->exists()) {
                    continue;
                }
                if ($shift && $leave->contains(fn ($l) => (int) $l->employee_id === $employee->id && $l->start_date->toDateString() <= $date && $l->end_date->toDateString() >= $date)) {
                    $onLeave++;

                    continue;
                }
                $shift
                    ? ShiftRoster::upsertForDate($employee->id, $companyId, $date, $shift->id)
                    : ShiftRoster::clearForDate($employee->id, $date);
                $changed++;
            }
        }

        return back()->with('status', ($shift ? "Assigned {$shift->code} to {$changed} ".($changed === 1 ? 'slot' : 'slots') : "Cleared {$changed} ".($changed === 1 ? 'slot' : 'slots')).'.'
            .($onLeave ? " Skipped {$onLeave} ".($onLeave === 1 ? 'day' : 'days').' on approved leave.' : '')
            .($skipped ? " Skipped {$skipped} for employees in another branch." : ''));
    }

    /**
     * Copy last week's roster into this week - empty cells only, so nothing
     * already planned is overwritten.
     */
    public function copyWeek(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('update_shift_roster'), 403);

        $start = Carbon::parse($request->validate(['start' => ['required', 'date']])['start'])->startOfWeek();
        $companyId = Company::current()->id;
        $employeeIds = $this->employeeQuery($companyId)->whereNotIn('status', ['terminated', 'inactive'])->pluck('id');

        $previous = ShiftRoster::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>=', $start->copy()->subWeek())
            ->whereDate('date', '<', $start)
            ->whereHas('shift', fn ($q) => $q->where('is_active', true))
            ->get();

        // Approved leave this week - copied shifts don't land on those days.
        $leave = \App\Models\LeaveRequest::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $start->copy()->addDays(6))
            ->whereDate('end_date', '>=', $start)
            ->get(['employee_id', 'start_date', 'end_date']);

        $copied = 0;
        foreach ($previous as $roster) {
            $target = $roster->date->copy()->addWeek()->toDateString();
            $isOnLeave = $leave->contains(fn ($l) => (int) $l->employee_id === (int) $roster->employee_id && $l->start_date->toDateString() <= $target && $l->end_date->toDateString() >= $target);
            if (! $isOnLeave && ! ShiftRoster::where('employee_id', $roster->employee_id)->whereDate('date', $target)->exists()) {
                ShiftRoster::upsertForDate($roster->employee_id, $companyId, $target, $roster->shift_id);
                $copied++;
            }
        }

        return redirect()->route('admin.shift-roster.planning', ['start' => $start->toDateString()])
            ->with('status', $copied ? "Copied {$copied} ".($copied === 1 ? 'shift' : 'shifts').' from last week into empty slots.' : 'Nothing to copy - last week was empty or this week is already filled.');
    }

    /** An active shift of this company (and branch), or a validation error. */
    protected function usableShift(int $shiftId): Shift
    {
        $shift = $this->shiftOptions(Company::current()->id)->firstWhere('id', $shiftId);

        if (! $shift) {
            throw \Illuminate\Validation\ValidationException::withMessages(['shift_id' => 'That shift is inactive or not available here.']);
        }

        return $shift;
    }

    protected function employeeExistsRule()
    {
        return Rule::exists('employees', 'id')
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    public function update(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        // Changing the roster needs the update permission, not just view.
        abort_unless($request->user()->can('update_shift_roster'), 403);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', $this->employeeExistsRule()],
            'date' => ['required', 'date'],
            'shift_id' => ['nullable', 'integer'],
            'start' => ['nullable', 'date'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $shift = null;

        if ($data['shift_id'] ?? null) {
            // Only active shifts of this company, from the employee's own branch.
            $shift = $this->usableShift((int) $data['shift_id']);
            if ($shift->branch_id && (int) $shift->branch_id !== (int) $employee->branch_id) {
                throw \Illuminate\Validation\ValidationException::withMessages(['shift_id' => "{$shift->name} belongs to another branch than {$employee->name}."]);
            }
            ShiftRoster::upsertForDate($employee->id, $companyId, $data['date'], $shift->id);
        } else {
            ShiftRoster::clearForDate($employee->id, $data['date']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'shift' => $shift ? [
                    'id' => $shift->id,
                    'code' => $shift->code,
                    'name' => $shift->name,
                    'time' => \App\Support\ShiftTime::format($shift->start_time).' – '.\App\Support\ShiftTime::format($shift->end_time),
                    'hours' => \App\Support\ShiftTime::netHours($shift),
                    'tone' => \App\Support\ShiftTime::style($shift)['tone'],
                ] : null,
            ]);
        }

        return redirect()->route('admin.shift-roster.planning', ($data['start'] ?? null) ? ['start' => $data['start']] : [])
            ->with('status', 'Roster updated.');
    }

    /** Longest date range one assignment may cover. */
    public const ASSIGN_MAX_DAYS = 62;

    public function assign(): View
    {
        $this->authorize('update', new ShiftRoster());

        $companyId = Company::current()->id;

        // Active people only - terminated/inactive staff aren't scheduled.
        $employees = $this->employeeQuery($companyId)
            ->whereNotIn('status', ['terminated', 'inactive'])
            ->with('department:id,name')
            ->get(['id', 'name', 'employee_code', 'department_id', 'branch_id']);
        $ids = $employees->pluck('id');

        // Existing roster + approved leave in a window around today, so the
        // summary can preview kept slots and leave skips before submitting.
        $from = now()->startOfMonth()->min(now()->startOfWeek())->startOfDay();
        $to = now()->addDays(92)->startOfDay();

        $rostered = [];
        ShiftRoster::query()
            ->whereIn('employee_id', $ids)
            ->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)
            ->get(['employee_id', 'date'])
            ->each(function ($r) use (&$rostered) {
                $rostered[$r->employee_id][] = $r->date->toDateString();
            });

        $onLeave = [];
        \App\Models\LeaveRequest::query()
            ->whereIn('employee_id', $ids)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->get(['employee_id', 'start_date', 'end_date'])
            ->each(function ($l) use (&$onLeave) {
                $onLeave[$l->employee_id][] = ['from' => $l->start_date->toDateString(), 'to' => $l->end_date->toDateString()];
            });

        $scheduledIn = fn (Carbon $weekStart) => ShiftRoster::query()
            ->whereIn('employee_id', $ids)
            ->whereDate('date', '>=', $weekStart)->whereDate('date', '<=', $weekStart->copy()->addDays(6))
            ->distinct()->count('employee_id');
        $shifts = $this->shiftOptions($companyId);

        return view('admin.hrm.shift.roster.assign', [
            'employees' => $employees,
            'shifts' => $shifts,
            'departments' => $employees->pluck('department')->filter()->unique('id')->sortBy('name')->pluck('name', 'id'),
            'rostered' => $rostered,
            'onLeave' => $onLeave,
            'window' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'maxDays' => self::ASSIGN_MAX_DAYS,
            'kpis' => [
                'employees' => $employees->count(),
                'this_week' => $scheduledIn(now()->startOfWeek()),
                'next_week_open' => max(0, $employees->count() - $scheduledIn(now()->addWeek()->startOfWeek())),
                'shifts' => $shifts->count(),
            ],
        ]);
    }

    public function storeAssign(Request $request): RedirectResponse
    {
        $this->authorize('update', new ShiftRoster());

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', $this->employeeExistsRule()],
            'shift_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer', 'between:1,7'],
            'mode' => ['nullable', Rule::in(['empty', 'replace'])],
        ], [
            'employee_ids.required' => 'Select at least one employee.',
            'days.required' => 'Pick at least one day of the week.',
        ]);

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();
        if ($start->diffInDays($end) + 1 > self::ASSIGN_MAX_DAYS) {
            throw \Illuminate\Validation\ValidationException::withMessages(['end_date' => 'Assign at most '.self::ASSIGN_MAX_DAYS.' days at a time.']);
        }

        // Only active shifts of this company/branch.
        $shift = $this->usableShift((int) $data['shift_id']);
        $onlyEmpty = ($data['mode'] ?? 'empty') === 'empty';
        $weekdays = array_map('intval', $data['days']);

        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (in_array($date->dayOfWeekIso, $weekdays, true)) {
                $dates[] = $date->toDateString();
            }
        }

        $employees = Employee::whereKey(array_unique($data['employee_ids']))
            ->whereNotIn('status', ['terminated', 'inactive'])
            ->get(['id', 'branch_id']);
        $ids = $employees->pluck('id');

        $leave = \App\Models\LeaveRequest::query()
            ->whereIn('employee_id', $ids)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->get(['employee_id', 'start_date', 'end_date']);
        $existing = ShiftRoster::query()
            ->whereIn('employee_id', $ids)
            ->whereDate('date', '>=', $start)->whereDate('date', '<=', $end)
            ->get(['employee_id', 'date'])
            ->mapWithKeys(fn ($r) => [$r->employee_id.'|'.$r->date->toDateString() => true]);

        $assigned = $kept = $onLeave = $otherBranch = 0;

        foreach ($employees as $employee) {
            // A branch's shift can't be given to someone in another branch.
            if ($shift->branch_id && (int) $shift->branch_id !== (int) $employee->branch_id) {
                $otherBranch++;

                continue;
            }
            foreach ($dates as $date) {
                if ($leave->contains(fn ($l) => (int) $l->employee_id === $employee->id && $l->start_date->toDateString() <= $date && $l->end_date->toDateString() >= $date)) {
                    $onLeave++;

                    continue;
                }
                if ($onlyEmpty && $existing->has($employee->id.'|'.$date)) {
                    $kept++;

                    continue;
                }
                ShiftRoster::upsertForDate($employee->id, $companyId, $date, $shift->id);
                $assigned++;
            }
        }

        $plural = fn (int $n, string $word) => $n.' '.$word.($n === 1 ? '' : 's');
        $message = ($assigned ? "Assigned {$shift->name} ({$shift->code}) to ".$plural($assigned, 'slot').'.' : 'Nothing was assigned.')
            .($kept ? ' Kept '.$plural($kept, 'existing shift').'.' : '')
            .($onLeave ? ' Skipped '.$plural($onLeave, 'day').' on approved leave.' : '')
            .($otherBranch ? ' Skipped '.$plural($otherBranch, 'employee').' from another branch.' : '')
            .(! $dates ? ' No selected weekdays fall in that date range.' : '');

        return redirect()->route('admin.shift-roster.assign')
            ->with($assigned ? 'status' : 'warning', $message)
            ->with('assign_week', $start->copy()->startOfWeek()->toDateString());
    }

    public function weekly(Request $request): View
    {
        $this->authorize('viewAny', ShiftRoster::class);

        $start = $this->weekStart($request);
        $end = $start->copy()->addDays(6);
        $dates = $this->weekDates($start);
        $companyId = Company::current()->id;
        $state = $request->only(['search', 'department_id', 'shift_id']);
        $view = $request->query('view') === 'day' ? 'day' : 'grid';
        $inWeek = fn ($q) => $q->whereDate('date', '>=', $start)->whereDate('date', '<=', $end);

        // Active people only; terminated/inactive staff have no schedule.
        $employees = $this->employeeQuery($companyId)
            ->whereNotIn('status', ['terminated', 'inactive'])
            ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->where('department_id', $departmentId))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%")))
            ->when($state['shift_id'] ?? null, fn ($q, $shiftId) => $q->whereHas('shiftRosters', fn ($r) => $inWeek($r)->where('shift_id', $shiftId)))
            ->with(['department:id,name', 'shiftRosters' => fn ($q) => $inWeek($q)->with('shift')])
            ->get();

        $onLeave = $this->leaveMap($employees->pluck('id'), $start, $end);
        $rosters = $employees->flatMap->shiftRosters->filter(fn ($r) => $r->shift);
        $weekShifts = $rosters->pluck('shift')->unique('id')->sortBy('start_time')->values();
        $leaveDays = collect($onLeave)->sum(fn ($days) => count($days));

        return view('admin.hrm.shift.roster.weekly', [
            'start' => $start,
            'dates' => $dates,
            'employees' => $employees,
            'onLeave' => $onLeave,
            'holidays' => $this->holidayMap($companyId, $dates),
            'weekShifts' => $weekShifts,
            'shiftFilter' => $this->shiftOptions($companyId)->keyBy('id')->union($weekShifts->keyBy('id'))->sortBy('name'),
            'state' => $state,
            'view' => $view,
            'kpis' => [
                'scheduled' => $employees->filter(fn ($e) => $e->shiftRosters->isNotEmpty())->count(),
                'employees' => $employees->count(),
                'hours' => $rosters->sum(fn ($r) => \App\Support\ShiftTime::netHours($r->shift)),
                'night' => $rosters->filter(fn ($r) => $r->shift->is_night_shift || \App\Support\ShiftTime::crossesMidnight($r->shift->start_time, $r->shift->end_time))->count(),
                'open' => max(0, $employees->count() * 7 - $rosters->count() - $leaveDays),
                'leave_days' => $leaveDays,
            ],
            'canEdit' => $request->user()->can('update_shift_roster'),
            'departments' => \App\Models\Department::query()
                ->where('company_id', $companyId)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ]);
    }

    /**
     * Approved leave per employee per day in the range: [employee_id][Y-m-d] => leave code.
     *
     * @return array<int, array<string, string>>
     */
    protected function leaveMap($employeeIds, Carbon $start, Carbon $end): array
    {
        $map = [];
        \App\Models\LeaveRequest::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->with('leaveType:id,name,code')
            ->get()
            ->each(function ($leave) use (&$map, $start, $end) {
                // max() can return $start itself - copy again so addDay() never moves the caller's range.
                for ($d = $leave->start_date->copy()->max($start)->copy();$d->lte($leave->end_date) && $d->lte($end); $d->addDay()) {
                    $map[$leave->employee_id][$d->toDateString()] = $leave->leaveType?->code ?? 'Leave';
                }
            });

        return $map;
    }

    /** Company holidays falling on the given dates: [Y-m-d] => name. */
    protected function holidayMap(int $companyId, array $dates)
    {
        return \App\Models\Holiday::where('company_id', $companyId)->get(['name', 'date', 'is_recurring'])
            ->flatMap(fn ($h) => collect($dates)->filter(fn ($d) => $h->date->isSameDay($d) || ($h->is_recurring && $h->date->format('m-d') === $d->format('m-d')))
                ->mapWithKeys(fn ($d) => [$d->toDateString() => $h->name]));
    }

    public function nightShift(Request $request): View
    {
        $this->authorize('viewAny', ShiftRoster::class);

        [$start, $end, $rangeNote] = $this->safeRange($request, 92);
        $companyId = Company::current()->id;
        $state = $request->only(['search', 'department_id', 'shift_id']);
        $view = $request->query('view') === 'people' ? 'people' : 'list';

        // Night = flagged as a night shift OR running past midnight (a 22:00-06:00
        // shift someone forgot to flag is still a night shift).
        $nightShifts = Shift::query()
            ->where('company_id', $companyId)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('start_time')
            ->get()
            ->filter(fn ($s) => $s->is_night_shift || \App\Support\ShiftTime::crossesMidnight($s->start_time, $s->end_time))
            ->keyBy('id');
        $shiftIds = ($state['shift_id'] ?? null) && $nightShifts->has($state['shift_id'])
            ? [(int) $state['shift_id']]
            : $nightShifts->keys()->all();

        $employeeFilter = fn ($q) => $q
            ->when(BranchContext::isRestricted(), fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId()))
            ->when($state['department_id'] ?? null, fn ($eq, $departmentId) => $eq->where('department_id', $departmentId))
            ->when($state['search'] ?? null, fn ($eq, $search) => $eq->where(fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%")));

        $rosters = ShiftRoster::query()
            ->where('company_id', $companyId)
            ->whereDate('date', '>=', $start)->whereDate('date', '<=', $end)
            ->whereIn('shift_id', $shiftIds)
            ->whereHas('employee', $employeeFilter)
            ->with(['employee.department:id,name', 'shift'])
            ->orderBy('date')
            ->get();
        $employeeIds = $rosters->pluck('employee_id')->unique()->values();

        // Rest check: whatever each person works the day after a night shift.
        $nextDay = ShiftRoster::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>', $start)->whereDate('date', '<=', $end->copy()->addDay())
            ->with('shift')
            ->get()
            ->keyBy(fn ($r) => $r->employee_id.'|'.$r->date->toDateString());
        $onLeave = $this->leaveMap($employeeIds, $start, $end);

        $flags = [];
        $streaks = [];
        foreach ($rosters->groupBy('employee_id') as $employeeId => $rows) {
            $run = 0;
            $previous = null;
            foreach ($rows as $roster) {
                $run = $previous && $previous->copy()->addDay()->isSameDay($roster->date) ? $run + 1 : 1;
                $previous = $roster->date;
                $streaks[$employeeId] = max($streaks[$employeeId] ?? 0, $run);

                $next = $nextDay->get($employeeId.'|'.$roster->date->copy()->addDay()->toDateString());
                // Overnight shifts end the next morning; others end the same day.
                $endsNextDay = \App\Support\ShiftTime::crossesMidnight($roster->shift->start_time, $roster->shift->end_time);
                $rest = $next?->shift
                    ? (\App\Support\ShiftTime::toMinutes($next->shift->start_time) + ($endsNextDay ? 0 : 1440) - \App\Support\ShiftTime::toMinutes($roster->shift->end_time)) / 60
                    : null;
                $flags[$roster->id] = [
                    'streak' => $run,
                    'rest' => $rest,
                    'short_rest' => $rest !== null && $rest < self::MIN_REST_HOURS,
                    'leave' => $onLeave[$employeeId][$roster->date->toDateString()] ?? null,
                ];
            }
        }

        $tonight = ShiftRoster::query()
            ->where('company_id', $companyId)
            ->whereDate('date', today())
            ->whereIn('shift_id', $nightShifts->keys())
            ->whereHas('employee', $employeeFilter)
            ->count();

        return view('admin.hrm.shift.roster.night', [
            'start' => $start,
            'end' => $end,
            'rangeNote' => $rangeNote,
            'rosters' => $rosters,
            'flags' => $flags,
            'streaks' => $streaks,
            'onLeave' => $onLeave,
            'nightShifts' => $nightShifts,
            'state' => $state,
            'view' => $view,
            'kpis' => [
                'nights' => $rosters->count(),
                'people' => $employeeIds->count(),
                'hours' => $rosters->sum(fn ($r) => \App\Support\ShiftTime::netHours($r->shift)),
                'tonight' => $tonight,
                'long_streaks' => collect($streaks)->filter(fn ($n) => $n >= self::LONG_NIGHT_STREAK)->count(),
                'short_rest' => collect($flags)->where('short_rest', true)->count(),
            ],
            'departments' => \App\Models\Department::query()
                ->where('company_id', $companyId)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ]);
    }

    /** Consecutive nights at/above which a person is flagged for fatigue. */
    public const LONG_NIGHT_STREAK = 5;

    /** Minimum hours off between a night shift ending and the next shift starting. */
    public const MIN_REST_HOURS = 11;

    /**
     * ?start/?end parsed safely: malformed dates fall back to the default week,
     * a reversed range is swapped and an over-long one is capped.
     *
     * @return array{0: Carbon, 1: Carbon, 2: ?string}
     */
    protected function safeRange(Request $request, int $maxDays, ?Carbon $defaultStart = null): array
    {
        $parse = function ($value) {
            try {
                return is_string($value) && $value !== '' ? Carbon::parse($value)->startOfDay() : null;
            } catch (\Throwable) {
                return null;
            }
        };
        $start = $parse($request->query('start')) ?? ($defaultStart ?? now())->copy()->startOfDay();
        $end = $parse($request->query('end')) ?? $start->copy()->addDays(6);
        $note = null;

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
            $note = 'The dates were reversed, so they have been swapped.';
        }
        if ($start->diffInDays($end) + 1 > $maxDays) {
            $end = $start->copy()->addDays($maxDays - 1);
            $note = "Showing the first {$maxDays} days - pick a shorter range to see the rest.";
        }

        return [$start, $end, $note];
    }

    public function flexibleShift(Request $request): View
    {
        $this->authorize('viewAny', ShiftRoster::class);

        // Defaults to this week (Mon-Sun) so past days show real check-ins.
        [$start, $end, $rangeNote] = $this->safeRange($request, 92, now()->startOfWeek());
        $companyId = Company::current()->id;
        $state = $request->only(['search', 'department_id', 'shift_id']);
        $status = (string) $request->query('status', '');

        $flexShifts = Shift::query()
            ->where('company_id', $companyId)
            ->where('is_flexible', true)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('start_time')
            ->get()
            ->keyBy('id');
        $shiftIds = ($state['shift_id'] ?? null) && $flexShifts->has($state['shift_id'])
            ? [(int) $state['shift_id']]
            : $flexShifts->keys()->all();

        $rosters = ShiftRoster::query()
            ->where('company_id', $companyId)
            ->whereDate('date', '>=', $start)->whereDate('date', '<=', $end)
            ->whereIn('shift_id', $shiftIds)
            ->whereHas('employee', fn ($q) => $q
                ->when(BranchContext::isRestricted(), fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId()))
                ->when($state['department_id'] ?? null, fn ($eq, $departmentId) => $eq->where('department_id', $departmentId))
                ->when($state['search'] ?? null, fn ($eq, $search) => $eq->where(fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%"))))
            ->with(['employee.department:id,name', 'shift'])
            ->orderBy('date')
            ->get();

        $employeeIds = $rosters->pluck('employee_id')->unique()->values();
        $attendance = \App\Models\AttendanceRecord::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>=', $start)->whereDate('date', '<=', $end)
            ->get()
            ->keyBy(fn ($a) => $a->employee_id.'|'.$a->date->toDateString());
        $onLeave = $this->leaveMap($employeeIds, $start, $end);

        // Compare each day's check-in with the shift's +/- window and the hours worked with the shift's hours.
        $rows = $rosters->map(function ($roster) use ($attendance, $onLeave) {
            $shift = $roster->shift;
            $key = $roster->date->toDateString();
            $record = $attendance->get($roster->employee_id.'|'.$key);
            $window = (int) ($shift->flexible_window_minutes ?? 0);
            $required = \App\Support\ShiftTime::netHours($shift);
            $offset = null;   // minutes after (+) / before (-) the base start
            $worked = null;

            if ($record?->check_in_time) {
                $offset = \App\Support\ShiftTime::toMinutes($record->check_in_time) - \App\Support\ShiftTime::toMinutes($shift->start_time);
                $offset = (($offset + 720) % 1440 + 1440) % 1440 - 720; // shortest way round the clock
                $worked = $record->work_hours !== null
                    ? (float) $record->work_hours
                    : ($record->check_out_time ? \App\Support\ShiftTime::spanMinutes($record->check_in_time, $record->check_out_time) / 60 : null);
            }

            $status = match (true) {
                isset($onLeave[$roster->employee_id][$key]) || $record?->status === 'on_leave' => 'leave',
                $offset !== null && $offset > $window => 'late',
                $offset !== null && $offset < -$window => 'early',
                $offset !== null => 'within',
                $record?->status === 'absent' => 'absent',
                $roster->date->isFuture() => 'upcoming',
                $roster->date->isToday() => 'awaiting',
                default => 'missing',
            };

            return [
                'roster' => $roster,
                'record' => $record,
                'window' => $window,
                'offset' => $offset,
                'required' => $required,
                'worked' => $worked,
                'short' => $worked !== null && $record?->check_out_time && $worked + 0.25 < $required,
                'status' => $status,
                'leave' => $onLeave[$roster->employee_id][$key] ?? null,
            ];
        });

        $counts = $rows->countBy('status');
        $checkedIn = $rows->whereIn('status', ['within', 'late', 'early'])->count();

        return view('admin.hrm.shift.roster.flexible', [
            'start' => $start,
            'end' => $end,
            'rangeNote' => $rangeNote,
            'rows' => $status !== '' ? $rows->where('status', $status)->values() : $rows,
            'status' => $status,
            'counts' => $counts->put('all', $rows->count()),
            'flexShifts' => $flexShifts,
            'state' => $state,
            'kpis' => [
                'shifts' => $rows->count(),
                'people' => $employeeIds->count(),
                'within_rate' => $checkedIn ? round($counts->get('within', 0) / $checkedIn * 100) : null,
                'late' => $counts->get('late', 0),
                'short' => $rows->where('short', true)->count(),
                'missing' => $counts->get('missing', 0) + $counts->get('absent', 0),
            ],
            'departments' => \App\Models\Department::query()
                ->where('company_id', $companyId)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ]);
    }

    protected function weekStart(Request $request): Carbon
    {
        // A malformed ?start= falls back to this week instead of a 500.
        try {
            $start = $request->query('start');

            return is_string($start) && $start !== '' ? Carbon::parse($start)->startOfWeek() : now()->startOfWeek();
        } catch (\Throwable) {
            return now()->startOfWeek();
        }
    }

    /**
     * @return array<int, Carbon>
     */
    protected function weekDates(Carbon $start): array
    {
        return collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i))->all();
    }

    protected function employeeQuery(int $companyId)
    {
        return Employee::query()
            ->where('company_id', $companyId)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name');
    }

    protected function employeeOptions(int $companyId)
    {
        return $this->employeeQuery($companyId)->pluck('name', 'id');
    }

    protected function shiftOptions(int $companyId)
    {
        return Shift::query()
            ->where('company_id', $companyId)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
