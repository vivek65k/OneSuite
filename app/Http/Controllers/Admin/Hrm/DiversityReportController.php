<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DiversityReportController extends Controller
{
    use ExportsCsv;

    public function index(): View
    {
        $this->authorize('viewAny', Employee::class);

        $employees = $this->activeQuery()->with('department')->get();

        $byDepartmentGender = $employees
            ->groupBy(fn (Employee $e) => $e->department?->name ?? 'Unassigned')
            ->map(fn ($group) => $group->countBy(fn (Employee $e) => $e->gender ?: 'unspecified'));

        return view('admin.hrm.reports.diversity', [
            'total' => $employees->count(),
            'byGender' => $employees->countBy(fn (Employee $e) => $e->gender ?: 'unspecified'),
            'byEmploymentType' => $employees->countBy(fn (Employee $e) => $e->employment_type ?: 'Unspecified'),
            'byDepartmentGender' => $byDepartmentGender,
        ]);
    }

    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', Employee::class);

        $employees = $this->activeQuery()->with(['department', 'branch'])->orderBy('name')->get();

        return $this->streamCsv(
            'diversity-report.csv',
            ['Employee Code', 'Name', 'Gender', 'Department', 'Branch', 'Employment Type'],
            (function () use ($employees) {
                foreach ($employees as $employee) {
                    yield [
                        $employee->employee_code, $employee->name, $employee->gender,
                        $employee->department?->name, $employee->branch?->name, $employee->employment_type,
                    ];
                }
            })()
        );
    }

    protected function activeQuery()
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->where('status', 'active')
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
