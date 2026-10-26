<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\LoanRequest;
use App\Models\PayrollRun;
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

class LoanController extends Controller
{
    protected const TABS = ['active', 'closed'];

    /** Employees payroll no longer pays; a loan to them could never be recovered from salary. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Loan::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'employee_id' => $id('employee_id'),
            'department_id' => $id('department_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('loan_type', 'like', $like)
                    ->orWhereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like))));
            });

        $loans = $filtered()
            ->when($state['tab'], fn ($q, $tab) => $q->where('status', $tab))
            ->with('employee.department:id,name')
            ->orderByRaw("status = 'active' desc")->orderByDesc('disbursed_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'employee_id', 'principal_amount', 'monthly_installment', 'outstanding_amount', 'disbursed_date', 'status']);
        $active = $all->where('status', 'active');
        $started = $active->filter(fn (Loan $l) => $l->disbursed_date->lte(today()->endOfMonth()));
        $canCreate = $request->user()->can('create', Loan::class);
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'branch_id', 'status']);
        $shown = $filtered()->when($state['tab'], fn ($q, $tab) => $q->where('status', $tab));

        return view('admin.hrm.payroll.loans.index', [
            'loans' => $loans,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'counts' => [
                'all' => $filtered()->count(),
                'active' => $filtered()->where('status', 'active')->count(),
                'closed' => $filtered()->where('status', 'closed')->count(),
            ],
            'totals' => [
                'count' => (clone $shown)->count(),
                'principal' => (float) (clone $shown)->sum('principal_amount'),
                'outstanding' => (float) (clone $shown)->sum('outstanding_amount'),
            ],
            'kpis' => [
                'active' => $active->count(),
                'borrowers' => $active->pluck('employee_id')->unique()->count(),
                'outstanding' => (float) $active->sum('outstanding_amount'),
                'active_principal' => (float) $active->sum('principal_amount'),
                'monthly' => (float) $started->sum(fn (Loan $l) => min((float) $l->monthly_installment, (float) $l->outstanding_amount)),
                'monthly_count' => $started->count(),
                'lent' => (float) $all->sum('principal_amount'),
                'recovered' => (float) $all->sum('principal_amount') - (float) $all->sum('outstanding_amount'),
                'closed' => $all->where('status', 'closed')->count(),
                'requests' => $request->user()->can('viewAny', LoanRequest::class)
                    ? LoanRequest::query()->where('company_id', Company::current()->id)->where('status', 'pending')->whereIn('employee_id', $people->pluck('id'))->count()
                    : 0,
            ],
            'canCreate' => $canCreate,
            'formData' => $canCreate ? $this->formData($people->whereNotIn('status', self::INACTIVE)->values(), $active) : [],
            'nextPayroll' => $this->nextPayroll(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Loan::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))],
            'loan_type' => ['required', 'string', 'max:120'],
            'principal_amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'monthly_installment' => ['required', 'numeric', 'gt:0', 'lte:principal_amount'],
            'disbursed_date' => ['required', 'date', 'after_or_equal:'.today()->subYears(5)->toDateString(), 'before_or_equal:'.today()->addYear()->toDateString()],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'monthly_installment.lte' => 'The monthly instalment can\'t be more than the loan itself.',
            'disbursed_date.before_or_equal' => 'The loan can be dated at most one year ahead.',
            'disbursed_date.after_or_equal' => 'The loan date can\'t be more than five years back.',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);

        if (in_array($employee->status, self::INACTIVE, true)) {
            throw ValidationException::withMessages(['employee_id' => $employee->name.' is no longer on payroll, so a loan could not be recovered from salary.']);
        }

        $principal = round((float) $data['principal_amount'], 2);
        $instalment = round((float) $data['monthly_installment'], 2);

        if (ceil($principal / $instalment) > 120) {
            throw ValidationException::withMessages(['monthly_installment' => 'At '.number_format($instalment, 2).' a month this loan would take more than 10 years to repay. Raise the instalment.']);
        }

        // Payroll refuses to run when deductions exceed pay, so every instalment together must fit in the salary.
        $salary = $this->salaries(collect([$employee->id]))->get($employee->id);
        $existing = (float) Loan::query()->where('employee_id', $employee->id)->where('status', 'active')->get()
            ->sum(fn (Loan $l) => min((float) $l->monthly_installment, (float) $l->outstanding_amount));

        if ($salary !== null && $existing + $instalment > (float) $salary) {
            throw ValidationException::withMessages(['monthly_installment' => $employee->name.'\'s loan instalments would come to '.number_format($existing + $instalment, 2).' a month, more than the '.number_format((float) $salary, 2).' they earn. Payroll would refuse to run. Lower the instalment.']);
        }

        Loan::create([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'loan_type' => trim($data['loan_type']),
            'principal_amount' => $principal,
            'monthly_installment' => $instalment,
            'outstanding_amount' => $principal,
            'disbursed_date' => Carbon::parse($data['disbursed_date'])->toDateString(),
            'status' => 'active',
            'remarks' => filled($data['remarks'] ?? null) ? trim($data['remarks']) : null,
        ]);

        return redirect()->route('admin.loans.index')->with('status', 'Loan of '.number_format($principal, 2).' issued to '.$employee->name.'. '.number_format($instalment, 2).' will be recovered from each payroll.');
    }

    /** Money paid back outside payroll (cash, bank transfer, final settlement). Closes the loan when nothing is left. */
    public function repay(Request $request, Loan $loan): RedirectResponse
    {
        $this->ensureOwn($loan);
        $this->authorize('update', $loan);

        if ($loan->status !== 'active' || (float) $loan->outstanding_amount <= 0) {
            return back()->withErrors(['amount' => 'This loan is already closed.']);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'lte:'.(float) $loan->outstanding_amount],
            'note' => ['nullable', 'string', 'max:200'],
        ], [
            'amount.lte' => 'Only '.number_format((float) $loan->outstanding_amount, 2).' is still owed on this loan.',
        ]);

        $amount = round((float) $data['amount'], 2);

        DB::transaction(function () use ($loan, $amount, $data) {
            $fresh = Loan::query()->whereKey($loan->id)->lockForUpdate()->first();
            $left = max(0, round((float) $fresh->outstanding_amount - $amount, 2));
            // There is no repayment ledger, so the note is the only trail of money taken outside payroll.
            $line = today()->format('M j, Y').': '.number_format($amount, 2).' repaid outside payroll'.(filled($data['note'] ?? null) ? ' ('.trim($data['note']).')' : '').'.';

            $fresh->update([
                'outstanding_amount' => $left,
                'status' => $left <= 0 ? 'closed' : 'active',
                'remarks' => mb_substr(trim(($fresh->remarks ? $fresh->remarks."\n" : '').$line), 0, 60000),
            ]);
        });

        $loan->refresh();

        return back()->with('status', number_format($amount, 2).' recorded against '.($loan->employee?->name ?? 'the employee').'\'s loan. '
            .($loan->status === 'closed' ? 'The loan is now fully repaid and closed.' : number_format((float) $loan->outstanding_amount, 2).' is still owed.'));
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        $this->ensureOwn($loan);
        $this->authorize('delete', $loan);

        if ((float) $loan->outstanding_amount < (float) $loan->principal_amount) {
            return back()->withErrors(['loan' => 'This loan already has repayments against it, so it can\'t be removed. Record the remaining amount as repaid to close it instead.']);
        }

        $loan->delete();

        return back()->with('status', 'Loan removed.');
    }

    /** 404 (not 403) for another company's or branch's loan, so the URL doesn't confirm it exists. */
    protected function ensureOwn(Loan $loan): void
    {
        abort_unless($this->scoped()->whereKey($loan->id)->exists(), 404);
    }

    protected function scoped(): Builder
    {
        return Loan::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    /** Monthly gross from each person's current salary structure, keyed by employee. People without one are absent. */
    protected function salaries(Collection $employeeIds): Collection
    {
        return DB::table('salary_structures')
            ->join('salary_structure_items', 'salary_structure_items.salary_structure_id', '=', 'salary_structures.id')
            ->whereIn('salary_structures.employee_id', $employeeIds)
            ->where('salary_structures.is_current', true)
            ->groupBy('salary_structures.employee_id')
            ->selectRaw('salary_structures.employee_id, SUM(salary_structure_items.amount) as total')
            ->pluck('total', 'employee_id');
    }

    /** The first month, from this one on, whose payroll hasn't been run for the branch in view. */
    protected function nextPayroll(): Carbon
    {
        $ran = PayrollRun::query()->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')))
            ->get(['month', 'year'])->map(fn (PayrollRun $r) => sprintf('%04d-%02d', $r->year, $r->month))->flip();
        $month = today()->startOfMonth();

        for ($i = 0; $i < 24 && $ran->has($month->format('Y-m')); $i++) {
            $month->addMonth();
        }

        return $month;
    }

    /** What the "Issue a loan" form shows live for the picked employee. */
    protected function formData(Collection $people, Collection $active): array
    {
        $salaries = $this->salaries($people->pluck('id'));
        $byEmployee = $active->groupBy('employee_id');

        return $people->mapWithKeys(function (Employee $employee) use ($salaries, $byEmployee) {
            $loans = $byEmployee->get($employee->id, collect());

            return [$employee->id => [
                'name' => $employee->name,
                'meta' => collect([$employee->employee_code, $employee->department?->name])->filter()->implode(' · '),
                'salary' => $salaries->has($employee->id) ? (float) $salaries[$employee->id] : null,
                'loans' => $loans->count(),
                'owed' => (float) $loans->sum('outstanding_amount'),
                'instalments' => (float) $loans->sum(fn (Loan $l) => min((float) $l->monthly_installment, (float) $l->outstanding_amount)),
            ]];
        })->all();
    }
}
