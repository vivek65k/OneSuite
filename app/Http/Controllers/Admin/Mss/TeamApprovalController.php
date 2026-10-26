<?php

namespace App\Http\Controllers\Admin\Mss;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\RegularizationRequest;
use App\Models\TravelRequest;
use App\Models\WfhRequest;
use App\Support\ApprovalFlow;
use App\Support\LeaveApproval;
use App\Support\RequestOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * One queue of everything waiting on a manager's team, across every kind of
 * request. Who may decide is the reporting line (the people below the signed-in
 * manager), not an admin permission. The effects of a decision are the same
 * as on HR's own approval pages: see LeaveApproval and RequestOutcome.
 */
class TeamApprovalController extends Controller
{
    use ResolvesCurrentEmployee;

    /** @var array<string, array{label: string, model: class-string<Model>, icon: string, tone: string}> */
    public const TYPES = [
        'leave' => ['label' => 'Leave', 'model' => LeaveRequest::class, 'icon' => 'ti-plane', 'tone' => 'teal'],
        'regularization' => ['label' => 'Attendance correction', 'model' => RegularizationRequest::class, 'icon' => 'ti-edit', 'tone' => 'indigo'],
        'wfh' => ['label' => 'Work from home', 'model' => WfhRequest::class, 'icon' => 'ti-home', 'tone' => 'sky'],
        'overtime' => ['label' => 'Overtime', 'model' => OvertimeRequest::class, 'icon' => 'ti-clock', 'tone' => 'amber'],
        'expense' => ['label' => 'Expense', 'model' => ExpenseClaim::class, 'icon' => 'ti-receipt', 'tone' => 'pink'],
        'travel' => ['label' => 'Travel', 'model' => TravelRequest::class, 'icon' => 'ti-map-pin', 'tone' => 'violet'],
    ];

    protected const TABS = ['mine', 'others'];

    /** Something that has waited this many days is shown as overdue. */
    public const SLOW_DAYS = 3;

    public function index(Request $request): View
    {
        $manager = $this->currentEmployee();
        $teamIds = $this->teamEmployeeIds();
        $user = $request->user();

        // Anything that isn't a plain value (e.g. ?type[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'type' => array_key_exists($text('type'), self::TYPES) ? $text('type') : '',
            'employee_id' => ctype_digit($text('employee_id')) && in_array((int) $text('employee_id'), $teamIds, true) ? (int) $text('employee_id') : null,
        ];

        // The team is the manager's own reporting line, read by id: it doesn't depend on what their role may browse.
        $people = Employee::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('id', $teamIds)->with('department:id,name')->orderBy('name')->get()->keyBy('id');
        $all = $this->pending($manager, $people)->map(function (array $item) use ($user, $manager) {
            $flow = ApprovalFlow::state($item['record']);
            $item['flow'] = ['required' => $flow['required'], 'done' => $flow['done'], 'waiting' => $flow['waiting']?->name];
            // I may decide it now (my turn, or a manager below me whose turn it is)...
            $item['can_act'] = ApprovalFlow::awaits($user, $item['record'], $flow);
            // ...and it is specifically me it is waiting on.
            $item['my_turn'] = $item['can_act'] && ($flow['waiting'] === null || (int) $flow['waiting']->id === (int) $manager->id);
            $item['waited'] = $item['record']->created_at ? (int) $item['record']->created_at->copy()->startOfDay()->diffInDays(today()) : 0;

            return $item;
        })
            // My turn first, then the longest-waiting.
            ->sortBy(fn (array $item) => [$item['my_turn'] ? 0 : ($item['can_act'] ? 1 : 2), -1 * $item['waited'], $item['date']?->timestamp ?? 0])->values();

