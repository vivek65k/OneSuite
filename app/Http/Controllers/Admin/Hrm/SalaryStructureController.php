<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalaryStructureController extends Controller
{
    /** Statuses that are no longer on payroll. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalaryStructure::class);

        $companyId = Company::current()->id;
        $branchId = BranchContext::isRestricted() ? BranchContext::currentBranchId() : null;
        $state = [
            'search' => is_string($request->query('search')) ? trim($request->query('search')) : '',
            'department_id' => ctype_digit((string) $request->query('department_id')) ? (int) $request->query('department_id') : null,
            'tab' => in_array($request->query('tab'), ['set', 'missing'], true) ? $request->query('tab') : '',
            'sort' => in_array($request->query('sort'), ['amount', 'recent'], true) ? $request->query('sort') : 'name',
        ];

        // Everyone payroll should cover, with their current structure and its components.
        $all = Employee::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('status', self::INACTIVE)
            ->with([
                'department:id,name',
                'salaryStructures' => fn ($q) => $q->where('is_current', true)->with('items.component:id,name,code'),
            ])
            ->withCount('salaryStructures')
            ->orderBy('name')
            ->get()
            ->map(function ($employee) {
                $structure = $employee->salaryStructures->first();
                $employee->setAttribute('current_structure', $structure && $structure->items->isNotEmpty() ? $structure : null);
                $employee->setAttribute('monthly_total', $employee->current_structure ? (float) $employee->current_structure->items->sum('amount') : 0.0);

                return $employee;
            });

        $withStructure = $all->filter->current_structure;
        $rows = $all
            ->when($state['tab'] === 'set', fn ($c) => $c->filter->current_structure)
            ->when($state['tab'] === 'missing', fn ($c) => $c->reject->current_structure)
            ->when($state['department_id'], fn ($c, $id) => $c->where('department_id', $id))
            ->when($state['search'] !== '', fn ($c) => $c->filter(fn ($e) => str_contains(mb_strtolower($e->name.' '.$e->employee_code.' '.$e->designation), mb_strtolower($state['search']))))
            ->when($state['sort'] === 'amount', fn ($c) => $c->sortByDesc('monthly_total'))
            ->when($state['sort'] === 'recent', fn ($c) => $c->sortByDesc(fn ($e) => $e->current_structure?->effective_date?->timestamp ?? 0))
            ->values();

        // Company-wide component mix, for the "where the money goes" bar.
        $mix = $withStructure->flatMap(fn ($e) => $e->current_structure->items)
            ->groupBy('salary_component_id')
            ->map(fn ($items) => ['name' => $items->first()->component?->name ?? 'Component', 'code' => $items->first()->component?->code, 'amount' => (float) $items->sum('amount')])
            ->sortByDesc('amount')->values();

        $totals = $withStructure->pluck('monthly_total')->sort()->values();

        return view('admin.hrm.payroll.structures.index', [
            'employees' => $rows,
            'state' => $state,
            'mix' => $mix,
            'counts' => ['all' => $all->count(), 'set' => $withStructure->count(), 'missing' => $all->count() - $withStructure->count()],
            'kpis' => [
                'monthly' => (float) $totals->sum(),
                'average' => $totals->count() ? (float) $totals->avg() : null,
                'median' => $totals->count() ? (float) $totals->median() : null,
                'highest' => $totals->count() ? (float) $totals->max() : null,
                'lowest' => $totals->count() ? (float) $totals->min() : null,
            ],
            'maxTotal' => max(1, (float) $totals->max()),
            'departments' => Department::whereIn('id', $all->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'canEdit' => $request->user()->can('create', SalaryStructure::class),
        ]);
    }

    public function edit(Request $request, Employee $employee): View
    {
        $this->authorize('viewAny', SalaryStructure::class);
        $this->ensureOwn($employee);

        $structures = $employee->salaryStructures()->with('items.component:id,name,code')->orderByDesc('effective_date')->orderByDesc('id')->get();
        $current = $structures->firstWhere('is_current', true);

        return view('admin.hrm.payroll.structures.edit', [
            'employee' => $employee->load(['department:id,name', 'branch:id,name']),
            'components' => $this->componentsFor($employee),
            'amounts' => $current ? $current->items->pluck('amount', 'salary_component_id') : collect(),
            'structure' => $current,
            'history' => $structures,
            'canEdit' => $request->user()->can('create', SalaryStructure::class) && ! in_array($employee->status, self::INACTIVE, true),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('create', SalaryStructure::class);
        $this->ensureOwn($employee);

        if (in_array($employee->status, self::INACTIVE, true)) {
            throw ValidationException::withMessages(['employee' => "{$employee->name} is no longer active, so their salary structure can't be changed."]);
        }

        $data = $request->validate([
            'effective_date' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:'.today()->addYear()->toDateString()],
            'amounts' => ['required', 'array'],
            'amounts.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        // Only this employee's own branch's active earning components may be used.
        $allowed = $this->componentsFor($employee)->keyBy('id');
        $amounts = collect($data['amounts'])->map(fn ($a) => round((float) $a, 2))->filter(fn ($a) => $a > 0);
        if ($unknown = $amounts->keys()->reject(fn ($id) => $allowed->has((int) $id))->first()) {
            throw ValidationException::withMessages(['amounts' => 'One of the components isn\'t available for this employee. Reload the page and try again.']);
        }
        if ($amounts->isEmpty()) {
            throw ValidationException::withMessages(['amounts' => 'Enter an amount for at least one component.']);
        }

        $current = $employee->salaryStructures()->where('is_current', true)->with('items')->first();
        $effective = Carbon::parse($data['effective_date'])->startOfDay();

        if ($current) {
            $existing = $current->items->mapWithKeys(fn ($i) => [(int) $i->salary_component_id => round((float) $i->amount, 2)])->sortKeys();
            $incoming = $amounts->mapWithKeys(fn ($a, $id) => [(int) $id => $a])->sortKeys();
            if ($existing->all() == $incoming->all()) {
                return redirect()->route('admin.salary-structures.edit', $employee)->with('status', 'Nothing changed - the amounts are the same as the current structure.');
            }
            if ($effective->lt($current->effective_date)) {
                throw ValidationException::withMessages(['effective_date' => 'The effective date can\'t be before the current structure\'s ('.$current->effective_date->format('M j, Y').').']);
            }
        }

        // The model un-marks the previous current structure; keep both writes together.
        DB::transaction(function () use ($employee, $effective, $amounts) {
            $structure = SalaryStructure::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'effective_date' => $effective->toDateString(),
                'is_current' => true,
            ]);
            foreach ($amounts as $componentId => $amount) {
                $structure->items()->create(['salary_component_id' => (int) $componentId, 'amount' => $amount]);
            }
        });

        return redirect()->route('admin.salary-structures.index')
            ->with('status', "Salary structure saved for {$employee->name}: ".number_format((float) $amounts->sum()).' a month.');
    }

    /** The employee must be in the current company (and branch). */
    protected function ensureOwn(Employee $employee): void
    {
        abort_unless((int) $employee->company_id === (int) Company::current()->id, 404);
        abort_if(BranchContext::isRestricted() && (int) $employee->branch_id !== (int) BranchContext::currentBranchId(), 404);
    }

    /**
     * Active earning components of the employee's own branch (not the
     * viewer's selected branch) - that is what their pay is built from.
     */
    protected function componentsFor(Employee $employee): Collection
    {
        SalaryComponent::seedDefaults($employee->company_id, $employee->branch_id);

        return SalaryComponent::query()
            ->where('company_id', $employee->company_id)
            ->where(fn ($q) => $employee->branch_id ? $q->where('branch_id', $employee->branch_id) : $q->whereNull('branch_id'))
            ->where('type', 'earning')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
