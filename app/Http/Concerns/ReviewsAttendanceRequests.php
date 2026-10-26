<?php

namespace App\Http\Concerns;

use App\Support\Attendance;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Shared bits of the regularization / WFH / overtime request screens:
 * common list filters, status-tab counts, headline KPIs and the guard
 * every approve / reject / delete goes through.
 */
trait ReviewsAttendanceRequests
{
    /** search (employee name/code), status, employee, date range on $dateColumn. */
    protected function applyRequestFilters(Builder $query, array $state, string $dateColumn): Builder
    {
        return $query
            ->when($state['employee_id'] ?? null, fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($state['from'] ?? null, fn ($q, $from) => $q->whereDate($dateColumn, '>=', $from))
            ->when($state['to'] ?? null, fn ($q, $to) => $q->whereDate($dateColumn, '<=', $to))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->whereHas('employee', fn ($eq) => $eq->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%");
            })));
    }

    protected function requestStatusCounts(Builder $query): Collection
    {
        $counts = $query->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count);

        return $counts->put('all', (int) $counts->sum());
    }

    /**
     * @param  class-string<Model>  $model
     * @return array{pending: int, approved_month: int, rejected_month: int, oldest_pending: ?string}
     */
    protected function requestKpis(string $model): array
    {
        $base = Attendance::requests($model);

        return [
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'approved_month' => (clone $base)->where('status', 'approved')->where('reviewed_at', '>=', now()->startOfMonth())->count(),
            'rejected_month' => (clone $base)->where('status', 'rejected')->where('reviewed_at', '>=', now()->startOfMonth())->count(),
            'oldest_pending' => (clone $base)->where('status', 'pending')->min('created_at'),
        ];
    }

    /**
     * Requests must belong to the current company/branch, and only pending
     * ones can be approved or rejected (no re-approving a rejected request).
     */
    protected function ensureReviewable(Model $request): void
    {
        Attendance::ensureOwn($request);

        abort_if($request->status !== 'pending', 409, 'This request has already been reviewed.');
    }
}
