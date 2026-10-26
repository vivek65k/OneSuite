<?php

namespace App\Support\Dashboard;

use App\Models\Appraisal;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\RegularizationRequest;
use App\Models\Resignation;
use App\Models\ShiftRoster;
use App\Models\TravelRequest;
use App\Models\User;
use App\Models\WfhRequest;
use App\Support\LeavePolicy;
use App\Support\Onboarding;
use App\Support\ReportingChain;
use App\Support\ShiftTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A manager's view of their team: what is waiting on them, who is in
 * today, what's coming up and how the team is doing this month.
 * Team = everyone active below them in the reporting chain, in the same
 * company (MSS convention: the reporting line, not the branch, defines
 * the team - see App\Support\ReportingChain).
 */
class TeamDashboard
{
    protected Carbon $today;

    protected Collection $teamIds;

    public function __construct(
        protected User $user,
        protected Employee $manager,
        protected int $companyId,
    ) {
        $this->today = today();
        $this->teamIds = $this->team()->pluck('id');
    }

    public function team(): Builder
    {
        return Employee::query()
            ->where('employees.company_id', $this->companyId)
            ->whereIn('employees.id', ReportingChain::below((int) $this->manager->id, $this->companyId))
            ->whereNotIn('employees.status', DashboardKit::INACTIVE);
    }

    /** Small summary for the strip shown to HR users who also manage people. */
    public function strip(): array
    {
        $today = $this->todayRows();

        return [
            'in' => $today->where('state', 'in')->count(),
            'away' => $today->where('state', 'away')->count(),
            'waiting' => $this->waiting()['total'],
        ];
    }

    public function build(): array
    {
        $policy = LeavePolicy::for($this->companyId, $this->manager->branch_id);
        $today = $this->todayRows();
        $waiting = $this->waiting();
        $holidays = DashboardKit::upcomingHolidays($policy, 4);

        $chips = [];
        if ($waiting['total']) {
            $oldest = collect($waiting['rows'])->max('oldest');
            $chips[] = ['tone' => $oldest > 3 ? 'danger' : 'amber', 'icon' => 'ti-inbox', 'text' => $waiting['total'].' '.str('request')->plural($waiting['total']).' waiting'.($oldest ? ' · oldest '.$oldest.'d' : ''), 'href' => '#team-waiting'];
        }
        $celebrations = DashboardKit::celebrations($this->team()->select(['employees.id', 'employees.name', 'employees.employee_code', 'employees.avatar', 'employees.date_of_birth', 'employees.joining_date']), 14);
        if ($n = $celebrations->filter(fn ($c) => $c['date']->isToday())->count()) {
            $chips[] = ['tone' => 'pink', 'icon' => 'ti-gift', 'text' => $n.' team '.str('celebration')->plural($n).' today', 'href' => '#team-celebrations'];
        }
        $holiday = $holidays->first();
        if ($holiday && $holiday['date']->lte($this->today->copy()->endOfWeek())) {
            $chips[] = ['tone' => 'pink', 'icon' => 'ti-confetti', 'text' => $holiday['name'].' '.($holiday['date']->isToday() ? 'today' : ($holiday['date']->isTomorrow() ? 'tomorrow' : 'on '.$holiday['date']->format('D'))), 'href' => '#team-holidays'];
        }

        return [
            'size' => $this->teamIds->count(),
            'today' => $today,
            'waiting' => $waiting,
            'coming' => $this->comingUp(),
            'week' => $this->week($policy),
            'celebrations' => $celebrations,
            'holidays' => $holidays,
            'pulse' => $this->pulse(),
            'low' => $this->lowBalances(),
            'chips' => array_slice($chips, 0, 3),
        ];
    }

