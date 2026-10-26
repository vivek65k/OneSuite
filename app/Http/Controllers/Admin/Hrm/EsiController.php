<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\EsiSetting;
use App\Models\TaxDeclaration;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EsiController extends Controller
{
    use ExportsCsv;

    /** Employees payroll no longer pays. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EsiSetting::class);

        $setting = EsiSetting::forBranch(Company::current()->id, BranchContext::currentBranchId());
        $runs = $this->runs();
        $period = $this->period($request, $runs);
        $all = $this->payslips($period)->get();
        $covered = $all->filter(fn (Payslip $p) => (float) $p->esi_employee_amount > 0 || (float) $p->esi_employer_amount > 0)->values();

        return view('admin.hrm.payroll.esi.index', [
            'setting' => $setting,
            'period' => $period,
            'periods' => $runs->map(fn (PayrollRun $r) => sprintf('%04d-%02d', $r->year, $r->month))->unique()->values(),
            'run' => $runs->first(fn (PayrollRun $r) => (int) $r->year === $period->year && (int) $r->month === $period->month),
            'payslips' => $covered,
            'notCovered' => $all->reject(fn (Payslip $p) => $covered->contains('id', $p->id))->values(),
            'totals' => [
                'slips' => $all->count(),
                'employee' => round((float) $covered->sum(fn (Payslip $p) => (float) $p->esi_employee_amount), 2),
                'employer' => round((float) $covered->sum(fn (Payslip $p) => (float) $p->esi_employer_amount), 2),
            ],
            'yearToDate' => $this->yearToDate($period),
            'estimate' => $this->estimate(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = EsiSetting::forBranch(Company::current()->id, BranchContext::currentBranchId());

        $this->authorize('update', $setting);

        $data = $request->validate([
            'employee_contribution_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'employer_contribution_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'wage_ceiling' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
        ], [
            'wage_ceiling.gt' => 'The wage ceiling must be more than zero. With a ceiling of zero nobody would be covered; switch ESI off instead.',
            'wage_ceiling.required' => 'ESI needs a wage ceiling: only people earning at or below it are covered.',
        ]);

        $setting->update([
            'employee_contribution_percent' => round((float) $data['employee_contribution_percent'], 2),
            'employer_contribution_percent' => round((float) $data['employer_contribution_percent'], 2),
            'wage_ceiling' => round((float) $data['wage_ceiling'], 2),
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return redirect()->route('admin.esi.index')->with('status', 'ESI settings saved. They apply from the next payroll run; payslips already generated are not changed.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', EsiSetting::class);

        $period = $this->period($request, $this->runs());
        $rows = $this->payslips($period)->get()->filter(fn (Payslip $p) => (float) $p->esi_employee_amount > 0 || (float) $p->esi_employer_amount > 0)->values();
        // A cell starting with = + - @ would run as a formula when the file is opened in a spreadsheet.
        $safe = fn (?string $text) => preg_match('/^[=+\-@\t\r]/', (string) $text) ? "'".$text : (string) $text;
        $num = fn ($n) => number_format((float) $n, 2, '.', '');

        return $this->streamCsv(
            'esi-register-'.$period->format('Y-m').'.csv',
            ['Employee Code', 'Employee', 'Department', 'Gross Earnings', 'ESI (Employee)', 'ESI (Employer)', 'Total ESI'],
            (function () use ($rows, $safe, $num) {
                foreach ($rows as $p) {
                    yield [
                        $safe($p->employee?->employee_code), $safe($p->employee?->name ?? 'Removed employee'), $safe($p->employee?->department?->name),
                        $num($p->gross_earnings), $num($p->esi_employee_amount), $num($p->esi_employer_amount), $num((float) $p->esi_employee_amount + (float) $p->esi_employer_amount),
                    ];
                }

                if ($rows->isNotEmpty()) {
                    $employee = $rows->sum(fn (Payslip $p) => (float) $p->esi_employee_amount);
                    $employer = $rows->sum(fn (Payslip $p) => (float) $p->esi_employer_amount);

                    yield ['', 'Total ('.$rows->count().')', '', $num($rows->sum(fn (Payslip $p) => (float) $p->gross_earnings)), $num($employee), $num($employer), $num($employee + $employer)];
                }
            })()
        );
    }

    /** The month being shown: ?period=YYYY-MM, the older ?month=&year=, else the latest month payroll was run. */
    protected function period(Request $request, Collection $runs): Carbon
    {
        $q = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';

        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $q('period'), $m) && (int) $m[1] >= 2000 && (int) $m[1] <= 2100) {
            return Carbon::create((int) $m[1], (int) $m[2], 1);
        }

        if (ctype_digit($q('month')) && ctype_digit($q('year')) && (int) $q('month') >= 1 && (int) $q('month') <= 12 && (int) $q('year') >= 2000 && (int) $q('year') <= 2100) {
            return Carbon::create((int) $q('year'), (int) $q('month'), 1);
        }

        $latest = $runs->first();

        return $latest ? Carbon::create((int) $latest->year, (int) $latest->month, 1) : today()->startOfMonth();
    }

    /** Payroll runs this login may see, newest first. */
    protected function runs(): Collection
    {
        return PayrollRun::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')))
            ->orderByDesc('year')->orderByDesc('month')
            ->get();
    }

    /** Payslips this login may see; the same rule as the Payslips page (branch of the run, not of the employee today). */
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

    protected function payslips(Carbon $period): Builder
    {
        return $this->scoped()
            ->whereHas('payrollRun', fn ($q) => $q->where('month', $period->month)->where('year', $period->year))
            ->with('employee.department:id,name')
            ->orderBy(Employee::query()->select('name')->whereColumn('employees.id', 'payslips.employee_id'))
            ->orderBy('id');
    }

    /** ESI from April of the period's financial year up to and including the period. */
    protected function yearToDate(Carbon $period): array
    {
        $start = (int) substr(TaxDeclaration::currentFinancialYear($period), 0, 4);
        $row = $this->scoped()
            ->whereHas('payrollRun', fn ($q) => $q->whereRaw('`year` * 100 + `month` BETWEEN ? AND ?', [$start * 100 + 4, $period->year * 100 + $period->month]))
            ->selectRaw('COALESCE(SUM(esi_employee_amount), 0) as employee, COALESCE(SUM(esi_employer_amount), 0) as employer, COUNT(DISTINCT payroll_run_id) as months')
            ->toBase()->first();

        return ['label' => 'FY '.$start.'–'.substr((string) ($start + 1), 2), 'employee' => (float) $row->employee, 'employer' => (float) $row->employer, 'months' => (int) $row->months];
    }

    /**
     * Everyone on payroll with a salary structure and their monthly gross, so
     * the page can show who the next payroll would cover. ESI is charged on
     * gross pay, and only for people earning at or below the wage ceiling.
     */
    protected function estimate(): array
    {
        $people = Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->whereNotIn('status', self::INACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);
        $salaries = \App\Support\LoanRules::salaries($people->pluck('id'));

        return [
            'people' => $people->filter(fn (Employee $e) => (float) $salaries->get($e->id, 0) > 0)
                ->map(fn (Employee $e) => ['name' => $e->name, 'gross' => (float) $salaries[$e->id]])->values()->all(),
        ];
    }
}
