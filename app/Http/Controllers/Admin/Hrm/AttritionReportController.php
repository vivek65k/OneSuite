<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Resignation;
use App\Support\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttritionReportController extends Controller
{
    use ExportsCsv;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Resignation::class);

        $year = (int) ($request->query('year') ?: now()->year);
        $resignations = $this->yearQuery($year)->with(['employee', 'employee.department'])->get();

        $currentHeadcount = Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->count();

        $attritionRate = $currentHeadcount > 0
            ? round($resignations->count() / $currentHeadcount * 100, 1)
            : 0;

        return view('admin.hrm.reports.attrition', [
            'year' => $year,
            'resignations' => $resignations,
            'currentHeadcount' => $currentHeadcount,
            'attritionRate' => $attritionRate,
            'byMonth' => $resignations->countBy(fn (Resignation $r) => $r->resignation_date->format('M')),
            'byDepartment' => $resignations->countBy(fn (Resignation $r) => $r->employee->department?->name ?? 'Unassigned'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Resignation::class);

        $year = (int) ($request->query('year') ?: now()->year);
        $resignations = $this->yearQuery($year)->with(['employee', 'employee.department'])->get();

        return $this->streamCsv(
            "attrition-report-{$year}.csv",
            ['Employee', 'Department', 'Resignation Date', 'Last Working Day', 'Status'],
            (function () use ($resignations) {
                foreach ($resignations as $resignation) {
                    yield [
                        $resignation->employee->name, $resignation->employee->department?->name,
                        $resignation->resignation_date->format('Y-m-d'), $resignation->last_working_day->format('Y-m-d'),
                        Resignation::STATUSES[$resignation->status] ?? $resignation->status,
                    ];
                }
            })()
        );
    }

    protected function yearQuery(int $year): Builder
    {
        return Resignation::query()
            ->where('company_id', Company::current()->id)
            ->whereYear('resignation_date', $year)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())));
    }
}
