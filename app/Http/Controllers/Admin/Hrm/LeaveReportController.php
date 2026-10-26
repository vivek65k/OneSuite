<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\LeaveBalance;
use App\Models\LeaveEncashment;
use App\Models\LeaveRequest;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveReportController extends Controller
{
    use ExportsCsv;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LeaveBalance::class);

        $year = $this->validYear($request->query('year'));
        $state = $request->only(['search', 'department_id', 'type', 'sort']);
        $balances = $this->buildSummary($year, $state);

        $entitled = fn (LeaveBalance $b) => (float) $b->allocated + (float) $b->carried_forward + (float) $b->adjusted;
        $totals = [
            'entitled' => $balances->sum($entitled),
            'used' => (float) $balances->sum('used'),
            'encashed' => (float) $balances->sum('encashed'),
            'remaining' => (float) $balances->sum(fn ($b) => max(0, (float) $b->remaining)),
        ];

        // Per leave type (by name, so each branch's "Casual Leave" is one row).
        $byType = $balances->groupBy(fn ($b) => $b->leaveType?->name ?? 'Unknown')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'code' => $rows->first()->leaveType?->code,
                'entitled' => $rows->sum($entitled),
                'used' => (float) $rows->sum('used'),
                'encashed' => (float) $rows->sum('encashed'),
                'remaining' => (float) $rows->sum(fn ($b) => max(0, (float) $b->remaining)),
                'employees' => $rows->pluck('employee_id')->unique()->count(),
            ])
            ->sortByDesc('entitled')
            ->values();

        // Per department.
        $byDepartment = $balances->groupBy(fn ($b) => $b->employee?->department?->name ?? 'No department')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'entitled' => $rows->sum($entitled),
                'used' => (float) $rows->sum('used'),
                'employees' => $rows->pluck('employee_id')->unique()->count(),
            ])
            ->sortByDesc(fn ($d) => $d['entitled'] ? $d['used'] / $d['entitled'] : 0)
            ->values();

        // Per employee, for the people insights.
        $people = $balances->groupBy('employee_id')
            ->map(fn ($rows) => [
                'employee' => $rows->first()->employee,
                'entitled' => $rows->sum($entitled),
                'used' => (float) $rows->sum('used'),
                'remaining' => (float) $rows->sum(fn ($b) => max(0, (float) $b->remaining)),
            ])
            ->values();
        $elapsed = $year < now()->year ? 1.0 : ($year > now()->year ? 0.0 : now()->dayOfYear / now()->daysInYear);

        // Approved leave days per month (multi-day leave split across months), by type name.
        $monthly = array_fill(1, 12, []);
        $requests = $this->requestsQuery($year, $state)->with('leaveType:id,name,code')->get();
        foreach ($requests->where('status', 'approved') as $leave) {
            for ($d = $leave->start_date->copy()->max(Carbon::create($year)->startOfYear()); $d->lte($leave->end_date) && $d->year === $year; $d->addDay()) {
                $name = $leave->leaveType?->name ?? 'Leave';
                $monthly[$d->month][$name] = ($monthly[$d->month][$name] ?? 0) + 1;
            }
        }

        $reviewed = $requests->whereIn('status', ['approved', 'rejected'])->count();

        return view('admin.hrm.leave.reports', [
            'year' => $year,
            'state' => $state,
            'summary' => $this->sortRows($balances, $state['sort'] ?? 'name'),
            'totals' => $totals,
            'byType' => $byType,
            'byDepartment' => $byDepartment,
            'monthly' => $monthly,
            'monthlyMax' => max(1, collect($monthly)->map(fn ($m) => array_sum($m))->max()),
            'topTakers' => $people->sortByDesc('used')->filter(fn ($p) => $p['used'] > 0)->take(5)->values(),
            // Low use relative to how far the year has gone, with real days left.
            'notTaking' => $people->filter(fn ($p) => $p['entitled'] > 0 && $p['remaining'] >= 5 && ($p['used'] / $p['entitled']) < max(0.15, $elapsed * 0.4))
                ->sortByDesc('remaining')->take(5)->values(),
            'requestStats' => [
                'approved' => $requests->where('status', 'approved')->count(),
                'rejected' => $requests->where('status', 'rejected')->count(),
                'pending' => $requests->where('status', 'pending')->count(),
                'approval_rate' => $reviewed ? (int) round($requests->where('status', 'approved')->count() / $reviewed * 100) : null,
            ],
            'encashedAmount' => (float) LeaveEncashment::query()
                ->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())))
                ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $departmentId)))
                ->when($state['type'] ?? null, fn ($q, $type) => $q->whereHas('leaveType', fn ($tq) => $tq->where('name', $type)))
                ->when($state['search'] ?? null, fn ($q, $search) => $q->whereHas('employee', fn ($eq) => $eq->where('name', 'like', "%{$search}%")))
                ->where('year', $year)->where('status', 'approved')->sum('amount'),
            'currency' => Company::current()->currency,
            'departments' => Department::query()
                ->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'typeNames' => $this->buildSummary($year, [])->map(fn ($b) => $b->leaveType?->name)->filter()->unique()->sort()->values(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', LeaveBalance::class);

        $year = $this->validYear($request->query('year'));
        $state = $request->only(['search', 'department_id', 'type', 'sort']);
        $summary = $this->sortRows($this->buildSummary($year, $state), $state['sort'] ?? 'name');

        return $this->streamCsv(
            "leave-report-{$year}.csv",
            ['Employee', 'Department', 'Leave Type', 'Allocated', 'Carried Forward', 'Adjusted', 'Used', 'Encashed', 'Remaining', 'Utilization %'],
            (function () use ($summary) {
                foreach ($summary as $row) {
                    $entitled = (float) $row->allocated + (float) $row->carried_forward + (float) $row->adjusted;
                    yield [
                        $row->employee->name, $row->employee->department?->name, $row->leaveType->name, $row->allocated,
                        $row->carried_forward, $row->adjusted, $row->used, $row->encashed, $row->remaining,
                        $entitled > 0 ? round((float) $row->used / $entitled * 100) : '',
                    ];
                }
            })()
        );
    }

    protected function buildSummary(int $year, array $state = []): Collection
    {
        $companyId = Company::current()->id;

        return LeaveBalance::query()
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())))
            ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $departmentId)))
            ->when($state['type'] ?? null, fn ($q, $type) => $q->whereHas('leaveType', fn ($tq) => $tq->where('name', $type)))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->whereHas('employee', fn ($eq) => $eq->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%");
            })))
            ->with(['employee.department', 'leaveType'])
            ->get()
            ->filter(fn ($balance) => $balance->employee && $balance->leaveType)
            ->values();
    }

    protected function requestsQuery(int $year, array $state)
    {
        return LeaveRequest::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())))
            ->where(fn ($q) => $q->whereYear('start_date', $year)->orWhereYear('end_date', $year))
            ->when($state['department_id'] ?? null, fn ($q, $departmentId) => $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $departmentId)))
            ->when($state['type'] ?? null, fn ($q, $type) => $q->whereHas('leaveType', fn ($tq) => $tq->where('name', $type)))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->whereHas('employee', fn ($eq) => $eq->where('name', 'like', "%{$search}%")));
    }

    protected function sortRows(Collection $rows, string $sort): Collection
    {
        $util = fn ($b) => ($e = (float) $b->allocated + (float) $b->carried_forward + (float) $b->adjusted) > 0 ? (float) $b->used / $e : 0;

        $sorted = match ($sort) {
            'used' => $rows->sortByDesc(fn ($b) => (float) $b->used),
            'remaining' => $rows->sortByDesc(fn ($b) => (float) $b->remaining),
            'util_desc' => $rows->sortByDesc($util),
            'util_asc' => $rows->sortBy($util),
            default => $rows->sortBy(fn ($b) => $b->employee->name.' '.$b->leaveType->name),
        };

        return $sorted->values();
    }

    protected function validYear(?string $value): int
    {
        $year = (int) $value;

        return $year >= 2000 && $year <= 2100 ? $year : (int) now()->year;
    }
}
