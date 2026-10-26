<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LeaveType::class);

        $state = $request->only(['search', 'status', 'paid']);
        $all = $this->scopedQuery()->orderBy('name')->get();

        // Usage this year, per leave type: requests (approved/pending) and
        // days taken, plus how many employees hold a balance.
        $year = now()->year;
        $ids = $all->pluck('id');
        $requestStats = LeaveRequest::query()
            ->whereIn('leave_type_id', $ids)
            ->whereYear('start_date', $year)
            ->toBase()
            ->selectRaw('leave_type_id, COUNT(*) as requests')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'approved' THEN days ELSE 0 END) as days_taken")
            ->groupBy('leave_type_id')
            ->get()
            ->keyBy('leave_type_id');
        $balanceHolders = LeaveBalance::query()
            ->whereIn('leave_type_id', $ids)
            ->where('year', $year)
            ->toBase()
            ->selectRaw('leave_type_id, COUNT(DISTINCT employee_id) as employees')
            ->groupBy('leave_type_id')
            ->pluck('employees', 'leave_type_id');

        $leaveTypes = $all
            ->when($state['search'] ?? null, fn ($c, $search) => $c->filter(fn ($t) => str_contains(mb_strtolower($t->name.' '.$t->code), mb_strtolower($search))))
            ->when(($state['status'] ?? null) === 'active', fn ($c) => $c->where('is_active', true))
            ->when(($state['status'] ?? null) === 'inactive', fn ($c) => $c->where('is_active', false))
            ->when(($state['paid'] ?? null) === 'paid', fn ($c) => $c->where('is_paid', true))
            ->when(($state['paid'] ?? null) === 'unpaid', fn ($c) => $c->where('is_paid', false))
            ->values();

        $active = $all->where('is_active', true);

        return view('admin.hrm.leave.leave-types.index', [
            'leaveTypes' => $leaveTypes,
            'state' => $state,
            'counts' => [
                'all' => $all->count(),
                'active' => $active->count(),
                'inactive' => $all->count() - $active->count(),
            ],
            'kpis' => [
                'active' => $active->count(),
                'paid_days' => (float) $active->where('is_paid', true)->sum('days_per_year'),
                'carry_forward' => $active->where('carry_forward', true)->count(),
                'requests' => (int) $requestStats->sum('requests'),
                'pending' => (int) $requestStats->sum('pending'),
            ],
            'requestStats' => $requestStats,
            'balanceHolders' => $balanceHolders,
            // Branch names only matter when this user sees several branches.
            'branchNames' => BranchContext::isRestricted() ? collect() : Branch::query()->where('company_id', Company::current()->id)->pluck('name', 'id'),
            'year' => $year,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', LeaveType::class);

        return view('admin.hrm.leave.leave-types.create', [
            'leaveType' => new LeaveType(),
            'existingCodes' => $this->scopedQuery()->pluck('code')->map(fn ($c) => strtoupper($c))->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LeaveType::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;
        $data['branch_id'] = BranchContext::currentBranchId();

        LeaveType::create($data);

        return redirect()->route('admin.leave-types.index')->with('status', 'Leave type added.');
    }

    public function edit(LeaveType $leaveType): View
    {
        $this->authorize('update', $leaveType);
        $this->ensureOwn($leaveType);

        return view('admin.hrm.leave.leave-types.edit', [
            'leaveType' => $leaveType,
            'existingCodes' => $this->scopedQuery()->whereKeyNot($leaveType->id)->pluck('code')->map(fn ($c) => strtoupper($c))->values(),
            'usage' => $this->usage($leaveType),
        ]);
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('update', $leaveType);
        $this->ensureOwn($leaveType);

        $leaveType->update($this->validated($request, $leaveType));

        return redirect()->route('admin.leave-types.index')->with('status', 'Leave type updated.');
    }

    /** One-click activate / deactivate from the card. */
    public function toggle(LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('update', $leaveType);
        $this->ensureOwn($leaveType);

        $leaveType->update(['is_active' => ! $leaveType->is_active]);

        return back()->with('status', "{$leaveType->name} ".($leaveType->is_active ? 'activated.' : 'deactivated - it can no longer be applied for.'));
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('delete', $leaveType);
        $this->ensureOwn($leaveType);

        // leave_requests / leave_balances cascade on delete - deleting a type
        // that's been used would silently wipe that leave history.
        $usage = $this->usage($leaveType);
        if ($usage['requests'] || $usage['balances']) {
            return back()->withErrors([
                'leave_type' => "{$leaveType->name} can't be deleted: it has {$usage['requests']} leave ".($usage['requests'] === 1 ? 'request' : 'requests')
                    ." and {$usage['balances']} ".($usage['balances'] === 1 ? 'balance' : 'balances').' that would be erased. Deactivate it instead.',
            ]);
        }

        $leaveType->delete();

        return redirect()->route('admin.leave-types.index')->with('status', 'Leave type deleted.');
    }

    protected function scopedQuery()
    {
        return LeaveType::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    /** @return array{requests: int, balances: int} */
    protected function usage(LeaveType $leaveType): array
    {
        return [
            'requests' => LeaveRequest::where('leave_type_id', $leaveType->id)->count(),
            'balances' => LeaveBalance::where('leave_type_id', $leaveType->id)->count(),
        ];
    }

    /** The policy only checks permissions - also require the current company (and branch). */
    protected function ensureOwn(LeaveType $leaveType): void
    {
        abort_unless((int) $leaveType->company_id === (int) Company::current()->id, 404);
        abort_if(BranchContext::isRestricted() && (int) $leaveType->branch_id !== (int) BranchContext::currentBranchId(), 404);
    }

    protected function validated(Request $request, ?LeaveType $leaveType = null): array
    {
        $companyId = Company::current()->id;

        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('leave_types', 'code')->where('company_id', $companyId)->where('branch_id', $leaveType?->branch_id ?? BranchContext::currentBranchId())->ignore($leaveType?->id),
            ],
            'days_per_year' => ['required', 'numeric', 'min:0', 'max:365'],
            'max_carry_forward_days' => ['nullable', 'numeric', 'min:0', 'max:365'],
        ]);

        $data['is_paid'] = $request->boolean('is_paid');
        $data['carry_forward'] = $request->boolean('carry_forward');
        $data['is_active'] = $request->boolean('is_active');

        // A carry-forward cap is meaningless without carry forward.
        if (! $data['carry_forward']) {
            $data['max_carry_forward_days'] = null;
        }

        return $data;
    }
}
