<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\AppraisalCycle;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeKpi;
use App\Models\Kpi;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KpiController extends Controller
{
    protected const TABS = ['on_target', 'behind', 'unmeasured'];

    /** People who have left can't be given new KPIs. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Kpi::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'kpi_id' => $id('kpi_id'),
            'cycle_id' => $id('cycle_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['kpi_id'], fn ($q, $kpiId) => $q->where('kpi_id', $kpiId))
            ->when($state['cycle_id'], fn ($q, $cycleId) => $q->where('appraisal_cycle_id', $cycleId))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->whereHas('kpi', fn ($k) => $k->where('name', 'like', $like))
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });
        $tabbed = fn (Builder $q, string $tab) => match ($tab) {
            'on_target' => $q->whereNotNull('actual_value')->whereColumn('actual_value', '>=', 'target_value'),
            'behind' => $q->whereNotNull('actual_value')->whereColumn('actual_value', '<', 'target_value'),
            'unmeasured' => $q->whereNull('actual_value'),
            default => $q,
        };

        $assignments = $tabbed($filtered(), $state['tab'])
            ->with(['employee.department:id,name', 'kpi', 'appraisalCycle:id,name'])
            ->orderBy(Employee::query()->select('name')->whereColumn('employees.id', 'employee_kpis.employee_id'))
            ->orderBy(Kpi::query()->select('name')->whereColumn('kpis.id', 'employee_kpis.kpi_id'))
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'employee_id', 'kpi_id', 'appraisal_cycle_id', 'target_value', 'actual_value', 'weight']);
        $measured = $all->filter(fn (EmployeeKpi $a) => $a->achievementPercent() !== null);
        $kpis = Kpi::query()->where('company_id', Company::current()->id)->orderByDesc('is_active')->orderBy('name')->get();
        $usage = $all->countBy('kpi_id');
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'status']);

        return view('admin.hrm.performance.kpis.index', [
            'assignments' => $assignments,
            'state' => $state,
            'kpis' => $kpis,
            // How many scorecard lines in view use each KPI; a used KPI can be switched off but not deleted.
            'usage' => $usage,
            'employees' => $people->pluck('name', 'id'),
            'payable' => $people->whereNotIn('status', self::INACTIVE)->values(),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'cycles' => AppraisalCycle::query()->where('company_id', Company::current()->id)->orderByDesc('period_start')->get(['id', 'name', 'status']),
            'counts' => [
                'all' => $filtered()->count(),
                'on_target' => $tabbed($filtered(), 'on_target')->count(),
                'behind' => $tabbed($filtered(), 'behind')->count(),
                'unmeasured' => $tabbed($filtered(), 'unmeasured')->count(),
            ],
            'kpiStats' => [
                'library' => $kpis->where('is_active', true)->count(),
                'inactive' => $kpis->where('is_active', false)->count(),
                'assigned' => $all->count(),
                'people' => $all->pluck('employee_id')->unique()->count(),
                'average' => $measured->isNotEmpty() ? round((float) $measured->avg(fn (EmployeeKpi $a) => $a->achievementPercent()), 1) : null,
                'on_target' => $measured->filter(fn (EmployeeKpi $a) => $a->achievementPercent() >= 100)->count(),
                'measured' => $measured->count(),
                'unmeasured' => $all->whereNull('actual_value')->count(),
            ],
            // Weight already handed out per person per cycle ("employee:cycle" => total), for the assign form.
            'weights' => $all->groupBy(fn (EmployeeKpi $a) => $a->employee_id.':'.($a->appraisal_cycle_id ?? 0))
                ->map(fn ($group) => round((float) $group->sum(fn (EmployeeKpi $a) => (float) $a->weight), 2)),
            'canCreateKpi' => $request->user()->can('create', Kpi::class),
            'canAssign' => $request->user()->can('create', EmployeeKpi::class),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Kpi::class);

        $data = $this->validatedKpi($request);

        Kpi::create($data + ['company_id' => Company::current()->id, 'is_active' => true]);

        return back()->with('status', 'KPI "'.$data['name'].'" added to the library.');
    }

    public function update(Request $request, Kpi $kpi): RedirectResponse
    {
        $this->ensureOwnKpi($kpi);
        $this->authorize('update', $kpi);

        // The switch on each row sends only is_active; the edit dialog sends the details.
        if (! $request->has('name')) {
            $kpi->update(['is_active' => $request->boolean('is_active')]);

            return back()->with('status', '"'.$kpi->name.'" is now '.($kpi->is_active ? 'active' : 'switched off. It stays on existing scorecards but can\'t be assigned to anyone new').'.');
        }

        $kpi->update($this->validatedKpi($request, $kpi));

        return back()->with('status', 'KPI updated.');
    }

    public function destroy(Kpi $kpi): RedirectResponse
    {
        $this->ensureOwnKpi($kpi);
        $this->authorize('delete', $kpi);

        // Deleting a KPI would silently delete every target and result recorded against it.
        $used = EmployeeKpi::query()->where('kpi_id', $kpi->id)->count();

        if ($used) {
            return back()->withErrors(['kpi' => '"'.$kpi->name.'" is on '.$used.' scorecard '.($used === 1 ? 'line' : 'lines').', so it can\'t be deleted. Switch it off instead; it stays on those scorecards but can\'t be assigned again.']);
        }

        $kpi->delete();

        return back()->with('status', 'KPI removed from the library.');
    }

    public function assign(Request $request): RedirectResponse
    {
        $this->authorize('create', EmployeeKpi::class);

        $companyId = Company::current()->id;

        // One KPI can go to many people at once; the old single employee_id still works.
        if (! $request->has('employee_ids') && $request->filled('employee_id')) {
            $request->merge(['employee_ids' => [$request->input('employee_id')]]);
        }

        $data = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1', 'max:500'],
            'employee_ids.*' => ['integer', 'distinct'],
            'kpi_id' => ['required', Rule::exists('kpis', 'id')->where('company_id', $companyId)],
            'appraisal_cycle_id' => ['nullable', Rule::exists('appraisal_cycles', 'id')->where('company_id', $companyId)],
            'target_value' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'employee_ids.required' => 'Pick at least one employee.',
            'target_value.gt' => 'The target must be more than zero, or achievement can\'t be worked out.',
        ]);

        $kpi = Kpi::findOrFail($data['kpi_id']);

        if (! $kpi->is_active) {
            throw ValidationException::withMessages(['kpi_id' => '"'.$kpi->name.'" is switched off. Switch it on in the library before assigning it.']);
        }

        $employees = $this->employeeQuery()->whereNotIn('status', self::INACTIVE)->whereIn('id', $data['employee_ids'])->get(['id', 'name']);

        if ($employees->count() !== count($data['employee_ids'])) {
            throw ValidationException::withMessages(['employee_ids' => 'One or more of the selected employees can\'t be given a KPI (not found, or no longer with the company).']);
        }

        $cycleId = filled($data['appraisal_cycle_id'] ?? null) ? (int) $data['appraisal_cycle_id'] : null;
        $weight = filled($data['weight'] ?? null) ? round((float) $data['weight'], 2) : null;
        $already = EmployeeKpi::query()->where('kpi_id', $kpi->id)->whereIn('employee_id', $employees->pluck('id'))
            ->when($cycleId, fn ($q) => $q->where('appraisal_cycle_id', $cycleId), fn ($q) => $q->whereNull('appraisal_cycle_id'))
            ->pluck('employee_id')->flip();
        $fresh = $employees->reject(fn (Employee $e) => $already->has($e->id))->values();

        if ($fresh->isEmpty()) {
            throw ValidationException::withMessages(['kpi_id' => ($employees->count() === 1 ? $employees->first()->name.' already has' : 'Everyone selected already has').' "'.$kpi->name.'" '.($cycleId ? 'in this cycle' : 'outside a cycle').'.']);
        }

        foreach ($fresh as $employee) {
            $this->guardWeight($employee->id, $cycleId, $weight, null, $employee->name);
        }

        DB::transaction(fn () => $fresh->each(fn (Employee $e) => EmployeeKpi::create([
            'company_id' => $companyId,
            'employee_id' => $e->id,
            'kpi_id' => $kpi->id,
            'appraisal_cycle_id' => $cycleId,
            'target_value' => round((float) $data['target_value'], 2),
            'weight' => $weight,
        ])));

        $skipped = $employees->count() - $fresh->count();

        return back()->with('status', '"'.$kpi->name.'" assigned to '.($fresh->count() === 1 ? $fresh->first()->name : $fresh->count().' employees').'.'.($skipped ? ' Skipped '.$skipped.' who already had it.' : ''));
    }

    /** Record the result, and optionally change the target and weight, of one scorecard line. */
    public function updateActual(Request $request, EmployeeKpi $employeeKpi): RedirectResponse
    {
        $this->ensureOwn($employeeKpi);
        $this->authorize('update', $employeeKpi);

        $data = $request->validate([
            'actual_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'target_value' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'target_value.gt' => 'The target must be more than zero.',
        ]);

        $changes = ['actual_value' => filled($data['actual_value'] ?? null) ? round((float) $data['actual_value'], 2) : null];

        if (array_key_exists('target_value', $data)) {
            $changes['target_value'] = round((float) $data['target_value'], 2);
        }

        if (array_key_exists('weight', $data)) {
            $changes['weight'] = filled($data['weight']) ? round((float) $data['weight'], 2) : null;
            $this->guardWeight($employeeKpi->employee_id, $employeeKpi->appraisal_cycle_id, $changes['weight'], $employeeKpi, $employeeKpi->employee?->name ?? 'This employee');
        }

        $employeeKpi->update($changes);
        $achievement = $employeeKpi->achievementPercent();

        return back()->with('status', $achievement !== null
            ? 'Result recorded: '.($employeeKpi->employee?->name ?? 'employee').' is at '.rtrim(rtrim(number_format($achievement, 1), '0'), '.').'% of target on "'.($employeeKpi->kpi?->name ?? 'KPI').'".'
            : 'Scorecard line updated. No result is recorded yet.');
    }

    public function destroyAssignment(EmployeeKpi $employeeKpi): RedirectResponse
    {
        $this->ensureOwn($employeeKpi);
        $this->authorize('delete', $employeeKpi);

        $employeeKpi->delete();

        return back()->with('status', 'KPI removed from that employee\'s scorecard.');
    }

    protected function validatedKpi(Request $request, ?Kpi $kpi = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('kpis', 'name')->where('company_id', Company::current()->id)->ignore($kpi?->id)],
            'unit' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.unique' => 'A KPI with that name is already in the library.',
        ]);

        return [
            'name' => trim($data['name']),
            'unit' => filled($data['unit'] ?? null) ? trim($data['unit']) : null,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
        ];
    }

    /** One person's KPI weights in one cycle can't add up to more than 100%. */
    protected function guardWeight(int $employeeId, ?int $cycleId, ?float $weight, ?EmployeeKpi $ignore, string $name): void
    {
        if (! $weight) {
            return;
        }

        $used = (float) EmployeeKpi::query()->where('employee_id', $employeeId)
            ->when($cycleId, fn ($q) => $q->where('appraisal_cycle_id', $cycleId), fn ($q) => $q->whereNull('appraisal_cycle_id'))
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))
            ->sum('weight');

        if ($used + $weight > 100.001) {
            $trim = fn (float $n) => rtrim(rtrim(number_format($n, 2), '0'), '.');

            throw ValidationException::withMessages(['weight' => $name.'\'s other KPIs '.($cycleId ? 'in this cycle' : 'outside a cycle').' already carry '.$trim($used).'% of the weight, so at most '.$trim(max(0, 100 - $used)).'% is left.']);
        }
    }

    /** 404 (not 403) for another company's KPI, so the URL doesn't confirm it exists. */
    protected function ensureOwnKpi(Kpi $kpi): void
    {
        abort_unless((int) $kpi->company_id === (int) Company::current()->id, 404);
    }

    /** 404 for another company's or branch's scorecard line. */
    protected function ensureOwn(EmployeeKpi $employeeKpi): void
    {
        abort_unless($this->scoped()->whereKey($employeeKpi->id)->exists(), 404);
    }

    protected function scoped(): Builder
    {
        return EmployeeKpi::query()
            ->where('employee_kpis.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