    /** Every pending request from the team, per type, oldest first. */
    protected function waiting(): array
    {
        $types = [
            ['key' => 'leave', 'label' => 'Leave requests', 'model' => LeaveRequest::class, 'icon' => 'ti-calendar-event', 'tone' => 'violet'],
            ['key' => 'overtime', 'label' => 'Overtime', 'model' => OvertimeRequest::class, 'icon' => 'ti-alarm', 'tone' => 'pink'],
            ['key' => 'wfh', 'label' => 'Work from home', 'model' => WfhRequest::class, 'icon' => 'ti-home', 'tone' => 'sky'],
            ['key' => 'regularization', 'label' => 'Attendance corrections', 'model' => RegularizationRequest::class, 'icon' => 'ti-edit', 'tone' => 'amber'],
            ['key' => 'travel', 'label' => 'Travel', 'model' => TravelRequest::class, 'icon' => 'ti-plane', 'tone' => 'teal'],
            ['key' => 'expense', 'label' => 'Expense claims', 'model' => ExpenseClaim::class, 'icon' => 'ti-receipt-2', 'tone' => 'lime'],
        ];

        $rows = [];
        foreach ($types as $t) {
            $agg = $t['model']::query()->where('company_id', $this->companyId)->whereIn('employee_id', $this->teamIds)->where('status', 'pending')
                ->toBase()->selectRaw('COUNT(*) as total, MIN(created_at) as oldest')->first();
            if (! (int) $agg->total) {
                continue;
            }
            $oldest = $agg->oldest ? max(0, (int) Carbon::parse($agg->oldest)->startOfDay()->diffInDays($this->today)) : 0;
            $row = ['key' => $t['key'], 'label' => $t['label'], 'icon' => $t['icon'], 'tone' => $t['tone'], 'count' => (int) $agg->total, 'oldest' => $oldest,
                'severity' => $oldest > 3 ? 'danger' : ($oldest > 1 ? 'warning' : 'info'), 'children' => collect()];
            if ($t['key'] === 'leave') {
                $row['children'] = LeaveRequest::query()->where('company_id', $this->companyId)->whereIn('employee_id', $this->teamIds)->where('status', 'pending')
                    ->with(['employee:id,name,employee_code,avatar', 'leaveType:id,name,code'])
                    ->orderBy('created_at')->limit(5)->get();
                if ($row['children']->contains(fn ($r) => $r->start_date->lt($this->today))) {
                    $row['severity'] = 'danger';
                }
            }
            $rows[] = $row;
        }
        $rank = ['danger' => 0, 'warning' => 1, 'info' => 2];
        usort($rows, fn ($a, $b) => [$rank[$a['severity']], -$a['oldest']] <=> [$rank[$b['severity']], -$b['oldest']]);

        return ['rows' => $rows, 'total' => array_sum(array_column($rows, 'count'))];
    }

    /** One row per team member: today's shift, attendance and whether they're late. */
    protected function todayRows(): Collection
    {
        $now = now()->hour * 60 + now()->minute;

        return $this->team()
            ->with([
                'attendanceRecords' => fn ($q) => $q->whereDate('date', $this->today),
                'shiftRosters' => fn ($q) => $q->whereDate('date', $this->today)->with('shift:id,code,name,start_time,end_time,grace_minutes,is_night_shift'),
                'department:id,name',
            ])
            ->orderBy('name')
            ->get(['employees.id', 'employees.name', 'employees.employee_code', 'employees.avatar', 'employees.designation', 'employees.department_id'])
            ->map(function ($member) use ($now) {
                $record = $member->attendanceRecords->first();
                $shift = $member->shiftRosters->first()?->shift;
                $late = null;
                if ($record?->check_in_time && $shift) {
                    $off = ShiftTime::toMinutes($record->check_in_time) - ShiftTime::toMinutes($shift->start_time);
                    $off = (($off + 720) % 1440 + 1440) % 1440 - 720;
                    $late = $off > (int) $shift->grace_minutes ? $off : null;
                }
                $status = $record?->status;
                $state = in_array($status, ['present', 'half_day', 'wfh'], true) ? 'in' : (in_array($status, ['absent', 'on_leave', 'holiday', 'weekend'], true) ? 'away' : 'unmarked');

                return ['member' => $member, 'record' => $record, 'shift' => $shift, 'late' => $late, 'state' => $state,
                    'overdue' => ! $record && $shift && ! ShiftTime::crossesMidnight($shift->start_time, $shift->end_time) && $now > ShiftTime::toMinutes($shift->start_time) + (int) $shift->grace_minutes];
            });
    }

    /** What's due for the team in the next 30 days (overdue first). */
    protected function comingUp(): Collection
    {
        $until = $this->today->copy()->addDays(30);
        $rows = collect();

        Onboarding::reviewsDueQuery($until)
            ->whereHas('employee', fn ($e) => $e->where('manager_id', $this->manager->id)->whereNotIn('status', DashboardKit::INACTIVE))
            ->with('employee:id,name,employee_code,avatar')->get()
            ->each(fn ($r) => $rows->push(['date' => Onboarding::probationDueDate($r), 'employee' => $r->employee, 'title' => 'Probation review', 'icon' => 'ti-shield-check', 'tone' => 'sky', 'model' => $r, 'type' => 'probation']));

        Appraisal::query()->where('company_id', $this->companyId)->whereIn('employee_id', $this->teamIds)
            ->whereNull('manager_review_submitted_at')->whereNotIn('status', ['completed'])
            ->with(['employee:id,name,employee_code,avatar', 'appraisalCycle:id,period_end'])->get()
            ->each(fn ($a) => $rows->push(['date' => $a->appraisalCycle?->period_end ?? $this->today, 'employee' => $a->employee,
                'title' => $a->self_review_submitted_at ? 'Self-review in · your review is due' : 'Appraisal · waiting for self-review',
                'icon' => 'ti-target', 'tone' => 'violet', 'link' => route('admin.team-performance.index'), 'type' => 'appraisal']));

        Onboarding::taskQuery()->where('assigned_to_user_id', $this->user->id)->where('is_completed', false)
            ->with('employee:id,name,employee_code,avatar')->get()
            ->each(fn ($t) => $rows->push(['date' => $t->due_date ?? $this->today, 'employee' => $t->employee, 'title' => $t->title, 'icon' => Onboarding::meta($t->category)['icon'],
                'tone' => Onboarding::meta($t->category)['tone'], 'model' => $t, 'type' => 'task']));

        $this->team()->whereBetween('joining_date', [$this->today->toDateString(), $until->toDateString()])->get(['id', 'name', 'employee_code', 'avatar', 'joining_date'])
            ->each(fn ($e) => $rows->push(['date' => $e->joining_date, 'employee' => $e, 'title' => 'Joins the team', 'icon' => 'ti-user-plus', 'tone' => 'teal', 'type' => 'joiner']));

        Resignation::query()->where('company_id', $this->companyId)->whereIn('employee_id', $this->teamIds)->whereIn('status', ['pending', 'accepted'])
            ->whereDate('last_working_day', '<=', $until)->with('employee:id,name,employee_code,avatar')->get()
            ->each(fn ($r) => $rows->push(['date' => $r->last_working_day, 'employee' => $r->employee, 'title' => 'Last working day'.($r->status === 'pending' ? ' · pending HR review' : ''), 'icon' => 'ti-logout', 'tone' => 'rose', 'type' => 'exit']));

        return $rows->filter(fn ($r) => $r['date'])
            ->map(function ($r) {
                $r['overdue'] = $r['date']->lt($this->today) && $r['type'] !== 'joiner';
                $r['days'] = (int) $this->today->diffInDays($r['date'], false);

                return $r;
            })
            ->sortBy(fn ($r) => [$r['overdue'] ? 0 : 1, $r['date']->timestamp])->values()->take(8);
    }

