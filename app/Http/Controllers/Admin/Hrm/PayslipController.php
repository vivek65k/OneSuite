<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payslip::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'status' => in_array($text('status'), array_keys(Payslip::STATUSES), true) ? $text('status') : '',
            'run' => $id('run'),
            'department_id' => $id('department_id'),
            'employee_id' => $id('employee_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        // Everything but the status tab, so the tabs and KPIs describe the same set.
        $filtered = fn () => $this->scoped()
            ->when($state['run'], fn ($q, $run) => $q->where('payroll_run_id', $run))
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->whereHas('employee', fn ($e) => $e->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('employee_code', 'like', $like)));
            });

        $summary = $filtered()
            ->selectRaw('status, COUNT(*) as slips, SUM(gross_earnings) as gross, SUM(total_deductions) as deductions, SUM(net_pay) as net')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $sum = fn (string $column) => (float) $summary->sum($column);

        $payslips = $filtered()
            ->when($state['status'], fn ($q, $status) => $q->where('status', $status))
            ->with(['employee.department:id,name', 'payrollRun'])
            ->orderByDesc(PayrollRun::query()->selectRaw('`year` * 100 + `month`')->whereColumn('payroll_runs.id', 'payslips.payroll_run_id'))
            ->orderBy(Employee::query()->select('name')->whereColumn('employees.id', 'payslips.employee_id'))
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $shown = $state['status'] ? $summary->only([$state['status']]) : $summary;

        return view('admin.hrm.payroll.payslips.index', [
            'payslips' => $payslips,
            'state' => $state,
            'runs' => $this->runOptions(),
            'departments' => $this->departmentOptions(),
            'employees' => $this->employeeOptions(),
            'counts' => [
                'all' => (int) $summary->sum('slips'),
                'generated' => (int) ($summary['generated']->slips ?? 0),
                'paid' => (int) ($summary['paid']->slips ?? 0),
            ],
            'kpis' => [
                'gross' => $sum('gross'),
                'deductions' => $sum('deductions'),
                'net' => $sum('net'),
                'unpaid_net' => (float) ($summary['generated']->net ?? 0),
                'paid_net' => (float) ($summary['paid']->net ?? 0),
            ],
            'totals' => [
                'slips' => (int) $shown->sum('slips'),
                'gross' => (float) $shown->sum('gross'),
                'deductions' => (float) $shown->sum('deductions'),
                'net' => (float) $shown->sum('net'),
            ],
        ]);
    }

    public function show(Payslip $payslip): View
    {
        $this->authorize('viewAny', Payslip::class);
        $this->ensureOwn($payslip);

        $payslip->load(['employee.department:id,name', 'payrollRun.processedBy:id,name', 'items']);
        $run = $payslip->payrollRun;

        // The same person's other payslips, newest first, for the side panel and year-to-date.
        $history = $this->scoped()
            ->where('employee_id', $payslip->employee_id)
            ->with('payrollRun')
            ->get()
            ->filter(fn ($p) => $p->payrollRun)
            ->sortByDesc(fn ($p) => $p->payrollRun->year * 100 + $p->payrollRun->month)
            ->values();
        $ytd = $run
            ? $history->filter(fn ($p) => (int) $p->payrollRun->year === (int) $run->year && (int) $p->payrollRun->month <= (int) $run->month)
            : collect();
        $position = $history->search(fn ($p) => $p->id === $payslip->id);

        return view('admin.hrm.payroll.payslips.show', [
            'payslip' => $payslip,
            'company' => Company::current(),
            'history' => $history,
            'newer' => $position !== false && $position > 0 ? $history[$position - 1] : null,
            'older' => $position !== false ? $history->get($position + 1) : null,
            'ytd' => [
                'months' => $ytd->count(),
                'gross' => (float) $ytd->sum('gross_earnings'),
                'deductions' => (float) $ytd->sum('total_deductions'),
                'net' => (float) $ytd->sum('net_pay'),
            ],
        ]);
    }

    /**
     * Payslips this login may see: its own company, and when the login is tied
     * to a branch, only that branch's payroll runs (or, for a company-wide run,
     * only that branch's employees).
     */
    protected function scoped(): Builder
    {
        $branchId = BranchContext::currentBranchId();

        return Payslip::query()
            ->where('payslips.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('payrollRun', fn ($r) => $r->where('branch_id', $branchId))
                ->orWhere(fn ($o) => $o
                    ->whereHas('payrollRun', fn ($r) => $r->whereNull('branch_id'))
                    ->whereHas('employee', fn ($e) => $e->where('branch_id', $branchId)))));
    }

    /** 404 (not 403) for another company's or branch's payslip, so the URL doesn't confirm it exists. */
    protected function ensureOwn(Payslip $payslip): void
    {
        abort_unless($this->scoped()->whereKey($payslip->id)->exists(), 404);
    }

    protected function runOptions()
    {
        return PayrollRun::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')))
            ->orderByDesc('year')->orderByDesc('month')
            ->get(['id', 'month', 'year', 'status'])
            ->mapWithKeys(fn ($run) => [$run->id => $run->monthLabel()]);
    }

    protected function departmentOptions()
    {
        return Department::query()
            ->where('company_id', Company::current()->id)
            ->whereIn('id', $this->employeeQuery()->whereNotNull('department_id')->select('department_id'))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function employeeOptions()
    {
        return $this->employeeQuery()->orderBy('name')->pluck('name', 'id');
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
