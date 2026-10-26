<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalaryRevisionController extends Controller
{
    protected const TABS = ['current', 'scheduled', 'past'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

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

        $all = $this->timeline();

        // A scheduled revision whose date has arrived becomes the current salary the next time anyone looks.
        $stale = $all->groupBy('employee_id')->filter(function (Collection $rows) {
            $due = $rows->filter(fn ($r) => $r->effective_date->lte(today()))->last();

            return $rows->where('is_current', true)->pluck('id')->all() !== ($due ? [$due->id] : []);
        })->keys();

        if ($stale->isNotEmpty()) {
            $stale->each(fn ($employeeId) => EmployeeSalary::syncCurrent((int) $employeeId));
            $all = $this->timeline();
        }

        $filtered = fn () => $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['year'], fn ($q, $year) => $q->whereYear('effective_date', $year))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->whereHas('employee', fn ($e) => $e->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('employee_code', 'like', $like)));
            });
        $tabbed = fn (Builder $q, string $tab) => match ($tab) {
            'current' => $q->where('is_current', true),
            'scheduled' => $q->whereDate('effective_date', '>', today()),
            'past' => $q->where('is_current', false)->whereDate('effective_date', '<=', today()),
            default => $q,
        };

        $revisions = $tabbed($filtered(), $state['tab'])
            ->with('employee.department:id,name')
            ->orderByDesc('effective_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $thisYear = $all->filter(fn ($r) => $r->effective_date->year === today()->year && $r->effective_date->lte(today()));
        $raises = $thisYear->filter(fn ($r) => $r->change_percent !== null && $r->change_percent > 0);
        $scheduled = $all->filter(fn ($r) => $r->effective_date->gt(today()));
        $canRevise = $request->user()->can('update_employee');
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'joining_date', 'status']);

        return view('admin.hrm.payroll.revisions.index', [
            'revisions' => $revisions,
            'changes' => $all->keyBy('id'),
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'years' => $all->map(fn ($r) => $r->effective_date->year)->unique()->sortDesc()->values(),
            'counts' => [
                'all' => $filtered()->count(),
                'current' => $tabbed($filtered(), 'current')->count(),
                'scheduled' => $tabbed($filtered(), 'scheduled')->count(),
                'past' => $tabbed($filtered(), 'past')->count(),
            ],
            'kpis' => [
                'this_year' => $thisYear->count(),
                'raises' => $raises->count(),
                'cuts' => $thisYear->filter(fn ($r) => $r->change_percent !== null && $r->change_percent < 0)->count(),
                'average_raise' => $raises->isNotEmpty() ? round($raises->avg('change_percent'), 1) : null,
                'top_raise' => $raises->max('change_percent'),
                'scheduled' => $scheduled->count(),
                'next_scheduled' => $scheduled->min('effective_date'),
                'without' => $people->whereNotIn('status', ['terminated', 'inactive'])->whereNotIn('id', $all->pluck('employee_id')->unique())->count(),
                'people' => $people->whereNotIn('status', ['terminated', 'inactive'])->count(),
            ],
            'canRevise' => $canRevise,
            'formData' => $canRevise ? $this->formData($people, $all) : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'effective_date' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:'.today()->addYear()->toDateString()],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'amount.gt' => 'The new salary must be more than zero.',
            'effective_date.before_or_equal' => 'A revision can be scheduled at most one year ahead.',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $this->authorize('update', $employee);

        $date = Carbon::parse($data['effective_date'])->startOfDay();
        $amount = round((float) $data['amount'], 2);

        if ($employee->joining_date && $date->lt($employee->joining_date->copy()->startOfDay())) {
            throw ValidationException::withMessages(['effective_date' => $employee->name.' joined on '.$employee->joining_date->format('M j, Y').'. A revision can\'t take effect before that.']);
        }

        $history = EmployeeSalary::query()->where('employee_id', $employee->id)->orderBy('effective_date')->orderBy('id')->get();

        if ($history->contains(fn ($row) => $row->effective_date->isSameDay($date))) {
            throw ValidationException::withMessages(['effective_date' => $employee->name.' already has a revision effective '.$date->format('M j, Y').'. Remove that one first if it was wrong.']);
        }

        $before = $history->filter(fn ($row) => $row->effective_date->lt($date))->last();

        if ($before && abs((float) $before->amount - $amount) < 0.005) {
            throw ValidationException::withMessages(['amount' => 'That is the same as the salary already in effect on that date ('.number_format($amount, 2).'). Nothing to revise.']);
        }

        DB::transaction(function () use ($employee, $amount, $date, $data) {
            // Saved as not-current, then the right row is picked by date: a back-dated
            // or future-dated revision must not replace today's salary.
            $employee->salaries()->create([
                'amount' => $amount,
                'currency' => Company::current()->currency ?: null,
                'effective_date' => $date->toDateString(),
                'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
                'is_current' => false,
            ]);
            EmployeeSalary::syncCurrent($employee->id);
        });

        $when = $date->gt(today()) ? 'scheduled for '.$date->format('M j, Y') : 'recorded';

        return redirect()->route('admin.salary-revisions.index')->with('status', 'Salary revision '.$when.' for '.$employee->name.'.');
    }

    public function destroy(EmployeeSalary $employeeSalary): RedirectResponse
    {
        $employee = $this->employeeQuery()->whereKey($employeeSalary->employee_id)->first();
        abort_unless($employee, 404);
        $this->authorize('update', $employee);

        DB::transaction(function () use ($employeeSalary, $employee) {
            $employeeSalary->delete();
            EmployeeSalary::syncCurrent($employee->id);
        });

        return back()->with('status', 'Revision removed for '.$employee->name.'.');
    }

    /** Revisions this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return EmployeeSalary::query()->whereIn('employee_id', $this->employeeQuery()->select('id'));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    /**
     * Every visible revision in date order, each tagged with the salary it replaced
     * (previous_amount) and the change in percent (null for a first salary).
     */
    protected function timeline(): Collection
    {
        $rows = $this->scoped()->orderBy('effective_date')->orderBy('id')->get(['id', 'employee_id', 'amount', 'effective_date', 'is_current']);

        foreach ($rows->groupBy('employee_id') as $group) {
            $previous = null;

            foreach ($group as $row) {
                $row->previous_amount = $previous ? (float) $previous->amount : null;
                $row->change_percent = $previous && (float) $previous->amount > 0
                    ? round(((float) $row->amount - (float) $previous->amount) / (float) $previous->amount * 100, 1)
                    : null;
                $previous = $row;
            }
        }

        return $rows;
    }

    /** What the "Record a revision" form shows live for the picked employee. */
    protected function formData(Collection $people, Collection $all): array
    {
        $byEmployee = $all->groupBy('employee_id');
        $structures = DB::table('salary_structures')
            ->join('salary_structure_items', 'salary_structure_items.salary_structure_id', '=', 'salary_structures.id')
            ->whereIn('salary_structures.employee_id', $people->pluck('id'))
            ->where('salary_structures.is_current', true)
            ->groupBy('salary_structures.employee_id')
            ->selectRaw('salary_structures.employee_id, SUM(salary_structure_items.amount) as total')
            ->pluck('total', 'employee_id');

        return $people->whereNotIn('status', ['terminated', 'inactive'])->mapWithKeys(function (Employee $employee) use ($byEmployee, $structures) {
            $rows = $byEmployee->get($employee->id, collect());
            $current = $rows->firstWhere('is_current', true);

            return [$employee->id => [
                'name' => $employee->name,
                'meta' => collect([$employee->employee_code, $employee->department?->name])->filter()->implode(' · '),
                'current' => $current ? (float) $current->amount : null,
                'since' => $current?->effective_date->format('M j, Y'),
                'joined' => $employee->joining_date?->toDateString(),
                'structure' => isset($structures[$employee->id]) ? (float) $structures[$employee->id] : null,
                'structureUrl' => route('admin.salary-structures.edit', $employee->id),
                'dates' => $rows->map(fn ($r) => $r->effective_date->toDateString())->values()->all(),
                'scheduled' => $rows->filter(fn ($r) => $r->effective_date->gt(today()))->map(fn ($r) => $r->effective_date->format('M j, Y'))->values()->all(),
            ]];
        })->all();
    }
}
