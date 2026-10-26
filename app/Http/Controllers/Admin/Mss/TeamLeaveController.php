<?php

namespace App\Http\Controllers\Admin\Mss;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Support\ApprovalFlow;
use App\Support\LeavePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TeamLeaveController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['pending', 'upcoming', 'taken', 'closed'];

    /** People who have left drop off the board (their requests still show when picked by name). */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** How far ahead the calendar can be turned; leave can be booked a year out. */
    protected const MONTHS_AHEAD = 12;

    /** "Coming up" on the summary cards looks this many days ahead. */
    public const SOON_DAYS = 14;

    public function index(Request $request): View
    {
        $manager = $this->currentEmployee();
        $teamIds = $this->teamEmployeeIds();

        // Anything that isn't a plain value (e.g. ?month[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $thisMonth = now()->format('Y-m');
        $lastMonth = now()->addMonths(self::MONTHS_AHEAD)->format('Y-m');
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $text('month')) && $text('month') >= '2000-01' ? min($text('month'), $lastMonth) : $thisMonth;
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth()->startOfDay();

        // The team is the manager's own reporting line, read by id: it doesn't depend on what their role may browse.
        $everyone = Employee::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('id', $teamIds)
            ->with('department:id,name')->orderBy('name')->get()
            ->each(fn (Employee $e) => $e->setAttribute('is_direct', (int) $e->manager_id === (int) $manager->id));
        // A filter for someone outside the team is simply ignored.
        $focus = ctype_digit($text('employee_id')) ? $everyone->firstWhere('id', (int) $text('employee_id')) : null;
        $scope = $text('scope') === 'direct' ? 'direct' : '';
        $people = $everyone
            ->when($focus, fn (Collection $c) => $c->where('id', $focus->id))
            ->when(! $focus, fn (Collection $c) => $c->reject(fn (Employee $e) => in_array($e->status, self::INACTIVE, true)))
            ->when(! $focus && $scope === 'direct', fn (Collection $c) => $c->where('is_direct', true))
            ->values();
        $ids = $people->pluck('id');
        $state = [
            'month' => $month === $thisMonth ? '' : $month,
            'employee_id' => $focus?->id,
            'scope' => $focus ? '' : $scope,
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
        ];

        $calendars = [];
        $calendar = function (Employee $e) use (&$calendars): LeavePolicy {
            return $calendars[$e->branch_id ?? 0] ??= LeavePolicy::for((int) $e->company_id, $e->branch_id);
        };
        $byId = $people->keyBy('id');

        // Every request of these people, with the days each one actually counts as leave (weekly offs and holidays skipped by the policy).
        $all = LeaveRequest::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('employee_id', $ids)
            ->with(['leaveType:id,name,code', 'reviewedBy:id,name'])->orderByDesc('start_date')->orderByDesc('id')->get()
            ->each(function (LeaveRequest $r) use ($byId, $calendar) {
                $person = $byId[$r->employee_id];
                $r->setRelation('employee', $person);
                $r->setAttribute('dates', in_array($r->status, ['pending', 'approved'], true) ? $calendar($person)->breakdown($r->start_date, $r->end_date, $r->half_day)['dates'] : []);
                $r->setAttribute('stage', match (true) {
                    $r->status === 'pending' => 'pending',
                    $r->status === 'approved' && $r->end_date->gte(today()) => 'upcoming',
                    $r->status === 'approved' => 'taken',
                    default => 'closed',
                });
            });
        $live = $all->whereIn('status', ['pending', 'approved']);
        // For a request still to come: who else on the team already has approved leave on any of the same days.
        $approved = $all->where('status', 'approved');
        $all->each(fn (LeaveRequest $r) => $r->setAttribute('overlap', in_array($r->stage, ['pending', 'upcoming'], true)
            ? $approved->filter(fn (LeaveRequest $o) => $o->employee_id !== $r->employee_id && array_intersect($o->dates, $r->dates))->pluck('employee')->unique('id')->values()
            : collect()));

        // The board: who is away on which day of the month.
        $dates = collect(range(1, $start->daysInMonth))->map(fn ($day) => $start->copy()->day($day));
        $away = [];   // date => number of people on approved leave
        $board = $people->map(function (Employee $e) use ($dates, $live, $calendar, &$away) {
            $mine = $live->where('employee_id', $e->id);
            $cal = $calendar($e);
            $cells = [];
            $monthDays = 0.0;
            foreach ($dates as $date) {
                $key = $date->toDateString();
                // An approved request wins over a pending one on the same day.
                $hit = $mine->where('status', 'approved')->first(fn (LeaveRequest $r) => in_array($key, $r->dates, true))
                    ?? $mine->where('status', 'pending')->first(fn (LeaveRequest $r) => in_array($key, $r->dates, true));
                $holiday = $cal->holidayOn($date);
                $cells[$key] = ['leave' => $hit, 'holiday' => $holiday, 'off' => $cal->isWeekend($date)];
                if ($hit?->status === 'approved') {
                    $away[$key] = ($away[$key] ?? 0) + 1;
                    $monthDays += (float) $hit->days === 0.5 ? 0.5 : 1;
                }
            }

            return ['employee' => $e, 'cells' => $cells, 'days' => $monthDays];
        })->values();
        // Too many away at once: more than one person, and at least half of the people on the board.
        $thin = collect($away)->filter(fn ($n) => $n >= 2 && $n >= $people->count() / 2)->keys();

        $year = now()->year;
        $balances = LeaveBalance::withoutGlobalScopes()->whereIn('employee_id', $ids)->where('year', $year)->with('leaveType:id,name,code')->get();
        $pendingDays = $all->where('status', 'pending')->filter(fn (LeaveRequest $r) => $r->start_date->year === $year)
            ->groupBy(fn (LeaveRequest $r) => $r->employee_id.':'.$r->leave_type_id)->map(fn ($rows) => (float) $rows->sum('days'));
        $types = $balances->pluck('leaveType')->filter()->unique('name')->sortBy('name')->values();

        $tabbed = fn (Collection $rows, string $tab) => $tab === '' ? $rows : $rows->where('stage', $tab);
        $user = $request->user();
        $pending = $all->where('stage', 'pending');
        // Who each pending request is waiting on, and whether that is the person looking at the page.
        $flow = $pending->mapWithKeys(function (LeaveRequest $r) use ($user, $manager) {
            $s = ApprovalFlow::state($r);

            return [$r->id => [
                'name' => $s['waiting']?->name, 'done' => $s['done'], 'required' => $s['required'],
                // It is this manager's turn...
                'mine' => (int) $s['waiting']?->id === (int) $manager->id,
                // ...or it is a manager below them whose turn it is, and they may step in.
                'can' => ApprovalFlow::awaits($user, $r, $s),
            ]];
        });
        $today = today()->toDateString();
        $soonEnd = today()->addDays(self::SOON_DAYS)->toDateString();
        $awayToday = $all->where('status', 'approved')->filter(fn (LeaveRequest $r) => in_array($today, $r->dates, true));
        $soon = $all->where('status', 'approved')->filter(fn (LeaveRequest $r) => collect($r->dates)->contains(fn ($d) => $d > $today && $d <= $soonEnd));

        return view('admin.mss.leave', [
            'state' => $state,
            'month' => $month,
            'start' => $start,
            'isCurrent' => $month === $thisMonth,
            'canGoForward' => $month < $lastMonth,
            'dates' => $dates,
            'board' => $board,
            'away' => $away,
            'thin' => $thin,
            'everyone' => $everyone,
            'hasIndirect' => $everyone->contains(fn (Employee $e) => ! $e->getAttribute('is_direct')),
            'focus' => $focus,
            'requests' => $tabbed($all, $state['tab'])->values()->take(200),
            'flow' => $flow,
            'counts' => ['all' => $all->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($all, $tab)->count()])->all(),
            'balances' => $people->map(fn (Employee $e) => [
                'employee' => $e,
                'types' => $balances->where('employee_id', $e->id)->mapWithKeys(fn (LeaveBalance $b) => [$b->leaveType?->name => [
                    'left' => (float) $b->remaining,
                    'total' => (float) $b->allocated + (float) $b->carried_forward + (float) $b->adjusted,
                    'pending' => (float) ($pendingDays[$e->id.':'.$b->leave_type_id] ?? 0),
                ]]),
            ])->values(),
            'types' => $types,
            'year' => $year,
            'kpis' => [
                'people' => $people->count(),
                'away_today' => $awayToday->pluck('employee')->unique('id')->values(),
                'mine' => $flow->where('mine', true)->count(),
                'pending' => $pending->count(),
                'pending_days' => (float) $pending->sum('days'),
                'soon' => $soon->pluck('employee')->unique('id')->values(),
                'taken_year' => (float) $all->where('status', 'approved')->filter(fn (LeaveRequest $r) => $r->start_date->year === $year)->sum('days'),
            ],
        ]);
    }
}
