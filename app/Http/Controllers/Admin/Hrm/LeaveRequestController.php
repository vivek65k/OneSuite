<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Support\ApprovalFlow;
use App\Support\BranchContext;
use App\Support\LeaveApproval;
use App\Support\LeavePolicy;
use App\Support\Notifier;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    use PersistsTableState;

    public function apply(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $state = $this->resolveTableState($request, 'leave-requests', ['per_page' => 25]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $requests = $this->filteredQuery($state)
            ->with(['employee.department', 'leaveType', 'reviewedBy'])
            ->orderByDesc('start_date')
            ->paginate($perPage)
            ->withQueryString();

        // Status-tab counts ignore the status filter itself.
        $statusCounts = $this->filteredQuery(array_diff_key($state, ['status' => true]))
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count);

        $today = now()->toDateString();
        $base = $this->filteredQuery([]);
        $kpis = [
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'on_leave_today' => (clone $base)->where('status', 'approved')->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->count(),
            'upcoming' => (clone $base)->where('status', 'approved')->whereDate('start_date', '>', $today)->whereDate('start_date', '<=', now()->addDays(30)->toDateString())->count(),
            'days_this_year' => (float) (clone $base)->where('status', 'approved')->whereYear('start_date', now()->year)->sum('days'),
        ];

        $employees = $this->scopedEmployees()->orderBy('name')->get(['id', 'name', 'employee_code', 'branch_id']);
        $types = $this->scopedLeaveTypes()->where('is_active', true)->orderBy('name')->get();

        return view('admin.hrm.leave.apply', [
            'requests' => $requests,
            'state' => $state,
            'statusCounts' => $statusCounts->put('all', (int) $statusCounts->sum()),
            'kpis' => $kpis,
            'employees' => $employees->pluck('name', 'id'),
            'leaveTypes' => $types->pluck('name', 'id'),
            // For the apply form: which types each employee may use (same
            // branch), current-year balances, and dates already booked.
            'formEmployees' => $employees,
            'formTypes' => $types,
            'balances' => $this->balanceMap($employees->pluck('id')),
            'booked' => $this->bookedMap($employees->pluck('id')),
            // Leave policy per branch so the form counts days like the server does.
            'policies' => $employees->pluck('branch_id')->unique()->mapWithKeys(fn ($branchId) => [
                (string) ($branchId ?? '') => LeavePolicy::for(Company::current()->id, $branchId)->toClient(),
            ]),
        ]);
    }

    public function approval(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $state = $this->resolveTableState($request, 'leave-approval', ['per_page' => 25]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $perPage = $this->clampPerPage($state['per_page'] ?? 25);
        $today = now()->toDateString();

        $pendingQuery = fn (array $state) => $this->filteredQuery($state)
            ->where('status', 'pending')
            ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $departmentId)))
            ->when($state['window'] ?? null, fn ($q, $window) => match ($window) {
                'urgent' => $q->whereDate('start_date', '<=', now()->addDays(7)->toDateString()),
                'past' => $q->whereDate('start_date', '<', $today),
                'later' => $q->whereDate('start_date', '>', now()->addDays(7)->toDateString()),
                default => $q,
            });

        $requests = $pendingQuery($state)
            ->with(['employee.department', 'leaveType'])
            ->when(($state['sort'] ?? 'start') === 'oldest', fn ($q) => $q->orderBy('created_at'), fn ($q) => $q->orderBy('start_date'))
            ->paginate($perPage)
            ->withQueryString();

        // Balance for each request's type/year, and colleagues in the same
        // department already off (approved) or asking (pending) on those days.
        $balances = LeaveBalance::query()
            ->whereIn('employee_id', $requests->pluck('employee_id'))
            ->get()
            ->keyBy(fn ($b) => $b->employee_id.'-'.$b->leave_type_id.'-'.$b->year);

        $clashes = [];
        foreach ($requests as $leaveRequest) {
            $departmentId = $leaveRequest->employee?->department_id;
            $clashes[$leaveRequest->id] = $departmentId
                ? LeaveRequest::query()
                    ->where('company_id', $leaveRequest->company_id)
                    ->whereKeyNot($leaveRequest->id)
                    ->where('employee_id', '!=', $leaveRequest->employee_id)
                    ->whereIn('status', ['approved', 'pending'])
                    ->whereDate('start_date', '<=', $leaveRequest->end_date)
                    ->whereDate('end_date', '>=', $leaveRequest->start_date)
                    ->whereHas('employee', fn ($q) => $q->where('department_id', $departmentId))
                    ->with('employee:id,name')
                    ->get(['id', 'employee_id', 'status', 'start_date', 'end_date'])
                : collect();
        }

        $all = $pendingQuery([]);
        $kpis = [
            'pending' => (clone $all)->count(),
            'urgent' => (clone $all)->whereDate('start_date', '<=', now()->addDays(7)->toDateString())->count(),
            'days' => (float) (clone $all)->sum('days'),
            'oldest' => (clone $all)->min('created_at'),
        ];

        return view('admin.hrm.leave.approval', [
            'requests' => $requests,
            'state' => $state,
            'kpis' => $kpis,
            'balances' => $balances,
            'clashes' => $clashes,
            'employees' => $this->employeeOptions(),
            'leaveTypes' => $this->scopedLeaveTypes()->orderBy('name')->pluck('name', 'id'),
            'departments' => \App\Models\Department::query()
                ->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ]);
    }

    /**
     * Approve several pending requests at once. Each goes through the same
     * checks as a single approval; anything not reviewable is skipped.
     */
    public function bulkApprove(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']])['ids'];

        $approved = 0;
        $skipped = 0;
        $forwarded = 0;
        foreach (LeaveRequest::whereKey($ids)->with('employee.user')->get() as $leaveRequest) {
            $reviewable = (int) $leaveRequest->company_id === (int) Company::current()->id
                && $leaveRequest->status === 'pending'
                && ! (BranchContext::isRestricted() && (int) $leaveRequest->employee?->branch_id !== (int) BranchContext::currentBranchId())
                && $request->user()->can('update', $leaveRequest)
                // Never your own request (same rule as denyOwnRequest() on the single approve).
                && ($request->user()->is_super_admin || (int) $leaveRequest->employee?->user_id !== (int) $request->user()->id);

            if (! $reviewable) {
                $skipped++;

                continue;
            }

            // Already approved at this manager's level - nothing for them to add.
            if (! ApprovalFlow::awaits($request->user(), $leaveRequest)) {
                $skipped++;

                continue;
            }

            // Moved up a level rather than settled: counted as handled, not as approved.
            if (ApprovalFlow::forward($leaveRequest)) {
                $forwarded++;

                continue;
            }

            if (! LeaveApproval::approve($leaveRequest)) {
                $skipped++;

                continue;
            }
            $approved++;
        }
        $skipped += count(array_unique($ids)) - $approved - $forwarded - $skipped;

        return back()->with('status', "Approved {$approved} leave ".($approved === 1 ? 'request' : 'requests').'.'.($forwarded ? " {$forwarded} moved to the next approval level." : '').($skipped ? " Skipped {$skipped} that were no longer pending." : ''));
    }

    public function calendar(Request $request): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month')) ? $request->query('month') : now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $state = $request->only(['search', 'department_id', 'leave_type_id', 'pending', 'view']);
        $statuses = ($state['pending'] ?? null) ? ['approved', 'pending'] : ['approved'];

        $requests = LeaveRequest::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())))
            ->whereIn('status', $statuses)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->when($state['leave_type_id'] ?? null, fn ($q, $typeId) => $q->where('leave_type_id', $typeId))
            ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $departmentId)))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->whereHas('employee', fn ($eq) => $eq->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%");
            })))
            ->with(['employee.department', 'leaveType'])
            ->orderBy('start_date')
            ->get();

        // Who is away on each day of the month (clipped to the month).
        $byDay = [];
        foreach ($requests as $leaveRequest) {
            $from = $leaveRequest->start_date->copy()->max($start);
            $to = $leaveRequest->end_date->copy()->min($end);
            for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
                $byDay[$day->toDateString()][] = $leaveRequest;
            }
        }

        // Company holidays this month; recurring ones repeat on the same month/day every year.
        $holidays = \App\Models\Holiday::query()
            ->where('company_id', Company::current()->id)
            ->where(fn ($q) => $q->where(fn ($q2) => $q2->whereDate('date', '>=', $start)->whereDate('date', '<=', $end))
                ->orWhere(fn ($q2) => $q2->where('is_recurring', true)->whereMonth('date', $start->month)))
            ->get()
            ->mapWithKeys(fn ($h) => [$start->copy()->day(min((int) $h->date->format('j'), $start->daysInMonth))->toDateString() => $h->name]);

        $approved = $requests->where('status', 'approved');
        $today = now()->toDateString();
        $busiest = collect($byDay)->map(fn ($list) => collect($list)->where('status', 'approved')->pluck('employee_id')->unique()->count())->sortDesc();

        return view('admin.hrm.leave.calendar', [
            'month' => $month,
            'start' => $start,
            'end' => $end,
            'requests' => $requests,
            'byDay' => $byDay,
            'holidays' => $holidays,
            'state' => $state,
            'kpis' => [
                'today' => collect($byDay[$today] ?? [])->where('status', 'approved')->pluck('employee_id')->unique()->count(),
                'people' => $approved->pluck('employee_id')->unique()->count(),
                'days' => (float) $approved->sum(fn ($r) => $r->start_date->copy()->max($start)->diffInDays($r->end_date->copy()->min($end)) + 1),
                'busiest_day' => $busiest->keys()->first(),
                'busiest_count' => (int) $busiest->first(),
                'pending' => $requests->where('status', 'pending')->count(),
            ],
            'offToday' => collect($byDay[$today] ?? [])->unique('employee_id'),
            'leaveTypes' => $this->scopedLeaveTypes()->orderBy('name')->get(['id', 'name', 'code']),
            'departments' => \App\Models\Department::query()
                ->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LeaveRequest::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('company_id', $companyId)->where('is_active', true)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'half_day' => ['nullable', Rule::in(array_keys(LeaveRequest::HALF_DAYS))],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $leaveType = LeaveType::findOrFail($data['leave_type_id']);

        // Leave types are defined per branch - only the employee's own
        // branch's types (or company-wide ones) can be booked.
        if ($leaveType->branch_id && (int) $leaveType->branch_id !== (int) $employee->branch_id) {
            throw ValidationException::withMessages(['leave_type_id' => "{$leaveType->name} isn't available in {$employee->name}'s branch."]);
        }

        // No double-booking the same days.
        $overlap = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $data['end_date'])
            ->whereDate('end_date', '>=', $data['start_date'])
            ->with('leaveType')
            ->first();
        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => "{$employee->name} already has {$overlap->status} {$overlap->leaveType?->name} from {$overlap->start_date->format('M j')} to {$overlap->end_date->format('M j, Y')} overlapping these dates."]);
        }

        // Days are counted by the leave policy of the employee's branch
        // (weekends, holidays, sandwich rule, half days). HR isn't bound by notice limits.
        $breakdown = LeavePolicy::for($companyId, $employee->branch_id)
            ->check(Carbon::parse($data['start_date']), Carbon::parse($data['end_date']), $data['half_day'] ?? null, false);

        $data['company_id'] = $companyId;
        $data['days'] = $breakdown['days'];
        $data['half_day'] = $breakdown['days'] === 0.5 ? $data['half_day'] : null;

        $leaveRequest = LeaveRequest::create($data);

        Notifier::notifyUsersWithPermission(
            'update_leave_request',
            'New leave request',
            "{$leaveRequest->employee->name} requested {$leaveRequest->days} day(s) of leave.",
            route('admin.leave-requests.approval'),
            'ti ti-calendar-event',
            $leaveRequest->employee->branch_id,
            employeeId: $leaveRequest->employee->id,
        );

        return redirect()->route('admin.leave-requests.apply')->with('status', 'Leave request submitted.');
    }

    public function approve(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('update', $leaveRequest);
        $this->denyOwnRequest($leaveRequest->employee);

        // Multi-level approval: stop here while a manager further up still has to approve.
        if ($waiting = ApprovalFlow::forward($leaveRequest)) {
            return back()->with('status', $waiting);
        }
        $this->ensureReviewable($leaveRequest);

        abort_unless(LeaveApproval::approve($leaveRequest), 409, 'This leave request has already been reviewed.');

        return back()->with('status', 'Leave approved, balance updated, and attendance marked.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('update', $leaveRequest);
        $this->denyOwnRequest($leaveRequest->employee);
        ApprovalFlow::rejected($leaveRequest, $request->input('remarks'));
        $this->ensureReviewable($leaveRequest);

        abort_unless(LeaveApproval::reject($leaveRequest, $request->input('remarks')), 409, 'This leave request has already been reviewed.');

        return back()->with('status', 'Leave request rejected.');
    }

    public function destroy(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('delete', $leaveRequest);
        $this->ensureOwn($leaveRequest);

        // An approved request has already been deducted from the balance and
        // marked on attendance - deleting it would leave both out of sync.
        if ($leaveRequest->status === 'approved') {
            return back()->withErrors(['leave_request' => "Approved leave can't be deleted - it has already been deducted from {$leaveRequest->employee?->name}'s balance and marked on attendance."]);
        }

        $leaveRequest->delete();

        return back(fallback: route('admin.leave-requests.apply'))->with('status', 'Request removed.');
    }

    protected function filteredQuery(array $state): Builder
    {
        return LeaveRequest::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())))
            ->when($state['employee_id'] ?? null, fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($state['leave_type_id'] ?? null, fn ($q, $typeId) => $q->where('leave_type_id', $typeId))
            ->when($state['from'] ?? null, fn ($q, $from) => $q->whereDate('end_date', '>=', $from))
            ->when($state['to'] ?? null, fn ($q, $to) => $q->whereDate('start_date', '<=', $to))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->whereHas('employee', fn ($eq) => $eq->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%");
            })));
    }

    /** The request policy only checks permissions - also require the current company (and branch). */
    protected function ensureOwn(LeaveRequest $leaveRequest): void
    {
        abort_unless((int) $leaveRequest->company_id === (int) Company::current()->id, 404);
        abort_if(BranchContext::isRestricted() && (int) $leaveRequest->employee?->branch_id !== (int) BranchContext::currentBranchId(), 404);
    }

    /** Only pending requests can be approved/rejected - re-approving would deduct the balance twice. */
    protected function ensureReviewable(LeaveRequest $leaveRequest): void
    {
        $this->ensureOwn($leaveRequest);

        abort_if($leaveRequest->status !== 'pending', 409, 'This leave request has already been reviewed.');
    }

    protected function scopedEmployees(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->whereNotIn('status', ['terminated', 'inactive']);
    }

    protected function scopedLeaveTypes(): Builder
    {
        return LeaveType::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    /** @return array<int, array<int, array{remaining: float, allocated: float, used: float}>> employee => type => balance */
    protected function balanceMap($employeeIds): array
    {
        return LeaveBalance::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('year', now()->year)
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->mapWithKeys(fn (LeaveBalance $b) => [$b->leave_type_id => [
                'remaining' => (float) $b->remaining,
                'allocated' => (float) $b->allocated + (float) $b->carried_forward + (float) $b->adjusted,
                'used' => (float) $b->used,
            ]]))
            ->all();
    }

    /** Pending/approved date ranges per employee, so the form can warn about overlaps before submitting. */
    protected function bookedMap($employeeIds): array
    {
        return LeaveRequest::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('end_date', '>=', now()->subMonths(1)->toDateString())
            ->with('leaveType:id,name')
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->map(fn ($r) => [
                'from' => $r->start_date->toDateString(),
                'to' => $r->end_date->toDateString(),
                'status' => $r->status,
                'type' => $r->leaveType?->name,
            ])->values())
            ->all();
    }

    protected function employeeOptions()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
