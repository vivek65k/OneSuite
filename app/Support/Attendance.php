<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\RegularizationRequest;
use App\Models\WfhRequest;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared attendance helpers: per-status presentation, tenant/branch-scoped
 * queries, the pending counts in the attendance sub-nav, and the ownership
 * check the request policies don't do themselves.
 */
class Attendance
{
    /** @var array<string, array{letter: string, tone: string, icon: string}> */
    public const STATUS_META = [
        'present' => ['letter' => 'P', 'tone' => 'success', 'icon' => 'ti-user-check'],
        'absent' => ['letter' => 'A', 'tone' => 'danger', 'icon' => 'ti-user-x'],
        'half_day' => ['letter' => 'H', 'tone' => 'amber', 'icon' => 'ti-clock'],
        'on_leave' => ['letter' => 'L', 'tone' => 'violet', 'icon' => 'ti-calendar-off'],
        'wfh' => ['letter' => 'W', 'tone' => 'sky', 'icon' => 'ti-home'],
        'holiday' => ['letter' => 'HO', 'tone' => 'pink', 'icon' => 'ti-confetti'],
        'weekend' => ['letter' => 'WK', 'tone' => 'slate', 'icon' => 'ti-calendar'],
    ];

    /** @var array<string, array{tone: string, icon: string}> */
    public const SOURCE_META = [
        'manual' => ['tone' => 'slate', 'icon' => 'ti-edit'],
        'biometric' => ['tone' => 'indigo', 'icon' => 'ti-fingerprint'],
        'geo' => ['tone' => 'teal', 'icon' => 'ti-map-pin'],
    ];

    /** Statuses that are quick-mark buttons on the daily sheet. */
    public const QUICK_STATUSES = ['present', 'absent', 'half_day', 'on_leave', 'wfh'];

    public static function meta(?string $status): array
    {
        return self::STATUS_META[$status] ?? ['letter' => '—', 'tone' => 'slate', 'icon' => 'ti-point'];
    }

    public static function label(?string $status): string
    {
        return AttendanceRecord::STATUSES[$status] ?? ($status ? ucfirst(str_replace('_', ' ', $status)) : 'Not marked');
    }

    /** Employees of the current company (and branch). */
    public static function employees(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }

    /** Attendance records of the current company (and branch). */
    public static function records(): Builder
    {
        return AttendanceRecord::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())));
    }

    /**
     * Regularization / WFH / overtime requests of the current company (and branch).
     *
     * @param  class-string<Model>  $model
     */
    public static function requests(string $model): Builder
    {
        return $model::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())));
    }

    /**
     * The request policies only check permissions - also require that the
     * request belongs to the current company (and branch).
     */
    public static function ensureOwn(Model $request): void
    {
        abort_unless((int) $request->company_id === (int) Company::current()->id, 404);
        abort_if(
            BranchContext::isRestricted() && (int) $request->employee?->branch_id !== (int) BranchContext::currentBranchId(),
            404
        );
    }

    /** @return array{regularization: int, wfh: int, overtime: int} */
    public static function navCounts(): array
    {
        return [
            'regularization' => self::requests(RegularizationRequest::class)->where('status', 'pending')->count(),
            'wfh' => self::requests(WfhRequest::class)->where('status', 'pending')->count(),
            'overtime' => self::requests(OvertimeRequest::class)->where('status', 'pending')->count(),
        ];
    }

    /** Hours between two HH:MM(:SS) times, or null. */
    public static function hoursBetween(?string $in, ?string $out): ?float
    {
        if (! $in || ! $out) {
            return null;
        }

        $minutes = (strtotime($out) - strtotime($in)) / 60;

        return $minutes > 0 ? round($minutes / 60, 2) : null;
    }

    public static function formatTime(?string $time): ?string
    {
        return $time ? date('g:i A', strtotime($time)) : null;
    }
}
