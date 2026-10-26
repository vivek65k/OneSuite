<?php

namespace App\Http\Controllers\Admin\Ess;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Support\ApprovalFlow;
use App\Support\LeavePolicy;
use App\Support\Notifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MyLeaveController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['pending', 'upcoming', 'taken', 'closed'];

    /** How far ahead leave can be requested, in days. */
    protected const MAX_AHEAD_DAYS = 365;

    /** People who have left can't book leave. */
    protected const INACTIVE = ['inactive', 'terminated'];

    public function index(Request $request): View
    {
        $employee = $this->currentEmployee();

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $years = $this->mine($employee)->selectRaw('distinct year(start_date) as y')->pluck('y')
            ->merge(LeaveBalance::withoutGlobalScopes()->where('employee_id', $employee->id)->distinct()->pluck('year'))
            ->push(now()->year)->map(fn ($y) => (int) $y)->unique()->sortDesc()->values();
        $year = ctype_digit($text('year')) && $years->contains((int) $text('year')) ? (int) $text('year') : now()->year;
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'year' => $year === now()->year ? null : $year,
        ];

        $all = $this->mine($employee)->whereYear('start_date', $year)->with(['leaveType:id,name,code', 'reviewedBy:id,name'])
            ->orderByDesc('start_date')->orderByDesc('id')->get();
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'pending' => $rows->where('status', 'pending'),
            'upcoming' => $rows->filter(fn (LeaveRequest $r) => $r->status === 'approved' && $r->end_date->gte(today())),
            'taken' => $rows->filter(fn (LeaveRequest $r) => $r->status === 'approved' && $r->end_date->lt(today())),
            'closed' => $rows->whereIn('status', ['rejected', 'cancelled']),
            default => $rows,
        };

        $pendingByType = $all->where('status', 'pending')->groupBy('leave_type_id')->map(fn ($rows) => (float) $rows->sum('days'));
        $balances = LeaveBalance::withoutGlobalScopes()->where('employee_id', $employee->id)->where('year', $year)
            ->with('leaveType:id,name,code,is_paid')->get()->sortBy(fn (LeaveBalance $b) => $b->leaveType?->name)->values()
            ->map(function (LeaveBalance $b) use ($pendingByType) {
                $pending = (float) ($pendingByType[$b->leave_type_id] ?? 0);

                return [
                    'balance' => $b,
                    'total' => (float) $b->allocated + (float) $b->carried_forward + (float) $b->adjusted,
                    'remaining' => (float) $b->remaining,
                    'pending' => $pending,
                    // What can still be asked for: days already waiting for approval are spoken for.
                    'available' => max(0, (float) $b->remaining - $pending),
                ];
            });

        $policy = LeavePolicy::for((int) $employee->company_id, $employee->branch_id);
        $types = $this->types($employee)->orderBy('name')->get(['id', 'name', 'code', 'is_paid']);
        $upcoming = $this->mine($employee)->where('status', 'approved')->whereDate('end_date', '>=', today())->with('leaveType:id,name')->orderBy('start_date')->first();
        $rows = $tabbed($all, $state['tab'])->values();

        return view('admin.ess.leave', [
            'employee' => $employee,
            'state' => $state,
            'year' => $year,
            'years' => $years,
            'balances' => $balances,
            'requests' => $rows,
            // Who a pending request is waiting on, so "pending" isn't a black box.
            'waiting' => $rows->where('status', 'pending')->mapWithKeys(function (LeaveRequest $r) {
                $flow = ApprovalFlow::state($r);

                return [$r->id => ['name' => $flow['waiting']?->name, 'done' => $flow['done'], 'required' => $flow['required']]];
            }),
            'counts' => ['all' => $all->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($all, $tab)->count()])->all(),
            'kpis' => [
                'available' => (float) $balances->sum('available'),
                'types' => $balances->count(),
                'pending' => (float) $all->where('status', 'pending')->sum('days'),
                'pending_count' => $all->where('status', 'pending')->count(),
                'taken' => (float) $all->where('status', 'approved')->sum('days'),
                'upcoming' => $upcoming,
            ],
            'leaveTypes' => $types,
            'canApply' => ! in_array($employee->status, self::INACTIVE, true) && $types->isNotEmpty(),
            'policy' => $policy->rules,
            'client' => [
                'policy' => $policy->toClient(),
                'today' => today()->toDateString(),
                'joined' => $employee->joining_date?->toDateString(),
                'maxDate' => today()->addDays(self::MAX_AHEAD_DAYS)->toDateString(),
                'year' => now()->year,
                // Balances are per year; the form checks requests in the current year against them.
                'balances' => $this->available($employee, now()->year),
                'paid' => $types->mapWithKeys(fn (LeaveType $t) => [$t->id => (bool) $t->is_paid]),
                'booked' => $this->mine($employee)->whereIn('status', ['pending', 'approved'])->whereDate('end_date', '>=', today()->subMonths(3))->with('leaveType:id,name')->get()
                    ->map(fn (LeaveRequest $r) => ['from' => $r->start_date->toDateString(), 'to' => $r->end_date->toDateString(), 'status' => $r->status, 'type' => $r->leaveType?->name])->values(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if (in_array($employee->status, self::INACTIVE, true)) {
            throw ValidationException::withMessages(['leave_type_id' => 'Your employee record is '.$employee->status.', so leave can\'t be requested.']);
        }

        $data = $request->validate([
            // Only active types of the employee's own branch (or company-wide ones).
            'leave_type_id' => ['required', Rule::in($this->types($employee)->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'half_day' => ['nullable', Rule::in(array_keys(LeaveRequest::HALF_DAYS))],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'leave_type_id.in' => 'Choose one of the leave types available to you.',
            'end_date.after_or_equal' => 'The last day can\'t be before the first day.',
            'reason.min' => 'Give a short reason for the leave.',
        ]);
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();

        if ($employee->joining_date && $start->lt($employee->joining_date)) {
            throw ValidationException::withMessages(['start_date' => 'Leave can\'t start before your joining date ('.$employee->joining_date->format('M j, Y').').']);
        }
        if ($end->gt(today()->addDays(self::MAX_AHEAD_DAYS))) {
            throw ValidationException::withMessages(['end_date' => 'Leave can be requested up to a year ahead.']);
        }
        // A balance belongs to one year, so one request can't draw on two.
        if ($start->year !== $end->year) {
            throw ValidationException::withMessages(['end_date' => 'This leave runs across two years. Request the days in '.$start->year.' and the days in '.$end->year.' separately.']);
        }

        // Days are counted by the leave policy, including its notice and backdating limits.
        $breakdown = LeavePolicy::for((int) $employee->company_id, $employee->branch_id)->check($start, $end, $data['half_day'] ?? null, true);
        $days = (float) $breakdown['days'];
        $type = LeaveType::query()->findOrFail($data['leave_type_id']);

        $leaveRequest = DB::transaction(function () use ($employee, $data, $start, $end, $days, $type) {
            // One request at a time per person, so two quick submits can't both pass the checks below.
            DB::table('employees')->where('id', $employee->id)->lockForUpdate()->first();

            $overlap = $this->mine($employee)->whereIn('status', ['pending', 'approved'])
                ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->with('leaveType:id,name')->first();
            if ($overlap) {
                throw ValidationException::withMessages(['start_date' => 'You already have '.$overlap->status.' '.($overlap->leaveType?->name ?? 'leave').' from '.$overlap->start_date->format('M j').' to '.$overlap->end_date->format('M j, Y').' on some of these days.']);
            }

            $available = $this->available($employee, $start->year)[$type->id] ?? null;
            if ($available !== null && $days > $available) {
                throw ValidationException::withMessages(['leave_type_id' => 'You have '.$this->days($available).' of '.$type->name.' available'.($start->year !== now()->year ? ' for '.$start->year : '').' (after requests already waiting for approval); this request needs '.$this->days($days).'.']);
            }

            $leaveRequest = (new LeaveRequest)->forceFill([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days' => $days,
                'half_day' => $days === 0.5 ? ($data['half_day'] ?? null) : null,
                'reason' => trim($data['reason']),
                'status' => 'pending',
            ]);
            $leaveRequest->save();

            return $leaveRequest;
        });

        Notifier::notifyUsersWithPermission(
            'update_leave_request',
            'New leave request',
            "{$employee->name} requested {$this->days($days)} of {$type->name} from {$start->format('M j')}.",
            route('admin.leave-requests.approval'),
            'ti ti-calendar-event',
            $employee->branch_id,
            employeeId: $employee->id,
        );

        return back()->with('status', 'Your request for '.$this->days($days).' of '.$type->name.' ('.$start->format('M j').($end->isSameDay($start) ? '' : ' – '.$end->format('M j')).') was sent for approval.');
    }

    /** Withdraw a request that is still waiting, or approved leave that hasn't started yet. */
    public function cancel(int $leaveRequest): RedirectResponse
    {
        $employee = $this->currentEmployee();
        // 404 (not 403) for someone else's request, so the id doesn't confirm it exists.
        $record = $this->mine($employee)->whereKey($leaveRequest)->firstOrFail();

        $was = DB::transaction(function () use ($record) {
            // The status flip is conditional, so a cancel racing an approval can't leave a cancelled request deducted.
            if (LeaveRequest::withoutGlobalScopes()->whereKey($record->id)->where('status', 'pending')->update(['status' => 'cancelled', 'updated_at' => now()])) {
                return 'pending';
            }

            $fresh = LeaveRequest::withoutGlobalScopes()->whereKey($record->id)->lockForUpdate()->first();
            if ($fresh->status !== 'approved') {
                throw ValidationException::withMessages(['leave' => 'This request is already '.$fresh->status.'.']);
            }
            if ($fresh->start_date->lte(today())) {
                throw ValidationException::withMessages(['leave' => 'This leave has already started. Ask HR if it needs to be changed.']);
            }

            // Approved leave was deducted from the balance and marked on attendance; put both back.
            $fresh->forceFill(['status' => 'cancelled'])->save();
            LeaveBalance::adjust($fresh->employee_id, $fresh->company_id, $fresh->leave_type_id, $fresh->start_date->year, 'used', -1 * (float) $fresh->days);
            AttendanceRecord::withoutGlobalScopes()->where('employee_id', $fresh->employee_id)->where('status', 'on_leave')
                ->whereDate('date', '>=', $fresh->start_date)->whereDate('date', '<=', $fresh->end_date)->delete();

            return 'approved';
        });

        Notifier::notifyUsersWithPermission(
            'update_leave_request',
            $was === 'approved' ? 'Approved leave cancelled' : 'Leave request withdrawn',
            "{$employee->name} cancelled {$this->days((float) $record->days)} of leave from {$record->start_date->format('M j, Y')}.",
            route('admin.leave-requests.approval'),
            'ti ti-calendar-off',
            $employee->branch_id,
            employeeId: $employee->id,
        );

        return back()->with('status', $was === 'approved'
            ? 'Your approved leave was cancelled and '.$this->days((float) $record->days).' went back to your balance.'
            : 'Your leave request was withdrawn.');
    }

    /** This employee's own requests, whatever their role may browse elsewhere. */
    protected function mine(Employee $employee): Builder
    {
        return LeaveRequest::withoutGlobalScopes()->where('employee_id', $employee->id);
    }

    /** Active leave types of the employee's branch, plus company-wide ones. */
    protected function types(Employee $employee): Builder
    {
        return LeaveType::query()->where('company_id', $employee->company_id)->where('is_active', true)
            ->where(fn ($q) => $q->where('branch_id', $employee->branch_id)->orWhereNull('branch_id'));
    }

    /**
     * Days that can still be requested per leave type in a year: what is left, less what is already waiting for approval.
     * A type with no balance row isn't listed - HR hasn't set an allowance, so nothing is checked.
     *
     * @return array<int, float>
     */
    protected function available(Employee $employee, int $year): array
    {
        $pending = $this->mine($employee)->where('status', 'pending')->whereYear('start_date', $year)
            ->selectRaw('leave_type_id, sum(days) as days')->groupBy('leave_type_id')->pluck('days', 'leave_type_id');

        return LeaveBalance::withoutGlobalScopes()->where('employee_id', $employee->id)->where('year', $year)->get()
            ->mapWithKeys(fn (LeaveBalance $b) => [$b->leave_type_id => max(0, (float) $b->remaining - (float) ($pending[$b->leave_type_id] ?? 0))])->all();
    }

    protected function days(float $days): string
    {
        return rtrim(rtrim(number_format($days, 1), '0'), '.').' '.($days === 1.0 ? 'day' : 'days');
    }
}
