<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\FinalSettlement;
use App\Models\Resignation;
use App\Models\SeparationClearance;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResignationController extends Controller
{
    protected const TABS = ['pending', 'accepted', 'completed', 'withdrawn'];

    /** Employees who have already left can't resign again. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** A resignation can be entered this many days after it was handed in (late paperwork). */
    protected const DAYS_BACK = 90;

    /** The longest notice anyone serves, in days. */
    protected const MAX_NOTICE = 365;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Resignation::class);

        // Anything that isn't a plain value (e.g. ?status[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        // The old page filtered with ?status=; those links keep working.
        $tab = $text('tab') !== '' ? $text('tab') : $text('status');
        $state = [
            'tab' => in_array($tab, self::TABS, true) ? $tab : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('reason', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });

        $resignations = $filtered()
            ->when($state['tab'], fn ($q, $status) => $q->where('status', $status))
            ->with(['employee.department:id,name', 'reviewedBy:id,name', 'clearances:id,resignation_id,department,is_cleared', 'exitInterview:id,resignation_id', 'finalSettlement:id,resignation_id,status'])
            // What still needs doing comes first: pending, then serving notice by who leaves soonest.
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'accepted' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN status IN ('pending', 'accepted') THEN last_working_day END")
            ->orderByDesc('resignation_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'employee_id', 'status', 'resignation_date', 'last_working_day']);
        $serving = $all->where('status', 'accepted');
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'department_id', 'designation', 'status', 'joining_date', 'user_id']);
        $open = $all->whereIn('status', ['pending', 'accepted'])->pluck('employee_id')->flip();
        $user = $request->user();

        return view('admin.hrm.separation.resignations.index', [
            'resignations' => $resignations,
            'state' => $state,
            'employees' => $people->whereIn('id', $all->pluck('employee_id')->unique())->pluck('name', 'id'),
            // Only people still employed and without a resignation already under way.
            'formPeople' => $people->whereNotIn('status', self::INACTIVE)->reject(fn (Employee $e) => $open->has($e->id))->values(),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            // Company property each leaver on this page still holds.
            'assetsHeld' => EmployeeAsset::query()->where('company_id', Company::current()->id)->where('status', 'assigned')
                ->whereIn('employee_id', $resignations->getCollection()->pluck('employee_id')->unique())
                ->selectRaw('employee_id, COUNT(*) as total')->groupBy('employee_id')->pluck('total', 'employee_id'),
            'counts' => ['all' => $filtered()->count()] + collect(self::TABS)->mapWithKeys(fn ($t) => [$t => $filtered()->where('status', $t)->count()])->all(),
            'kpis' => [
                'pending' => $all->where('status', 'pending')->count(),
                'serving' => $serving->count(),
                'next' => $serving->filter(fn (Resignation $r) => $r->last_working_day->gte(today()))->min('last_working_day'),
                'month' => $serving->filter(fn (Resignation $r) => $r->last_working_day->gte(today()) && $r->last_working_day->lte(today()->addDays(30)))->count(),
                // Last day has passed but the separation was never closed: the person still counts as an employee.
                'overdue' => $serving->filter(fn (Resignation $r) => $r->last_working_day->lt(today()))->count(),
                'left_year' => $all->where('status', 'completed')->filter(fn (Resignation $r) => $r->last_working_day->year === today()->year)->count(),
                'staff' => $people->whereNotIn('status', self::INACTIVE)->count(),
            ],
            'ownEmployeeId' => $user->is_super_admin ? null : $people->firstWhere('user_id', $user->id)?->id,
            'canCreate' => $user->can('create', Resignation::class),
            'canReview' => $user->can('update', new Resignation),
            'limits' => ['min' => today()->subDays(self::DAYS_BACK)->toDateString(), 'max' => today()->toDateString(), 'notice' => self::MAX_NOTICE],
        ]);
    }

    /**
     * Pending-only queue, mirroring the leave-requests/travel-requests
     * ".approval" convention - a separate URL from the full history list,
     * reusing the same accept()/withdraw() actions as approve/reject.
     */
    public function approval(): View
    {
        $this->authorize('viewAny', Resignation::class);

        $resignations = $this->scoped()->where('status', 'pending')->with('employee.department')->orderBy('resignation_date')->get();

        return view('admin.hrm.approvals.resignation-approval', ['resignations' => $resignations]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Resignation::class);

        $employeeId = $request->input('employee_id');
        $employee = is_scalar($employeeId) && ctype_digit((string) $employeeId)
            ? $this->employeeQuery()->whereNotIn('status', self::INACTIVE)->whereKey((int) $employeeId)->first(['id', 'name', 'joining_date'])
            : null;

        if (! $employee) {
            throw ValidationException::withMessages(['employee_id' => 'Choose a current employee from the list.']);
        }

        // One resignation at a time: a second would start a second exit process for the same person.
        $existing = Resignation::withoutGlobalScopes()->where('employee_id', $employee->id)->whereIn('status', ['pending', 'accepted'])->first();

        if ($existing) {
            throw ValidationException::withMessages(['employee_id' => $employee->name.' already has a resignation '.($existing->status === 'accepted' ? 'accepted (serving notice)' : 'waiting for a decision').'.']);
        }

        $data = $this->validated($request, $employee);

        Resignation::create($data + ['company_id' => Company::current()->id, 'employee_id' => $employee->id, 'status' => 'pending']);

        return back()->with('status', 'Resignation of '.$employee->name.' recorded, last working day '.Carbon::parse($data['last_working_day'])->format('M j, Y').'. It is waiting to be accepted.');
    }

    /** Correct a resignation that hasn't been decided yet. */
    public function update(Request $request, Resignation $resignation): RedirectResponse
    {
        $this->ensureOwn($resignation);
        $this->authorize('update', $resignation);

        if ($resignation->status !== 'pending') {
            return back()->withErrors(['resignation' => 'This resignation is already '.$resignation->status.'. '.($resignation->status === 'accepted' ? 'Change the last working day on the Notice Period page.' : 'It can\'t be changed.')]);
        }

        $resignation->update($this->validated($request, $resignation->employee, $resignation));

        return back()->with('status', 'Resignation updated.');
    }

    public function accept(Resignation $resignation): RedirectResponse
    {
        $this->ensureOwn($resignation);
        $this->authorize('update', $resignation);
        $this->denyOwnRequest($resignation->employee);

        DB::transaction(function () use ($resignation) {
            $this->lock($resignation, ['pending'], 'accepted');

            $resignation->update(['status' => 'accepted', 'reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now()]);

            foreach (array_keys(SeparationClearance::DEPARTMENTS) as $department) {
                SeparationClearance::firstOrCreate(
                    ['resignation_id' => $resignation->id, 'department' => $department],
                    ['company_id' => $resignation->company_id, 'employee_id' => $resignation->employee_id]
                );
            }
        });

        return back()->with('status', 'Resignation of '.($resignation->employee?->name ?? 'the employee').' accepted. They are serving notice until '.$resignation->last_working_day->format('M j, Y').', and the clearance checklist has been created.');
    }

    public function withdraw(Request $request, Resignation $resignation): RedirectResponse
    {
        $this->ensureOwn($resignation);
        $this->authorize('update', $resignation);

        $remarks = is_scalar($request->input('remarks')) && trim((string) $request->input('remarks')) !== '' ? mb_substr(trim((string) $request->input('remarks')), 0, 1000) : null;

        DB::transaction(function () use ($resignation, $remarks) {
            $this->lock($resignation, ['pending', 'accepted'], 'withdrawn');

            // Once the final dues have been worked out or paid, the exit can't simply be called off.
            $settlement = FinalSettlement::withoutGlobalScopes()->where('resignation_id', $resignation->id)->whereIn('status', ['processed', 'paid'])->first();

            if ($settlement) {
                throw ValidationException::withMessages(['resignation' => 'The final settlement for this resignation has already been '.$settlement->status.', so it can\'t be withdrawn.']);
            }

            $resignation->update(['status' => 'withdrawn', 'reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now(), 'remarks' => $remarks]);
        });

        return back()->with('status', 'Resignation of '.($resignation->employee?->name ?? 'the employee').' withdrawn. They stay with the company.');
    }

    /** Close the exit: the person has left. This ends their employment record and switches off their login. */
    public function complete(Resignation $resignation): RedirectResponse
    {
        $this->ensureOwn($resignation);
        $this->authorize('update', $resignation);
        $this->denyOwnRequest($resignation->employee);

        $loginOff = DB::transaction(function () use ($resignation) {
            $this->lock($resignation, ['accepted'], 'completed');

            if ($resignation->last_working_day->gt(today())) {
                throw ValidationException::withMessages(['resignation' => 'The last working day is '.$resignation->last_working_day->format('M j, Y').'. The separation can be completed from that day. To release them earlier, change the last working day on the Notice Period page.']);
            }

            $open = SeparationClearance::withoutGlobalScopes()->where('resignation_id', $resignation->id)->where('is_cleared', false)->pluck('department')
                ->map(fn ($department) => SeparationClearance::DEPARTMENTS[$department] ?? $department);

            if ($open->isNotEmpty()) {
                throw ValidationException::withMessages(['resignation' => 'Clearance is still open for '.$open->implode(', ').'. Finish it on the Clearance page before completing the separation.']);
            }

            $resignation->update(['status' => 'completed']);

            $employee = Employee::withoutGlobalScopes()->whereKey($resignation->employee_id)->first();
            $employee?->update(['status' => 'terminated']);

            // A former employee must not keep access to the system. Administrators are never locked out this way.
            $login = $employee?->user_id ? User::query()->whereKey($employee->user_id)->first() : null;

            if ($login && ! $login->is_super_admin && ! $login->is_platform_admin && $login->is_active) {
                $login->forceFill(['is_active' => false])->save();

                return true;
            }

            return false;
        });

        return back()->with('status', 'Separation completed. '.($resignation->employee?->name ?? 'The employee').' is marked as having left'.($loginOff ? ' and their login has been switched off' : '').'.');
    }

    public function destroy(Resignation $resignation): RedirectResponse
    {
        $this->ensureOwn($resignation);
        $this->authorize('delete', $resignation);

        if ($resignation->status !== 'pending') {
            return back()->withErrors(['resignation' => 'Only a resignation that is still pending can be removed. This one is '.$resignation->status.'; it stays on record.']);
        }

        $resignation->delete();

        return back()->with('status', 'Resignation removed.');
    }

    protected function validated(Request $request, ?Employee $employee, ?Resignation $existing = null): array
    {
        $data = $request->validate([
            'resignation_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.today()->subDays(self::DAYS_BACK)->toDateString()],
            'notice_period_days' => ['required', 'integer', 'min:0', 'max:'.self::MAX_NOTICE],
            'last_working_day' => ['required', 'date', 'after_or_equal:resignation_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ], [
            'resignation_date.before_or_equal' => 'The resignation date can\'t be in the future.',
            'resignation_date.after_or_equal' => 'A resignation can be entered at most '.self::DAYS_BACK.' days after it was handed in.',
            'last_working_day.after_or_equal' => 'The last working day can\'t be before the resignation date.',
        ]);

        $resigned = Carbon::parse($data['resignation_date'])->startOfDay();
        $lastDay = Carbon::parse($data['last_working_day'])->startOfDay();

        if ($employee?->joining_date && $resigned->lt($employee->joining_date)) {
            throw ValidationException::withMessages(['resignation_date' => $employee->name.' joined on '.$employee->joining_date->format('M j, Y').', so they can\'t have resigned before that.']);
        }
        if ($resigned->diffInDays($lastDay) > self::MAX_NOTICE) {
            throw ValidationException::withMessages(['last_working_day' => 'The last working day is more than a year after the resignation. Check the date.']);
        }

        return [
            'resignation_date' => $resigned->toDateString(),
            'notice_period_days' => (int) $data['notice_period_days'],
            'last_working_day' => $lastDay->toDateString(),
            'reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : null,
        ];
    }

    /** Lock the resignation and make sure it is still in a state this step applies to. */
    protected function lock(Resignation $resignation, array $from, string $to): void
    {
        $row = Resignation::withoutGlobalScopes()->whereKey($resignation->id)->lockForUpdate()->first();

        if (! in_array($row->status, $from, true)) {
            throw ValidationException::withMessages(['resignation' => 'This resignation is '.$row->status.', so it can\'t be '.$to.'.']);
        }
    }

    /** 404 (not 403) for another company's or branch's resignation, so the URL doesn't confirm it exists. */
    protected function ensureOwn(Resignation $resignation): void
    {
        abort_unless($this->scoped()->whereKey($resignation->id)->exists(), 404);
    }

    /** Resignations this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return Resignation::query()
            ->where('resignations.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
