<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The one approve / reject routine behind every leave review screen (HR
 * approval queue, bulk approve, Manager Self Service team approvals), so the
 * side effects - balance deduction, attendance, notifying the employee -
 * can't drift apart between them.
 */
class LeaveApproval
{
    /**
     * Approve: mark reviewed, deduct the balance, mark attendance, notify the employee.
     *
     * The status flip is a conditional update, so two approvals racing on the
     * same request can't both deduct the balance. Returns false (and changes
     * nothing) when the request is no longer pending.
     */
    public static function approve(LeaveRequest $leaveRequest): bool
    {
        $approved = DB::transaction(function () use ($leaveRequest) {
            if (! static::claim($leaveRequest, ['status' => 'approved'])) {
                return false;
            }

            LeaveBalance::adjust(
                $leaveRequest->employee_id, $leaveRequest->company_id, $leaveRequest->leave_type_id,
                $leaveRequest->start_date->year, 'used', (float) $leaveRequest->days
            );

            // Only the days that count as leave; skipped weekends and holidays stay as they are.
            $dates = LeavePolicy::for($leaveRequest->company_id, $leaveRequest->employee?->branch_id)
                ->breakdown($leaveRequest->start_date, $leaveRequest->end_date)['dates'];
            foreach ($dates as $date) {
                AttendanceRecord::upsertForDate(
                    $leaveRequest->employee_id, $leaveRequest->company_id, $date,
                    ['status' => 'on_leave', 'source' => 'manual']
                );
            }

            return true;
        });

        if ($approved) {
            Notifier::notifyUser(
                $leaveRequest->employee?->user,
                'Leave request approved',
                "Your leave request for {$leaveRequest->days} day(s) starting {$leaveRequest->start_date->format('M j, Y')} was approved.",
                route('admin.my-leave.index'),
                'ti ti-circle-check',
            );
        }

        return $approved;
    }

    /** Reject a pending request and notify the employee. Returns false when it was already reviewed. */
    public static function reject(LeaveRequest $leaveRequest, ?string $remarks = null): bool
    {
        if (! static::claim($leaveRequest, ['status' => 'rejected', 'remarks' => $remarks])) {
            return false;
        }

        Notifier::notifyUser(
            $leaveRequest->employee?->user,
            'Leave request rejected',
            "Your leave request starting {$leaveRequest->start_date->format('M j, Y')} was rejected.".($remarks ? " Reason: {$remarks}" : ''),
            route('admin.my-leave.index'),
            'ti ti-circle-x',
        );

        return true;
    }

    /** Move the request out of "pending" only if it is still pending; syncs the model on success. */
    protected static function claim(LeaveRequest $leaveRequest, array $attributes): bool
    {
        $attributes += ['reviewed_by_user_id' => Auth::id(), 'reviewed_at' => now()];

        $updated = LeaveRequest::whereKey($leaveRequest->getKey())
            ->where('status', 'pending')
            ->update($attributes);

        if (! $updated) {
            return false;
        }

        $leaveRequest->forceFill($attributes)->syncOriginal();

        return true;
    }
}
