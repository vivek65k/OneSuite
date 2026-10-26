<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveEncashment;
use App\Models\LeaveType;
use App\Support\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeaveEncashmentController extends Controller
{
    use PersistsTableState;

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', LeaveEncashment::class);

        $state = $this->resolveTableState($request, 'leave-encashments', ['per_page' => 25]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $requests = $this->filteredQuery($state)
            ->with(['employee.department', 'leaveType', 'reviewedBy'])
            ->orderByRaw("status = 'pending' DESC")
            ->orderByDesc('year')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $statusCounts = $this->filteredQuery(array_diff_key($state, ['status' => true]))
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count);

        $base = $this->filteredQuery([]);
        $year = now()->year;

        $employees = $this->scopedEmployees()->with(['salaries'])->orderBy('name')->get();
        $types = $this->scopedLeaveTypes()->where('is_active', true)->where('is_paid', true)->orderBy('name')->get();

        return view('admin.hrm.leave.encashments.index', [
            'requests' => $requests,
            'state' => $state,
            'statusCounts' => $statusCounts->put('all', (int) $statusCounts->sum()),
            'kpis' => [
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'pending_days' => (float) (clone $base)->where('status', 'pending')->sum('days'),
                'approved_days' => (float) (clone $base)->where('status', 'approved')->where('year', $year)->sum('days'),
                'approved_amount' => (float) (clone $base)->where('status', 'approved')->where('year', $year)->sum('amount'),
                'pending_amount' => (float) (clone $base)->where('status', 'pending')->sum('amount'),
            ],
            'currency' => Company::current()->currency,
            'rowBalances' => LeaveBalance::query()
                ->whereIn('employee_id', $requests->pluck('employee_id'))
                ->get()
                ->keyBy(fn ($b) => $b->employee_id.'-'.$b->leave_type_id.'-'.$b->year),
            'employees' => $employees->pluck('name', 'id'),
            'leaveTypes' => $this->scopedLeaveTypes()->orderBy('name')->pluck('name', 'id'),
            // For the request form: branch-matched paid types, encashable days
            // per employee/type for the year, and a salary-based rate hint.
            'formEmployees' => $employees,
            'formTypes' => $types,
            'available' => $this->availableMap($employees->pluck('id'), $year),
            'dailyRates' => $employees->mapWithKeys(fn (Employee $e) => [$e->id => ($s = $e->currentSalary()) ? round((float) $s->amount / 30, 2) : null])->filter(),
            'year' => $year,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LeaveEncashment::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('company_id', $companyId)],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'days' => ['required', 'numeric', 'min:0.5', 'max:365'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $type = LeaveType::findOrFail($data['leave_type_id']);

        if ($type->branch_id && (int) $type->branch_id !== (int) $employee->branch_id) {
            throw ValidationException::withMessages(['leave_type_id' => "{$type->name} isn't offered in {$employee->name}'s branch."]);
        }
        if (! $type->is_paid) {
            throw ValidationException::withMessages(['leave_type_id' => "{$type->name} is unpaid leave, so it can't be encashed."]);
        }

        // Can't encash more than is left, counting other pending encashments.
        $available = $this->availableDays($employee->id, $type->id, (int) $data['year']);
        if ((float) $data['days'] > $available) {
            throw ValidationException::withMessages(['days' => "{$employee->name} has only ".$this->fmt($available)." {$type->name} day(s) available to encash for {$data['year']}."]);
        }

        $data['company_id'] = $companyId;

        LeaveEncashment::create($data);

        return redirect()->route('admin.leave-encashments.index')->with('status', 'Encashment request submitted.');
    }

    public function approve(LeaveEncashment $leaveEncashment): RedirectResponse
    {
        $this->authorize('update', $leaveEncashment);
        $this->denyOwnRequest($leaveEncashment->employee);
        $this->ensureReviewable($leaveEncashment);

        // The balance may have been used since the request - re-check it.
        $available = $this->availableDays($leaveEncashment->employee_id, $leaveEncashment->leave_type_id, (int) $leaveEncashment->year, $leaveEncashment->id);
        if ((float) $leaveEncashment->days > $available) {
            return back()->withErrors(['encashment' => 'Can\'t approve: only '.$this->fmt($available).' day(s) are left in '.$leaveEncashment->employee?->name.'\'s '.$leaveEncashment->leaveType?->name.' balance, but '.$this->fmt($leaveEncashment->days).' were requested.']);
        }

        $leaveEncashment->update([
            'status' => 'approved',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        LeaveBalance::adjust(
            $leaveEncashment->employee_id, $leaveEncashment->company_id, $leaveEncashment->leave_type_id,
            $leaveEncashment->year, 'encashed', (float) $leaveEncashment->days
        );

        return back()->with('status', 'Encashment approved - '.$this->fmt($leaveEncashment->days).' day(s) deducted from the balance.');
    }

    public function reject(Request $request, LeaveEncashment $leaveEncashment): RedirectResponse
    {
        $this->authorize('update', $leaveEncashment);
        $this->denyOwnRequest($leaveEncashment->employee);
        $this->ensureReviewable($leaveEncashment);

        $leaveEncashment->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        return back()->with('status', 'Encashment request rejected.');
    }

    public function destroy(LeaveEncashment $leaveEncashment): RedirectResponse
    {
        $this->authorize('delete', $leaveEncashment);
        $this->ensureOwn($leaveEncashment);

        // Approval already deducted the days - deleting would leave the balance short.
        if ($leaveEncashment->status === 'approved') {
            return back()->withErrors(['encashment' => 'Approved encashments can\'t be deleted - the days have already been deducted from the balance.']);
        }

        $leaveEncashment->delete();

        return back(fallback: route('admin.leave-encashments.index'))->with('status', 'Request removed.');
    }

    protected function filteredQuery(array $state): Builder
    {
        return LeaveEncashment::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())))
            ->when($state['employee_id'] ?? null, fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($state['leave_type_id'] ?? null, fn ($q, $typeId) => $q->where('leave_type_id', $typeId))
            ->when($state['year'] ?? null, fn ($q, $year) => $q->where('year', $year))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->whereHas('employee', fn ($eq) => $eq->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%");
            })));
    }

    /** Remaining balance minus other pending encashments for the same employee/type/year. */
    protected function availableDays(int $employeeId, int $typeId, int $year, ?int $ignoreId = null): float
    {
        $balance = LeaveBalance::where('employee_id', $employeeId)->where('leave_type_id', $typeId)->where('year', $year)->first();
        $pending = LeaveEncashment::query()
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $typeId)
            ->where('year', $year)
            ->where('status', 'pending')
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->sum('days');

        return max(0.0, round((float) ($balance?->remaining ?? 0) - (float) $pending, 2));
    }

    /** @return array<int, array<int, float>> employee => type => encashable days this year */
    protected function availableMap($employeeIds, int $year): array
    {
        $pending = LeaveEncashment::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('year', $year)
            ->where('status', 'pending')
            ->toBase()
            ->selectRaw('employee_id, leave_type_id, SUM(days) as days')
            ->groupBy('employee_id', 'leave_type_id')
            ->get()
            ->keyBy(fn ($r) => $r->employee_id.'-'.$r->leave_type_id);

        $map = [];
        foreach (LeaveBalance::whereIn('employee_id', $employeeIds)->where('year', $year)->get() as $balance) {
            $held = (float) ($pending[$balance->employee_id.'-'.$balance->leave_type_id]->days ?? 0);
            $map[$balance->employee_id][$balance->leave_type_id] = max(0.0, round((float) $balance->remaining - $held, 2));
        }

        return $map;
    }

    /** The policy only checks permissions - also require the current company (and branch). */
    protected function ensureOwn(LeaveEncashment $leaveEncashment): void
    {
        abort_unless((int) $leaveEncashment->company_id === (int) Company::current()->id, 404);
        abort_if(BranchContext::isRestricted() && (int) $leaveEncashment->employee?->branch_id !== (int) BranchContext::currentBranchId(), 404);
    }

    /** Only pending requests can be reviewed - re-approving would deduct the days twice. */
    protected function ensureReviewable(LeaveEncashment $leaveEncashment): void
    {
        $this->ensureOwn($leaveEncashment);

        abort_if($leaveEncashment->status !== 'pending', 409, 'This encashment has already been reviewed.');
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

    protected function fmt($n): string
    {
        return rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    }
}
