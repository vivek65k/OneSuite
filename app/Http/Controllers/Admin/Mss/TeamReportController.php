<?php

namespace App\Http\Controllers\Admin\Mss;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\TravelRequest;
use App\Support\AttendanceMonth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeamReportController extends Controller
{
    use ResolvesCurrentEmployee;

    /** People who have left drop off the report. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        [$state, $month, $start, $end, $people, $everyone] = $this->resolve($request);
        $rows = $this->rows($people, $start, $end);
        $sum = fn (string $key) => $rows->sum($key);
        $working = (int) $sum('working');
        $types = $rows->flatMap(fn ($r) => $r['leave_types']->keys())->unique()->sort()->values();

        return view('admin.mss.reports', [
            'state' => $state,
            'month' => $month,
            'start' => $start,
            'isCurrent' => $month === now()->format('Y-m'),
            'hasTeam' => $everyone->isNotEmpty(),
            'hasIndirect' => $everyone->contains(fn (Employee $e) => ! $e->getAttribute('is_direct')),
            'rows' => $rows,
            'leaveTypes' => $types,
            'totals' => [
                'people' => $rows->count(),
                'working' => $working,
                'worked' => (float) $sum('worked'),
                'rate' => $working ? (int) round($sum('worked') / $working * 100) : null,
                'wfh' => (int) $sum('wfh'),
                'half' => (int) $sum('half'),
                'leave' => (int) $sum('leave'),
                'absent' => (int) $sum('absent'),
                'missing' => (int) $sum('missing'),
                'late' => (int) $sum('late'),
                'late_minutes' => (int) $sum('late_minutes'),
                'hours' => round((float) $sum('hours'), 1),
                'leave_month' => (float) $sum('leave_month'),
                'leave_year' => (float) $sum('leave_year'),
                'leave_pending' => (float) $sum('leave_pending'),
                'leave_left' => (float) $sum('leave_left'),
                'overtime' => (float) $sum('overtime'),
                'overtime_pending' => (float) $sum('overtime_pending'),
                'overtime_people' => $rows->where('overtime', '>', 0)->count(),
                'expenses' => (float) $sum('expenses'),
                'expenses_pending' => (float) $sum('expenses_pending'),
                'claims' => (int) $sum('claims'),
                'trips' => (int) $sum('trips'),
                'trip_days' => (int) $sum('trip_days'),
            ],
            // Who stands out, for the summary cards.
            'lowest' => $rows->where('working', '>', 0)->sortBy('rate')->first(),
            'topOvertime' => $rows->where('overtime', '>', 0)->sortByDesc('overtime')->first(),
        ]);
    }

    /** The month on screen as a spreadsheet: one line per person, every figure from the page. */
    public function export(Request $request): StreamedResponse
    {
        [, $month, $start, $end, $people] = $this->resolve($request);
        $rows = $this->rows($people, $start, $end);

        // Stops a spreadsheet from running a cell as a formula.
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
        $columns = [
            'working' => 'Working days so far', 'worked' => 'Days worked', 'rate' => 'Attendance %', 'wfh' => 'Work from home', 'half' => 'Half days', 'leave' => 'On leave',
            'absent' => 'Absent', 'missing' => 'Not marked', 'late' => 'Late arrivals', 'late_minutes' => 'Late minutes', 'hours' => 'Hours worked',
            'leave_month' => 'Leave days this month', 'leave_year' => 'Leave days this year', 'leave_pending' => 'Leave days requested', 'leave_left' => 'Leave days left',
            'overtime' => 'Overtime hours approved', 'overtime_pending' => 'Overtime hours pending',
            'expenses' => 'Expenses approved', 'expenses_pending' => 'Expenses pending', 'trips' => 'Trips', 'trip_days' => 'Trip days',
        ];

        return response()->streamDownload(function () use ($rows, $columns, $safe) {
            $out = fopen('php://output', 'w');
            // The original columns keep their place for anyone who already uses this file; the new ones follow.
            fputcsv($out, array_merge(['Employee', 'Present', 'Absent', 'Half Day', 'On Leave', 'WFH', 'Overtime Hours', 'Code', 'Designation'], array_values($columns)));
            foreach ($rows as $r) {
                fputcsv($out, array_map($safe, array_merge(
                    [$r['employee']->name, $r['present'], $r['absent'], $r['half'], $r['leave'], $r['wfh'], $r['overtime'], $r['employee']->employee_code, $r['employee']->designation],
                    array_map(fn ($key) => $r[$key], array_keys($columns))
                )));
            }
            fclose($out);
        }, 'team-report-'.$month.'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * The filters from the URL and the people they select. Only ever people below the
     * signed-in manager in the reporting line.
     *
     * @return array{0: array<string, mixed>, 1: string, 2: Carbon, 3: Carbon, 4: Collection, 5: Collection}
     */
    protected function resolve(Request $request): array
    {
        $manager = $this->currentEmployee();

        // Anything that isn't a plain value (e.g. ?month[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $thisMonth = now()->format('Y-m');
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $text('month')) && $text('month') >= '2000-01' ? $text('month') : $thisMonth;
        // A report on a month that hasn't happened has nothing in it.
        $month = min($month, $thisMonth);
        // From the first of the month, not from "today's day number in that month" (which rolls a short month over into the next).
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth()->startOfDay();

        // The team is the manager's own reporting line, read by id: it doesn't depend on what their role may browse.
        $everyone = Employee::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('id', $this->teamEmployeeIds())
            ->with('department:id,name')->orderBy('name')->get()
            ->each(fn (Employee $e) => $e->setAttribute('is_direct', (int) $e->manager_id === (int) $manager->id));
        $scope = $text('scope') === 'direct' ? 'direct' : '';
        $people = $everyone->reject(fn (Employee $e) => in_array($e->status, self::INACTIVE, true))
            ->when($scope === 'direct', fn (Collection $c) => $c->where('is_direct', true))->values();

        return [['month' => $month === $thisMonth ? '' : $month, 'scope' => $scope], $month, $start, $end, $people, $everyone];
    }

    /**
     * One line per person for the month: attendance (the same reading as Team Attendance),
     * leave, overtime, expenses and travel.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function rows(Collection $people, Carbon $start, Carbon $end): Collection
    {
        $ids = $people->pluck('id');
        $year = $start->year;
        $mine = fn (string $model) => $model::withoutGlobalScopes()->whereIn('employee_id', $ids);
        $inMonth = fn ($q, string $column) => $q->whereDate($column, '>=', $start)->whereDate($column, '<=', $end);

        $leaves = $mine(LeaveRequest::class)->whereIn('status', ['approved', 'pending'])->whereYear('start_date', $year)->with('leaveType:id,name')->get()->groupBy('employee_id');
        $balances = $mine(LeaveBalance::class)->where('year', $year)->get()->groupBy('employee_id');
        $overtime = $inMonth($mine(OvertimeRequest::class), 'date')->whereIn('status', ['approved', 'pending'])->get()->groupBy('employee_id');
        $claims = $inMonth($mine(ExpenseClaim::class), 'expense_date')->where('status', '!=', 'rejected')->get()->groupBy('employee_id');
        $trips = $mine(TravelRequest::class)->where('status', 'approved')->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->get()->groupBy('employee_id');

        return $people->map(function (Employee $e) use ($start, $end, $leaves, $balances, $overtime, $claims, $trips) {
            $days = AttendanceMonth::days($e, $start, $end);
            $a = AttendanceMonth::summary($days);
            $past = $days->filter(fn ($d) => $d['date']->lte(today()));
            $myLeaves = $leaves->get($e->id, collect());
            $approved = $myLeaves->where('status', 'approved');
            $ot = $overtime->get($e->id, collect());
            $ex = $claims->get($e->id, collect());
            $tr = $trips->get($e->id, collect());

            return $a + [
                'employee' => $e,
                'present' => $past->where('kind', 'present')->count(),
                'rate' => $a['working'] ? (int) round($a['worked'] / $a['working'] * 100) : null,
                // Leave that starts in this month counts here; the attendance columns count it day by day.
                'leave_month' => (float) $approved->filter(fn (LeaveRequest $l) => $l->start_date->isSameMonth($start))->sum('days'),
                'leave_year' => (float) $approved->sum('days'),
                'leave_pending' => (float) $myLeaves->where('status', 'pending')->sum('days'),
                'leave_left' => (float) $balances->get($e->id, collect())->sum(fn (LeaveBalance $b) => max(0, (float) $b->remaining)),
                'leave_types' => $approved->groupBy(fn (LeaveRequest $l) => $l->leaveType?->name ?? 'Other')->map(fn ($rows) => (float) $rows->sum('days')),
                'overtime' => (float) $ot->where('status', 'approved')->sum('hours'),
                'overtime_pending' => (float) $ot->where('status', 'pending')->sum('hours'),
                'expenses' => (float) $ex->whereIn('status', ['approved', 'reimbursed'])->sum('amount'),
                'expenses_pending' => (float) $ex->where('status', 'pending')->sum('amount'),
                'claims' => $ex->count(),
                'trips' => $tr->count(),
                // Only the days of each trip that fall inside this month.
                'trip_days' => (int) $tr->sum(fn (TravelRequest $t) => (int) $t->start_date->max($start)->diffInDays($t->end_date->min($end)) + 1),
            ];
        })->values();
    }
}
