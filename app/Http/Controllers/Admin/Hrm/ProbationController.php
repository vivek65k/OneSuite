<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ProbationRecord;
use App\Support\BranchContext;
use App\Support\Onboarding;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProbationController extends Controller
{
    use PersistsTableState;

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', ProbationRecord::class);

        $state = $this->resolveTableState($request, 'probation-records', [
            'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $records = $this->filteredQuery($state)
            ->with(['employee.department', 'reviewedBy'])
            ->orderByDesc('start_date')
            ->paginate($perPage)
            ->withQueryString();

        // Status-tab counts ignore the status filter itself (so every tab
        // shows what clicking it would return) but respect the rest.
        $statusCounts = $this->filteredQuery(array_diff_key($state, ['status' => true]))
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count);

        // Headline KPIs are company/branch-wide, unaffected by list filters.
        $kpis = [
            'active' => Onboarding::probationQuery()->whereIn('status', ['on_probation', 'extended'])->count(),
            'due_30' => Onboarding::reviewsDueQuery(now()->addDays(30))->count(),
            'overdue' => Onboarding::reviewsDueQuery(now()->subDay())->count(),
            'confirmed_year' => Onboarding::probationQuery()->where('status', 'confirmed')->where('reviewed_at', '>=', now()->startOfYear())->count(),
        ];

        return view('admin.hrm.onboarding.probation-records.index', [
            'records' => $records,
            'state' => $state,
            'statusCounts' => $statusCounts->put('all', (int) $statusCounts->sum()),
            'kpis' => $kpis,
            'employees' => $this->employeeOptions(),
        ]);
    }

    protected function filteredQuery(array $state): Builder
    {
        return Onboarding::probationQuery()
            ->when($state['employee_id'] ?? null, fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->whereHas('employee', fn ($q2) => $q2->where(function ($q3) use ($search) {
                $q3->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%");
            })))
            ->when($state['review'] ?? null, fn ($q, $window) => match ($window) {
                'overdue' => $q->whereIn('id', Onboarding::reviewsDueQuery(now()->subDay())->select('id')),
                'next_30' => $q->whereIn('id', Onboarding::reviewsDueQuery(now()->addDays(30))->select('id'))
                    ->whereNotIn('id', Onboarding::reviewsDueQuery(now()->subDay())->select('id')),
                default => $q,
            });
    }

    public function create(): View
    {
        $this->authorize('create', ProbationRecord::class);

        // ?employee_id= preselects the employee, only if it's one this user may pick.
        $record = new ProbationRecord();
        $employees = $this->employeeOptions();
        if ($employees->has((int) request('employee_id'))) {
            $record->employee_id = (int) request('employee_id');
        }

        return view('admin.hrm.onboarding.probation-records.create', [
            'record' => $record,
            'employees' => $employees,
            'joiningDates' => $this->joiningDates(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ProbationRecord::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        ProbationRecord::create($data);

        return redirect()->route('admin.probation-records.index')->with('status', 'Probation record added.');
    }

    public function edit(ProbationRecord $probationRecord): View
    {
        $this->authorize('update', $probationRecord);
        $this->ensureOwnRecord($probationRecord);

        return view('admin.hrm.onboarding.probation-records.edit', [
            'record' => $probationRecord,
            'employees' => $this->employeeOptions(),
            'joiningDates' => $this->joiningDates(),
        ]);
    }

    public function update(Request $request, ProbationRecord $probationRecord): RedirectResponse
    {
        $this->authorize('update', $probationRecord);
        $this->ensureOwnRecord($probationRecord);

        $data = $this->validated($request);

        if ($data['status'] !== $probationRecord->status && in_array($data['status'], ['confirmed', 'terminated'], true)) {
            $data['reviewed_by_user_id'] = Auth::id();
            $data['reviewed_at'] = now();
        }

        $probationRecord->update($data);

        return redirect()->route('admin.probation-records.index')->with('status', 'Probation record updated.');
    }

    /**
     * Quick Confirm / Terminate from the list's row menu. Extending needs a
     * new date, so that goes through the edit form instead.
     */
    public function updateStatus(Request $request, ProbationRecord $probationRecord): RedirectResponse
    {
        $this->authorize('update', $probationRecord);
        $this->ensureOwnRecord($probationRecord);

        $data = $request->validate(['status' => ['required', Rule::in(['confirmed', 'terminated', 'on_probation'])]]);

        $changes = ['status' => $data['status']];
        if (in_array($data['status'], ['confirmed', 'terminated'], true)) {
            $changes += ['reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now()];
        }
        $probationRecord->update($changes);

        return back()->with('status', "{$probationRecord->employee?->name}: probation ".strtolower(ProbationRecord::STATUSES[$data['status']]).'.');
    }

    public function destroy(ProbationRecord $probationRecord): RedirectResponse
    {
        $this->authorize('delete', $probationRecord);
        $this->ensureOwnRecord($probationRecord);

        $probationRecord->delete();

        return redirect()->route('admin.probation-records.index')->with('status', 'Probation record deleted.');
    }

    /** The policy only checks permissions - also require the current company (and branch). */
    protected function ensureOwnRecord(ProbationRecord $record): void
    {
        abort_unless((int) $record->company_id === (int) Company::current()->id, 404);
        abort_if(
            BranchContext::isRestricted() && (int) $record->employee?->branch_id !== (int) BranchContext::currentBranchId(),
            404
        );
    }

    protected function employeeOptions()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /** Joining date per employee id, so the form can start probation from it. */
    protected function joiningDates(): array
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->whereNotNull('joining_date')
            ->get(['id', 'joining_date'])
            ->mapWithKeys(fn ($employee) => [$employee->id => $employee->joining_date->format('Y-m-d')])
            ->all();
    }

    protected function validated(Request $request): array
    {
        $companyId = Company::current()->id;

        return $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(array_keys(ProbationRecord::STATUSES))],
            'extended_to' => ['nullable', 'date', 'after:end_date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
