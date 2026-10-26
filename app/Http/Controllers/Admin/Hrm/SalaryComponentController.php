<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SalaryComponent;
use App\Models\SalaryStructureItem;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalaryComponentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalaryComponent::class);

        $companyId = Company::current()->id;
        $branchId = BranchContext::currentBranchId();

        SalaryComponent::seedDefaults($companyId, $branchId);

        $state = [
            'search' => is_string($request->query('search')) ? trim($request->query('search')) : '',
            'tab' => in_array($request->query('tab'), ['earning', 'deduction', 'inactive'], true) ? $request->query('tab') : '',
        ];

        $all = $this->scoped()->orderBy('type')->orderBy('name')->get();

        // How each component is used in employees' current salary structures.
        $usage = SalaryStructureItem::query()
            ->whereIn('salary_component_id', $all->pluck('id'))
            ->whereHas('structure', fn ($q) => $q->where('is_current', true))
            ->selectRaw('salary_component_id, COUNT(*) as employees, SUM(amount) as total')
            ->groupBy('salary_component_id')
            ->get()
            ->keyBy('salary_component_id');
        $everUsed = SalaryStructureItem::whereIn('salary_component_id', $all->pluck('id'))->distinct()->pluck('salary_component_id')->flip();

        $components = $all
            ->when($state['tab'] === 'inactive', fn ($c) => $c->where('is_active', false))
            ->when(in_array($state['tab'], ['earning', 'deduction'], true), fn ($c) => $c->where('type', $state['tab']))
            ->when($state['search'] !== '', fn ($c) => $c->filter(fn ($x) => str_contains(mb_strtolower($x->name.' '.$x->code), mb_strtolower($state['search']))))
            ->values();

        $earningTotal = (float) $all->where('type', 'earning')->sum(fn ($c) => (float) ($usage[$c->id]->total ?? 0));

        return view('admin.hrm.payroll.components.index', [
            'components' => $components,
            'state' => $state,
            'usage' => $usage,
            'everUsed' => $everUsed,
            'earningTotal' => $earningTotal,
            'counts' => [
                'all' => $all->count(),
                'earning' => $all->where('type', 'earning')->count(),
                'deduction' => $all->where('type', 'deduction')->count(),
                'inactive' => $all->where('is_active', false)->count(),
            ],
            'kpis' => [
                'earnings' => $all->where('type', 'earning')->where('is_active', true)->count(),
                'deductions' => $all->where('type', 'deduction')->where('is_active', true)->count(),
                'in_use' => $usage->count(),
                'monthly' => $earningTotal,
                'employees' => (int) SalaryStructureItem::query()
                    ->join('salary_structures', 'salary_structures.id', '=', 'salary_structure_items.salary_structure_id')
                    ->whereIn('salary_structure_items.salary_component_id', $all->pluck('id'))
                    ->where('salary_structures.is_current', true)
                    ->distinct()->count('salary_structures.employee_id'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SalaryComponent::class);

        $data = $this->validated($request);
        $component = SalaryComponent::create($data + [
            'company_id' => Company::current()->id,
            'branch_id' => BranchContext::currentBranchId(),
            'is_active' => true,
        ]);

        return back()->with('status', "“{$component->name}” ({$component->code}) added as ".Str::lower(SalaryComponent::TYPES[$component->type]).'.');
    }

    public function update(Request $request, SalaryComponent $salaryComponent): RedirectResponse
    {
        $this->authorize('update', $salaryComponent);
        $this->ensureOwn($salaryComponent);

        $data = $this->validated($request, $salaryComponent);

        // Salary structures are built from earnings; flipping a component that's
        // already in someone's structure would silently change their pay.
        if ($data['type'] !== $salaryComponent->type && $this->isUsed($salaryComponent)) {
            throw ValidationException::withMessages(['type' => "{$salaryComponent->name} is used in salary structures, so its type can't change. Add a new component instead."]);
        }

        $salaryComponent->update($data);

        return back()->with('status', "“{$salaryComponent->name}” updated.");
    }

    public function toggle(SalaryComponent $salaryComponent): RedirectResponse
    {
        $this->authorize('update', $salaryComponent);
        $this->ensureOwn($salaryComponent);

        $salaryComponent->update(['is_active' => ! $salaryComponent->is_active]);

        return back()->with('status', "“{$salaryComponent->name}” ".($salaryComponent->is_active ? 'activated.' : 'deactivated - it won\'t be offered for new salary structures.'));
    }

    public function destroy(SalaryComponent $salaryComponent): RedirectResponse
    {
        $this->authorize('delete', $salaryComponent);
        $this->ensureOwn($salaryComponent);

        // Structure items cascade on delete - deleting a used component would wipe pay history.
        if ($this->isUsed($salaryComponent)) {
            return back()->withErrors(['component' => "“{$salaryComponent->name}” is used in salary structures, so it can't be deleted. Deactivate it instead."]);
        }

        $salaryComponent->delete();

        return back()->with('status', "“{$salaryComponent->name}” removed.");
    }

    protected function validated(Request $request, ?SalaryComponent $ignore = null): array
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);
        $branchId = $ignore ? $ignore->branch_id : BranchContext::currentBranchId();

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_\-]+$/',
                // Unique per company and branch (company-wide codes when there's no branch).
                Rule::unique('salary_components', 'code')
                    ->where('company_id', Company::current()->id)
                    ->where(fn ($q) => $branchId ? $q->where('branch_id', $branchId) : $q->whereNull('branch_id'))
                    ->ignore($ignore?->id),
            ],
            'type' => ['required', Rule::in(array_keys(SalaryComponent::TYPES))],
        ], [
            'code.regex' => 'Use letters, numbers, dashes or underscores for the code.',
            'code.unique' => 'Another component already uses that code.',
        ]);
    }

    protected function isUsed(SalaryComponent $component): bool
    {
        return SalaryStructureItem::where('salary_component_id', $component->id)->exists();
    }

    protected function ensureOwn(SalaryComponent $component): void
    {
        abort_unless((int) $component->company_id === (int) Company::current()->id, 404);
        abort_if(BranchContext::isRestricted() && (int) $component->branch_id !== (int) BranchContext::currentBranchId(), 404);
    }

    protected function scoped()
    {
        return SalaryComponent::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
