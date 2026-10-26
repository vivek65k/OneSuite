<?php

namespace App\Http\Controllers\Admin\Ess;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\ApprovalStep;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\RegularizationRequest;
use App\Models\TravelRequest;
use App\Models\WfhRequest;
use App\Support\ApprovalFlow;
use App\Support\Notifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MyRequestController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['pending', 'approved', 'closed'];

    /**
     * Every kind of request an employee makes. The first three are made on this page
     * (and can be withdrawn here); the others have pages of their own and are listed
     * so everything waiting on someone can be seen in one place.
     *
     * @var array<string, array{label: string, model: class-string<Model>, icon: string, tone: string, here: bool, route: ?string, permission: ?string, approval: ?string}>
     */
    public const TYPES = [
        'regularization' => ['label' => 'Attendance correction', 'model' => RegularizationRequest::class, 'icon' => 'ti-edit', 'tone' => 'indigo', 'here' => true, 'route' => null, 'permission' => 'update_regularization_request', 'approval' => 'admin.regularization-requests.index'],
        'wfh' => ['label' => 'Work from home', 'model' => WfhRequest::class, 'icon' => 'ti-home', 'tone' => 'sky', 'here' => true, 'route' => null, 'permission' => 'update_wfh_request', 'approval' => 'admin.wfh-requests.index'],
        'overtime' => ['label' => 'Overtime', 'model' => OvertimeRequest::class, 'icon' => 'ti-clock', 'tone' => 'amber', 'here' => true, 'route' => null, 'permission' => 'update_overtime_request', 'approval' => 'admin.overtime-requests.index'],
        'leave' => ['label' => 'Leave', 'model' => LeaveRequest::class, 'icon' => 'ti-plane', 'tone' => 'teal', 'here' => false, 'route' => 'admin.my-leave.index', 'permission' => null, 'approval' => null],
        'expense' => ['label' => 'Expense', 'model' => ExpenseClaim::class, 'icon' => 'ti-receipt', 'tone' => 'pink', 'here' => false, 'route' => 'admin.my-expenses.index', 'permission' => null, 'approval' => null],
        'travel' => ['label' => 'Travel', 'model' => TravelRequest::class, 'icon' => 'ti-map-pin', 'tone' => 'violet', 'here' => false, 'route' => 'admin.my-travel.index', 'permission' => null, 'approval' => null],
    ];

    /** People who have left can't make new requests. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** How far back a missed punch can still be corrected. */
    public const REGULARIZE_DAYS_BACK = 60;

    /** Work from home: a few days back (it was agreed on the phone), up to three months ahead, a month at a time. */
    public const WFH_DAYS_BACK = 7;

    public const WFH_DAYS_AHEAD = 90;

    public const WFH_MAX_DAYS = 31;

    /** The same limits HR's overtime form uses. */
    public const OVERTIME_DAYS_BACK = 90;

    public const OVERTIME_DAYS_AHEAD = 30;

    public const OVERTIME_MAX_HOURS = 16;

    /** The combined list shows the most recent requests; older ones stay on their own pages. */
    protected const LIST_LIMIT = 200;

    public function index(Request $request): View
    {
        $employee = $this->currentEmployee();

        // Anything that isn't a plain value (e.g. ?type[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'type' => array_key_exists($text('type'), self::TYPES) ? $text('type') : '',
        ];
        // "Regularize" on My Attendance sends the day here so it doesn't have to be typed again.
        $day = $text('date');
        $prefill = preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) && $day <= today()->toDateString() && $day >= today()->subDays(self::REGULARIZE_DAYS_BACK)->toDateString() ? $day : '';

        $all = $this->allRequests($employee);
        $typed = $state['type'] !== '' ? $all->where('type', $state['type'])->values() : $all;
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'pending' => $rows->where('status', 'pending'),
            'approved' => $rows->whereIn('status', ['approved', 'reimbursed']),
            'closed' => $rows->whereIn('status', ['rejected', 'cancelled']),
            default => $rows,
        };
        $rows = $tabbed($typed, $state['tab'])->values()->take(self::LIST_LIMIT);
        // Who each pending request is waiting on.
        $flow = $rows->where('status', 'pending')->mapWithKeys(function (array $item) {
            $s = ApprovalFlow::state($item['record']);

            return [$item['key'] => ['name' => $s['waiting']?->name, 'done' => $s['done'], 'required' => $s['required']]];
        });
        $pending = $all->where('status', 'pending');
        $month = fn (array $item) => $item['date']?->isSameMonth(today());

        return view('admin.ess.requests', [
            'state' => $state,
            'items' => $rows,
            'flow' => $flow,
            'shownOf' => [$rows->count(), $tabbed($typed, $state['tab'])->count()],
            'counts' => ['all' => $typed->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($typed, $tab)->count()])->all(),
            'typeCounts' => $all->countBy('type'),
            'kpis' => [
                'pending' => $pending->count(),
                'pending_types' => $pending->countBy('type')->map(fn ($n, $type) => $n.' '.strtolower(self::TYPES[$type]['label']))->values(),
                'oldest' => $pending->min('created_at'),
                'approved_month' => $all->whereIn('status', ['approved', 'reimbursed'])->filter($month)->count(),
                'overtime_month' => (float) $all->where('type', 'overtime')->where('status', 'approved')->filter($month)->sum(fn ($i) => (float) $i['record']->hours),
                'wfh_ahead' => (int) $all->where('type', 'wfh')->where('status', 'approved')->sum(fn ($i) => $this->daysAhead($i['record'])),
                'rejected' => $all->where('status', 'rejected')->filter(fn ($i) => $i['created_at']?->gte(today()->subDays(30)))->count(),
            ],
            'canRequest' => ! in_array($employee->status, self::INACTIVE, true),
            'employeeStatus' => $employee->status,
            'prefill' => $prefill,
            'limits' => [
                'regularize_min' => today()->subDays(self::REGULARIZE_DAYS_BACK)->toDateString(),
                'wfh_min' => today()->subDays(self::WFH_DAYS_BACK)->toDateString(),
                'wfh_max' => today()->addDays(self::WFH_DAYS_AHEAD)->toDateString(),
                'overtime_min' => today()->subDays(self::OVERTIME_DAYS_BACK)->toDateString(),
                'overtime_max' => today()->addDays(self::OVERTIME_DAYS_AHEAD)->toDateString(),
                'today' => today()->toDateString(),
            ],
        ]);
    }

    public function storeRegularization(Request $request): RedirectResponse
    {
        $employee = $this->requester();

        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.today()->subDays(self::REGULARIZE_DAYS_BACK)->toDateString()],
            'requested_check_in' => ['nullable', 'date_format:H:i'],
            'requested_check_out' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'date.before_or_equal' => 'Attendance can only be corrected for a day that has happened.',
            'date.after_or_equal' => 'Attendance can be corrected up to '.self::REGULARIZE_DAYS_BACK.' days back. Ask HR about anything older.',
            'requested_check_in.date_format' => 'Enter the check-in time as hours and minutes.',
            'requested_check_out.date_format' => 'Enter the check-out time as hours and minutes.',
            'reason.*' => 'Say why the attendance needs correcting.',
        ]);
        $in = $data['requested_check_in'] ?? null;
        $out = $data['requested_check_out'] ?? null;
        $date = Carbon::parse($data['date'])->startOfDay();

        if (! $in && ! $out) {
            throw ValidationException::withMessages(['requested_check_in' => 'Enter the check-in time, the check-out time, or both.']);
        }
        if ($in && $out && $in === $out) {
            throw ValidationException::withMessages(['requested_check_out' => 'The check-out time is the same as the check-in time.']);
        }
        $this->notBeforeJoining($employee, $date, 'date');

        $record = $this->save($employee, RegularizationRequest::class, function () use ($employee, $date) {
            // One open correction per day; a second would only confuse whoever approves it.
            if (RegularizationRequest::withoutGlobalScopes()->where('employee_id', $employee->id)->whereDate('date', $date)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['date' => 'You already have a correction waiting for '.$date->format('M j, Y').'. Withdraw it first if it needs changing.']);
            }
        }, ['date' => $date->toDateString(), 'requested_check_in' => $in, 'requested_check_out' => $out, 'reason' => $this->clean($data['reason'])]);

        $this->notify($employee, 'regularization', 'asked to correct their attendance for '.$date->format('M j, Y').'.');

        return back()->with('status', 'Your attendance correction for '.$date->format('M j').' was sent for approval.');
    }

    public function storeWfh(Request $request): RedirectResponse
    {
        $employee = $this->requester();

        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:'.today()->subDays(self::WFH_DAYS_BACK)->toDateString(), 'before_or_equal:'.today()->addDays(self::WFH_DAYS_AHEAD)->toDateString()],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'start_date.after_or_equal' => 'Work from home can be requested for at most '.self::WFH_DAYS_BACK.' days back.',
            'start_date.before_or_equal' => 'Work from home can be requested up to '.self::WFH_DAYS_AHEAD.' days ahead.',
            'end_date.after_or_equal' => 'The last day can\'t be before the first day.',
            'reason.*' => 'Say why you need to work from home.',
        ]);
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();

        if ($start->diffInDays($end) + 1 > self::WFH_MAX_DAYS) {
            throw ValidationException::withMessages(['end_date' => 'One request can cover at most '.self::WFH_MAX_DAYS.' days. Send a second request for a longer stretch.']);
        }
        $this->notBeforeJoining($employee, $start, 'start_date');

        $record = $this->save($employee, WfhRequest::class, function () use ($employee, $start, $end) {
            $clash = WfhRequest::withoutGlobalScopes()->where('employee_id', $employee->id)->whereIn('status', ['pending', 'approved'])
                ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->first();
            if ($clash) {
                throw ValidationException::withMessages(['start_date' => 'You already have '.$clash->status.' work from home for '.$clash->start_date->format('M j').($clash->end_date->isSameDay($clash->start_date) ? '' : ' – '.$clash->end_date->format('M j')).'.']);
            }
            $leave = $this->leaveOn($employee, $start, $end);
            if ($leave) {
                throw ValidationException::withMessages(['start_date' => 'You are on approved leave '.$leave->start_date->format('M j').($leave->end_date->isSameDay($leave->start_date) ? '' : ' – '.$leave->end_date->format('M j')).', which overlaps these days.']);
            }
        }, ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString(), 'reason' => $this->clean($data['reason'])]);

        $this->notify($employee, 'wfh', 'asked to work from home '.$start->format('M j').($end->isSameDay($start) ? '' : ' – '.$end->format('M j')).'.');

        return back()->with('status', 'Your work-from-home request for '.$start->format('M j').($end->isSameDay($start) ? '' : ' – '.$end->format('M j')).' was sent for approval.');
    }

    public function storeOvertime(Request $request): RedirectResponse
    {
        $employee = $this->requester();

        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:'.today()->subDays(self::OVERTIME_DAYS_BACK)->toDateString(), 'before_or_equal:'.today()->addDays(self::OVERTIME_DAYS_AHEAD)->toDateString()],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:'.self::OVERTIME_MAX_HOURS],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'date.after_or_equal' => 'Overtime older than '.self::OVERTIME_DAYS_BACK.' days can\'t be claimed here.',
            'date.before_or_equal' => 'Overtime can be pre-approved up to '.self::OVERTIME_DAYS_AHEAD.' days ahead.',
            'hours.*' => 'Enter the overtime hours, from 0.25 to '.self::OVERTIME_MAX_HOURS.'.',
            'reason.*' => 'Say what the overtime was for.',
        ]);
        $date = Carbon::parse($data['date'])->startOfDay();
        // Overtime is counted in quarter hours.
        $hours = round((float) $data['hours'] * 4) / 4;
        $this->notBeforeJoining($employee, $date, 'date');

        $record = $this->save($employee, OvertimeRequest::class, function () use ($employee, $date) {
            $twin = OvertimeRequest::withoutGlobalScopes()->where('employee_id', $employee->id)->whereDate('date', $date)->whereIn('status', ['pending', 'approved'])->first();
            if ($twin) {
                throw ValidationException::withMessages(['date' => 'You already have a '.$twin->status.' overtime request for '.$date->format('M j, Y').' ('.rtrim(rtrim(number_format((float) $twin->hours, 2), '0'), '.').' h).']);
            }
            if ($this->leaveOn($employee, $date, $date)) {
                throw ValidationException::withMessages(['date' => 'You are on approved leave that day.']);
            }
        }, ['date' => $date->toDateString(), 'hours' => $hours, 'reason' => $this->clean($data['reason'])]);

        $this->notify($employee, 'overtime', 'requested '.rtrim(rtrim(number_format($hours, 2), '0'), '.').' h of overtime for '.$date->format('M j, Y').'.');

        return back()->with('status', 'Your overtime request for '.$date->format('M j').' was sent for approval.');
    }

    /** Take back a request made on this page while it is still waiting for approval. */
    public function destroy(string $type, int $id): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $definition = self::TYPES[$type] ?? null;
        abort_unless($definition && $definition['here'], 404);

        /** @var class-string<Model> $model */
        $model = $definition['model'];
        // 404 (not 403) for someone else's request, so the id doesn't confirm it exists.
        $record = $model::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id)->whereKey($id)->firstOrFail();

        DB::transaction(function () use ($model, $record) {
            // The delete is conditional, so a withdrawal racing an approval can't remove an approved request.
            if (! $model::withoutGlobalScopes()->whereKey($record->id)->where('status', 'pending')->delete()) {
                throw ValidationException::withMessages(['request' => 'This request is already '.($model::withoutGlobalScopes()->whereKey($record->id)->value('status') ?? 'gone').', so it can\'t be withdrawn.']);
            }
            ApprovalStep::query()->where('approvable_type', $record->getMorphClass())->where('approvable_id', $record->id)->delete();
        });

        return back()->with('status', 'Your '.strtolower($definition['label']).' request was withdrawn.');
    }

    /** The employee, if they may still make requests. */
    protected function requester(): Employee
    {
        $employee = $this->currentEmployee();

        if (in_array($employee->status, self::INACTIVE, true)) {
            throw ValidationException::withMessages(['reason' => 'Your employee record is '.$employee->status.', so new requests can\'t be made. Ask HR.']);
        }

        return $employee;
    }

    /**
     * Check and save in one go, with the employee row locked, so two quick submits can't both pass the check.
     *
     * @param  class-string<Model>  $model
     */
    protected function save(Employee $employee, string $model, callable $check, array $values): Model
    {
        return DB::transaction(function () use ($employee, $model, $check, $values) {
            DB::table('employees')->where('id', $employee->id)->lockForUpdate()->first();
            $check();

            $record = (new $model)->forceFill($values + ['company_id' => $employee->company_id, 'employee_id' => $employee->id, 'status' => 'pending']);
            $record->save();

            return $record;
        });
    }

    protected function notify(Employee $employee, string $type, string $what): void
    {
        $definition = self::TYPES[$type];

        Notifier::notifyUsersWithPermission(
            $definition['permission'],
            $definition['label'].' request',
            $employee->name.' '.$what,
            route($definition['approval']),
            'ti '.$definition['icon'],
            $employee->branch_id,
            employeeId: $employee->id,
        );
    }

    protected function notBeforeJoining(Employee $employee, Carbon $date, string $field): void
    {
        if ($employee->joining_date && $date->lt($employee->joining_date)) {
            throw ValidationException::withMessages([$field => 'That is before you joined ('.$employee->joining_date->format('M j, Y').').']);
        }
    }

    protected function leaveOn(Employee $employee, Carbon $start, Carbon $end): ?LeaveRequest
    {
        return LeaveRequest::withoutGlobalScopes()->where('employee_id', $employee->id)->where('status', 'approved')
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->first();
    }

    protected function clean(string $text): string
    {
        return trim(preg_replace('/[ \t]+/', ' ', strip_tags($text)));
    }

    /** Days of an approved work-from-home stretch that are still to come (today included). */
    protected function daysAhead(WfhRequest $request): int
    {
        $from = $request->start_date->max(today());

        return $request->end_date->lt($from) ? 0 : (int) $from->diffInDays($request->end_date) + 1;
    }

    /**
     * Everything the employee has asked for, newest first, in one shape.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function allRequests(Employee $employee): Collection
    {
        $mine = fn (string $model) => $model::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id);
        $span = fn ($from, $to) => $from->format('M j').($to && ! $to->isSameDay($from) ? ' – '.$to->format('M j') : '').', '.($to ?? $from)->format('Y');
        $time = fn (?string $t) => $t ? date('g:i A', strtotime($t)) : null;
        $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
        $items = collect();
        $push = function (string $type, Model $r, $date, string $when, string $detail, ?string $note) use ($items) {
            $items->push([
                'key' => $type.'-'.$r->id, 'type' => $type, 'id' => $r->id, 'record' => $r, 'date' => $date, 'when' => $when,
                'detail' => $detail, 'note' => $note, 'status' => $r->status, 'remarks' => $r->remarks,
                'reviewer' => $r->reviewedBy?->name, 'reviewed_at' => $r->reviewed_at, 'created_at' => $r->created_at,
            ]);
        };

        foreach ($mine(RegularizationRequest::class)->with('reviewedBy:id,name')->get() as $r) {
            $push('regularization', $r, $r->date, $r->date->format('D, M j, Y'), collect([$time($r->requested_check_in) ? 'in '.$time($r->requested_check_in) : null, $time($r->requested_check_out) ? 'out '.$time($r->requested_check_out) : null])->filter()->implode(' · ') ?: 'No times given', $r->reason);
        }
        foreach ($mine(WfhRequest::class)->with('reviewedBy:id,name')->get() as $r) {
            $days = (int) $r->start_date->diffInDays($r->end_date) + 1;
            $push('wfh', $r, $r->start_date, $span($r->start_date, $r->end_date), $days.' '.($days === 1 ? 'day' : 'days'), $r->reason);
        }
        foreach ($mine(OvertimeRequest::class)->with('reviewedBy:id,name')->get() as $r) {
            $push('overtime', $r, $r->date, $r->date->format('D, M j, Y'), $num($r->hours).' h', $r->reason);
        }
        foreach ($mine(LeaveRequest::class)->with(['leaveType:id,name', 'reviewedBy:id,name'])->get() as $r) {
            $push('leave', $r, $r->start_date, $span($r->start_date, $r->end_date), ($r->leaveType?->name ?? 'Leave').' · '.$num($r->days).' '.((float) $r->days === 1.0 ? 'day' : 'days'), $r->reason);
        }
        foreach ($mine(ExpenseClaim::class)->with(['category:id,name', 'reviewedBy:id,name'])->get() as $r) {
            $push('expense', $r, $r->expense_date, $r->expense_date->format('D, M j, Y'), ($r->category?->name ?? 'Expense').' · '.number_format((float) $r->amount, 2), $r->description);
        }
        foreach ($mine(TravelRequest::class)->with('reviewedBy:id,name')->get() as $r) {
            $push('travel', $r, $r->start_date, $span($r->start_date, $r->end_date), $r->from_location.' to '.$r->to_location, $r->purpose);
        }

        return $items->sortByDesc(fn (array $item) => $item['created_at']?->timestamp ?? 0)->values();
    }
}
