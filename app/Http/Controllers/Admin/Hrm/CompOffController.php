<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\CompOff;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Support\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompOffController extends Controller
{
    use PersistsTableState;

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', CompOff::class);

        $state = $this->resolveTableState($request, 'comp-offs', ['per_page' => 25]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $requests = $this->filteredQuery($state)
            ->with(['employee.department', 'reviewedBy'])
            ->orderByRaw("status = 'pending' DESC")
            ->orderByDesc('worked_date')
            ->paginate($perPage)
            ->withQueryString();

        // Evidence for each claim: attendance on the worked day, and whether
        // that day was a weekend or a company holiday.
        $attendance = AttendanceRecord::query()
            ->whereIn('employee_id', $requests->pluck('employee_id'))
            ->where(function ($q) use ($requests) {
                foreach ($requests as $r) {
                    $q->orWhere(fn ($q2) => $q2->where('employee_id', $r->employee_id)->whereDate('date', $r->worked_date));
                }
            })
            ->get()
            ->keyBy(fn ($a) => $a->employee_id.'-'.$a->date->toDateString());

        $statusCounts = $this->filteredQuery(array_diff_key($state, ['status' => true]))
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count);

        $base = $this->filteredQuery([]);
        $compTypeIds = LeaveType::where('company_id', Company::current()->id)->where('code', 'COMP-OFF')->pluck('id');
        $compBalances = LeaveBalance::query()
            ->whereIn('leave_type_id', $compTypeIds)
            ->where('year', now()->year)
            ->get();

        return view('admin.hrm.leave.comp-offs.index', [
            'requests' => $requests,
            'state' => $state,
            'statusCounts' => $statusCounts->put('all', (int) $statusCounts->sum()),
            'kpis' => [
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'credited_month' => (clone $base)->where('status', 'approved')->where('reviewed_at', '>=', now()->startOfMonth())->count(),
                'credited_year' => (clone $base)->where('status', 'approved')->whereYear('worked_date', now()->year)->count(),
                'balance' => (float) $compBalances->sum(fn ($b) => max(0, (float) $b->remaining)),
                'holders' => $compBalances->filter(fn ($b) => (float) $b->remaining > 0)->count(),
            ],
            'attendance' => $attendance,
            'holidays' => $this->holidayMap($requests->pluck('worked_date')),
            'compBalances' => $compBalances->groupBy('employee_id')->map(fn ($rows) => (float) $rows->sum(fn ($b) => (float) $b->remaining)),
            'recentHolidays' => Holiday::query()
                ->where('company_id', Company::current()->id)
                ->whereDate('date', '<=', now())
                ->whereDate('date', '>=', now()->subDays(60))
                ->orderByDesc('date')
                ->limit(3)
                ->get(['name', 'date']),
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CompOff::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            // A comp off is earned by work already done - no future dates.
            'worked_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'worked_date.before_or_equal' => 'The worked date can\'t be in the future - comp offs are claimed after the extra day is worked.',
        ]);

        // One claim per worked day.
        $existing = CompOff::query()
            ->where('employee_id', $data['employee_id'])
            ->whereDate('worked_date', $data['worked_date'])
            ->whereIn('status', ['pending', 'approved'])
            ->first();
        if ($existing) {
            throw ValidationException::withMessages(['worked_date' => 'There is already a '.$existing->status.' comp off for '.Carbon::parse($data['worked_date'])->format('M j, Y').'.']);
        }

        $data['company_id'] = $companyId;

        CompOff::create($data);

        return redirect()->route('admin.comp-offs.index')->with('status', 'Comp off request submitted.');
    }

    public function approve(CompOff $compOff): RedirectResponse
    {
        $this->authorize('update', $compOff);
        $this->denyOwnRequest($compOff->employee);
        $this->ensureReviewable($compOff);

        $compOff->update([
            'status' => 'approved',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $leaveType = LeaveType::firstOrCreate(
            ['company_id' => $compOff->company_id, 'branch_id' => $compOff->employee->branch_id, 'code' => 'COMP-OFF'],
            ['name' => 'Comp Off', 'days_per_year' => 0, 'is_paid' => true, 'carry_forward' => true]
        );

        LeaveBalance::adjust($compOff->employee_id, $compOff->company_id, $leaveType->id, $compOff->worked_date->year, 'allocated', 1);

        return back()->with('status', 'Comp off approved - 1 day credited to '.$compOff->employee?->name."'s Comp Off balance.");
    }

    public function reject(Request $request, CompOff $compOff): RedirectResponse
    {
        $this->authorize('update', $compOff);
        $this->denyOwnRequest($compOff->employee);
        $this->ensureReviewable($compOff);

        $compOff->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        return back()->with('status', 'Comp off request rejected.');
    }

    public function destroy(CompOff $compOff): RedirectResponse
    {
        $this->authorize('delete', $compOff);
        $this->ensureOwn($compOff);

        // Approval already credited a day to the balance - deleting would leave it behind.
        if ($compOff->status === 'approved') {
            return back()->withErrors(['comp_off' => 'Approved comp offs can\'t be deleted - the day has already been credited to '.$compOff->employee?->name.'\'s balance.']);
        }

        $compOff->delete();

        return back(fallback: route('admin.comp-offs.index'))->with('status', 'Request removed.');
    }

    protected function filteredQuery(array $state): Builder
    {
        return CompOff::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())))
            ->when($state['employee_id'] ?? null, fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($state['from'] ?? null, fn ($q, $from) => $q->whereDate('worked_date', '>=', $from))
            ->when($state['to'] ?? null, fn ($q, $to) => $q->whereDate('worked_date', '<=', $to))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->whereHas('employee', fn ($eq) => $eq->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%");
            })));
    }

    /** The policy only checks permissions - also require the current company (and branch). */
    protected function ensureOwn(CompOff $compOff): void
    {
        abort_unless((int) $compOff->company_id === (int) Company::current()->id, 404);
        abort_if(BranchContext::isRestricted() && (int) $compOff->employee?->branch_id !== (int) BranchContext::currentBranchId(), 404);
    }

    /** Only pending claims can be reviewed - re-approving would credit the day twice. */
    protected function ensureReviewable(CompOff $compOff): void
    {
        $this->ensureOwn($compOff);

        abort_if($compOff->status !== 'pending', 409, 'This comp off has already been reviewed.');
    }

    /** @return array<string, string> Y-m-d => holiday name, for the given dates (recurring holidays included). */
    protected function holidayMap($dates): array
    {
        $holidays = Holiday::where('company_id', Company::current()->id)->get(['name', 'date', 'is_recurring']);
        $map = [];
        foreach ($dates as $date) {
            $hit = $holidays->first(fn ($h) => $h->date->isSameDay($date) || ($h->is_recurring && $h->date->format('m-d') === $date->format('m-d')));
            if ($hit) {
                $map[$date->toDateString()] = $hit->name;
            }
        }

        return $map;
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
