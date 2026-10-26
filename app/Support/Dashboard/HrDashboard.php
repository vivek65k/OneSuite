<?php

namespace App\Support\Dashboard;

use App\Models\AttendanceRecord;
use App\Models\Bonus;
use App\Models\Candidate;
use App\Models\CompOff;
use App\Models\Deduction;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\ExpenseClaim;
use App\Models\Incentive;
use App\Models\Interview;
use App\Models\JobPosition;
use App\Models\LeaveBalance;
use App\Models\LeaveEncashment;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LoanRequest;
use App\Models\OfferLetter;
use App\Models\OvertimeRequest;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PromotionRequest;
use App\Models\Resignation;
use App\Models\Shift;
use App\Models\ShiftRoster;
use App\Models\ShiftSwapRequest;
use App\Models\TransferRequest;
use App\Models\TravelExpense;
use App\Models\TravelRequest;
use App\Models\User;
use App\Support\Attendance;
use App\Support\EmploymentType;
use App\Support\LeavePolicy;
use App\Support\Onboarding;
use App\Support\ShiftTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything the HR home dashboard shows. Company and branch are resolved
 * once and every query is scoped by them; each widget is only computed
 * when the viewer has the permission for its module (null otherwise).
 */
class HrDashboard
{
    protected Carbon $today;

    protected LeavePolicy $policy;

    protected ?int $headcountNow = null;

    public function __construct(
        protected User $user,
        protected int $companyId,
        protected ?int $branchId,
    ) {
        $this->today = today();
        $this->policy = LeavePolicy::for($companyId, $branchId);
    }

    public function build(): array
    {
        $data = [
            'policy' => $this->policy,
            'kpis' => $this->kpis(),
            'inbox' => $this->inbox(),
            'dates' => $this->datesYouCantMiss(),
            'around' => $this->can('view_any_attendance_record') ? $this->whoIsAround() : null,
            'coverage' => $this->can('view_any_shift_roster') ? $this->shiftCoverage() : null,
            'week' => $this->weekPlanner(),
            'celebrations' => $this->can('view_any_employee') ? $this->celebrations() : null,
            'holidays' => DashboardKit::upcomingHolidays($this->policy, 4),
            'payroll' => $this->can('view_any_payroll_run') ? $this->payroll() : null,
            'hiring' => $this->hiring(),
            'health' => $this->dataHealth(),
            'trend' => $this->can('view_any_employee') ? $this->trend() : null,
            'departments' => $this->can('view_any_employee') ? $this->departments() : null,
            'mix' => $this->can('view_any_employee') ? $this->workforceMix() : null,
            'watch' => $this->leaveWatch(),
        ];
        $data['highlights'] = $this->highlights($data);

        return $data;
    }

    // ---------------------------------------------------------------- scopes

    protected function can(string $permission): bool
    {
        return $this->user->can($permission);
    }

    protected function people(): Builder
    {
        return Employee::query()
            ->where('employees.company_id', $this->companyId)
            ->when($this->branchId, fn ($q) => $q->where('employees.branch_id', $this->branchId));
    }

    /** Current workforce: not terminated/inactive and already joined. */
    protected function headcountQuery(): Builder
    {
        return $this->people()
            ->whereNotIn('employees.status', DashboardKit::INACTIVE)
            ->where(fn ($q) => $q->whereNull('employees.joining_date')->orWhereDate('employees.joining_date', '<=', $this->today));
    }

    protected function headcount(): int
    {
        return $this->headcountNow ??= $this->headcountQuery()->count();
    }

