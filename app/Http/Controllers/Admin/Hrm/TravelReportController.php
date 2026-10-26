<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TravelBooking;
use App\Models\TravelExpense;
use App\Models\TravelRequest;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TravelReportController extends Controller
{
    use ExportsCsv;

    protected const SORTS = ['date', 'cost', 'over', 'name'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TravelRequest::class);

        $state = $this->state($request);
        $all = $this->rows($state['year']);
        $rows = $this->sorted($this->filtered($all, $state), $state['sort']);
        // Money is only counted for trips that were approved; pending and rejected requests cost nothing.
        $trips = $rows->where('status', 'approved')->values();

        return view('admin.hrm.travel.reports', [
            'state' => $state,
            'years' => $this->scoped()->pluck('start_date')->map(fn ($d) => $d?->year)->filter()->unique()->sortDesc()->values(),
            'departments' => $all->filter(fn ($r) => $r['department_id'])->pluck('department', 'department_id')->sort(),
            'rows' => $rows,
            'totals' => $this->totals($trips),
            'statusCounts' => $this->filtered($all, ['status' => ''] + $state)->countBy('status'),
            // Tickets, stay and each kind of expense, largest first.
            'spend' => $this->spend($trips),
            'travellers' => $trips->groupBy('employee_id')->map(fn (Collection $g) => [
                'employee' => $g->first()['request']->employee, 'name' => $g->first()['name'], 'trips' => $g->count(), 'days' => $g->sum('days'), 'cost' => (float) $g->sum('cost'),
            ])->sortByDesc('cost')->take(3)->values(),
            'destinations' => $trips->groupBy(fn ($r) => mb_strtolower(trim($r['request']->to_location)))->map(fn (Collection $g) => [
                // "mumbai" and "Mumbai" are one place; show it tidily whichever way it was typed.
                'name' => \Illuminate\Support\Str::title(trim($g->first()['request']->to_location)), 'trips' => $g->count(), 'cost' => (float) $g->sum('cost'),
            ])->sortByDesc('trips')->take(5)->values(),
            'byDepartment' => $trips->groupBy(fn ($r) => $r['department'] ?? 'No department')->map(fn (Collection $g, string $name) => [
                'name' => $name, 'id' => $g->first()['department_id'], 'trips' => $g->count(), 'people' => $g->pluck('employee_id')->unique()->count(),
                'cost' => (float) $g->sum('cost'), 'estimate' => (float) $g->sum(fn ($r) => (float) $r['estimate']),
            ])->sortByDesc('cost')->values(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', TravelRequest::class);

        $state = $this->state($request);
        $rows = $this->sorted($this->filtered($this->rows($state['year']), $state), $state['sort']);
        // A cell starting with = + - @ would run as a formula when the file is opened in a spreadsheet.
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;

        return $this->streamCsv(
            'travel-report'.($state['year'] ? '-'.$state['year'] : '').'.csv',
            // The first ten columns are where they always were; new ones are added at the end.
            ['Employee', 'Purpose', 'From', 'To', 'Dates', 'Status', 'Estimated Cost', 'Booking Cost', 'Approved Expenses', 'Reimbursement', 'Total Cost', 'Over Estimate', 'Days', 'Travelling By', 'Department', 'Settled On'],
            (function () use ($rows, $safe) {
                foreach ($rows as $row) {
                    $tr = $row['request'];
                    yield array_map($safe, [
                        $row['name'], $tr->purpose, $tr->from_location, $tr->to_location,
                        $tr->start_date->format('Y-m-d').' to '.$tr->end_date->format('Y-m-d'),
                        TravelRequest::STATUSES[$tr->status] ?? $tr->status,
                        $tr->estimated_cost, $row['booked'], $row['expenses'], $row['reimbursement'],
                        $row['cost'], $row['over'], $row['days'], TravelRequest::MODES[$tr->mode_of_travel] ?? $tr->mode_of_travel, $row['department'], $row['settled_on']?->toDateString(),
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

        return [
            'year' => $year,
            'department_id' => ctype_digit($text('department_id')) ? (int) $text('department_id') : null,
            // The report is about trips that happened, so it opens on approved ones.
            'status' => $text('status') === 'all' || array_key_exists($text('status'), TravelRequest::STATUSES) ? $text('status') : 'approved',
            'sort' => in_array($text('sort'), self::SORTS, true) ? $text('sort') : 'date',
        ];
    }

    protected function filtered(Collection $rows, array $state): Collection
    {
        return $rows
            ->when($state['department_id'], fn ($c, $departmentId) => $c->where('department_id', $departmentId))
            ->when(! in_array($state['status'], ['', 'all'], true), fn ($c) => $c->where('status', $state['status']))
            ->values();
    }

    protected function sorted(Collection $rows, string $sort): Collection
    {
        return match ($sort) {
            'cost' => $rows->sortByDesc('cost')->values(),
            'over' => $rows->sortByDesc(fn ($r) => $r['over'] ?? -INF)->values(),
            'name' => $rows->sortBy(fn ($r) => mb_strtolower($r['name']))->values(),
            default => $rows->values(),
        };
    }

    /** One line per travel request, newest first, with what it cost. */
    protected function rows(?int $year): Collection
    {
        return $this->scoped()
            ->when($year, fn ($q) => $q->whereYear('start_date', $year))
            ->with(['employee.department:id,name', 'bookings:id,travel_request_id,booking_type,cost', 'expenses:id,travel_request_id,category,amount,status', 'settlement'])
            ->orderByDesc('start_date')->orderByDesc('id')
            ->get()
            ->map(function (TravelRequest $tr) {
                $approved = $tr->status === 'approved';
                $settled = $tr->settlement && $tr->settlement->status === 'settled';
                // A request that was never approved has no cost, whatever may have been recorded against it.
                $booked = $approved ? round((float) $tr->bookings->sum('cost'), 2) : 0.0;
                $expenses = $approved ? round((float) $tr->expenses->where('status', 'approved')->sum('amount'), 2) : 0.0;
                $cost = $booked + $expenses;
                $estimate = $tr->estimated_cost !== null ? (float) $tr->estimated_cost : null;

                return [
                    'request' => $tr,
                    'status' => $tr->status,
                    'employee_id' => $tr->employee_id,
                    'name' => $tr->employee?->name ?? 'Removed employee',
                    'department_id' => $tr->employee?->department_id,
                    'department' => $tr->employee?->department?->name,
                    'days' => (int) $tr->start_date->diffInDays($tr->end_date) + 1,
                    'estimate' => $estimate,
                    'booked' => $booked,
                    'expenses' => $expenses,
                    'cost' => $cost,
                    // How far the trip went past its estimate; null when there was no estimate to compare with.
                    'over' => $approved && $estimate !== null ? round($cost - $estimate, 2) : null,
                    'settled' => $settled,
                    'settled_on' => $settled ? $tr->settlement->settled_date : null,
                    // A reopened settlement hasn't been paid as far as the report is concerned.
                    'reimbursement' => $settled ? (float) $tr->settlement->reimbursement_amount : null,
                    'pending_expenses' => $tr->expenses->where('status', 'pending')->count(),
                ];
            });
    }

    protected function totals(Collection $trips): array
    {
        $withEstimate = $trips->whereNotNull('estimate');
        $cost = (float) $trips->sum('cost');
        $days = (int) $trips->sum('days');
        $estimated = (float) $withEstimate->sum('estimate');
        $spentOnEstimated = (float) $withEstimate->sum('cost');

        return [
            'trips' => $trips->count(),
            'people' => $trips->pluck('employee_id')->unique()->count(),
            'days' => $days,
            'booked' => (float) $trips->sum('booked'),
            'expenses' => (float) $trips->sum('expenses'),
            'cost' => $cost,
            'per_trip' => $trips->count() ? $cost / $trips->count() : null,
            'per_day' => $days ? $cost / $days : null,
            'estimated' => $estimated,
            'estimate_used' => $estimated > 0 ? (int) round($spentOnEstimated / $estimated * 100) : null,
            'over_trips' => $trips->filter(fn ($r) => $r['over'] !== null && $r['over'] > 0)->count(),
            'over_amount' => (float) $trips->sum(fn ($r) => max(0, (float) $r['over'])),
            'reimbursed' => (float) $trips->sum(fn ($r) => (float) $r['reimbursement']),
            'settled' => $trips->where('settled', true)->count(),
            'unsettled' => $trips->where('settled', false)->filter(fn ($r) => $r['request']->end_date->lt(today()))->count(),
        ];
    }

    protected function spend(Collection $trips): Collection
    {
        $bookings = $trips->flatMap(fn ($r) => $r['request']->bookings)->groupBy('booking_type')
            ->map(fn (Collection $g, string $type) => ['label' => (TravelBooking::TYPES[$type] ?? ucfirst($type)).' bookings', 'kind' => 'booked', 'amount' => (float) $g->sum('cost')]);
        $expenses = $trips->flatMap(fn ($r) => $r['request']->expenses->where('status', 'approved'))->groupBy('category')
            ->map(fn (Collection $g, string $category) => ['label' => (TravelExpense::CATEGORIES[$category] ?? ucfirst($category)).' expenses', 'kind' => 'expense', 'amount' => (float) $g->sum('amount')]);

        return $bookings->values()->merge($expenses->values())->where('amount', '>', 0)->sortByDesc('amount')->values();
    }

    /** Requests this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return TravelRequest::query()
            ->where('travel_requests.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }
}
