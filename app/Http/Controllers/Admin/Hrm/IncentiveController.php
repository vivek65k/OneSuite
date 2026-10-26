<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Incentive;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IncentiveController extends Controller
{
    protected const TABS = ['pending', 'paid', 'missed'];

    /** Employees payroll no longer pays; an incentive for them would never be picked up. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** How far back and ahead an incentive period may be. */
    protected const MONTHS_BACK = 5;

    protected const MONTHS_AHEAD = 12;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Incentive::class);

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

        $runs = $this->runs();
        $all = $this->scoped()->with('employee:id,name,branch_id')->get(['id', 'employee_id', 'amount', 'period_month', 'period_year', 'status']);
        // Pending incentives whose month's payroll has already run: nothing will ever pay them.
        $missedIds = $all->filter(fn (Incentive $b) => $b->status === 'pending' && $this->runFor($runs, $b->employee?->branch_id, $b->period_year, $b->period_month))->pluck('id');

        $filtered = fn () => $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['year'], fn ($q, $year) => $q->where('period_year', $year))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('title', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });
        $tabbed = fn (Builder $q, string $tab) => match ($tab) {
            'pending' => $q->where('status', 'pending')->whereNotIn('id', $missedIds),
            'paid' => $q->where('status', 'paid'),
            'missed' => $q->whereIn('id', $missedIds),
            default => $q,
        };

        $incentives = $tabbed($filtered(), $state['tab'])
            ->with('employee.department:id,name')
            ->orderByDesc('period_year')->orderByDesc('period_month')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $waiting = $all->where('status', 'pending')->whereNotIn('id', $missedIds);
        $paidThisYear = $all->where('status', 'paid')->where('period_year', today()->year);
        $thisMonth = $all->where('period_year', today()->year)->where('period_month', today()->month);
        $canCreate = $request->user()->can('create', Incentive::class);
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'branch_id', 'status']);
        $shown = $tabbed($filtered(), $state['tab']);

        return view('admin.hrm.payroll.incentives.index', [
            'incentives' => $incentives,
            'state' => $state,
            'missedIds' => $missedIds->flip(),
            'runIds' => $runs->pluck('id')->flip(),
            'employees' => $people->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'years' => $all->pluck('period_year')->unique()->sortDesc()->values(),
            'counts' => [
                'all' => $filtered()->count(),
                'pending' => $tabbed($filtered(), 'pending')->count(),
                'paid' => $tabbed($filtered(), 'paid')->count(),
                'missed' => $tabbed($filtered(), 'missed')->count(),
            ],
            'totals' => ['count' => (clone $shown)->count(), 'amount' => (float) (clone $shown)->sum('amount')],
            'kpis' => [
                'waiting' => $waiting->count(),
                'waiting_amount' => (float) $waiting->sum('amount'),
                'paid_year' => (float) $paidThisYear->sum('amount'),
                'paid_year_count' => $paidThisYear->count(),
                'month_amount' => (float) $thisMonth->sum('amount'),
                'month_people' => $thisMonth->pluck('employee_id')->unique()->count(),
                'missed' => $missedIds->count(),
                'missed_amount' => (float) $all->whereIn('id', $missedIds)->sum('amount'),
            ],
            'canCreate' => $canCreate,
            'form' => $canCreate ? $this->formData($people->whereNotIn('status', self::INACTIVE)->values(), $runs) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Incentive::class);

        // One incentive can go to many people at once; the old single employee_id still works.
        if (! $request->has('employee_ids') && $request->filled('employee_id')) {
            $request->merge(['employee_ids' => [$request->input('employee_id')]]);
        }
        if (! $request->filled('period') && $request->filled('period_month') && $request->filled('period_year') && is_scalar($request->input('period_month')) && is_scalar($request->input('period_year'))) {
            $request->merge(['period' => sprintf('%04d-%02d', (int) $request->input('period_year'), (int) $request->input('period_month'))]);
        }

        $data = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1', 'max:500'],
            'employee_ids.*' => ['integer', 'distinct'],
            'title' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'employee_ids.required' => 'Pick at least one employee.',
            'amount.gt' => 'The incentive must be more than zero.',
            'period.regex' => 'Choose the month the incentive should be paid with.',
        ]);

        $period = Carbon::createFromFormat('!Y-m', $data['period']);
        $first = today()->startOfMonth()->subMonths(self::MONTHS_BACK);
        $last = today()->startOfMonth()->addMonths(self::MONTHS_AHEAD);

        if ($period->lt($first) || $period->gt($last)) {
            throw ValidationException::withMessages(['period' => 'Choose a month between '.$first->format('F Y').' and '.$last->format('F Y').'.']);
        }

        $employees = $this->employeeQuery()->whereNotIn('status', self::INACTIVE)->whereIn('id', $data['employee_ids'])->get(['id', 'name', 'branch_id']);

        if ($employees->count() !== count($data['employee_ids'])) {
            throw ValidationException::withMessages(['employee_ids' => 'One or more of the selected employees can\'t be given an incentive (not found, or no longer on payroll).']);
        }

        $runs = $this->runs();
        $blocked = $employees->filter(fn (Employee $e) => $this->runFor($runs, $e->branch_id, $period->year, $period->month));

        if ($blocked->isNotEmpty()) {
            throw ValidationException::withMessages(['period' => 'Payroll for '.$period->format('F Y').' has already been run, so an incentive there would never be paid. Choose a later month.']);
        }

        $title = trim($data['title']);
        $amount = round((float) $data['amount'], 2);
        $companyId = Company::current()->id;
        $already = Incentive::query()->where('company_id', $companyId)->whereIn('employee_id', $employees->pluck('id'))
            ->where('period_year', $period->year)->where('period_month', $period->month)->where('title', $title)->pluck('employee_id')->flip();
        $fresh = $employees->reject(fn (Employee $e) => $already->has($e->id));

        if ($fresh->isEmpty()) {
            throw ValidationException::withMessages(['title' => ($employees->count() === 1 ? $employees->first()->name.' already has' : 'Everyone selected already has').' a "'.$title.'" incentive for '.$period->format('F Y').'.']);
        }

        DB::transaction(fn () => $fresh->each(fn (Employee $e) => Incentive::create([
            'company_id' => $companyId,
            'employee_id' => $e->id,
            'title' => $title,
            'amount' => $amount,
            'period_month' => $period->month,
            'period_year' => $period->year,
            'status' => 'pending',
            'remarks' => filled($data['remarks'] ?? null) ? trim($data['remarks']) : null,
        ])));

        $message = $fresh->count() === 1
            ? 'Incentive added for '.$fresh->first()->name.'. It will be paid with '.$period->format('F Y').' payroll.'
            : $fresh->count().' incentives added ('.number_format($amount * $fresh->count(), 2).' in total). They will be paid with '.$period->format('F Y').' payroll.';
        $skipped = $employees->count() - $fresh->count();

        return redirect()->route('admin.incentives.index')->with('status', $message.($skipped ? ' Skipped '.$skipped.' who already had this incentive.' : ''));
    }

    /** Move an incentive that missed its payroll to the next month that hasn't been run yet. */
    public function move(Incentive $incentive): RedirectResponse
    {
        $this->ensureOwn($incentive);
        $this->authorize('update', $incentive);
        abort_unless($incentive->status === 'pending', 409, 'This incentive has already been paid.');

        $runs = $this->runs();
        $period = today()->startOfMonth();

        for ($i = 0; $i <= self::MONTHS_AHEAD && $this->runFor($runs, $incentive->employee?->branch_id, $period->year, $period->month); $i++) {
            $period->addMonth();
        }

        if ((int) $incentive->period_year === $period->year && (int) $incentive->period_month === $period->month) {
            return back()->with('status', 'That incentive is already set for '.$period->format('F Y').'.');
        }

        $incentive->update(['period_month' => $period->month, 'period_year' => $period->year]);

        return back()->with('status', 'Incentive for '.($incentive->employee?->name ?? 'the employee').' moved to '.$period->format('F Y').' payroll.');
    }

    public function destroy(Incentive $incentive): RedirectResponse
    {
        $this->ensureOwn($incentive);
        $this->authorize('delete', $incentive);

        // A paid incentive is already on someone's payslip; deleting it would leave the payslip unexplained.
        if ($incentive->status !== 'pending') {
            return back()->withErrors(['incentive' => 'This incentive has already been paid through payroll and can\'t be removed.']);
        }

        $incentive->delete();

        return back()->with('status', 'Incentive removed.');
    }

    /** 404 (not 403) for another company's or branch's incentive, so the URL doesn't confirm it exists. */
    protected function ensureOwn(Incentive $incentive): void
    {
        abort_unless($this->scoped()->whereKey($incentive->id)->exists(), 404);
    }

    protected function scoped(): Builder
    {
        return Incentive::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    protected function runs(): Collection
    {
        return PayrollRun::query()->where('company_id', Company::current()->id)->get(['id', 'branch_id', 'month', 'year']);
    }

    /** The payroll run that covered this branch for a month, if it has been run. A run with no branch covers everyone. */
    protected function runFor(Collection $runs, mixed $branchId, mixed $year, mixed $month): ?PayrollRun
    {
        return $runs->first(fn (PayrollRun $run) => (int) $run->year === (int) $year && (int) $run->month === (int) $month
            && ($run->branch_id === null || (int) $run->branch_id === (int) $branchId));
    }

    /** People and months offered by the "New incentive" form. */
    protected function formData(Collection $people, Collection $runs): array
    {
        $withStructure = DB::table('salary_structures')->whereIn('employee_id', $people->pluck('id'))->where('is_current', true)->pluck('employee_id')->flip();
        $branchIds = $people->pluck('branch_id')->unique();
        $periods = collect(range(-self::MONTHS_BACK, self::MONTHS_AHEAD))->map(function (int $offset) use ($runs, $branchIds) {
            $month = today()->startOfMonth()->addMonths($offset);

            return [
                'value' => $month->format('Y-m'),
                'label' => $month->format('F Y'),
                // Closed when payroll has run for any branch in view: nothing would pay an incentive added there.
                'ran' => $branchIds->contains(fn ($branchId) => (bool) $this->runFor($runs, $branchId, $month->year, $month->month)),
            ];
        });

        return [
            'people' => $people->map(fn (Employee $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'meta' => collect([$e->employee_code, $e->department?->name])->filter()->implode(' · '),
                'department_id' => $e->department_id,
                'structure' => $withStructure->has($e->id),
                'model' => $e,
            ]),
            'periods' => $periods,
            'default' => $periods->first(fn ($p) => ! $p['ran'] && $p['value'] >= today()->format('Y-m'))['value'] ?? null,
        ];
    }
}