    /** Records of a model owned by an employee of this company (and branch). */
    protected function ownedBy(string $model): Builder
    {
        return $model::query()
            ->where('company_id', $this->companyId)
            ->when($this->branchId, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', $this->branchId)));
    }

    protected function activeEmployee(): \Closure
    {
        return fn ($e) => $e->whereNotIn('status', DashboardKit::INACTIVE)
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId));
    }

    protected function candidateScope(): \Closure
    {
        return fn ($q) => $q->where('company_id', $this->companyId)
            ->when($this->branchId, fn ($c) => $c->where('branch_id', $this->branchId));
    }

    protected function ageDays($from): int
    {
        return $from ? max(0, (int) Carbon::parse($from)->startOfDay()->diffInDays($this->today)) : 0;
    }

    // ------------------------------------------------------------------ KPIs

    protected function kpis(): array
    {
        if (! $this->can('view_any_employee')) {
            return [];
        }
        $monthStart = $this->today->copy()->startOfMonth()->toDateString();
        $todayStr = $this->today->toDateString();

        $people = $this->people()->toBase()
            ->selectRaw("SUM(CASE WHEN status NOT IN ('terminated','inactive') AND joining_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as joined_mtd", [$monthStart, $todayStr])
            ->selectRaw("SUM(CASE WHEN status NOT IN ('terminated','inactive') AND joining_date > ? AND joining_date <= ? THEN 1 ELSE 0 END) as joining_30", [$todayStr, $this->today->copy()->addDays(30)->toDateString()])
            ->selectRaw('SUM(CASE WHEN joining_date > ? AND joining_date <= ? THEN 1 ELSE 0 END) as joined_12m', [$this->today->copy()->subYear()->toDateString(), $todayStr])
            ->first();

        $exits = ['mtd' => 0, '12m' => 0, 'notice' => 0, 'rate' => null];
        if ($this->can('view_any_resignation')) {
            $row = $this->ownedBy(Resignation::class)->toBase()
                ->selectRaw("SUM(CASE WHEN status IN ('accepted','completed') AND last_working_day BETWEEN ? AND ? THEN 1 ELSE 0 END) as mtd", [$monthStart, $todayStr])
                ->selectRaw("SUM(CASE WHEN status IN ('accepted','completed') AND last_working_day > ? AND last_working_day <= ? THEN 1 ELSE 0 END) as yr", [$this->today->copy()->subYear()->toDateString(), $todayStr])
                ->selectRaw("SUM(CASE WHEN status = 'accepted' AND last_working_day >= ? THEN 1 ELSE 0 END) as notice", [$todayStr])
                ->first();
            $hcNow = $this->headcount();
            $exits12 = (int) $row->yr;
            $hcThen = $hcNow - (int) $people->joined_12m + $exits12;
            $avg = ($hcNow + $hcThen) / 2;
            $exits = ['mtd' => (int) $row->mtd, '12m' => $exits12, 'notice' => (int) $row->notice, 'rate' => $avg > 0 ? round($exits12 / $avg * 100, 1) : null];
        }

        $attendance = null;
        if ($this->can('view_any_attendance_record')) {
            $counts = Attendance::records()
                ->whereDate('date', '>=', $monthStart)->whereDate('date', '<=', $todayStr)
                ->toBase()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
            $attendance = DashboardKit::attendanceRate($counts);
        }

        return [
            'headcount' => $this->headcount(),
            'joined_mtd' => (int) $people->joined_mtd,
            'joining_30' => (int) $people->joining_30,
            'exits' => $exits,
            'attendance' => $attendance,
            'attendance_allowed' => $this->can('view_any_attendance_record'),
        ];
    }

    // ---------------------------------------------------------- action inbox

    protected function inbox(): array
    {
        $queues = [
            ['key' => 'leave', 'label' => 'Leave requests', 'model' => LeaveRequest::class, 'perm' => 'view_any_leave_request', 'icon' => 'ti-calendar-event', 'tone' => 'violet', 'route' => 'admin.leave-requests.approval', 'params' => ['sort' => 'oldest', 'per_page' => 25]],
            ['key' => 'swaps', 'label' => 'Shift swaps', 'model' => ShiftSwapRequest::class, 'perm' => 'view_any_shift_swap_request', 'icon' => 'ti-switch-horizontal', 'tone' => 'indigo', 'route' => 'admin.shift-swaps.index', 'params' => ['status' => 'pending', 'per_page' => 25]],
            ['key' => 'compoff', 'label' => 'Comp offs', 'model' => CompOff::class, 'perm' => 'view_any_comp_off', 'icon' => 'ti-repeat', 'tone' => 'teal', 'route' => 'admin.comp-offs.index', 'params' => ['status' => 'pending', 'per_page' => 25]],
            ['key' => 'encash', 'label' => 'Leave encashments', 'model' => LeaveEncashment::class, 'perm' => 'view_any_leave_encashment', 'icon' => 'ti-cash', 'tone' => 'success', 'route' => 'admin.leave-encashments.index', 'params' => ['status' => 'pending', 'per_page' => 25]],
            ['key' => 'expense', 'label' => 'Expense claims', 'model' => ExpenseClaim::class, 'perm' => 'view_any_expense_claim', 'icon' => 'ti-receipt-2', 'tone' => 'amber', 'route' => 'admin.expense-approval.index', 'params' => []],
            ['key' => 'travel', 'label' => 'Travel requests', 'model' => TravelRequest::class, 'perm' => 'view_any_travel_request', 'icon' => 'ti-plane', 'tone' => 'sky', 'route' => 'admin.travel-approval.index', 'params' => []],
            ['key' => 'travel_exp', 'label' => 'Travel expenses', 'model' => TravelExpense::class, 'perm' => 'view_any_travel_expense', 'icon' => 'ti-receipt', 'tone' => 'sky', 'route' => 'admin.travel-expenses.index', 'params' => ['status' => 'pending']],
            ['key' => 'loan', 'label' => 'Loan requests', 'model' => LoanRequest::class, 'perm' => 'view_any_loan_request', 'icon' => 'ti-cash-banknote', 'tone' => 'lime', 'route' => 'admin.loan-requests.index', 'params' => ['status' => 'pending']],
            ['key' => 'promotion', 'label' => 'Promotions', 'model' => PromotionRequest::class, 'perm' => 'view_any_promotion_request', 'icon' => 'ti-stairs-up', 'tone' => 'violet', 'route' => 'admin.promotion-requests.index', 'params' => ['status' => 'pending']],
            ['key' => 'transfer', 'label' => 'Transfers', 'model' => TransferRequest::class, 'perm' => 'view_any_transfer_request', 'icon' => 'ti-arrows-left-right', 'tone' => 'indigo', 'route' => 'admin.transfer-requests.index', 'params' => ['status' => 'pending']],
            ['key' => 'resignation', 'label' => 'Resignations', 'model' => Resignation::class, 'perm' => 'view_any_resignation', 'icon' => 'ti-logout', 'tone' => 'rose', 'route' => 'admin.resignation-approval.index', 'params' => []],
        ];

        $rows = [];
        foreach ($queues as $q) {
            if (! $this->can($q['perm'])) {
                continue;
            }
            $agg = $this->ownedBy($q['model'])->where('status', 'pending')->toBase()
                ->selectRaw('COUNT(*) as total, MIN(created_at) as oldest')->first();
            if (! (int) $agg->total) {
                continue;
            }
            $row = $q + ['count' => (int) $agg->total, 'oldest' => $this->ageDays($agg->oldest), 'meta' => null, 'severity' => 'info', 'children' => []];
            unset($row['model']);

            if ($q['key'] === 'leave') {
                $pending = $this->ownedBy(LeaveRequest::class)->where('status', 'pending');
                $extra = (clone $pending)->toBase()
                    ->selectRaw('SUM(CASE WHEN start_date < ? THEN 1 ELSE 0 END) as past', [$this->today->toDateString()])
                    ->selectRaw('SUM(CASE WHEN start_date <= ? THEN 1 ELSE 0 END) as urgent', [$this->today->copy()->addDays(7)->toDateString()])
                    ->first();
                $row['meta'] = trim(((int) $extra->past ? $extra->past.' already started · ' : '').((int) $extra->urgent ? $extra->urgent.' start within 7 days' : 'none starting this week'), ' ·');
                if ((int) $extra->urgent) {
                    $row['params'] = ['window' => 'urgent', 'per_page' => 25];
                }
                if ((int) $extra->past) {
                    $row['severity'] = 'danger';
                }
                $row['children'] = $this->leaveQueue(clone $pending);
            } elseif ($q['key'] === 'encash') {
                $sum = $this->ownedBy(LeaveEncashment::class)->where('status', 'pending')->toBase()->selectRaw('SUM(days) as d, SUM(amount) as a')->first();
                $row['meta'] = DashboardKit::days((float) $sum->d).' days · '.DashboardKit::money((float) $sum->a);
            } elseif ($q['key'] === 'expense') {
                $row['meta'] = DashboardKit::money((float) $this->ownedBy(ExpenseClaim::class)->where('status', 'pending')->sum('amount')).' claimed';
            }

            if ($row['oldest'] > 5) {
                $row['severity'] = 'danger';
            } elseif ($row['severity'] !== 'danger' && $row['oldest'] > 2) {
                $row['severity'] = 'warning';
            }
            $rows[] = $row;
        }

        // Attendance requests (regularization, WFH, overtime) share one approvals page.
        if ($this->can('view_any_regularization_request') || $this->can('view_any_wfh_request') || $this->can('view_any_overtime_request')) {
            $nav = Attendance::navCounts();
            $total = array_sum($nav);
            if ($total) {
                $oldest = collect([\App\Models\RegularizationRequest::class, \App\Models\WfhRequest::class, OvertimeRequest::class])
                    ->map(fn ($m) => Attendance::requests($m)->where('status', 'pending')->min('created_at'))
                    ->filter()->min();
                $hours = (float) Attendance::requests(OvertimeRequest::class)->where('status', 'pending')->sum('hours');
                $parts = array_filter([
                    $nav['regularization'] ? $nav['regularization'].' regularization' : null,
                    $nav['wfh'] ? $nav['wfh'].' WFH' : null,
                    $nav['overtime'] ? $nav['overtime'].' overtime ('.DashboardKit::days($hours).' h)' : null,
                ]);
                $age = $this->ageDays($oldest);
                $rows[] = ['key' => 'attendance', 'label' => 'Attendance requests', 'icon' => 'ti-clock', 'tone' => 'amber', 'route' => 'admin.attendance-approval.index', 'params' => [],
                    'count' => $total, 'oldest' => $age, 'meta' => implode(' · ', $parts), 'severity' => $age > 5 ? 'danger' : ($age > 2 ? 'warning' : 'info'), 'children' => []];
            }
        }

        if ($this->can('view_any_offer_letter')) {
            $drafts = OfferLetter::query()->where('status', 'draft')->whereHas('candidate', $this->candidateScope());
            $agg = $drafts->toBase()->selectRaw('COUNT(*) as total, MIN(created_at) as oldest')->first();
            if ((int) $agg->total) {
                $age = $this->ageDays($agg->oldest);
                $rows[] = ['key' => 'offer', 'label' => 'Offer letters to approve', 'icon' => 'ti-signature', 'tone' => 'pink', 'route' => 'admin.recruitment-approval.index', 'params' => [],
                    'count' => (int) $agg->total, 'oldest' => $age, 'meta' => 'Drafts waiting to be sent', 'severity' => $age > 5 ? 'danger' : ($age > 2 ? 'warning' : 'info'), 'children' => []];
            }
        }

        $rank = ['danger' => 0, 'warning' => 1, 'info' => 2];
        usort($rows, fn ($a, $b) => [$rank[$a['severity']], -$a['oldest']] <=> [$rank[$b['severity']], -$b['oldest']]);

        return ['rows' => $rows, 'total' => array_sum(array_column($rows, 'count'))];
    }

    /** The three most pressing pending leave requests, with balance checks. */
    protected function leaveQueue(Builder $pending): Collection
    {
        $requests = $pending
            ->with(['employee:id,name,employee_code,avatar,department_id', 'leaveType:id,name,code'])
            ->orderByRaw('CASE WHEN start_date < ? THEN 0 ELSE 1 END', [$this->today->toDateString()])
            ->orderBy('start_date')
            ->limit(3)
            ->get();

        $balances = LeaveBalance::query()
            ->whereIn('employee_id', $requests->pluck('employee_id'))
            ->whereIn('leave_type_id', $requests->pluck('leave_type_id'))
            ->get()
            ->keyBy(fn ($b) => $b->employee_id.'-'.$b->leave_type_id.'-'.$b->year);

        return $requests->map(function ($r) use ($balances) {
            $balance = $balances->get($r->employee_id.'-'.$r->leave_type_id.'-'.$r->start_date->year);
            $remaining = $balance ? (float) $balance->allocated + (float) $balance->carried_forward + (float) $balance->adjusted - (float) $balance->used - (float) $balance->encashed : null;

            return [
                'request' => $r,
                'past' => $r->start_date->lt($this->today),
                'starts_in' => max(0, (int) $this->today->diffInDays($r->start_date, false)),
                'waiting' => $this->ageDays($r->created_at),
                'balance_issue' => $remaining === null ? 'No balance' : ($remaining < (float) $r->days ? 'Exceeds balance' : null),
            ];
        });
    }

    // -------------------------------------------------- dates you can't miss

    protected function datesYouCantMiss(): array
    {
        $until = $this->today->copy()->addDays(14);
        $rows = collect();
        $sources = ['probation' => false, 'documents' => false];

        if ($this->can('view_any_probation_record')) {
            $sources['probation'] = Onboarding::probationQuery()->exists();
            Onboarding::reviewsDueQuery($until)
                ->whereHas('employee', fn ($e) => $e->whereNotIn('status', DashboardKit::INACTIVE))
                ->with('employee:id,name,employee_code,designation')
                ->get()
                ->each(function ($record) use ($rows) {
                    $due = Onboarding::probationDueDate($record);
                    $span = max(1, (int) $record->start_date->diffInDays($due));
                    $rows->push(['type' => 'probation', 'date' => $due, 'employee' => $record->employee, 'model' => $record,
                        'title' => 'Probation review', 'icon' => 'ti-shield-check', 'tone' => 'sky',
                        'progress' => min(100, max(0, round($record->start_date->diffInDays($this->today) / $span * 100))),
                        'link' => route('admin.probation-records.index', ['review' => 'overdue'])]);
                });
        }

        if ($this->can('view_any_employee_document')) {
            $docs = EmployeeDocument::query()->where('company_id', $this->companyId)->whereNotNull('expiry_date')
                ->whereHas('employee', $this->activeEmployee());
            $sources['documents'] = (clone $docs)->exists();
            (clone $docs)->whereDate('expiry_date', '<=', $until)
                ->with('employee:id,name,employee_code')
                ->orderBy('expiry_date')->limit(10)->get()
                ->each(fn ($doc) => $rows->push(['type' => 'document', 'date' => $doc->expiry_date, 'employee' => $doc->employee, 'model' => $doc,
                    'title' => (EmployeeDocument::TYPES[$doc->category] ?? $doc->name).' '.($doc->expiry_date->lt($this->today) ? 'expired' : 'expires'),
                    'icon' => 'ti-file-alert', 'tone' => 'amber',
                    'link' => route('admin.document-expiry.index', ['expiry_status' => $doc->expiry_date->lt($this->today) ? 'expired' : 'expiring_soon'])]));
        }

        if ($this->can('view_any_onboarding_task')) {
            Onboarding::taskQuery()->where('is_completed', false)->whereDate('due_date', '<', $this->today)
                ->with(['employee:id,name,employee_code', 'assignedTo:id,name'])
                ->orderBy('due_date')->limit(10)->get()
                ->each(fn ($task) => $rows->push(['type' => 'task', 'date' => $task->due_date, 'employee' => $task->employee, 'model' => $task,
                    'title' => $task->title, 'icon' => Onboarding::meta($task->category)['icon'], 'tone' => Onboarding::meta($task->category)['tone'],
                    'owner' => $task->assignedTo?->name ?? (\App\Models\OnboardingTask::ROLES[$task->assigned_role] ?? null),
                    'link' => route('admin.onboarding.overview')]));

            $this->people()->whereNotIn('status', DashboardKit::INACTIVE)
                ->whereBetween('joining_date', [$this->today->copy()->subDays(30)->toDateString(), $until->toDateString()])
                ->doesntHave('onboardingTasks')
                ->get(['id', 'name', 'employee_code', 'joining_date'])
                ->each(fn ($e) => $rows->push(['type' => 'joiner', 'date' => $e->joining_date, 'employee' => $e, 'model' => null,
                    'title' => ($e->joining_date->gt($this->today) ? 'Starts' : 'Started').' · no onboarding plan', 'icon' => 'ti-user-plus', 'tone' => 'teal',
                    'link' => route('admin.onboarding.overview'), 'upcoming_ok' => true]));
        }

        if ($this->can('view_any_resignation')) {
            $this->ownedBy(Resignation::class)->where('status', 'accepted')->whereDate('last_working_day', '<=', $until)
                ->with('employee:id,name,employee_code')
                ->withCount(['clearances', 'clearances as cleared_count' => fn ($q) => $q->where('is_cleared', true)])
                ->withExists('exitInterview')
                ->get()
                ->each(fn ($r) => $rows->push(['type' => 'exit', 'date' => $r->last_working_day, 'employee' => $r->employee, 'model' => $r,
                    'title' => $r->last_working_day->lt($this->today) ? 'Close separation' : 'Last working day', 'icon' => 'ti-logout', 'tone' => 'rose',
                    'clearance' => $r->clearances_count ? [$r->cleared_count, $r->clearances_count] : null,
                    'exit_interview' => (bool) $r->exit_interview_exists,
                    'link' => route($r->last_working_day->lt($this->today) ? 'admin.resignations.index' : 'admin.notice-period.index', $r->last_working_day->lt($this->today) ? ['status' => 'accepted'] : [])]));
        }

        $rows = $rows->map(function ($row) {
            $row['overdue'] = $row['date']->lt($this->today) && empty($row['upcoming_ok']);
            $row['days'] = (int) $this->today->diffInDays($row['date'], false);

            return $row;
        })->sortBy(fn ($r) => [$r['overdue'] ? 0 : 1, $r['date']->timestamp])->values();

        return [
            'rows' => $rows->take(8),
            'more' => max(0, $rows->count() - 8),
            'overdue' => $rows->where('overdue', true)->count(),
            'sources' => $sources,
            'can' => [
                'probation' => $this->can('view_any_probation_record'),
                'documents' => $this->can('view_any_employee_document'),
                'onboarding' => $this->can('view_any_onboarding_task'),
            ],
        ];
    }

    // ------------------------------------------------------- who's around

    protected function whoIsAround(): array
    {
        $headcount = $this->headcount();
        $counts = Attendance::records()->whereDate('date', $this->today)
            ->whereHas('employee', fn ($q) => $q->whereNotIn('status', DashboardKit::INACTIVE))
            ->toBase()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status')
            ->map(fn ($n) => (int) $n);
        $offDay = $this->policy->holidayOn($this->today) ?? ($this->policy->isWeekend($this->today) ? 'Weekly off' : null);

        $groups = [];
        if ($this->can('view_any_leave_request')) {
            $leave = DashboardKit::leaveBetween($this->headcountQuery(), $this->today, $this->today)->unique('employee_id');
            $groups['leave'] = $leave->sortBy(fn ($l) => $l->status === 'pending' ? 1 : 0)->values();
        }
        $groups['wfh'] = Attendance::records()->whereDate('date', $this->today)->where('status', 'wfh')
            ->with('employee:id,name,employee_code,avatar')->get()->pluck('employee')->filter()->values();
        if ($this->can('view_any_travel_request')) {
            $groups['travel'] = $this->ownedBy(TravelRequest::class)->where('status', 'approved')
                ->whereDate('start_date', '<=', $this->today)->whereDate('end_date', '>=', $this->today)
                ->with('employee:id,name,employee_code,avatar')->get(['id', 'employee_id', 'to_location', 'end_date']);
        }
        $groups['late'] = $offDay ? collect() : $this->lateAndNoShows();

        $missedPunchOut = Attendance::records()
            ->whereDate('date', '>=', $this->today->copy()->subDays(7))->whereDate('date', '<', $this->today)
            ->whereNotNull('check_in_time')->whereNull('check_out_time')->count();

        $marked = $counts->sum();
        $firstShift = ShiftRoster::query()->where('shift_rosters.company_id', $this->companyId)->whereDate('shift_rosters.date', $this->today)
            ->join('shifts', 'shifts.id', '=', 'shift_rosters.shift_id')->min('shifts.start_time');

        return [
            'headcount' => $headcount,
            'counts' => $counts,
            'not_marked' => max(0, $headcount - $marked),
            'in' => (int) $counts->get('present', 0) + (int) $counts->get('half_day', 0) + (int) $counts->get('wfh', 0),
            'off_day' => $offDay,
            'groups' => $groups,
            'missed_punch_out' => $missedPunchOut,
            'first_shift' => $firstShift,
            'before_first_shift' => $firstShift && now()->format('H:i:s') < $firstShift,
        ];
    }

    /** Rostered people who checked in late, or haven't checked in after their shift started. */
    protected function lateAndNoShows(): Collection
    {
        $now = now()->hour * 60 + now()->minute;

        return DB::table('shift_rosters')
            ->join('shifts', 'shifts.id', '=', 'shift_rosters.shift_id')
            ->join('employees as e', 'e.id', '=', 'shift_rosters.employee_id')
            ->leftJoin('attendance_records as ar', fn ($j) => $j->on('ar.employee_id', '=', 'shift_rosters.employee_id')->whereDate('ar.date', $this->today))
            ->where('shift_rosters.company_id', $this->companyId)
            ->whereDate('shift_rosters.date', $this->today)
            ->whereNotIn('e.status', DashboardKit::INACTIVE)
            ->when($this->branchId, fn ($q) => $q->where('e.branch_id', $this->branchId))
            ->get(['e.id', 'e.name', 'e.employee_code', 'e.avatar', 'shifts.code', 'shifts.start_time', 'shifts.end_time', 'shifts.grace_minutes', 'ar.status', 'ar.check_in_time'])
            ->map(function ($row) use ($now) {
                if (in_array($row->status, ['on_leave', 'absent', 'wfh', 'holiday', 'weekend'], true)) {
                    return null;
                }
                $start = ShiftTime::toMinutes($row->start_time);
                $grace = (int) $row->grace_minutes;
                if ($row->check_in_time) {
                    $off = ShiftTime::toMinutes($row->check_in_time) - $start;
                    $off = (($off + 720) % 1440 + 1440) % 1440 - 720;

                    return $off > $grace ? ['row' => $row, 'kind' => 'late', 'minutes' => $off] : null;
                }
                // Overnight shifts that haven't started yet aren't no-shows.
                if ($row->status === null && $now > $start + $grace) {
                    return ['row' => $row, 'kind' => 'no_show', 'minutes' => $now - $start];
                }

                return null;
            })
            ->filter()
            ->sortBy(fn ($r) => $r['kind'] === 'no_show' ? 0 : 1)
            ->values();
    }

    // ---------------------------------------------------- shift coverage

    protected function shiftCoverage(): array
    {
        $rows = DB::table('shift_rosters')
            ->join('employees as e', 'e.id', '=', 'shift_rosters.employee_id')
            ->leftJoin('attendance_records as ar', fn ($j) => $j->on('ar.employee_id', '=', 'shift_rosters.employee_id')->whereDate('ar.date', $this->today))
            ->where('shift_rosters.company_id', $this->companyId)
            ->whereDate('shift_rosters.date', $this->today)
            ->whereNotIn('e.status', DashboardKit::INACTIVE)
            ->when($this->branchId, fn ($q) => $q->where('e.branch_id', $this->branchId))
            ->groupBy('shift_rosters.shift_id')
            ->selectRaw('shift_rosters.shift_id, COUNT(*) as rostered, COUNT(ar.check_in_time) as checked_in')
            ->get()->keyBy('shift_id');

        $shifts = Shift::whereIn('id', $rows->keys())->orderBy('start_time')->get();
        $now = now()->hour * 60 + now()->minute;
        $rostered = (int) $rows->sum('rostered');

        $nights = Shift::query()->where('company_id', $this->companyId)->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->get()->filter(fn ($s) => $s->is_night_shift || ShiftTime::crossesMidnight($s->start_time, $s->end_time))->pluck('id');
        $tonight = $nights->isEmpty() ? null : ShiftRoster::query()->whereIn('shift_id', $nights)->whereDate('date', $this->today)
            ->whereHas('employee', $this->activeEmployee())->count();
        $streak = \App\Http\Controllers\Admin\Hrm\ShiftRosterController::LONG_NIGHT_STREAK;
        $longStreaks = $nights->isEmpty() ? 0 : ShiftRoster::query()->whereIn('shift_id', $nights)
            ->whereDate('date', '>', $this->today->copy()->subDays($streak))->whereDate('date', '<=', $this->today)
            ->whereHas('employee', $this->activeEmployee())
            ->toBase()->selectRaw('employee_id, COUNT(DISTINCT DATE(date)) as nights')->groupBy('employee_id')
            ->get()->where('nights', '>=', $streak)->count();

        $onLeaveToday = (int) Attendance::records()->whereDate('date', $this->today)->where('status', 'on_leave')->count();

        return [
            'has_shifts' => Shift::where('company_id', $this->companyId)->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->exists(),
            'rows' => $shifts->map(fn ($s) => [
                'shift' => $s,
                'style' => ShiftTime::style($s),
                'rostered' => (int) $rows[$s->id]->rostered,
                'in' => (int) $rows[$s->id]->checked_in,
                'started' => ! ShiftTime::crossesMidnight($s->start_time, $s->end_time) && $now >= ShiftTime::toMinutes($s->start_time),
            ]),
            'unrostered' => max(0, $this->headcount() - $rostered - $onLeaveToday),
            'tonight' => $tonight,
            'long_streaks' => $longStreaks,
        ];
    }

    // ------------------------------------------------------- week planner

    protected function weekPlanner(): array
    {
        $start = $this->today->copy()->startOfWeek();
        $end = $start->copy()->addDays(6);
        $leave = $this->can('view_any_leave_request') ? DashboardKit::leaveBetween($this->headcountQuery(), $start, $end) : collect();

        $rostered = $this->can('view_any_shift_roster')
            ? ShiftRoster::query()->where('company_id', $this->companyId)->whereDate('date', '>=', $start)->whereDate('date', '<=', $end)
                ->whereHas('employee', $this->activeEmployee())
                ->toBase()->selectRaw('DATE(date) as day, COUNT(*) as n')->groupByRaw('DATE(date)')->pluck('n', 'day')
            : null;

        $marks = [];
        if ($this->can('view_any_employee')) {
            foreach (DashboardKit::celebrations($this->headcountQuery()->select(['id', 'name', 'employee_code', 'date_of_birth', 'joining_date']), 7) as $c) {
                if ($c['date']->between($start, $end)) {
                    $marks[$c['date']->toDateString()][] = ['icon' => $c['kind'] === 'birthday' ? 'ti-gift' : 'ti-award', 'tone' => $c['kind'] === 'birthday' ? 'pink' : 'amber', 'title' => $c['employee']->name.' · '.($c['kind'] === 'birthday' ? 'birthday' : $c['years'].' yr anniversary')];
                }
            }
            $this->people()->whereNotIn('status', DashboardKit::INACTIVE)->whereDate('joining_date', '>=', $start)->whereDate('joining_date', '<=', $end)
                ->get(['name', 'joining_date'])
                ->each(function ($e) use (&$marks) {
                    $marks[$e->joining_date->toDateString()][] = ['icon' => 'ti-user-plus', 'tone' => 'teal', 'title' => $e->name.' joins'];
                });
        }
        if ($this->can('view_any_resignation')) {
            $this->ownedBy(Resignation::class)->where('status', 'accepted')->whereDate('last_working_day', '>=', $start)->whereDate('last_working_day', '<=', $end)
                ->with('employee:id,name')->get()
                ->each(function ($r) use (&$marks) {
                    $marks[$r->last_working_day->toDateString()][] = ['icon' => 'ti-logout', 'tone' => 'rose', 'title' => $r->employee?->name.' · last day'];
                });
        }

        $days = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $people = $leave->filter(fn ($l) => $l->start_date->lte($day) && $l->end_date->gte($day))->unique('employee_id')->values();
            $holiday = $this->policy->holidayOn($day);
            $off = $this->policy->isWeekend($day);
            $days[] = [
                'date' => $day->copy(), 'holiday' => $holiday, 'off' => $off,
                'people' => $people, 'rostered' => $rostered ? (int) ($rostered[$key] ?? 0) : null,
                'marks' => $marks[$key] ?? [],
            ];
        }

        $nextWeek = null;
        if ($this->can('view_any_shift_roster')) {
            $nextStart = $start->copy()->addWeek();
            $covered = ShiftRoster::query()->where('company_id', $this->companyId)
                ->whereDate('date', '>=', $nextStart)->whereDate('date', '<=', $nextStart->copy()->addDays(6))
                ->whereHas('employee', $this->activeEmployee())->distinct()->count('employee_id');
            $nextWeek = ['start' => $nextStart, 'unscheduled' => max(0, $this->headcount() - $covered)];
        }

        return ['days' => $days, 'away' => $leave->where('status', 'approved')->unique('employee_id')->count(), 'next_week' => $nextWeek, 'show_leave' => $this->can('view_any_leave_request')];
    }

    // ------------------------------------------------------- celebrations

    protected function celebrations(): array
    {
        $base = $this->headcountQuery()->with('department:id,name')
            ->select(['employees.id', 'employees.name', 'employees.employee_code', 'employees.avatar', 'employees.department_id', 'employees.date_of_birth', 'employees.joining_date']);

        return [
            'items' => DashboardKit::celebrations($base, 14),
            'joiners' => $this->headcountQuery()->with('department:id,name')
                ->whereDate('joining_date', '>=', $this->today->copy()->subDays(30))
                ->orderByDesc('joining_date')->limit(3)
                ->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'designation', 'joining_date']),
            'no_dob' => $this->headcountQuery()->whereNull('date_of_birth')->count(),
        ];
    }

    // ------------------------------------------------------------ payroll

    protected function payroll(): array
    {
        $runs = PayrollRun::query()->where('company_id', $this->companyId)
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId), fn ($q) => $q->whereNull('branch_id'))
            ->orderByDesc('year')->orderByDesc('month')->limit(2)->get();
        $current = $runs->first(fn ($r) => (int) $r->month === $this->today->month && (int) $r->year === $this->today->year);
        $last = $runs->first();
        $previous = $runs->get(1);

        $cost = $last ? (float) Payslip::where('payroll_run_id', $last->id)->sum(DB::raw('gross_earnings + COALESCE(pf_employer_amount,0) + COALESCE(esi_employer_amount,0)')) : 0.0;

        $pendingInputs = 0;
        foreach ([Bonus::class, Incentive::class, Deduction::class] as $model) {
            $pendingInputs += $this->ownedBy($model)->where('status', 'pending')
                ->where('period_month', $this->today->month)->where('period_year', $this->today->year)->count();
        }

        $structures = null;
        if ($this->can('view_any_salary_structure')) {
            $missing = $this->headcountQuery()->whereDoesntHave('salaryStructures', fn ($q) => $q->where('is_current', true)->has('items'));
            $structures = ['missing' => (clone $missing)->count(), 'total' => $this->headcount(), 'names' => (clone $missing)->limit(3)->get(['id', 'name'])];
        }

        return [
            'current' => $current,
            'last' => $last,
            'previous' => $previous,
            'cost' => $cost,
            'change' => $last && $previous && (float) $previous->total_gross > 0 ? round(((float) $last->total_gross - (float) $previous->total_gross) / (float) $previous->total_gross * 100, 1) : null,
            'days_left' => max(0, (int) $this->today->diffInDays($this->today->copy()->endOfMonth())),
            'pending_inputs' => $pendingInputs,
            'structures' => $structures,
        ];
    }

    // ------------------------------------------------------------- hiring

    protected function hiring(): ?array
    {
        $out = [];
        $cand = $this->candidateScope();

        if ($this->can('view_any_interview')) {
            $live = Interview::query()->whereIn('status', ['scheduled', 'rescheduled'])->whereHas('candidate', $cand);
            $agg = (clone $live)->toBase()
                ->selectRaw('SUM(CASE WHEN DATE(scheduled_at) = ? THEN 1 ELSE 0 END) as today', [$this->today->toDateString()])
                ->selectRaw('SUM(CASE WHEN DATE(scheduled_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) as week', [$this->today->toDateString(), $this->today->copy()->endOfWeek()->toDateString()])
                ->selectRaw('SUM(CASE WHEN scheduled_at < ? THEN 1 ELSE 0 END) as no_outcome', [$this->today->toDateTimeString()])
                ->first();
            $out['interviews_today'] = (int) $agg->today;
            $out['interviews_week'] = (int) $agg->week;
            $out['upcoming'] = (clone $live)->whereBetween('scheduled_at', [now()->startOfDay(), $this->today->copy()->addDay()->endOfDay()])
                ->with(['candidate:id,name,job_position_id', 'candidate.jobPosition:id,title'])->orderBy('scheduled_at')->limit(4)->get();
            $out['actions'][] = ['count' => (int) $agg->no_outcome, 'label' => 'Interviews with no outcome', 'icon' => 'ti-calendar-time', 'route' => 'admin.interviews.index', 'params' => ['when' => 'past', 'status' => 'scheduled']];
            $feedback = Interview::query()->where('status', 'completed')->doesntHave('feedback')->whereHas('candidate', $cand)->count();
            if ($this->user->can('create', \App\Models\InterviewFeedback::class)) {
                $out['actions'][] = ['count' => $feedback, 'label' => 'Interviews waiting for feedback', 'icon' => 'ti-message-report', 'route' => 'admin.interview-feedback.index', 'params' => []];
            }
        }
        if ($this->can('view_any_offer_letter')) {
            $offers = OfferLetter::query()->whereHas('candidate', $cand);
            $out['offers_pending'] = (clone $offers)->whereIn('status', ['draft', 'sent'])->count();
            $out['actions'][] = ['count' => (clone $offers)->where('status', 'sent')->whereDate('offer_date', '<=', $this->today->copy()->subDays(7))->count(),
                'label' => 'Offers sent 7+ days ago, no reply', 'icon' => 'ti-mail-forward', 'route' => 'admin.offer-letters.index', 'params' => ['status' => 'sent']];
        }
        if ($this->can('view_any_job_position')) {
            $jobs = JobPosition::query()->where('company_id', $this->companyId)->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->where('status', 'open');
            $out['open_roles'] = (clone $jobs)->count();
            $out['openings'] = (int) (clone $jobs)->sum('headcount');
            $out['actions'][] = ['count' => (clone $jobs)->doesntHave('candidates')->count(), 'label' => 'Open roles with no applicants', 'icon' => 'ti-alert-triangle', 'route' => 'admin.job-positions.open', 'params' => []];
        }
        if ($this->can('view_any_candidate')) {
            $out['pipeline'] = Candidate::query()->where($cand)->whereNotIn('stage', ['hired', 'rejected'])->count();
        }
        if ($this->can('view_any_onboarding_task')) {
            $agg = Onboarding::taskQuery()->where('is_completed', false)->toBase()
                ->selectRaw('COUNT(*) as open, SUM(CASE WHEN due_date < ? THEN 1 ELSE 0 END) as overdue', [$this->today->toDateString()])->first();
            $out['onboarding'] = ['open' => (int) $agg->open, 'overdue' => (int) $agg->overdue];
        }

        if (! $out) {
            return null;
        }
        $out['actions'] = collect($out['actions'] ?? [])->filter(fn ($a) => $a['count'] > 0)->values()->all();

        return $out;
    }

    // -------------------------------------------------------- data health

    protected function dataHealth(): array
    {
        $out = [];
        if ($this->can('view_any_leave_type')) {
            $year = $this->today->year;
            $steps = [
                ['label' => $this->policy->saved ? 'Leave policy saved' : 'Leave policy not saved · counting calendar days', 'done' => $this->policy->saved, 'route' => 'admin.leave-policies.index', 'params' => []],
                ['label' => ($types = LeaveType::where('company_id', $this->companyId)->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))->where('is_active', true)->count()).' active leave types', 'done' => $types > 0, 'route' => 'admin.leave-types.index', 'params' => []],
                ['label' => ($missing = $this->headcountQuery()->whereDoesntHave('leaveBalances', fn ($q) => $q->where('year', $year))->count()) ? $missing.' people without a '.$year.' leave balance' : 'Everyone has a '.$year.' balance', 'done' => ! $missing, 'route' => 'admin.leave-balances.index', 'params' => ['year' => $year]],
                ['label' => ($holidays = $this->policy->holidays->filter(fn ($h) => $h->date->year === $year || ($h->is_recurring && $h->date->year <= $year))->count()).' holidays in '.$year, 'done' => $holidays > 0, 'route' => 'admin.holidays.index', 'params' => ['year' => $year]],
            ];
            $out['leave'] = ['steps' => $steps, 'done' => collect($steps)->where('done', true)->count()];
        }

        if ($this->can('view_any_employee')) {
            $agg = $this->headcountQuery()->toBase()
                ->selectRaw('SUM(CASE WHEN department_id IS NULL THEN 1 ELSE 0 END) as no_department')
                ->selectRaw("SUM(CASE WHEN designation IS NULL OR designation = '' THEN 1 ELSE 0 END) as no_designation")
                ->selectRaw('SUM(CASE WHEN date_of_birth IS NULL THEN 1 ELSE 0 END) as no_dob')
                ->selectRaw('SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) as no_login')
                ->first();
            $orphans = $this->headcountQuery()->whereNull('manager_id')
                ->whereDoesntHave('directReports', fn ($q) => $q->whereNotIn('status', DashboardKit::INACTIVE))->count();
            $noProbation = $this->can('view_any_probation_record')
                ? $this->headcountQuery()->whereDate('joining_date', '>=', $this->today->copy()->subDays(90))->doesntHave('probationRecords')->count() : 0;

            $out['records'] = collect([
                ['key' => 'no_dob', 'count' => (int) $agg->no_dob, 'label' => 'No date of birth', 'icon' => 'ti-calendar-event'],
                ['key' => 'orphans', 'count' => $orphans, 'label' => 'No reporting manager', 'icon' => 'ti-hierarchy'],
                ['key' => 'no_login', 'count' => (int) $agg->no_login, 'label' => 'No login for self-service', 'icon' => 'ti-user-off'],
                ['key' => 'no_department', 'count' => (int) $agg->no_department, 'label' => 'No department', 'icon' => 'ti-sitemap'],
                ['key' => 'no_designation', 'count' => (int) $agg->no_designation, 'label' => 'No designation', 'icon' => 'ti-briefcase'],
                ['key' => 'no_probation', 'count' => $noProbation, 'label' => 'Recent joiners with no probation record', 'icon' => 'ti-shield-check'],
            ])->filter(fn ($r) => $r['count'] > 0)->values();
        }

        return $out;
    }

    // -------------------------------------------------------------- trends

    protected function trend(): array
    {
        $from = $this->today->copy()->startOfMonth()->subMonths(11);
        $joins = $this->people()->whereDate('joining_date', '>=', $from)->whereDate('joining_date', '<=', $this->today)
            ->toBase()->selectRaw(DashboardKit::formatDateSql('joining_date', '%Y-%m').' as ym, COUNT(*) as n')->groupBy('ym')->pluck('n', 'ym');
        $exits = $this->can('view_any_resignation')
            ? $this->ownedBy(Resignation::class)->whereIn('status', ['accepted', 'completed'])
                ->whereDate('last_working_day', '>=', $from)->whereDate('last_working_day', '<=', $this->today)
                ->toBase()->selectRaw(DashboardKit::formatDateSql('last_working_day', '%Y-%m').' as ym, COUNT(*) as n')->groupBy('ym')->pluck('n', 'ym')
            : collect();

        $months = collect(range(0, 11))->map(fn ($i) => $from->copy()->addMonths($i));
        $labels = $months->map->format('M y')->all();
        $j = $months->map(fn ($m) => (int) ($joins[$m->format('Y-m')] ?? 0))->all();
        $x = $months->map(fn ($m) => (int) ($exits[$m->format('Y-m')] ?? 0))->all();

        // Walk headcount back from today: before each month it was smaller by its joins, larger by its exits.
        $hc = [];
        $running = $this->headcount();
        for ($i = 11; $i >= 0; $i--) {
            $hc[$i] = $running;
            $running = $running - $j[$i] + $x[$i];
        }
        ksort($hc);

        return ['labels' => $labels, 'joins' => $j, 'exits' => $x, 'headcount' => array_values($hc), 'empty' => ! array_sum($j) && ! array_sum($x)];
    }

    protected function departments(): array
    {
        $counts = $this->headcountQuery()->toBase()->selectRaw('department_id, COUNT(*) as total')->groupBy('department_id')->pluck('total', 'department_id');
        $departments = Department::whereIn('id', $counts->keys()->filter())->get(['id', 'name', 'code'])->keyBy('id');
        $dupes = $departments->countBy('name')->filter(fn ($n) => $n > 1)->keys();

        $rows = $counts->map(function ($total, $id) use ($departments, $dupes) {
            $d = $departments->get($id);

            return [
                'id' => $id ?: null,
                'name' => $d ? ($dupes->contains($d->name) && $d->code ? $d->name.' ('.$d->code.')' : $d->name) : 'Unassigned',
                'total' => (int) $total,
            ];
        })->sortByDesc('total')->values();

        $top = $rows->take(6);
        if ($rows->count() > 6) {
            $top->push(['id' => null, 'name' => 'Others', 'total' => $rows->slice(6)->sum('total'), 'others' => true]);
        }

        return ['rows' => $top, 'max' => max(1, (int) $rows->max('total'))];
    }

    protected function workforceMix(): array
    {
        $rows = $this->headcountQuery()->toBase()->selectRaw('employment_type, gender, COUNT(*) as total')->groupBy('employment_type', 'gender')->get();
        $types = $rows->groupBy(fn ($r) => EmploymentType::slug($r->employment_type) ?: 'unspecified')
            ->map(fn ($g, $slug) => ['label' => $slug === 'unspecified' ? 'Unspecified' : EmploymentType::label($slug), 'slug' => $slug, 'count' => (int) $g->sum('total')])
            ->sortByDesc('count')->values();
        $genders = $rows->groupBy(fn ($r) => $r->gender ? ucfirst(strtolower($r->gender)) : 'Unspecified')
            ->map(fn ($g, $label) => ['label' => $label, 'count' => (int) $g->sum('total')])->sortByDesc('count')->values();

        $tenureBuckets = ['< 1 yr' => 0, '1–3 yrs' => 0, '3–5 yrs' => 0, '5+ yrs' => 0];
        $this->headcountQuery()->whereNotNull('joining_date')->pluck('joining_date')->each(function ($d) use (&$tenureBuckets) {
            $years = Carbon::parse($d)->diffInYears($this->today);
            $tenureBuckets[$years < 1 ? '< 1 yr' : ($years < 3 ? '1–3 yrs' : ($years < 5 ? '3–5 yrs' : '5+ yrs'))]++;
        });
        $ageBuckets = ['Under 25' => 0, '25–34' => 0, '35–44' => 0, '45+' => 0];
        $dob = $this->headcountQuery()->whereNotNull('date_of_birth')->pluck('date_of_birth');
        $dob->each(function ($d) use (&$ageBuckets) {
            $age = Carbon::parse($d)->age;
            $ageBuckets[$age < 25 ? 'Under 25' : ($age < 35 ? '25–34' : ($age < 45 ? '35–44' : '45+'))]++;
        });

        return [
            'types' => $types,
            'genders' => $genders,
            'tenure' => collect($tenureBuckets),
            'age' => collect($ageBuckets),
            'age_missing' => $this->headcount() - $dob->count(),
            'total' => $this->headcount(),
        ];
    }

    protected function leaveWatch(): array
    {
        $out = [];
        $year = $this->today->year;

        if ($this->can('view_any_leave_balance')) {
            $balances = LeaveBalance::query()->where('leave_balances.company_id', $this->companyId)->where('year', $year)
                ->whereHas('employee', $this->activeEmployee())
                ->with(['leaveType:id,name,code,carry_forward,max_carry_forward_days', 'employee:id,name,employee_code,avatar'])
                ->get();
            $remaining = fn ($b) => (float) $b->allocated + (float) $b->carried_forward + (float) $b->adjusted - (float) $b->used - (float) $b->encashed;

            if ($this->today->month >= 9) {
                $lapsing = $balances->map(function ($b) use ($remaining) {
                    $keep = $b->leaveType?->carry_forward ? ($b->leaveType->max_carry_forward_days !== null ? (float) $b->leaveType->max_carry_forward_days : INF) : 0;

                    return ['employee_id' => $b->employee_id, 'lapse' => max(0, $remaining($b) - $keep)];
                })->filter(fn ($r) => $r['lapse'] > 0);
                if ($lapsing->isNotEmpty()) {
                    $out['lapse'] = ['days' => (float) $lapsing->sum('lapse'), 'people' => $lapsing->unique('employee_id')->count()];
                }
            }

            $low = $balances->filter(fn ($b) => $b->leaveType && stripos((string) $b->leaveType->code, 'COMP') === false
                && ((float) $b->allocated + (float) $b->carried_forward + (float) $b->adjusted) > 0 && $remaining($b) <= 2)
                ->sortBy(fn ($b) => $remaining($b));
            if ($low->isNotEmpty()) {
                $out['low'] = ['count' => $low->unique('employee_id')->count(), 'rows' => $low->take(3)->map(fn ($b) => ['employee' => $b->employee, 'type' => $b->leaveType, 'left' => $remaining($b)])->values()];
            }
        }

        if ($this->can('view_any_leave_request')) {
            $since = $this->today->copy()->subDays(90);
            $rested = $this->headcountQuery()->whereDate('joining_date', '<=', $since)
                ->whereDoesntHave('leaveRequests', fn ($q) => $q->where('status', 'approved')->whereDate('end_date', '>=', $since));
            $count = (clone $rested)->count();
            if ($count) {
                $out['no_leave'] = ['count' => $count, 'people' => (clone $rested)->limit(5)->get(['id', 'name', 'employee_code', 'avatar'])];
            }
        }

        if ($this->can('view_any_attendance_record')) {
            $absent = Attendance::records()->where('status', 'absent')->whereDate('date', '>=', $this->today->copy()->subDays(30))
                ->toBase()->selectRaw('employee_id, COUNT(*) as n')->groupBy('employee_id')->havingRaw('COUNT(*) >= 3')->pluck('n', 'employee_id');
            if ($absent->isNotEmpty()) {
                $out['absences'] = ['count' => $absent->count(), 'people' => Employee::whereIn('id', $absent->keys())->limit(5)->get(['id', 'name', 'employee_code', 'avatar'])];
            }
        }

        return $out;
    }

    // ------------------------------------------------------ hero highlights

    protected function highlights(array $data): array
    {
        $chips = [];
        if ($this->can('view_any_leave_type') && ! $this->policy->saved) {
            $chips[] = ['tone' => 'danger', 'icon' => 'ti-settings', 'text' => 'Leave policy not saved — counting calendar days', 'href' => route('admin.leave-policies.index')];
        }
        $inbox = $data['inbox'];
        if ($inbox['total']) {
            $oldest = collect($inbox['rows'])->max('oldest');
            $chips[] = ['tone' => $oldest > 5 ? 'danger' : 'amber', 'icon' => 'ti-inbox', 'text' => $inbox['total'].' '.str('approval')->plural($inbox['total']).' waiting'.($oldest ? ' · oldest '.$oldest.'d' : ''), 'href' => '#action-inbox'];
        }
        if ($data['payroll'] && ! $data['payroll']['current'] && $data['payroll']['days_left'] <= 5) {
            $chips[] = ['tone' => 'amber', 'icon' => 'ti-report-money', 'text' => $this->today->format('F').' payroll not run · '.$data['payroll']['days_left'].' days left', 'href' => route('admin.payroll-runs.index')];
        }
        $holiday = $data['holidays']->first();
        if ($holiday && $holiday['date']->lte($this->today->copy()->addDays(7))) {
            $when = $holiday['date']->isToday() ? 'today' : ($holiday['date']->isTomorrow() ? 'tomorrow' : 'on '.$holiday['date']->format('D'));
            $chips[] = ['tone' => 'pink', 'icon' => 'ti-confetti', 'text' => $holiday['name'].' '.$when.($holiday['long_weekend'] ? ' · long weekend' : ''), 'href' => '#holidays'];
        }
        if ($data['celebrations'] && ($todays = $data['celebrations']['items']->filter(fn ($c) => $c['date']->isToday())->count())) {
            $chips[] = ['tone' => 'pink', 'icon' => 'ti-gift', 'text' => $todays.' '.str('celebration')->plural($todays).' today', 'href' => '#celebrations'];
        }

        return array_slice($chips, 0, 3);
    }
}
