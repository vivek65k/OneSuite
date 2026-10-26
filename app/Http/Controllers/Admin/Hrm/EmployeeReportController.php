<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeReportController extends Controller
{
    use ExportsCsv;

    public function index(): View
    {
        $this->authorize('viewAny', Employee::class);

        $employees = $this->companyQuery()->with(['department', 'branch'])->get();

        return view('admin.hrm.reports.employees', [
            'total' => $employees->count(),
            'byStatus' => $employees->countBy('status'),
            'byDepartment' => $employees->countBy(fn (Employee $e) => $e->department?->name ?? 'Unassigned'),
            'byBranch' => $employees->countBy(fn (Employee $e) => $e->branch?->name ?? 'Unassigned'),
            'byEmploymentType' => $employees->countBy(fn (Employee $e) => $e->employment_type ?: 'Unspecified'),
        ]);
    }

    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', Employee::class);

        $employees = $this->companyQuery()->with(['department', 'branch'])->orderBy('name')->get();

        return $this->streamCsv(
            'employee-report.csv',
            ['Employee Code', 'Name', 'Department', 'Branch', 'Designation', 'Employment Type', 'Status', 'Joining Date'],
            (function () use ($employees) {
                foreach ($employees as $employee) {
                    yield [
                        $employee->employee_code, $employee->name, $employee->department?->name, $employee->branch?->name,
                        $employee->designation, $employee->employment_type, $employee->status,
                        optional($employee->joining_date)->format('Y-m-d'),
                    ];
                }
            })()
        );
    }

    protected function companyQuery()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
