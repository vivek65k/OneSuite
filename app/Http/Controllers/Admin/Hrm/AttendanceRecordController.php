<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Department;
use App\Support\Attendance;
use App\Support\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceRecordController extends Controller
{
    use ExportsCsv, PersistsTableState;

    public function daily(Request $request): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $date = $this->validDate($request->query('date')) ?? now()->toDateString();
        $state = $request->only(['status', 'search', 'department_id']);

        $employees = $this->employeesQuery($state)
            ->with(['department', 'attendanceRecords' => fn ($q) => $q->whereDate('date', $date)])
            ->orderBy('name')
            ->get();

        // Status chips count the whole (search/department-filtered) roster,
        // before the status chip itself narrows it.
        $counts = $employees->countBy(fn ($employee) => $employee->attendanceRecords->first()?->status ?? 'unmarked');

        if ($status = $state['status'] ?? null) {
            $employees = $employees->filter(fn ($employee) => ($employee->attendanceRecords->first()?->status ?? 'unmarked') === $status)->values();
        }

        return view('admin.hrm.attendance.daily', [
            'date' => $date,
            'day' => Carbon::parse($date),
            'employees' => $employees,
            'counts' => $counts,
            'rosterTotal' => (int) $counts->sum(),
            'state' => $state,
            'departments' => $this->departmentOptions(),
        ]);
    }

    public function markDaily(Request $request): RedirectResponse
    {
        $this->authorize('create', AttendanceRecord::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_id' => ['required', $this->employeeExistsRule()],
            'date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(AttendanceRecord::STATUSES))],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
        ]);

        AttendanceRecord::upsertForDate((int) $data['employee_id'], $companyId, $data['date'], [
            'status' => $data['status'],
            'check_in_time' => $data['check_in_time'] ?? null,
            'check_out_time' => $data['check_out_time'] ?? null,
            'source' => 'manual',
        ]);

        return back(fallback: route('admin.attendance.daily', ['date' => $data['date']]))->with('status', 'Attendance updated.');
    }

    /**
     * Mark several employees at once (the daily sheet's bulk bar).
     * Existing check-in/out times are kept - only the status changes.
     */
    public function markBulk(Request $request): RedirectResponse
    {
        $this->authorize('create', AttendanceRecord::class);

        $companyId = Company::current()->id;

        $data = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', $this->employeeExistsRule()],
            'date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(AttendanceRecord::STATUSES))],
        ]);

        foreach (array_unique($data['employee_ids']) as $employeeId) {
            AttendanceRecord::upsertForDate((int) $employeeId, $companyId, $data['date'], [
                'status' => $data['status'],
                'source' => 'manual',
            ]);
        }

        $count = count(array_unique($data['employee_ids']));

        return back(fallback: route('admin.attendance.daily', ['date' => $data['date']]))
            ->with('status', "{$count} ".($count === 1 ? 'employee' : 'employees').' marked '.strtolower(AttendanceRecord::STATUSES[$data['status']]).'.');
    }

    public function monthly(Request $request): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month')) ? $request->query('month') : now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $state = $request->only(['search', 'department_id']);

        $employees = $this->employeesQuery($state)
            // whereBetween on plain date-string boundaries would silently
            // drop the month's last day — see AttendanceRecord::upsertForDate()
            // for why stored values carry a time component. whereDate()
            // compares only the date part, boundary edge cases included.
            ->with(['department', 'attendanceRecords' => fn ($q) => $q->whereDate('date', '>=', $start)->whereDate('date', '<=', $end)])
            ->orderBy('name')
            ->get();

        return view('admin.hrm.attendance.monthly', [
            'month' => $month,
            'days' => collect(range(1, $start->daysInMonth)),
            'start' => $start,
            'employees' => $employees,
            'state' => $state,
            'departments' => $this->departmentOptions(),
        ]);
    }

    public function punchLogs(Request $request): View|RedirectResponse
    {
        return $this->logList($request, 'punch-logs', false);
    }

    public function geo(Request $request): View|RedirectResponse
    {
        return $this->logList($request, 'geo-attendance', true);
    }

    /** Punch logs and geo check-ins share one list screen. */
    protected function logList(Request $request, string $stateKey, bool $geoOnly): View|RedirectResponse
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $state = $this->resolveTableState($request, $stateKey, [
            'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $perPage = $this->clampPerPage($state['per_page'] ?? 25);
        $base = fn (array $state) => $this->filteredQuery($state)->when($geoOnly, fn ($q) => $q->where('source', 'geo'));

        $records = $base($state)
            ->with('employee.department')
            ->orderByDesc('date')
            ->orderByDesc('check_in_time')
            ->paginate($perPage)
            ->withQueryString();

        // Status-tab counts ignore the status filter itself.
        $statusCounts = $base(array_diff_key($state, ['status' => true]))
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count);

        $filtered = $base($state)->toBase();
        $kpis = [
            'records' => (clone $filtered)->count(),
            'employees' => (clone $filtered)->distinct()->count('employee_id'),
            'with_checkout' => (clone $filtered)->whereNotNull('check_out_time')->count(),
            'avg_hours' => (clone $filtered)->whereNotNull('work_hours')->avg('work_hours'),
            'by_source' => (clone $filtered)->selectRaw('source, COUNT(*) as aggregate')->groupBy('source')->pluck('aggregate', 'source')->map(fn ($c) => (int) $c),
        ];

        return view($geoOnly ? 'admin.hrm.attendance.geo' : 'admin.hrm.attendance.punch-logs', [
            'records' => $records,
            'state' => $state,
            'statusCounts' => $statusCounts->put('all', (int) $statusCounts->sum()),
            'kpis' => $kpis,
            'employees' => $this->employeeOptions(),
            'departments' => $this->departmentOptions(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $state = $request->query();
        $records = $this->filteredQuery($state)->with('employee')->orderByDesc('date')->lazy(500);

        return $this->streamCsv(
            'attendance-'.now()->format('Y-m-d-His').'.csv',
            ['Employee', 'Date', 'Check In', 'Check Out', 'Status', 'Source', 'Work Hours'],
            (function () use ($records) {
                foreach ($records as $record) {
                    yield [
                        $record->employee?->name, $record->date->format('Y-m-d'),
                        $record->check_in_time, $record->check_out_time,
                        AttendanceRecord::STATUSES[$record->status] ?? $record->status,
                        AttendanceRecord::SOURCES[$record->source] ?? $record->source,
                        $record->work_hours,
                    ];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return Attendance::records()
            ->when($state['employee_id'] ?? null, fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['from'] ?? null, fn ($q, $from) => $q->whereDate('date', '>=', $from))
            ->when($state['to'] ?? null, fn ($q, $to) => $q->whereDate('date', '<=', $to))
            ->when($state['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($state['source'] ?? null, fn ($q, $source) => $q->where('source', $source))
            ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $departmentId)))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->whereHas('employee', fn ($eq) => $eq->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%");
            })));
    }

    /** Active roster for the daily / monthly sheets, with search + department filters. */
    protected function employeesQuery(array $state): Builder
    {
        return Attendance::employees()
            ->whereNotIn('status', ['terminated', 'inactive'])
            ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->where('department_id', $departmentId))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%");
            }));
    }

    protected function employeeExistsRule()
    {
        return Rule::exists('employees', 'id')
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    protected function employeeOptions()
    {
        return Attendance::employees()->orderBy('name')->pluck('name', 'id');
    }

    protected function departmentOptions()
    {
        return Department::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function validDate(?string $value): ?string
    {
        return $value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
    }
}
