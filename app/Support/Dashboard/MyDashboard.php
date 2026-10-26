<?php

namespace App\Support\Dashboard;

use App\Models\Appraisal;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\ExpenseClaim;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Payslip;
use App\Models\ShiftRoster;
use App\Models\TrainingEnrollment;
use App\Models\TravelRequest;
use App\Support\LeavePolicy;
use App\Support\Onboarding;
use App\Support\Overtime;
use App\Support\ShiftTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * An employee's own home page: today, to-dos, leave, attendance, pay and
 * their team. Only ever reads the signed-in employee's own records (plus
 * names and dates of teammates, never their leave reasons or types).
 */
class MyDashboard
{
    protected Carbon $today;

    protected LeavePolicy $policy;

    public function __construct(protected Employee $employee)
    {
        $this->today = today();
        $this->policy = LeavePolicy::for($employee->company_id, $employee->branch_id);
    }

    public function build(): array
    {
        return [
            'profile' => $this->profile(),
            'day' => $this->myDay(),
            'todos' => $this->todos(),
            'leave' => $this->leave(),
            'month' => $this->month(),
            'holidays' => DashboardKit::upcomingHolidays($this->policy, 4),
            'pay' => $this->pay(),
            'team' => $this->team(),
        ];
    }

    protected function profile(): array
    {
        $e = $this->employee->loadCount(['documents', 'familyMembers', 'educations'])->load(['manager:id,name,employee_code,avatar,designation', 'department:id,name', 'branch:id,name']);
        $fields = ['phone', 'date_of_birth', 'gender', 'address', 'city', 'postal_code'];
        $missing = collect($fields)->filter(fn ($f) => blank($e->{$f}))->values();
        $done = count($fields) - $missing->count() + ($e->documents_count ? 1 : 0) + ($e->family_members_count ? 1 : 0) + ($e->educations_count ? 1 : 0);
        foreach (['documents_count' => 'documents', 'family_members_count' => 'family details', 'educations_count' => 'education'] as $count => $label) {
            if (! $e->{$count}) {
                $missing->push($label);
            }
        }

        $anniversary = $e->joining_date && $e->joining_date->lte($this->today) ? DashboardKit::nextOccurrence($e->joining_date) : null;

        return [
            'employee' => $e,
            'completeness' => (int) round($done / 9 * 100),
            'missing' => $missing->map(fn ($f) => str_replace('_', ' ', $f))->all(),
            'tenure' => $e->joining_date && $e->joining_date->lte($this->today) ? $e->joining_date->diffForHumans($this->today, ['syntax' => Carbon::DIFF_ABSOLUTE, 'parts' => 2]) : null,
            'next_anniversary' => $anniversary,
            'birthday_today' => $e->date_of_birth && $e->date_of_birth->format('m-d') === $this->today->format('m-d'),
            'anniversary_years' => $anniversary && $anniversary->isToday() ? $this->today->year - $e->joining_date->year : 0,
        ];
    }

    protected function myDay(): array
    {
        $rosters = ShiftRoster::query()->where('employee_id', $this->employee->id)
            ->whereDate('date', '>=', $this->today)->whereDate('date', '<=', $this->today->copy()->addDays(6))
            ->with('shift')->get()->keyBy(fn ($r) => $r->date->toDateString());
        $record = AttendanceRecord::query()->where('employee_id', $this->employee->id)->whereDate('date', $this->today)->first();
        $shift = $rosters->get($this->today->toDateString())?->shift;
        $late = null;
        if ($record?->check_in_time && $shift) {
            $off = ShiftTime::toMinutes($record->check_in_time) - ShiftTime::toMinutes($shift->start_time);
            $off = (($off + 720) % 1440 + 1440) % 1440 - 720;
            $late = $off > (int) $shift->grace_minutes ? $off : null;
        }
        $yesterday = AttendanceRecord::query()->where('employee_id', $this->employee->id)->whereDate('date', $this->today->copy()->subDay())->first();

        $next = collect(range(1, 6))->map(function ($i) use ($rosters) {
            $d = $this->today->copy()->addDays($i);

            return ['date' => $d, 'shift' => $rosters->get($d->toDateString())?->shift, 'holiday' => $this->policy->holidayOn($d), 'off' => $this->policy->isWeekend($d)];
        });

        return [
            'shift' => $shift,
            'record' => $record,
            'worked' => Overtime::worked($record),
            'late' => $late,
            'holiday' => $this->policy->holidayOn($this->today) ?? ($this->policy->isWeekend($this->today) ? 'Weekly off' : null),
            'missed_punch_out' => $yesterday && $yesterday->check_in_time && ! $yesterday->check_out_time,
            'next' => $next,
        ];
    }

