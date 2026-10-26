<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ExpenseClaim;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseReportController extends Controller
{
    use ExportsCsv;

    protected const SORTS = ['date', 'amount', 'name'];

    /** Claims the company accepted: approved (still to pay) or already paid back. */
    protected const SPEND = ['approved', 'reimbursed'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ExpenseClaim::class);

        $state = $this->state($request);
        $period = $this->claims($state);
        $inScope = $state['department_id'] ? $period->where('department_id', $state['department_id'])->values() : $period;
        $rows = $this->sorted($this->byStatus($inScope, $state['status']), $state['sort']);
        // Money is only counted for claims that were accepted; pending and rejected ones cost nothing (yet).
        $spend = $inScope->whereIn('status', self::SPEND)->values();
        $sum = fn (Collection $claims) => (float) $claims->sum('amount');
        $decided = $inScope->whereIn('status', ['approved', 'reimbursed', 'rejected']);
        $rejected = $inScope->where('status', 'rejected');
        $paid = $spend->where('status', 'reimbursed');

        return view('admin.hrm.expenses.reports', [
            'state' => $state,
            'years' => $this->scoped()->pluck('expense_date')->map(fn ($d) => $d?->year)->filter()->unique()->sortDesc()->values(),
            'departments' => $period->filter(fn ($r) => $r['department_id'])->pluck('department', 'department_id')->sort(),
            'rows' => $rows,
            'statusCounts' => $inScope->countBy('status'),
            'totals' => [
                'claims' => $spend->count(),
                'people' => $spend->pluck('employee_id')->unique()->count(),
                'spend' => $sum($spend),
                'paid' => $sum($paid),
                'owed' => $sum($spend->where('status', 'approved')),
                'owed_count' => $spend->where('status', 'approved')->count(),
                'per_claim' => $spend->count() ? $sum($spend) / $spend->count() : null,
                'per_person' => $spend->pluck('employee_id')->unique()->count() ? $sum($spend) / $spend->pluck('employee_id')->unique()->count() : null,
                'pending' => $sum($inScope->where('status', 'pending')),
                'pending_count' => $inScope->where('status', 'pending')->count(),
                'rejected' => $sum($rejected),
                'rejected_count' => $rejected->count(),
                'rejected_rate' => $decided->count() ? (int) round($rejected->count() / $decided->count() * 100) : null,
                'shown' => $sum($rows),
            ],
            'byCategory' => $spend->groupBy('category')->map(fn (Collection $g, string $name) => ['name' => $name, 'claims' => $g->count(), 'amount' => $sum($g)])->sortByDesc('amount')->values(),
            'claimants' => $spend->groupBy('employee_id')->map(fn (Collection $g) => ['employee' => $g->first()['claim']->employee, 'name' => $g->first()['name'], 'claims' => $g->count(), 'amount' => $sum($g)])->sortByDesc('amount')->take(3)->values(),
            'byMonth' => $this->byMonth($spend, $state),
            'byDepartment' => $spend->groupBy(fn ($r) => $r['department'] ?? 'No department')->map(fn (Collection $g, string $name) => [
                'name' => $name, 'id' => $g->first()['department_id'], 'claims' => $g->count(), 'people' => $g->pluck('employee_id')->unique()->count(), 'amount' => $sum($g),
            ])->sortByDesc('amount')->values(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', ExpenseClaim::class);

        $state = $this->state($request);
        $period = $this->claims($state);
        $rows = $this->sorted($this->byStatus($state['department_id'] ? $period->where('department_id', $state['department_id'])->values() : $period, $state['status']), $state['sort']);
        // A cell starting with = + - @ would run as a formula when the file is opened in a spreadsheet.
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;

        return $this->streamCsv(
            'expense-report'.($state['year'] ? '-'.$state['year'].($state['month'] ? '-'.sprintf('%02d', $state['month']) : '') : '').'.csv',
            // The first seven columns are where they always were; new ones are added at the end.
            ['Employee', 'Category', 'Amount', 'Date', 'Status', 'Reviewed By', 'Reimbursed At', 'Description', 'Department', 'Employee Code', 'Reviewed At', 'Rejection Reason', 'Days To Pay'],
            (function () use ($rows, $safe) {
                foreach ($rows as $row) {
                    $claim = $row['claim'];
                    yield array_map($safe, [
                        $row['name'], $row['category'], $claim->amount, $claim->expense_date->format('Y-m-d'),
                        ExpenseClaim::STATUSES[$claim->status] ?? $claim->status,
                        $claim->reviewedBy?->name, $claim->reimbursed_at?->format('Y-m-d'),
                        $claim->description, $row['department'], $claim->employee?->employee_code,
                        $claim->reviewed_at?->format('Y-m-d'), $claim->status === 'rejected' ? $claim->remarks : null, $row['days_to_pay'],
                    ]);
                }
            })()
        );
    }

    protected function state(Request $request): array
    {
        // Anything that isn't a plain value (e.g. ?year[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $year = ctype_digit($text('year')) && (int) $text('year') >= 2000 && (int) $text('year') <= 2100 ? (int) $text('year') : null;
        $month = $year && ctype_digit($text('month')) && (int) $text('month') >= 1 && (int) $text('month') <= 12 ? (int) $text('month') : null;
        $statuses = array_merge(['spend', 'all'], array_keys(ExpenseClaim::STATUSES));

        return [
            'year' => $year,
            // A month only means something within a year.
            'month' => $month,
            'department_id' => ctype_digit($text('department_id')) ? (int) $text('department_id') : null,
            // The report is about money spent, so it opens on accepted claims.
            'status' => in_array($text('status'), $statuses, true) ? $text('status') : 'spend',
            'sort' => in_array($text('sort'), self::SORTS, true) ? $text('sort') : 'date',
        ];
    }

    /** Every claim in the chosen period, newest first, flattened for grouping and sorting. */
    protected function claims(array $state): Collection
    {
        return $this->scoped()
            ->when($state['year'], fn ($q, $year) => $q->whereYear('expense_date', $year))
            ->when($state['month'], fn ($q, $month) => $q->whereMonth('expense_date', $month))
            ->with(['employee.department:id,name', 'category:id,name', 'reviewedBy:id,name'])
            ->orderByDesc('expense_date')->orderByDesc('id')
            ->get()
            ->map(fn (ExpenseClaim $claim) => [
                'claim' => $claim,
                'status' => $claim->status,
                'amount' => (float) $claim->amount,
                'employee_id' => $claim->employee_id,
                'name' => $claim->employee?->name ?? 'Removed employee',
                'department_id' => $claim->employee?->department_id,
                'department' => $claim->employee?->department?->name,
                'category' => $claim->category?->name ?? 'Removed category',
                'days_to_pay' => $claim->reviewed_at && $claim->reimbursed_at ? max(0, (int) $claim->reviewed_at->copy()->startOfDay()->diffInDays($claim->reimbursed_at->copy()->startOfDay())) : null,
            ]);
    }

    protected function byStatus(Collection $rows, string $status): Collection
    {
        return match ($status) {
            'all' => $rows->values(),
            'spend' => $rows->whereIn('status', self::SPEND)->values(),
            default => $rows->where('status', $status)->values(),
        };
    }

    protected function sorted(Collection $rows, string $sort): Collection
    {
        return match ($sort) {
            'amount' => $rows->sortByDesc('amount')->values(),
            'name' => $rows->sortBy(fn ($r) => mb_strtolower($r['name']))->values(),
            default => $rows->values(),
        };
    }

    /** Accepted spend month by month: the chosen year, or the last twelve months when no year is picked. */
    protected function byMonth(Collection $spend, array $state): Collection
    {
        if ($state['month']) {
            return collect();
        }

        $first = $state['year'] ? Carbon::create($state['year'], 1, 1) : today()->startOfMonth()->subMonths(11);
        $totals = $spend->groupBy(fn ($r) => $r['claim']->expense_date->format('Y-m'))->map(fn (Collection $g) => (float) $g->sum('amount'));

        return collect(range(0, 11))->map(function (int $i) use ($first, $totals, $state) {
            $month = $first->copy()->addMonths($i);

            return ['label' => $month->format($state['year'] ? 'M' : 'M y'), 'amount' => (float) ($totals[$month->format('Y-m')] ?? 0), 'future' => $month->gt(today())];
        })->reject(fn ($m) => $m['future'])->values();
    }

    /** Claims this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return ExpenseClaim::query()
            ->where('expense_claims.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }
}
