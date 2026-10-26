<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Support\Attendance;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    use ExportsCsv;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $month = $this->validMonth($request->query('month'));
        $state = $request->only(['search', 'department_id', 'sort']);
        $summary = $this->buildSummary($month, $state);

        $rated = $summary->whereNotNull('rate');
        $totals = collect(['present', 'absent', 'half_day', 'on_leave', 'wfh', 'overtime_hours', 'marked'])
            ->mapWithKeys(fn ($key) => [$key => $summary->sum($key)]);

        return view('admin.hrm.attendance.reports', [
            'month' => $month,
            'start' => Carbon::createFromFormat('Y-m-d', $month.'-01'),
            'summary' => $summary,
            'totals' => $totals,
            'state' => $state,
            'departments' => Department::query()
                ->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'kpis' => [
                'avg_rate' => $rated->isNotEmpty() ? (int) round($rated->avg('rate')) : null,
                'perfect' => $summary->filter(fn ($row) => $row['marked'] > 0 && $row['absent'] === 0 && $row['half_day'] === 0)->count(),
                'absences' => (int) $totals['absent'],
                'overtime' => (float) $totals['overtime_hours'],
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $month = $this->validMonth($request->query('month'));
        $summary = $this->buildSummary($month, $request->only(['search', 'department_id', 'sort']));

        return $this->streamCsv(
            "attendance-report-{$month}.csv",
            ['Employee', 'Present', 'Absent', 'Half Day', 'On Leave', 'WFH', 'Overtime Hours', 'Attendance %'],
            (function () use ($summary) {
                foreach ($summary as $row) {
                    yield [
                        $row['employee']->name, $row['present'], $row['absent'],
                        $row['half_day'], $row['on_leave'], $row['wfh'], $row['overtime_hours'],
                        $row['rate'] ?? '',
                    ];
                }
            })()
        );
    }

    protected function buildSummary(string $month, array $state = []): Collection
    {
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $companyId = Company::current()->id;

        // whereDate(), not whereBetween() with plain date-string boundaries —
        // see AttendanceRecord::upsertForDate() for why stored date values
        // carry a time component that would make the month's last day
        // silently fall outside a whereBetween(['...-01', '...-31']) range.
        $employees = Attendance::employees()
            ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->where('department_id', $departmentId))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%");
            }))
            ->with(['department', 'attendanceRecords' => fn ($q) => $q->whereDate('date', '>=', $start)->whereDate('date', '<=', $end)])
            ->orderBy('name')
            ->get();

        $overtimeByEmployee = OvertimeRequest::query()
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->selectRaw('employee_id, SUM(hours) as total_hours')
            ->groupBy('employee_id')
            ->pluck('total_hours', 'employee_id');

        $rows = $employees->map(function (Employee $employee) use ($overtimeByEmployee) {
            $counts = $employee->attendanceRecords->countBy('status');
            $present = $counts->get('present', 0);
            $absent = $counts->get('absent', 0);
            $halfDay = $counts->get('half_day', 0);
            $onLeave = $counts->get('on_leave', 0);
            $wfh = $counts->get('wfh', 0);

            // Working days = days that expected work (holidays/weekends excluded).
            $workingDays = $present + $absent + $halfDay + $onLeave + $wfh;
            $attended = $present + $wfh + $halfDay * 0.5;

            return [
                'employee' => $employee,
                'present' => $present,
                'absent' => $absent,
                'half_day' => $halfDay,
                'on_leave' => $onLeave,
                'wfh' => $wfh,
                'overtime_hours' => (float) $overtimeByEmployee->get($employee->id, 0),
                'marked' => $workingDays,
                'rate' => $workingDays ? (int) round($attended / $workingDays * 100) : null,
            ];
        });

        return match ($state['sort'] ?? 'name') {
            'rate_desc' => $rows->sortByDesc(fn ($row) => $row['rate'] ?? -1)->values(),
            'rate_asc' => $rows->sortBy(fn ($row) => $row['rate'] ?? 101)->values(),
            'absent' => $rows->sortByDesc('absent')->values(),
            'overtime' => $rows->sortByDesc('overtime_hours')->values(),
            default => $rows->values(),
        };
    }

    protected function validMonth(?string $value): string
    {
        return $value && preg_match('/^\d{4}-\d{2}$/', $value) ? $value : now()->format('Y-m');
    }
}