        $filtered = $all
            ->when($state['type'] !== '', fn (Collection $c) => $c->where('type', $state['type']))
            ->when($state['employee_id'], fn (Collection $c, $id) => $c->filter(fn ($item) => $item['employee']->id === $id))
            ->values();
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'mine' => $rows->where('my_turn', true),
            'others' => $rows->where('my_turn', false),
            default => $rows,
        };
        $rows = $tabbed($filtered, $state['tab'])->values();
        $mine = $all->where('my_turn', true);
        $weekAgo = now()->subDays(7);

        return view('admin.mss.approvals', [
            'state' => $state,
            'items' => $rows,
            'context' => $this->context($rows),
            'counts' => ['all' => $filtered->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($filtered, $tab)->count()])->all(),
            'typeCounts' => $all->countBy('type'),
            'people' => $people->only($all->pluck('employee.id')->unique()->all())->values(),
            'hasTeam' => $people->isNotEmpty(),
            'actable' => $rows->where('can_act', true)->count(),
            'kpis' => [
                'mine' => $mine->count(),
                'mine_types' => $mine->countBy('type')->map(fn ($n, $type) => $n.' '.strtolower(self::TYPES[$type]['label']))->values(),
                'oldest' => (int) $mine->max('waited'),
                'slow' => $mine->where('waited', '>=', self::SLOW_DAYS)->count(),
                'others' => $all->where('my_turn', false)->count(),
                'starting_soon' => $mine->filter(fn ($item) => in_array($item['type'], ['leave', 'wfh', 'travel'], true) && $item['date'] && $item['date']->lte(today()->addDays(3)))->count(),
                'decided_week' => collect(self::TYPES)->sum(fn ($type) => $type['model']::withoutGlobalScopes()->where('company_id', $manager->company_id)
                    ->whereIn('employee_id', $teamIds)->where('reviewed_by_user_id', $user->id)->where('reviewed_at', '>=', $weekAgo)->count()),
            ],
        ]);
    }

    /** Approve several requests in one go. Each is decided exactly like a single approval; any that can't be are skipped and reported. */
    public function bulkApprove(Request $request): RedirectResponse
    {
        $this->currentEmployee();

        $keys = collect(is_array($request->input('items')) ? $request->input('items') : [])
            ->filter(fn ($key) => is_string($key) && preg_match('/^[a-z]+:\d+$/', $key))->unique()->take(100)->values();
        if ($keys->isEmpty()) {
            return back()->withErrors(['items' => 'Select at least one request to approve.']);
        }

        [$done, $forwarded, $skipped] = [0, 0, []];
        foreach ($keys as $key) {
            [$type, $id] = explode(':', $key);
            try {
                $message = $this->decide($type, (int) $id, true, null);
                str_contains($message, 'next') || str_contains($message, 'waiting') ? $forwarded++ : $done++;
            } catch (ValidationException $e) {
                $skipped[] = collect($e->errors())->flatten()->first();
            } catch (HttpException) {
                $skipped[] = 'One request is not on your team or no longer exists.';
            }
        }
        $summary = collect([
            $done ? $done.' '.($done === 1 ? 'request' : 'requests').' approved' : null,
            $forwarded ? $forwarded.' passed to the next approver' : null,
            $skipped ? count($skipped).' skipped' : null,
        ])->filter()->implode(', ').'.';

        return $skipped && ! $done && ! $forwarded
            ? back()->withErrors(['items' => 'Nothing was approved. '.collect($skipped)->unique()->implode(' ')])
            : back()->with('status', $summary.($skipped ? ' '.collect($skipped)->unique()->take(3)->implode(' ') : ''));
    }

    public function approveLeave(int $leaveRequest): RedirectResponse
    {
        return back()->with('status', $this->decide('leave', $leaveRequest, true, null));
    }

    public function rejectLeave(Request $request, int $leaveRequest): RedirectResponse
    {
        return back()->with('status', $this->decide('leave', $leaveRequest, false, $request));
    }

    public function approveOvertime(int $overtimeRequest): RedirectResponse
    {
        return back()->with('status', $this->decide('overtime', $overtimeRequest, true, null));
    }

    public function rejectOvertime(Request $request, int $overtimeRequest): RedirectResponse
    {
        return back()->with('status', $this->decide('overtime', $overtimeRequest, false, $request));
    }

    public function approveWfh(int $wfhRequest): RedirectResponse
    {
        return back()->with('status', $this->decide('wfh', $wfhRequest, true, null));
    }

    public function rejectWfh(Request $request, int $wfhRequest): RedirectResponse
    {
        return back()->with('status', $this->decide('wfh', $wfhRequest, false, $request));
    }

    public function approveRegularization(int $regularizationRequest): RedirectResponse
    {
        return back()->with('status', $this->decide('regularization', $regularizationRequest, true, null));
    }

    public function rejectRegularization(Request $request, int $regularizationRequest): RedirectResponse
    {
        return back()->with('status', $this->decide('regularization', $regularizationRequest, false, $request));
    }

    public function approveTravel(int $travelRequest): RedirectResponse
    {
        return back()->with('status', $this->decide('travel', $travelRequest, true, null));
    }

    public function rejectTravel(Request $request, int $travelRequest): RedirectResponse
    {
        return back()->with('status', $this->decide('travel', $travelRequest, false, $request));
    }

    public function approveExpense(int $expenseClaim): RedirectResponse
    {
        return back()->with('status', $this->decide('expense', $expenseClaim, true, null));
    }

    public function rejectExpense(Request $request, int $expenseClaim): RedirectResponse
    {
        return back()->with('status', $this->decide('expense', $expenseClaim, false, $request));
    }

    /** A rejection always says why: the employee is told. */
    protected function reason(Request $request): string
    {
        $data = $request->validate(['remarks' => ['required', 'string', 'min:3', 'max:500']], ['remarks.*' => 'Say why the request is rejected (3 to 500 characters). The employee is told.']);

        return trim(strip_tags($data['remarks']));
    }

    /**
     * Approve or reject one request and return what happened, in words.
     *
     * The request must belong to someone below the signed-in manager, and still be pending when
     * the row is locked: a second click, or HR deciding at the same moment, changes nothing.
     */
    protected function decide(string $type, int $id, bool $approve, ?Request $request): string
    {
        $manager = $this->currentEmployee();
        $definition = self::TYPES[$type] ?? abort(404);
        $model = $definition['model'];

        // 404 (not 403) for a request outside the team, so the id doesn't confirm it exists.
        $found = $model::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('employee_id', $this->teamEmployeeIds())->whereKey($id)->first();
        abort_unless($found, 404);
        // Only once the request is known to be on this manager's team is anything else about it checked.
        $reason = $approve ? null : $this->reason($request);
        $name = Employee::withoutGlobalScopes()->whereKey($found->employee_id)->value('name') ?? 'the employee';
        $label = strtolower($definition['label']);

        return DB::transaction(function () use ($model, $found, $approve, $reason, $name, $label) {
            $row = $model::withoutGlobalScopes()->whereKey($found->id)->lockForUpdate()->first();

            if (! $row || $row->status !== 'pending') {
                throw ValidationException::withMessages(['request' => $name.'\'s '.$label.' request has already been '.($row?->status ?? 'removed').'.']);
            }
            $state = ApprovalFlow::state($row);
            if (! ApprovalFlow::awaits(Auth::user(), $row, $state)) {
                throw ValidationException::withMessages(['request' => 'You have already approved '.$name.'\'s '.$label.' request. It is waiting for '.($state['waiting']?->name ?? 'the next approver').'.']);
            }

            if (! $approve) {
                ApprovalFlow::rejected($row, $reason);
                if ($row instanceof LeaveRequest) {
                    // Notifies the employee itself.
                    LeaveApproval::reject($row, $reason);
                } else {
                    $row->forceFill(['status' => 'rejected', 'reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now(), 'remarks' => $reason])->save();
                    RequestOutcome::rejected($row);
                }

                return $name.'\'s '.$label.' request was rejected and they have been told why.';
            }

            // Multi-level approval: stop here while a manager further up still has to approve.
            if ($waiting = ApprovalFlow::forward($row)) {
                return $waiting;
            }

            if ($row instanceof LeaveRequest) {
                // The same routine as HR's approval screen: balance, policy-counted attendance days, notification.
                if (! LeaveApproval::approve($row)) {
                    throw ValidationException::withMessages(['request' => $name.'\'s leave request has already been reviewed.']);
                }

                return $name.'\'s leave was approved: the balance and attendance are updated.';
            }

            $row->forceFill(['status' => 'approved', 'reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now()])->save();
            RequestOutcome::approved($row);

            return $name.'\'s '.$label.' request was approved'.(in_array(get_class($row), [WfhRequest::class, RegularizationRequest::class], true) ? ' and their attendance is updated.' : '.');
        });
    }

    /**
     * Every pending request of the team, in one shape.
     *
     * @param  Collection<int, Employee>  $people
     * @return Collection<int, array<string, mixed>>
     */
    protected function pending(Employee $manager, Collection $people): Collection
    {
        $mine = fn (string $model) => $model::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('employee_id', $people->keys())->where('status', 'pending');
        $time = fn (?string $t) => $t ? date('g:i A', strtotime($t)) : null;
        $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
        $span = fn ($from, $to) => $from->format('M j').($to && ! $to->isSameDay($from) ? ' – '.$to->format('M j') : '');
        $items = collect();
        $push = function (string $type, Model $r, $date, string $when, string $headline, ?string $reason) use ($items, $people) {
            // A request whose employee is no longer on the team list can't be decided here.
            if ($employee = $people->get($r->employee_id)) {
                $items->push(['key' => $type.':'.$r->id, 'type' => $type, 'id' => $r->id, 'record' => $r, 'employee' => $employee, 'date' => $date, 'when' => $when, 'headline' => $headline, 'reason' => $reason]);
            }
        };

        foreach ($mine(LeaveRequest::class)->with('leaveType:id,name,code')->get() as $r) {
            $push('leave', $r, $r->start_date, $span($r->start_date, $r->end_date), $num($r->days).' '.((float) $r->days === 1.0 ? 'day' : 'days').' · '.($r->leaveType?->name ?? 'Leave'), $r->reason);
        }
        foreach ($mine(RegularizationRequest::class)->get() as $r) {
            $push('regularization', $r, $r->date, $r->date->format('D, M j'), collect([$time($r->requested_check_in) ? 'in '.$time($r->requested_check_in) : null, $time($r->requested_check_out) ? 'out '.$time($r->requested_check_out) : null])->filter()->implode(' · ') ?: 'No times given', $r->reason);
        }
        foreach ($mine(WfhRequest::class)->get() as $r) {
            $days = (int) $r->start_date->diffInDays($r->end_date) + 1;
            $push('wfh', $r, $r->start_date, $span($r->start_date, $r->end_date), $days.' '.($days === 1 ? 'day' : 'days').' from home', $r->reason);
        }
        foreach ($mine(OvertimeRequest::class)->get() as $r) {
            $push('overtime', $r, $r->date, $r->date->format('D, M j'), $num($r->hours).' h overtime', $r->reason);
        }
        foreach ($mine(ExpenseClaim::class)->with('category:id,name')->get() as $r) {
            $push('expense', $r, $r->expense_date, $r->expense_date->format('D, M j'), number_format((float) $r->amount, 2).' · '.($r->category?->name ?? 'Expense'), $r->description);
        }
        foreach ($mine(TravelRequest::class)->get() as $r) {
            $push('travel', $r, $r->start_date, $span($r->start_date, $r->end_date), $r->from_location.' to '.$r->to_location, $r->purpose);
        }

        return $items;
    }

    /**
     * What a manager would want to know before deciding each request on screen.
     *
     * @return array<string, array<int, array{0: string, 1: string, 2: string}>> key => [tone, icon, text]
     */
    protected function context(Collection $rows): array
    {
        $employeeIds = $rows->pluck('employee.id')->unique();
        $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
        $time = fn (?string $t) => $t ? date('g:i A', strtotime($t)) : null;

        $balances = LeaveBalance::withoutGlobalScopes()->whereIn('employee_id', $employeeIds)->where('year', now()->year)->get()->keyBy(fn ($b) => $b->employee_id.':'.$b->leave_type_id);
        $teamLeave = LeaveRequest::withoutGlobalScopes()->whereIn('employee_id', $this->teamEmployeeIds())->where('status', 'approved')->whereDate('end_date', '>=', today())->get();
        $names = Employee::withoutGlobalScopes()->whereIn('id', $teamLeave->pluck('employee_id')->unique())->pluck('name', 'id');
        $dates = $rows->whereIn('type', ['regularization', 'overtime'])->map(fn ($i) => $i['date']->toDateString())->unique();
        $attendance = $dates->isEmpty() ? collect() : AttendanceRecord::withoutGlobalScopes()->whereIn('employee_id', $employeeIds)
            ->where(fn ($q) => $dates->each(fn ($d) => $q->orWhereDate('date', $d)))->get()->keyBy(fn ($r) => $r->employee_id.':'.$r->date->toDateString());
        $monthSpend = ExpenseClaim::withoutGlobalScopes()->whereIn('employee_id', $employeeIds)->where('status', '!=', 'rejected')
            ->whereDate('expense_date', '>=', today()->startOfMonth())->selectRaw('employee_id, sum(amount) as total, count(*) as n')->groupBy('employee_id')->get()->keyBy('employee_id');

        $out = [];
        foreach ($rows as $item) {
            $r = $item['record'];
            $facts = [];
            switch ($item['type']) {
                case 'leave':
                    $balance = $balances[$r->employee_id.':'.$r->leave_type_id] ?? null;
                    if ($balance) {
                        $after = (float) $balance->remaining - (float) $r->days;
                        $facts[] = [$after < 0 ? 'bad' : 'good', 'ti-wallet', $after < 0 ? 'Only '.$num($balance->remaining).' left; this needs '.$num($r->days) : $num($balance->remaining).' left, '.$num($after).' after this'];
                    } else {
                        $facts[] = ['warn', 'ti-wallet', 'No balance set for this leave type'];
                    }
                    $also = $teamLeave->filter(fn ($o) => $o->employee_id !== $r->employee_id && $o->start_date->lte($r->end_date) && $o->end_date->gte($r->start_date))
                        ->map(fn ($o) => strtok((string) ($names[$o->employee_id] ?? ''), ' '))->filter()->unique()->values();
                    $facts[] = $also->isNotEmpty() ? ['warn', 'ti-users', $also->take(3)->implode(', ').' already away then'] : ['', 'ti-users', 'Nobody else on the team is away then'];
                    break;
                case 'regularization':
                    $record = $attendance[$r->employee_id.':'.$r->date->toDateString()] ?? null;
                    $facts[] = $record
                        ? ['', 'ti-clock', 'Recorded: '.(collect([$time($record->check_in_time) ? 'in '.$time($record->check_in_time) : null, $time($record->check_out_time) ? 'out '.$time($record->check_out_time) : null])->filter()->implode(' · ') ?: 'no times').' ('.str_replace('_', ' ', $record->status).')']
                        : ['warn', 'ti-clock', 'Nothing is recorded for that day'];
                    break;
                case 'overtime':
                    $record = $attendance[$r->employee_id.':'.$r->date->toDateString()] ?? null;
                    $facts[] = $record && $record->check_in_time && $record->check_out_time
                        ? ['', 'ti-clock', 'Worked '.$time($record->check_in_time).' – '.$time($record->check_out_time)]
                        : [$r->date->gt(today()) ? '' : 'warn', 'ti-clock', $r->date->gt(today()) ? 'Asked for in advance' : 'No check-in and check-out recorded that day'];
                    break;
                case 'wfh':
                    $onLeave = $teamLeave->first(fn ($o) => $o->employee_id === $r->employee_id && $o->start_date->lte($r->end_date) && $o->end_date->gte($r->start_date));
                    if ($onLeave) {
                        $facts[] = ['warn', 'ti-plane', 'On approved leave for part of this'];
                    }
                    if ($r->start_date->lt(today())) {
                        $facts[] = ['warn', 'ti-history', 'Already started'];
                    }
                    break;
                case 'expense':
                    $spend = $monthSpend[$r->employee_id] ?? null;
                    $facts[] = ['', 'ti-calendar-event', $spend && $spend->n > 1 ? $spend->n.' claims this month · '.number_format((float) $spend->total, 2) : 'Their only claim this month'];
                    break;
                case 'travel':
                    $days = (int) $r->start_date->diffInDays($r->end_date) + 1;
                    $facts[] = ['', 'ti-calendar-event', $days.' '.($days === 1 ? 'day' : 'days').' · '.ucfirst((string) $r->mode_of_travel).($r->estimated_cost !== null ? ' · about '.number_format((float) $r->estimated_cost) : '')];
                    if ($r->start_date->lt(today())) {
                        $facts[] = ['warn', 'ti-history', 'The trip has already started'];
                    }
                    break;
            }
            $out[$item['key']] = $facts;
        }

        return $out;
    }
}
