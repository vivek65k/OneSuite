<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\TaxDeclaration;
use App\Models\TaxSlab;
use App\Support\BranchContext;
use App\Support\LoanRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaxDeclarationController extends Controller
{
    protected const TABS = ['new', 'old', 'missing'];

    /** Employees payroll no longer pays, so no tax is deducted for them. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TaxDeclaration::class);

        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $years = $this->offeredYears();
        $financialYear = $this->validYear($text('financial_year')) ? $text('financial_year') : TaxDeclaration::currentFinancialYear();
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')
            ->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'status']);
        $active = $people->whereNotIn('status', self::INACTIVE)->values();
        $declarations = $this->scoped()->where('financial_year', $financialYear)->with('employee.department:id,name')->get()
            ->sortBy(fn (TaxDeclaration $d) => mb_strtolower($d->employee?->name ?? ''))->values();
        $missing = $active->whereNotIn('id', $declarations->pluck('employee_id'))->values();
        $salaries = LoanRules::salaries($people->pluck('id'));
        $deducted = $this->deductedSoFar($financialYear, $declarations->pluck('employee_id'));

        $matches = function (?Employee $e) use ($state) {
            return $state['search'] === '' || ($e && (str_contains(mb_strtolower($e->name), mb_strtolower($state['search'])) || str_contains(mb_strtolower((string) $e->employee_code), mb_strtolower($state['search']))));
        };
        $shown = $declarations
            ->when(in_array($state['tab'], ['new', 'old'], true), fn (Collection $c) => $c->where('regime', $state['tab']))
            ->filter(fn (TaxDeclaration $d) => $matches($d->employee))->values();
        $canCreate = $request->user()->can('create', TaxDeclaration::class);

        return view('admin.hrm.payroll.tax.index', [
            'financialYear' => $financialYear,
            'years' => collect($years)->merge($this->scoped()->distinct()->pluck('financial_year'))->filter(fn ($y) => $this->validYear($y, false))->unique()->sort()->values(),
            'state' => $state,
            'declarations' => $state['tab'] === 'missing' ? collect() : $shown,
            'missing' => $missing->filter(fn (Employee $e) => $matches($e))->values(),
            'salaries' => $salaries,
            'deducted' => $deducted,
            'monthsRun' => $this->monthsRun($financialYear),
            'counts' => [
                'all' => $declarations->count(),
                'new' => $declarations->where('regime', 'new')->count(),
                'old' => $declarations->where('regime', 'old')->count(),
                'missing' => $missing->count(),
                'people' => $active->count(),
            ],
            'kpis' => [
                'annual_tax' => (float) $declarations->sum('annual_tax'),
                'monthly_tds' => (float) $declarations->sum(fn (TaxDeclaration $d) => $d->monthlyTds()),
                'investments' => (float) $declarations->where('regime', 'old')->sum('declared_investments'),
                'deducted' => (float) $deducted->sum(),
            ],
            'canCreate' => $canCreate,
            'formData' => $canCreate ? $active->mapWithKeys(function (Employee $e) use ($salaries, $declarations) {
                $existing = $declarations->firstWhere('employee_id', $e->id);

                return [$e->id => [
                    'name' => $e->name,
                    'meta' => collect([$e->employee_code, $e->department?->name])->filter()->implode(' · '),
                    'salary' => $salaries->get($e->id),
                    'declared' => $existing ? [
                        'regime' => $existing->regime,
                        'investments' => (float) $existing->declared_investments,
                        'annual_tax' => (float) $existing->annual_tax,
                        'remarks' => (string) $existing->remarks,
                    ] : null,
                ]];
            })->all() : [],
            'slabs' => $canCreate ? $this->slabs($financialYear) : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TaxDeclaration::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'financial_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'regime' => ['required', Rule::in(array_keys(TaxDeclaration::REGIMES))],
            'declared_investments' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'annual_tax' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        // "2026-2030" passes the pattern but payroll would never find it: the two years must be consecutive.
        if (! $this->validYear($data['financial_year'])) {
            throw ValidationException::withMessages(['financial_year' => 'Choose one of the financial years offered ('.implode(', ', $this->offeredYears()).').']);
        }

        $employee = Employee::findOrFail($data['employee_id']);

        if (in_array($employee->status, self::INACTIVE, true)) {
            throw ValidationException::withMessages(['employee_id' => $employee->name.' is no longer on payroll, so no tax would be deducted.']);
        }

        $annualTax = round((float) $data['annual_tax'], 2);
        $salary = LoanRules::salaries(collect([$employee->id]))->get($employee->id);

        // Payroll takes a twelfth every month and refuses to run when deductions exceed pay.
        if ($salary !== null && $annualTax / 12 > $salary) {
            throw ValidationException::withMessages(['annual_tax' => 'A yearly tax of '.number_format($annualTax, 2).' means '.number_format($annualTax / 12, 2).' a month, more than the '.number_format($salary, 2).' '.$employee->name.' earns. Check the amount.']);
        }

        $declaration = TaxDeclaration::updateOrCreate(
            ['employee_id' => $employee->id, 'financial_year' => $data['financial_year']],
            [
                'company_id' => $companyId,
                'regime' => $data['regime'],
                // Investments only reduce tax under the old regime; don't keep a figure that means nothing.
                'declared_investments' => $data['regime'] === 'old' && filled($data['declared_investments'] ?? null) ? round((float) $data['declared_investments'], 2) : null,
                'annual_tax' => $annualTax,
                'remarks' => filled($data['remarks'] ?? null) ? trim($data['remarks']) : null,
            ]
        );

        return redirect()->route('admin.tax-declarations.index', ['financial_year' => $data['financial_year']])
            ->with('status', 'Tax declaration '.($declaration->wasRecentlyCreated ? 'saved' : 'updated').' for '.$employee->name.'. '
                .($annualTax > 0 ? number_format($annualTax / 12, 2).' will be deducted each payroll.' : 'No tax will be deducted.'));
    }

    public function destroy(TaxDeclaration $taxDeclaration): RedirectResponse
    {
        abort_unless($this->scoped()->whereKey($taxDeclaration->id)->exists(), 404);
        $this->authorize('delete', $taxDeclaration);

        $financialYear = $taxDeclaration->financial_year;
        $taxDeclaration->delete();

        return redirect()->route('admin.tax-declarations.index', ['financial_year' => $financialYear])->with('status', 'Tax declaration removed. No tax will be deducted for that employee from the next payroll.');
    }

    /** Declarations this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return TaxDeclaration::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    /** Last, current and next financial year, e.g. ["2025-2026", "2026-2027", "2027-2028"]. */
    protected function offeredYears(): array
    {
        $start = (int) substr(TaxDeclaration::currentFinancialYear(), 0, 4);

        return collect([-1, 0, 1])->map(fn (int $i) => ($start + $i).'-'.($start + $i + 1))->all();
    }

    /** A real April-to-March year ("2026-2027"); with $offeredOnly, also one of the three on offer. */
    protected function validYear(mixed $value, bool $offeredOnly = true): bool
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{4})$/', $value, $m) || (int) $m[2] !== (int) $m[1] + 1) {
            return false;
        }

        return ! $offeredOnly || in_array($value, $this->offeredYears(), true);
    }

    /** Tax already taken through payroll in this financial year, keyed by employee. */
    protected function deductedSoFar(string $financialYear, Collection $employeeIds): Collection
    {
        [$from, $to] = $this->yearRange($financialYear);

        return Payslip::query()
            ->where('company_id', Company::current()->id)
            ->whereIn('employee_id', $employeeIds)
            ->whereHas('payrollRun', fn ($q) => $q->whereRaw('`year` * 100 + `month` BETWEEN ? AND ?', [$from, $to]))
            ->selectRaw('employee_id, SUM(tax_amount) as total')
            ->groupBy('employee_id')
            ->pluck('total', 'employee_id')
            ->map(fn ($total) => (float) $total);
    }

    /** How many of the year's twelve payrolls have already been run for the branch in view. */
    protected function monthsRun(string $financialYear): int
    {
        [$from, $to] = $this->yearRange($financialYear);

        return \App\Models\PayrollRun::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')))
            ->whereRaw('`year` * 100 + `month` BETWEEN ? AND ?', [$from, $to])
            ->selectRaw('DISTINCT `year` * 100 + `month` as ym')
            ->get()->count();
    }

    /** April of the first year to March of the second, as YYYYMM numbers. */
    protected function yearRange(string $financialYear): array
    {
        $start = (int) substr($financialYear, 0, 4);

        return [$start * 100 + 4, ($start + 1) * 100 + 3];
    }

    /** Tax slabs for the year, per regime, for the form's estimate. Empty when none are set up. */
    protected function slabs(string $financialYear): array
    {
        return TaxSlab::query()
            ->where('company_id', Company::current()->id)
            ->where('financial_year', $financialYear)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')))
            ->orderBy('min_income')
            ->get()
            ->groupBy('regime')
            ->map(fn (Collection $slabs) => $slabs->map(fn (TaxSlab $s) => [
                'min' => (float) $s->min_income,
                'max' => $s->max_income !== null ? (float) $s->max_income : null,
                'rate' => (float) $s->tax_rate_percent,
            ])->values())
            ->all();
    }
}