    protected function todos(): Collection
    {
        $id = $this->employee->id;
        $rows = collect();

        Appraisal::query()->where('employee_id', $id)->whereNull('self_review_submitted_at')->whereNotIn('status', ['completed'])
            ->with('appraisalCycle:id,name,period_end')->get()
            ->each(fn ($a) => $rows->push(['icon' => 'ti-target', 'tone' => 'violet', 'title' => 'Submit your self-review'.($a->appraisalCycle ? ' · '.$a->appraisalCycle->name : ''),
                'date' => $a->appraisalCycle?->period_end, 'route' => 'admin.my-performance.index']));

        TrainingEnrollment::query()->where('employee_id', $id)->whereNotIn('status', ['completed', 'dropped', 'cancelled'])
            ->with('trainingSession.course:id,title')->get()
            ->each(fn ($t) => $rows->push(['icon' => 'ti-school', 'tone' => 'teal', 'title' => 'Training: '.($t->trainingSession?->course?->title ?? 'session'),
                'date' => $t->trainingSession?->end_date, 'route' => 'admin.my-training.index']));

        $this->employee->documents()->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', $this->today->copy()->addDays(60))->get()
            ->each(fn ($d) => $rows->push(['icon' => 'ti-file-alert', 'tone' => 'amber', 'title' => $d->name.' '.($d->expiry_date->lt($this->today) ? 'expired' : 'expires soon'),
                'date' => $d->expiry_date, 'route' => 'admin.my-documents.index']));

        $tasks = $this->employee->onboardingTasks()->get();
        if ($tasks->isNotEmpty() && $tasks->where('is_completed', false)->isNotEmpty()) {
            $rows->push(['icon' => 'ti-list-check', 'tone' => 'sky', 'title' => 'Onboarding checklist · '.$tasks->where('is_completed', true)->count().' of '.$tasks->count().' done',
                'date' => $tasks->where('is_completed', false)->min('due_date'), 'route' => 'admin.my-profile.index', 'progress' => (int) round($tasks->where('is_completed', true)->count() / $tasks->count() * 100)]);
        }

        $probation = $this->employee->probationRecords()->whereIn('status', ['on_probation', 'extended'])->latest('start_date')->first();
        if ($probation) {
            $due = Onboarding::probationDueDate($probation);
            $rows->push(['icon' => 'ti-shield-check', 'tone' => 'sky', 'title' => 'Probation review', 'date' => $due, 'route' => 'admin.my-profile.index',
                'progress' => min(100, max(0, (int) round($probation->start_date->diffInDays($this->today) / max(1, $probation->start_date->diffInDays($due)) * 100)))]);
        }

        return $rows->sortBy(fn ($r) => $r['date']?->timestamp ?? PHP_INT_MAX)->values();
    }

    protected function leave(): array
    {
        $year = $this->today->year;
        $balances = $this->employee->leaveBalances()->where('year', $year)->whereHas('leaveType', fn ($q) => $q->where('is_active', true))->with('leaveType:id,name,code')->get();
        $pending = LeaveRequest::query()->where('employee_id', $this->employee->id)->where('status', 'pending')->whereYear('start_date', $year)
            ->toBase()->selectRaw('leave_type_id, SUM(days) as d')->groupBy('leave_type_id')->pluck('d', 'leave_type_id');

        return [
            'balances' => $balances->map(function ($b) use ($pending) {
                $entitled = (float) $b->allocated + (float) $b->carried_forward + (float) $b->adjusted;
                $used = (float) $b->used + (float) $b->encashed;
                $held = (float) ($pending[$b->leave_type_id] ?? 0);

                return ['type' => $b->leaveType, 'entitled' => $entitled, 'used' => $used, 'pending' => $held, 'left' => max(0, $entitled - $used)];
            })->sortBy(fn ($r) => $r['type']?->name)->values(),
            'upcoming' => LeaveRequest::query()->where('employee_id', $this->employee->id)->whereIn('status', ['pending', 'approved'])
                ->whereDate('end_date', '>=', $this->today)->with('leaveType:id,name,code')->orderBy('start_date')->limit(4)->get(),
        ];
    }

