<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\PersistsTableState;
use App\Http\Concerns\ReviewsAttendanceRequests;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\ShiftRoster;
use App\Support\ApprovalFlow;
use App\Support\Attendance;
use App\Support\BranchContext;
use App\Support\Overtime;
use App\Support\ShiftTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OvertimeRequestController extends Controller
{
    use PersistsTableState, ReviewsAttendanceRequests;

    /** Claimed hours may exceed what attendance shows by this much before it's flagged. */
    public const TOLERANCE_HOURS = 0.25;

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', OvertimeRequest::class);

        $state = $this->resolveTableState($request, 'overtime-requests', [
            'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $requests = $this->filteredQuery($state)
            ->with(['employee.department', 'reviewedBy'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('date')
            ->paginate($perPage)
            ->withQueryString();

        $scoped = Attendance::requests(OvertimeRequest::class);
        $approvedMonth = (clone $scoped)->where('status', 'approved')->whereDate('date', '>=', now()->startOfMonth())->with('employee:id,name,employee_code,branch_id,department_id')->get();
        $monthContext = $this->context($approvedMonth);

        return view('admin.hrm.attendance.overtime-requests.index', [
            'requests' => $requests,
            'context' => $this->context($requests->getCollection()),
            'statusCounts' => $this->requestStatusCounts($this->filteredQuery(array_diff_key($state, ['status' => true]))),
            'kpis' => $this->requestKpis(OvertimeRequest::class) + [
                'pending_hours' => (float) (clone $scoped)->where('status', 'pending')->sum('hours'),
                'approved_hours' => (float) $approvedMonth->sum('hours'),
                'paid_hours' => $approvedMonth->sum(fn ($r) => $monthContext[$r->id]['paid'] ?? (float) $r->hours),
                'people_month' => $approvedMonth->pluck('employee_id')->unique()->count(),
            ],
            'leaders' => $approvedMonth->groupBy('employee_id')
                ->map(fn ($rows) => ['employee' => $rows->first()->employee, 'hours' => (float) $rows->sum('hours'), 'count' => $rows->count()])
                ->sortByDesc('hours')->take(5)->values(),
            'state' => $state,
            'employees' => $this->employeeQuery()->pluck('name', 'id'),
            'formEmployees' => $this->employeeQuery()->whereNotIn('status', ['terminated', 'inactive'])->get(['id', 'name', 'employee_code']),
        ]);
    }

    /** Shift, attendance, leave and rule for one employee/day - feeds the request form preview. */
    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('create', OvertimeRequest::class);

        // Web routes redirect on validation errors; this endpoint is fetched by JS, so answer in JSON.
        $validator = validator($request->query(), [
            'employee_id' => ['required', 'integer', $this->employeeRule()],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();

        $employee = Employee::findOrFail($data['employee_id']);
        $probe = new OvertimeRequest(['employee_id' => $employee->id, 'date' => $data['date'], 'hours' => 0]);
        $probe->id = 0;
        $probe->setRelation('employee', $employee);
        $ctx = $this->context(collect([$probe]))[0];

        $existing = Attendance::requests(OvertimeRequest::class)
            ->where('employee_id', $employee->id)
            ->whereDate('date', $data['date'])
            ->whereIn('status', ['pending', 'approved'])
            ->first(['status', 'hours']);

        return response()->json([
            'shift' => $ctx['shift'] ? ['code' => $ctx['shift']->code, 'name' => $ctx['shift']->name, 'time' => ShiftTime::format($ctx['shift']->start_time).' – '.ShiftTime::format($ctx['shift']->end_time), 'net' => ShiftTime::netHours($ctx['shift'])] : null,
            'attendance' => $ctx['record'] ? [
                'status' => Attendance::label($ctx['record']->status),
                'in' => Attendance::formatTime($ctx['record']->check_in_time),
                'out' => Attendance::formatTime($ctx['record']->check_out_time),
                'worked' => $ctx['worked'],
            ] : null,
            'rule' => $ctx['rule'] ? ['name' => $ctx['rule']->name, 'threshold' => (float) $ctx['rule']->daily_threshold_hours, 'multiplier' => (float) $ctx['rule']->multiplier] : null,
            'baseline' => $ctx['baseline'],
            'extra' => $ctx['extra'],
            'leave' => $ctx['leave'],
            'existing' => $existing ? ['status' => $existing->status, 'hours' => (float) $existing->hours] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', OvertimeRequest::class);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', $this->employeeRule()],
            'date' => ['required', 'date', 'after_or_equal:'.today()->subDays(90)->toDateString(), 'before_or_equal:'.today()->addDays(30)->toDateString()],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:16'],
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'date.after_or_equal' => 'Overtime older than 90 days can\'t be claimed here.',
            'date.before_or_equal' => 'Overtime can be pre-approved up to 30 days ahead.',
            'hours.max' => 'More than 16 hours of overtime in one day isn\'t allowed.',
        ]);

        $date = Carbon::parse($data['date'])->toDateString();
        $duplicate = Attendance::requests(OvertimeRequest::class)
            ->where('employee_id', $data['employee_id'])
            ->whereDate('date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->first();
        if ($duplicate) {
            throw ValidationException::withMessages(['date' => "There is already a {$duplicate->status} overtime request for this day ({$this->fmt($duplicate->hours)} h)."]);
        }
        $onLeave = LeaveRequest::where('employee_id', $data['employee_id'])->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->exists();
        if ($onLeave) {
            throw ValidationException::withMessages(['date' => 'The employee is on approved leave that day.']);
        }

        OvertimeRequest::create($data + ['company_id' => Company::current()->id, 'status' => 'pending']);

        return redirect()->route('admin.overtime-requests.index', ['status' => 'pending', 'per_page' => 25])
            ->with('status', 'Overtime request submitted.');
    }

    public function approve(OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $this->authorize('update', $overtimeRequest);
        $this->denyOwnRequest($overtimeRequest->employee);

        // Multi-level approval: stop here while a manager further up still has to approve.
        if ($waiting = ApprovalFlow::forward($overtimeRequest)) {
            return back()->with('status', $waiting);
        }
        $this->ensureReviewable($overtimeRequest);

        $overtimeRequest->update([
            'status' => 'approved',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('status', "Approved {$this->fmt($overtimeRequest->hours)} h of overtime for {$overtimeRequest->employee?->name}.");
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $this->authorize('update', new OvertimeRequest());

        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']])['ids'];

        // Only this company's (and branch's) still-pending requests.
        $requests = Attendance::requests(OvertimeRequest::class)
            ->whereKey($ids)
            ->where('status', 'pending')
            // Never your own request (same rule as denyOwnRequest() on the single approve).
            ->when(! Auth::user()->is_super_admin, fn ($q) => $q->whereDoesntHave('employee', fn ($e) => $e->where('user_id', Auth::id())))
            ->get();

        $count = 0;
        $forwarded = 0;

        // One by one rather than a single UPDATE, so each request follows its approval levels.
        DB::transaction(function () use ($requests, &$count, &$forwarded) {
            foreach ($requests as $overtimeRequest) {
                if (ApprovalFlow::awaits(Auth::user(), $overtimeRequest) && ApprovalFlow::forward($overtimeRequest)) {
                    $forwarded++;

                    continue;
                }

                if (! ApprovalFlow::awaits(Auth::user(), $overtimeRequest)) {
                    continue;
                }

                $overtimeRequest->update(['status' => 'approved', 'reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now()]);
                $count++;
            }
        });

        $message = $count ? "Approved {$count} overtime ".($count === 1 ? 'request' : 'requests').'.' : '';
        $message .= $forwarded ? " {$forwarded} moved to the next approval level." : '';

        return back()->with('status', trim($message) ?: 'Nothing to approve - those requests were already reviewed.');
    }

    public function reject(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $this->authorize('update', $overtimeRequest);
        $this->denyOwnRequest($overtimeRequest->employee);
        ApprovalFlow::rejected($overtimeRequest, $request->input('remarks'));
        $this->ensureReviewable($overtimeRequest);

        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        $overtimeRequest->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'remarks' => $data['remarks'] ?? null,
        ]);

        return back()->with('status', 'Overtime request rejected.');
    }

    public function destroy(OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $this->authorize('delete', $overtimeRequest);
        Attendance::ensureOwn($overtimeRequest);

        // Approved hours are already in the monthly attendance report.
        if ($overtimeRequest->status === 'approved') {
            return back()->with('error', 'Approved overtime can\'t be deleted - it is already counted in the attendance report.');
        }

        $overtimeRequest->delete();

        return back(fallback: route('admin.overtime-requests.index'))->with('status', 'Request removed.');
    }

    /**
     * For each request: the shift rostered that day, the attendance record,
     * the overtime rule that applies, and how the claim compares.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function context(Collection $items): array
    {
        if ($items->isEmpty()) {
            return [];
        }

        $ids = $items->pluck('employee_id')->unique();
        $dates = $items->map(fn ($r) => Carbon::parse($r->date)->toDateString())->unique();
        $key = fn ($employeeId, $date) => $employeeId.'|'.$date;

        $rosters = ShiftRoster::whereIn('employee_id', $ids)->whereIn(DB::raw('DATE(date)'), $dates)->with('shift')->get()
            ->keyBy(fn ($r) => $key($r->employee_id, $r->date->toDateString()));
        $records = AttendanceRecord::whereIn('employee_id', $ids)->whereIn(DB::raw('DATE(date)'), $dates)->get()
            ->keyBy(fn ($r) => $key($r->employee_id, $r->date->toDateString()));
        $leave = LeaveRequest::whereIn('employee_id', $ids)->where('status', 'approved')
            ->whereDate('start_date', '<=', $dates->max())->whereDate('end_date', '>=', $dates->min())
            ->with('leaveType:id,code')->get();
        $rules = Overtime::activeRules();

        $out = [];
        foreach ($items as $item) {
            $date = Carbon::parse($item->date)->startOfDay();
            $k = $key($item->employee_id, $date->toDateString());
            $shift = $rosters->get($k)?->shift;
            $record = $records->get($k);
            $rule = Overtime::ruleFor($rules, $shift?->id, $item->employee?->branch_id);
            $worked = Overtime::worked($record);
            $baseline = $rule ? (float) $rule->daily_threshold_hours : ($shift ? ShiftTime::netHours($shift) : null);
            $extra = $worked !== null && $baseline !== null ? round(max(0, $worked - $baseline), 2) : null;
            $leaveRow = $leave->first(fn ($l) => (int) $l->employee_id === (int) $item->employee_id && $l->start_date->lte($date) && $l->end_date->gte($date));
            $hours = (float) $item->hours;

            $check = match (true) {
                (bool) $leaveRow => 'leave',
                $record?->status === 'absent' => 'absent',
                $date->isFuture() => 'future',
                ! $record => 'no_record',
                $extra === null => 'no_punch',
                $hours > $extra + self::TOLERANCE_HOURS => 'over',
                default => 'ok',
            };

            $out[$item->id] = [
                'shift' => $shift,
                'record' => $record,
                'rule' => $rule,
                'worked' => $worked,
                'baseline' => $baseline,
                'extra' => $extra,
                'leave' => $leaveRow ? ($leaveRow->leaveType?->code ?? 'Leave') : null,
                'check' => $check,
                'paid' => round($hours * (float) ($rule?->multiplier ?? 1), 2),
            ];
        }

        return $out;
    }

    protected function filteredQuery(array $state): Builder
    {
        foreach (['from', 'to'] as $field) {
            if (isset($state[$field]) && ! (is_string($state[$field]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $state[$field]) && strtotime($state[$field]))) {
                unset($state[$field]);
            }
        }

        return $this->applyRequestFilters(Attendance::requests(OvertimeRequest::class), $state, 'date');
    }

    protected function employeeRule()
    {
        return Rule::exists('employees', 'id')
            ->where('company_id', Company::current()->id)
            ->whereNotIn('status', ['terminated', 'inactive'])
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    protected function employeeQuery()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name');
    }

    protected function fmt($n): string
    {
        return rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    }
}
