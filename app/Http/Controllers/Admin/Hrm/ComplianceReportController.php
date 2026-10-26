<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EsiSetting;
use App\Models\PfSetting;
use App\Models\TaxDeclaration;
use App\Support\BranchContext;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceReportController extends Controller
{
    use ExportsCsv;

    public function index(): View
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $companyId = Company::current()->id;
        $financialYear = TaxDeclaration::currentFinancialYear();

        return view('admin.hrm.reports.compliance', [
            'pfSetting' => PfSetting::forBranch($companyId, BranchContext::currentBranchId()),
            'esiSetting' => EsiSetting::forBranch($companyId, BranchContext::currentBranchId()),
            'financialYear' => $financialYear,
            'missingTaxDeclaration' => $this->missingTaxDeclaration($financialYear),
            'missingKyc' => $this->missingKyc(),
        ]);
    }

    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $financialYear = TaxDeclaration::currentFinancialYear();
        $missingTax = $this->missingTaxDeclaration($financialYear)->map(fn ($e) => [$e->name, $e->employee_code, 'Tax Declaration ('.$financialYear.')']);
        $missingKyc = $this->missingKyc()->map(fn ($row) => [$row['employee']->name, $row['employee']->employee_code, 'Missing: '.implode(', ', $row['missing'])]);

        return $this->streamCsv(
            'compliance-report.csv',
            ['Employee', 'Employee Code', 'Compliance Gap'],
            (function () use ($missingTax, $missingKyc) {
                foreach ($missingTax->concat($missingKyc) as $row) {
                    yield $row;
                }
            })()
        );
    }

    protected function missingTaxDeclaration(string $financialYear): Collection
    {
        $companyId = Company::current()->id;

        $declaredEmployeeIds = TaxDeclaration::query()
            ->where('company_id', $companyId)
            ->where('financial_year', $financialYear)
            ->pluck('employee_id');

        return $this->activeEmployees()->whereNotIn('id', $declaredEmployeeIds)->get();
    }

    protected function missingKyc(): Collection
    {
        $employees = $this->activeEmployees()
            ->with(['documents' => fn ($q) => $q->whereIn('category', ['pan', 'aadhar'])])
            ->get();

        return $employees->map(function ($employee) {
            $have = $employee->documents->pluck('category')->all();
            $missing = array_values(array_diff(['pan', 'aadhar'], $have));

            return ['employee' => $employee, 'missing' => $missing];
        })->filter(fn ($row) => $row['missing'] !== [])->values();
    }

    protected function activeEmployees()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->where('status', 'active')
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
