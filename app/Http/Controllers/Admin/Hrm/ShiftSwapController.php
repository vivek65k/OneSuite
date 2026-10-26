<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\ShiftRoster;
use App\Models\ShiftSwapRequest;
use App\Support\Attendance;
use App\Support\BranchContext;
use App\Support\DataScope;
use App\Support\ShiftTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShiftSwapController extends Controller
{
    use PersistsTableState;

    /** How far ahead the request form previews rosters. */
    protected const PREVIEW_DAYS = 60;

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', ShiftSwapRequest::class);

        $state = $this->resolveTableState($request, 'shift-swaps', ['per_page' => 25]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $requests = $this->filteredQuery($state)
            ->with(['employee.department:id,name', 'swapWithEmployee.department:id,name', 'reviewedBy:id,name'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('date')
            ->paginate($perPage)
            ->withQueryString();

        // Each side's shift on the requested day, as rostered right now.
        $ids = $requests->getCollection()->flatMap(fn ($r) => [$r->employee_id, $r->swap_with_employee_id])->unique();
        $dates = $requests->getCollection()->map(fn ($r) => $r->date->toDateString())->unique();
        $rostered = $dates->isEmpty() ? collect() : ShiftRoster::query()
            ->whereIn('employee_id', $ids)
            ->whereIn(DB::raw('DATE(date)'), $dates)
            ->with('shift')
            ->get()
            ->keyBy(fn ($r) => $r->employee_id.'|'.$r->date->toDateString());
        $leave = $dates->isEmpty() ? collect() : LeaveRequest::query()
            ->whereIn('employee_id', $ids)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $dates->max())
            ->whereDate('end_date', '>=', $dates->min())
            ->get(['employee_id', 'start_date', 'end_date']);

        $base = $this->filteredQuery(array_diff_key($state, ['status' => true]));
        $scoped = $this->filteredQuery([]);

        return view('admin.hrm.shift.swaps.index', [
            'requests' => $requests,
            'state' => $state,
            'rostered' => $rostered,
            'leave' => $leave,
            'employees' => $this->employeeQuery()->pluck('name', 'id'),
            'statusCounts' => (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')
                ->put('all', (clone $base)->count()),
            'kpis' => [
                'pending' => (clone $scoped)->where('status', 'pending')->count(),
                'pending_soon' => (clone $scoped)->where('status', 'pending')->whereDate('date', '>=', today())->whereDate('date', '<=', today()->addDays(7))->count(),
                'stale' => (clone $scoped)->where('status', 'pending')->whereDate('date', '<', today())->count(),
                'upcoming' => (clone $scoped)->where('status', 'approved')->whereDate('date', '>=', today())->count(),
                'approved_month' => (clone $scoped)->where('status', 'approved')->where('reviewed_at', '>=', now()->startOfMonth())->count(),
                'rejected_month' => (clone $scoped)->where('status', 'rejected')->where('reviewed_at', '>=', now()->startOfMonth())->count(),
            ],
        ] + ($request->user()->can('create', ShiftSwapRequest::class) ? $this->formData() : []));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ShiftSwapRequest::class);

        $companyId = Company::current()->id;
        $activeEmployee = Rule::exists('employees', 'id')
            ->where('company_id', $companyId)
            ->whereNotIn('status', ['terminated', 'inactive'])
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));

        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'different:swap_with_employee_id', $activeEmployee],
            'swap_with_employee_id' => ['required', 'integer', $activeEmployee],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'employee_id.different' => 'Pick two different employees.',
            'date.after_or_equal' => 'Swaps can only be requested for today or a later date.',
        ]);

        $date = Carbon::parse($data['date'])->toDateString();
        DataScope::unrestricted(fn () => $this->assertSwappable((int) $data['employee_id'], (int) $data['swap_with_employee_id'], $date));

        $duplicate = ShiftSwapRequest::query()
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->whereDate('date', $date)
            ->where(fn ($q) => $q->whereIn('employee_id', [$data['employee_id'], $data['swap_with_employee_id']])
                ->orWhereIn('swap_with_employee_id', [$data['employee_id'], $data['swap_with_employee_id']]))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['date' => 'One of them already has a pending swap on that day - review that first.']);
        }

        ShiftSwapRequest::create($data + ['company_id' => $companyId, 'status' => 'pending']);

        return redirect()->route('admin.shift-swaps.index', ['status' => 'pending', 'per_page' => 25])
            ->with('status', 'Shift swap request submitted.');
    }

    public function approve(ShiftSwapRequest $shiftSwap): RedirectResponse
    {
        $this->authorize('update', $shiftSwap);
        $this->denyOwnRequest($shiftSwap->employee);
        Attendance::ensureOwn($shiftSwap);
        $this->ensurePending($shiftSwap);

        $date = $shiftSwap->date->toDateString();

        // Re-check at approval time: rosters or leave may have changed since the request.
        try {
            // The colleague swapped with may be outside the reviewer's team (data access) -
            // checking and updating their roster is still part of this approval.
            DataScope::unrestricted(fn () => $this->assertSwappable($shiftSwap->employee_id, $shiftSwap->swap_with_employee_id, $date));
        } catch (ValidationException $e) {
            return back()->with('error', 'Cannot approve: '.collect($e->errors())->flatten()->first());
        }

        DataScope::unrestricted(fn () => DB::transaction(function () use ($shiftSwap, $date) {
            $requesterShiftId = ShiftRoster::where('employee_id', $shiftSwap->employee_id)->whereDate('date', $date)->value('shift_id');
            $targetShiftId = ShiftRoster::where('employee_id', $shiftSwap->swap_with_employee_id)->whereDate('date', $date)->value('shift_id');

            $targetShiftId
                ? ShiftRoster::upsertForDate($shiftSwap->employee_id, $shiftSwap->company_id, $date, (int) $targetShiftId)
                : ShiftRoster::clearForDate($shiftSwap->employee_id, $date);
            $requesterShiftId
                ? ShiftRoster::upsertForDate($shiftSwap->swap_with_employee_id, $shiftSwap->company_id, $date, (int) $requesterShiftId)
                : ShiftRoster::clearForDate($shiftSwap->swap_with_employee_id, $date);

            $shiftSwap->update([
                'status' => 'approved',
                'reviewed_by_user_id' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        }));

        return back()->with('status', "Swap approved - {$shiftSwap->employee?->name} and {$shiftSwap->swapWithEmployee?->name} have traded shifts on {$shiftSwap->date->format('M j')}.");
    }

    public function reject(Request $request, ShiftSwapRequest $shiftSwap): RedirectResponse
    {
        $this->authorize('update', $shiftSwap);
        $this->denyOwnRequest($shiftSwap->employee);
        Attendance::ensureOwn($shiftSwap);
        $this->ensurePending($shiftSwap);

        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        $shiftSwap->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'remarks' => $data['remarks'] ?? null,
        ]);

        return back()->with('status', 'Shift swap request rejected.');
    }

    public function destroy(ShiftSwapRequest $shiftSwap): RedirectResponse
    {
        $this->authorize('delete', $shiftSwap);
        Attendance::ensureOwn($shiftSwap);

        // An approved swap already changed the roster; keep it as the record of why.
        if ($shiftSwap->status === 'approved') {
            return back()->with('error', 'Approved swaps can\'t be deleted - they are the record of a roster change.');
        }

        $shiftSwap->delete();

        return back()->with('status', 'Request removed.');
    }

    protected function ensurePending(ShiftSwapRequest $shiftSwap): void
    {
        // Approving twice would swap the shifts straight back.
        abort_unless($shiftSwap->status === 'pending', 409, 'This request has already been reviewed.');
    }

    /**
     * Same branch, neither on approved leave, and there is actually something to swap.
     *
     * @throws ValidationException
     */
    protected function assertSwappable(int $employeeId, int $otherId, string $date): void
    {
        $people = Employee::whereKey([$employeeId, $otherId])->get(['id', 'name', 'branch_id'])->keyBy('id');
        [$a, $b] = [$people->get($employeeId), $people->get($otherId)];

        if (! $a || ! $b) {
            throw ValidationException::withMessages(['employee_id' => 'Employee not found.']);
        }
        if ((int) $a->branch_id !== (int) $b->branch_id) {
            throw ValidationException::withMessages(['swap_with_employee_id' => "{$a->name} and {$b->name} work in different branches, so their shifts can't be swapped."]);
        }

        $onLeave = LeaveRequest::query()
            ->whereIn('employee_id', [$employeeId, $otherId])
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->pluck('employee_id');
        if ($onLeave->isNotEmpty()) {
            throw ValidationException::withMessages(['date' => $people->only($onLeave->all())->pluck('name')->implode(' and ').' is on approved leave that day.']);
        }

        $shifts = ShiftRoster::whereIn('employee_id', [$employeeId, $otherId])->whereDate('date', $date)->pluck('shift_id', 'employee_id');
        if ($shifts->isEmpty()) {
            throw ValidationException::withMessages(['date' => 'Neither of them has a shift that day - there is nothing to swap.']);
        }
        if ($shifts->count() === 2 && (int) $shifts[$employeeId] === (int) $shifts[$otherId]) {
            throw ValidationException::withMessages(['date' => 'They already work the same shift that day.']);
        }
    }

    /** Employees, shifts, upcoming rosters and leave for the request form's live preview. */
    protected function formData(): array
    {
        $employees = $this->employeeQuery()
            ->whereNotIn('status', ['terminated', 'inactive'])
            ->with('department:id,name')
            ->get(['id', 'name', 'employee_code', 'branch_id', 'department_id']);
        $ids = $employees->pluck('id');
        $until = today()->addDays(self::PREVIEW_DAYS);

        $rosters = ShiftRoster::query()
            ->whereIn('employee_id', $ids)
            ->whereDate('date', '>=', today())->whereDate('date', '<=', $until)
            ->get(['employee_id', 'date', 'shift_id']);
        $shifts = Shift::whereIn('id', $rosters->pluck('shift_id')->unique())->get();

        $roster = [];
        foreach ($rosters as $r) {
            $roster[$r->employee_id][$r->date->toDateString()] = $r->shift_id;
        }
        $leave = [];
        LeaveRequest::query()
            ->whereIn('employee_id', $ids)
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', today())
            ->whereDate('start_date', '<=', $until)
            ->get(['employee_id', 'start_date', 'end_date'])
            ->each(function ($l) use (&$leave) {
                $leave[$l->employee_id][] = ['from' => $l->start_date->toDateString(), 'to' => $l->end_date->toDateString()];
            });

        return [
            'formEmployees' => $employees,
            'formPayload' => [
                'employees' => $employees->mapWithKeys(fn ($e) => [$e->id => ['name' => $e->name, 'branch_id' => $e->branch_id, 'dept' => $e->department?->name]]),
                'shifts' => $shifts->mapWithKeys(fn ($s) => [$s->id => [
                    'code' => $s->code,
                    'name' => $s->name,
                    'time' => ShiftTime::format($s->start_time).' – '.ShiftTime::format($s->end_time),
                    'tone' => ShiftTime::style($s)['tone'],
                ]]),
                'roster' => $roster,
                'leave' => $leave,
                'until' => $until->toDateString(),
            ],
        ];
    }

    protected function filteredQuery(array $state): Builder
    {
        return ShiftSwapRequest::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())))
            ->when($state['employee_id'] ?? null, fn ($q, $employeeId) => $q->where(fn ($q2) => $q2->where('employee_id', $employeeId)->orWhere('swap_with_employee_id', $employeeId)))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(fn ($q2) => $q2
                ->whereHas('employee', fn ($eq) => $eq->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%"))
                ->orWhereHas('swapWithEmployee', fn ($eq) => $eq->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%"))
                ->orWhere('reason', 'like', "%{$search}%")))
            ->when($this->validDate($state['from'] ?? null), fn ($q, $from) => $q->whereDate('date', '>=', $from))
            ->when($this->validDate($state['to'] ?? null), fn ($q, $to) => $q->whereDate('date', '<=', $to))
            ->when($state['status'] ?? null, fn ($q, $status) => $q->where('status', $status));
    }

    /** A Y-m-d string from the query, or null when it isn't a real date. */
    protected function validDate(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
    }

    protected function employeeQuery()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name');
    }
}