    protected function week(LeavePolicy $policy): array
    {
        $start = $this->today->copy()->startOfWeek();
        $end = $start->copy()->addDays(6);
        $members = $this->team()->orderBy('name')->get(['employees.id', 'employees.name', 'employees.employee_code', 'employees.avatar']);
        $leave = DashboardKit::leaveBetween($this->team(), $start, $end);
        $rosters = ShiftRoster::query()->where('company_id', $this->companyId)->whereIn('employee_id', $this->teamIds)
            ->whereDate('date', '>=', $start)->whereDate('date', '<=', $end)->with('shift:id,code,name,start_time,end_time,is_night_shift')->get()
            ->keyBy(fn ($r) => $r->employee_id.'|'.$r->date->toDateString());

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = ['date' => $d->copy(), 'holiday' => $policy->holidayOn($d), 'off' => $policy->isWeekend($d)];
        }

        $grid = $members->map(fn ($m) => [
            'member' => $m,
            'cells' => collect($days)->map(function ($day) use ($m, $leave, $rosters) {
                $key = $day['date']->toDateString();
                $l = $leave->first(fn ($x) => $x->employee_id === $m->id && $x->start_date->lte($day['date']) && $x->end_date->gte($day['date']));

                return ['leave' => $l, 'shift' => $rosters->get($m->id.'|'.$key)?->shift];
            }),
        ]);

        return ['days' => $days, 'grid' => $grid, 'out_today' => $leave->where('status', 'approved')->filter(fn ($l) => $l->start_date->lte($this->today) && $l->end_date->gte($this->today))->unique('employee_id')->count()];
    }

    protected function pulse(): array
    {
        $monthStart = $this->today->copy()->startOfMonth();
        $counts = AttendanceRecord::query()->where('company_id', $this->companyId)->whereIn('employee_id', $this->teamIds)
            ->whereDate('date', '>=', $monthStart)->whereDate('date', '<=', $this->today)
            ->toBase()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return [
            'on_leave' => (int) $this->team()->where('status', 'on_leave')->count(),
            'joining' => (int) Employee::query()->where('company_id', $this->companyId)->where('manager_id', $this->manager->id)->whereNotIn('status', DashboardKit::INACTIVE)->whereDate('joining_date', '>', $this->today)->count(),
            'attendance' => DashboardKit::attendanceRate($counts),
            'absences' => (int) $counts->get('absent', 0),
            'overtime' => (float) OvertimeRequest::query()->where('company_id', $this->companyId)->whereIn('employee_id', $this->teamIds)->where('status', 'approved')
                ->whereDate('date', '>=', $monthStart)->whereDate('date', '<=', $this->today->copy()->endOfMonth())->sum('hours'),
        ];
    }

    protected function lowBalances(): Collection
    {
        return LeaveBalance::query()->where('company_id', $this->companyId)->whereIn('employee_id', $this->teamIds)->where('year', $this->today->year)
            ->with(['leaveType:id,name,code', 'employee:id,name,employee_code,avatar'])->get()
            ->map(fn ($b) => ['employee' => $b->employee, 'type' => $b->leaveType,
                'left' => (float) $b->allocated + (float) $b->carried_forward + (float) $b->adjusted - (float) $b->used - (float) $b->encashed,
                'entitled' => (float) $b->allocated + (float) $b->carried_forward + (float) $b->adjusted])
            ->filter(fn ($r) => $r['type'] && $r['entitled'] > 0 && $r['left'] <= 2 && stripos((string) $r['type']->code, 'COMP') === false)
            ->sortBy('left')->values()->take(5);
    }
}