    protected function month(): array
    {
        $start = $this->today->copy()->startOfMonth();
        $records = AttendanceRecord::query()->where('employee_id', $this->employee->id)
            ->whereDate('date', '>=', $start)->whereDate('date', '<=', $start->copy()->endOfMonth())->get()
            ->keyBy(fn ($r) => $r->date->toDateString());

        $days = collect(range(0, $start->daysInMonth - 1))->map(function ($i) use ($start, $records) {
            $d = $start->copy()->addDays($i);

            return ['date' => $d, 'record' => $records->get($d->toDateString()), 'holiday' => $this->policy->holidayOn($d), 'off' => $this->policy->isWeekend($d)];
        });

        return ['days' => $days, 'counts' => $records->countBy('status'), 'rate' => DashboardKit::attendanceRate($records->countBy('status'))];
    }

    protected function pay(): array
    {
        $id = $this->employee->id;
        $payslip = Payslip::query()->where('employee_id', $id)->with('payrollRun:id,month,year,status')
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
            ->orderByDesc('payroll_runs.year')->orderByDesc('payroll_runs.month')
            ->select('payslips.*')->first();

        $requests = collect();
        foreach ([
            [\App\Models\RegularizationRequest::class, 'Attendance correction', 'ti-edit', 'admin.my-requests.index', ['type' => 'regularization']],
            [\App\Models\WfhRequest::class, 'Work from home', 'ti-home', 'admin.my-requests.index', ['type' => 'wfh']],
            [OvertimeRequest::class, 'Overtime', 'ti-alarm', 'admin.my-requests.index', ['type' => 'overtime']],
            [ExpenseClaim::class, 'Expense claim', 'ti-receipt-2', 'admin.my-expenses.index', []],
            [TravelRequest::class, 'Travel', 'ti-plane', 'admin.my-travel.index', []],
            [LeaveRequest::class, 'Leave', 'ti-calendar-event', 'admin.my-leave.index', []],
        ] as [$model, $label, $icon, $route, $params]) {
            $model::query()->where('employee_id', $id)->latest()->limit(3)->get(['id', 'status', 'created_at'])
                ->each(fn ($r) => $requests->push(['label' => $label, 'icon' => $icon, 'status' => $r->status, 'at' => $r->created_at, 'route' => $route, 'params' => $params]));
        }

        $monthStart = $this->today->copy()->startOfMonth();
        $ot = OvertimeRequest::query()->where('employee_id', $id)->whereDate('date', '>=', $monthStart)->whereDate('date', '<=', $this->today->copy()->endOfMonth())
            ->toBase()->selectRaw("SUM(CASE WHEN status = 'approved' THEN hours ELSE 0 END) as approved, SUM(CASE WHEN status = 'pending' THEN hours ELSE 0 END) as pending")->first();

        return [
            'payslip' => $payslip,
            'requests' => $requests->sortByDesc(fn ($r) => $r['at']?->timestamp)->take(5)->values(),
            'ot' => ['approved' => (float) $ot->approved, 'pending' => (float) $ot->pending],
        ];
    }

    protected function team(): array
    {
        $e = $this->employee;
        $active = fn ($q) => $q->where('company_id', $e->company_id)->whereNotIn('status', DashboardKit::INACTIVE)->whereKeyNot($e->id);

        $teammates = $e->manager_id
            ? Employee::query()->where($active)->where('manager_id', $e->manager_id)->orderBy('name')->limit(8)->get(['id', 'name', 'employee_code', 'avatar', 'designation'])
            : collect();

        // Colleagues for "away this week" and celebrations: same department, else same manager.
        $circle = Employee::query()->where($active)->when(
            $e->department_id,
            fn ($q) => $q->where('department_id', $e->department_id),
            fn ($q) => $e->manager_id ? $q->where('manager_id', $e->manager_id) : $q->whereRaw('1 = 0')
        );
        $weekStart = $this->today->copy()->startOfWeek();
        $away = LeaveRequest::query()->whereIn('employee_id', (clone $circle)->select('id'))->where('status', 'approved')
            ->whereDate('start_date', '<=', $weekStart->copy()->addDays(6))->whereDate('end_date', '>=', $weekStart)
            ->with('employee:id,name,employee_code,avatar')->get(['id', 'employee_id', 'start_date', 'end_date']);

        return [
            'manager' => $e->manager,
            'teammates' => $teammates,
            'away' => $away,
            'celebrations' => DashboardKit::celebrations((clone $circle)->select(['id', 'name', 'employee_code', 'avatar', 'date_of_birth', 'joining_date']), 14),
            'new_faces' => (clone $circle)->whereDate('joining_date', '>=', $this->today->copy()->subDays(30))->whereDate('joining_date', '<=', $this->today)->get(['id', 'name', 'employee_code', 'avatar', 'designation', 'joining_date']),
            'assets' => EmployeeAsset::query()->where('employee_id', $e->id)->whereNull('returned_date')->count(),
        ];
    }
}
