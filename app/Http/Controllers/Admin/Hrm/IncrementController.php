<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Increment;
use App\Support\BranchContext;
use App\Support\LoanRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IncrementController extends Controller
{
    protected const TABS = ['effective', 'scheduled'];

    /** Employees payroll no longer pays. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** The increments table stores the percentage as decimal(5,2); anything bigger is almost certainly a typo anyway. */
    protected const MAX_PERCENT = 200;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Increment::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'year' => $id('year'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['year'], fn ($q, $year) => $q->whereYear('effective_date', $year))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->whereHas('employee', fn ($e) => $e->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('employee_code', 'like', $like)));
            });
        $tabbed = fn (Builder $q, string $tab) => match ($tab) {
            'effective' => $q->whereDate('effective_date', '<=', today()),
            'scheduled' => $q->whereDate('effective_date', '>', today()),
            default => $q,
        };

        $increments = $tabbed($filtered(), $state['tab'])
            ->with(['employee.department:id,name', 'appraisal.appraisalCycle'])
            ->orderByDesc('effective_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'employee_id', 'appraisal_id', 'previous_amount', 'increment_percent', 'new_amount', 'effective_date']);
        $thisYear = $all->filter(fn (Increment $i) => $i->effective_date->year === today()->year);
        $canCreate = $request->user()->can('create', Increment::class);
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'joining_date', 'status']);
        $active = $people->whereNotIn('status', self::INACTIVE)->values();
        // Finished appraisals nobody has acted on yet: the usual reason to give an increment.
        $waiting = Appraisal::query()
            ->where('company_id', Company::current()->id)
            ->where('status', 'completed')
            ->whereIn('employee_id', $active->pluck('id'))
            ->whereNotIn('id', Increment::query()->whereNotNull('appraisal_id')->select('appraisal_id'))
            ->with(['employee:id,name,employee_code,avatar', 'appraisalCycle'])
            ->orderByDesc('completed_at')
            ->get();
        $shown = $tabbed($filtered(), $state['tab']);

        return view('admin.hrm.performance.increments.index', [
            'increments' => $increments,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'years' => $all->map(fn (Increment $i) => $i->effective_date->year)->unique()->sortDesc()->values(),
            'counts' => [
                'all' => $filtered()->count(),
                'effective' => $tabbed($filtered(), 'effective')->count(),
                'scheduled' => $tabbed($filtered(), 'scheduled')->count(),
            ],
            'totals' => [
                'count' => (clone $shown)->count(),
                'added' => (float) (clone $shown)->selectRaw('COALESCE(SUM(new_amount - previous_amount), 0) as added')->value('added'),
            ],
            'kpis' => [
                'this_year' => $thisYear->count(),
                'people' => $thisYear->pluck('employee_id')->unique()->count(),
                'average' => $thisYear->isNotEmpty() ? round((float) $thisYear->avg('increment_percent'), 1) : null,
                'highest' => $thisYear->max('increment_percent'),
                'added' => (float) $thisYear->sum(fn (Increment $i) => (float) $i->new_amount - (float) $i->previous_amount),
                'waiting' => $waiting->count(),
                'scheduled' => $all->filter(fn (Increment $i) => $i->effective_date->gt(today()))->count(),
            ],
            'waiting' => $waiting,
            // Only the latest salary change for a person can be undone; older ones have later salaries built on them.
            'undoable' => $this->undoableIds($increments->getCollection()),
            'canCreate' => $canCreate,
            'formData' => $canCreate ? $this->formData($active) : [],
            'maxPercent' => self::MAX_PERCENT,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Increment::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'appraisal_id' => ['nullable', 'integer'],
            'increment_percent' => ['required', 'numeric', 'min:0.01', 'max:'.self::MAX_PERCENT],
            'effective_date' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:'.today()->addYear()->toDateString()],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'increment_percent.max' => 'An increment of more than '.self::MAX_PERCENT.'% looks like a typo. Use Salary Revision for a change that large.',
            'effective_date.before_or_equal' => 'An increment can be scheduled at most one year ahead.',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);

        if (in_array($employee->status, self::INACTIVE, true)) {
            throw ValidationException::withMessages(['employee_id' => $employee->name.' is no longer on payroll.']);
        }

        $date = Carbon::parse($data['effective_date'])->startOfDay();

        if ($employee->joining_date && $date->lt($employee->joining_date->copy()->startOfDay())) {
            throw ValidationException::withMessages(['effective_date' => $employee->name.' joined on '.$employee->joining_date->format('M j, Y').'. An increment can\'t take effect before that.']);
        }

        $appraisal = null;

        if (filled($data['appraisal_id'] ?? null)) {
            // The appraisal must be this person's own, finished, and not already rewarded.
            $appraisal = Appraisal::query()->where('company_id', $companyId)->where('employee_id', $employee->id)->whereKey($data['appraisal_id'])->first();

            if (! $appraisal) {
                throw ValidationException::withMessages(['appraisal_id' => 'That appraisal does not belong to '.$employee->name.'.']);
            }
            if ($appraisal->status !== 'completed') {
                throw ValidationException::withMessages(['appraisal_id' => 'That appraisal is not completed yet.']);
            }
            if (Increment::query()->where('appraisal_id', $appraisal->id)->exists()) {
                throw ValidationException::withMessages(['appraisal_id' => 'An increment has already been given for that appraisal.']);
            }
        }

        $history = EmployeeSalary::query()->where('employee_id', $employee->id)->orderBy('effective_date')->orderBy('id')->get();

        if ($history->contains(fn (EmployeeSalary $row) => $row->effective_date->isSameDay($date))) {
            throw ValidationException::withMessages(['effective_date' => $employee->name.' already has a salary change effective '.$date->format('M j, Y').'. Pick another date.']);
        }

        // The increment builds on the salary in force on its effective date, not whatever is current today.
        $base = $history->filter(fn (EmployeeSalary $row) => $row->effective_date->lt($date))->last();

        if (! $base || (float) $base->amount <= 0) {
            throw ValidationException::withMessages(['employee_id' => $employee->name.' has no salary on record'.($history->isNotEmpty() ? ' before '.$date->format('M j, Y') : '').', so there is nothing to increase. Record their salary under Salary Revision first.']);
        }

        $percent = round((float) $data['increment_percent'], 2);
        $previous = (float) $base->amount;
        $new = round($previous * (1 + $percent / 100), 2);

        DB::transaction(function () use ($employee, $companyId, $appraisal, $previous, $percent, $new, $date, $data) {
            Increment::create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'appraisal_id' => $appraisal?->id,
                'previous_amount' => $previous,
                'increment_percent' => $percent,
                'new_amount' => $new,
                'effective_date' => $date->toDateString(),
                'remarks' => filled($data['remarks'] ?? null) ? trim($data['remarks']) : null,
            ]);

            // Saved as not-current, then the right row is picked by date: a back-dated
            // or future-dated increment must not replace today's salary.
            $employee->salaries()->create([
                'amount' => $new,
                'currency' => Company::current()->currency ?: null,
                'effective_date' => $date->toDateString(),
                'is_current' => false,
                'notes' => 'Increment: '.rtrim(rtrim(number_format($percent, 2), '0'), '.').'%'.(filled($data['remarks'] ?? null) ? ' · '.trim($data['remarks']) : ''),
            ]);
            EmployeeSalary::syncCurrent($employee->id);
        });

        return redirect()->route('admin.increments.index')->with('status', rtrim(rtrim(number_format($percent, 2), '0'), '.').'% increment '
            .($date->gt(today()) ? 'scheduled for '.$date->format('M j, Y') : 'recorded').' for '.$employee->name.': '.number_format($previous, 2).' to '.number_format($new, 2).' a month.');
    }

    /** Undo an increment: removes it and the salary it created. Only while it is the person's latest salary change. */
    public function destroy(Increment $increment): RedirectResponse
    {
        abort_unless($this->scoped()->whereKey($increment->id)->exists(), 404);
        $this->authorize('delete', $increment);

        $later = EmployeeSalary::query()->where('employee_id', $increment->employee_id)->whereDate('effective_date', '>', $increment->effective_date)->exists();

        if ($later) {
            return back()->withErrors(['increment' => 'A later salary change builds on this increment, so it can\'t be undone. Remove the later change first.']);
        }

        DB::transaction(function () use ($increment) {
            EmployeeSalary::query()->where('employee_id', $increment->employee_id)
                ->whereDate('effective_date', $increment->effective_date)
                ->where('amount', $increment->new_amount)
                ->delete();
            $increment->delete();
            EmployeeSalary::syncCurrent($increment->employee_id);
        });

        // Report the salary actually in force now, which need not be the one this increment replaced.
        $current = EmployeeSalary::query()->where('employee_id', $increment->employee_id)->where('is_current', true)->value('amount');

        return back()->with('status', 'Increment undone.'.($current !== null ? ' '.($increment->employee?->name ?? 'The employee').'\'s current salary is '.number_format((float) $current, 2).' a month.' : ''));
    }

    /** Increments this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return Increment::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    /** Ids (as keys) of the listed increments that are still each person's latest salary change. */
    protected function undoableIds(Collection $increments): Collection
    {
        $latest = EmployeeSalary::query()->whereIn('employee_id', $increments->pluck('employee_id')->unique())
            ->selectRaw('employee_id, MAX(effective_date) as latest')->groupBy('employee_id')->pluck('latest', 'employee_id');

        return $increments->filter(fn (Increment $i) => ! isset($latest[$i->employee_id]) || Carbon::parse($latest[$i->employee_id])->lte($i->effective_date))
            ->pluck('id')->flip();
    }

    /** What the "Give an increment" form shows live for the picked employee. */
    protected function formData(Collection $people): array
    {
        $ids = $people->pluck('id');
        $salaries = EmployeeSalary::query()->whereIn('employee_id', $ids)->orderBy('effective_date')->orderBy('id')->get()->groupBy('employee_id');
        $structures = LoanRules::salaries($ids);
        $used = Increment::query()->whereNotNull('appraisal_id')->pluck('appraisal_id')->flip();
        $appraisals = Appraisal::query()->where('company_id', Company::current()->id)->where('status', 'completed')->whereIn('employee_id', $ids)
            ->with('appraisalCycle')->orderByDesc('completed_at')->get()->groupBy('employee_id');
        $lastIncrement = Increment::query()->whereIn('employee_id', $ids)->orderBy('effective_date')->get()->groupBy('employee_id');

        return $people->mapWithKeys(function (Employee $employee) use ($salaries, $structures, $appraisals, $used, $lastIncrement) {
            $rows = $salaries->get($employee->id, collect());
            $current = $rows->firstWhere('is_current', true) ?? $rows->filter(fn ($r) => $r->effective_date->lte(today()))->last();
            $last = $lastIncrement->get($employee->id, collect())->last();

            return [$employee->id => [
                'name' => $employee->name,
                'meta' => collect([$employee->employee_code, $employee->department?->name])->filter()->implode(' · '),
                'current' => $current ? (float) $current->amount : null,
                'since' => $current?->effective_date->format('M j, Y'),
                'joined' => $employee->joining_date?->toDateString(),
                'structure' => $structures->get($employee->id),
                // Every salary on record, so the page can work out the base for any effective date.
                'history' => $rows->map(fn ($r) => ['date' => $r->effective_date->toDateString(), 'amount' => (float) $r->amount])->values()->all(),
                'last' => $last ? rtrim(rtrim(number_format((float) $last->increment_percent, 2), '0'), '.').'% on '.$last->effective_date->format('M j, Y') : null,
                'appraisals' => $appraisals->get($employee->id, collect())->reject(fn (Appraisal $a) => $used->has($a->id))->map(fn (Appraisal $a) => [
                    'id' => $a->id,
                    'label' => ($a->appraisalCycle?->name ?? 'Appraisal').($a->overall_rating !== null ? ' · rated '.rtrim(rtrim(number_format((float) $a->overall_rating, 2), '0'), '.') : ''),
                ])->values()->all(),
            ]];
        })->all();
    }
}
