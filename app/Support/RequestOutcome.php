<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\RegularizationRequest;
use App\Models\TravelRequest;
use App\Models\WfhRequest;
use Illuminate\Database\Eloquent\Model;

/**
 * What happens once a request is finally approved or rejected, whoever decided
 * it (the manager on Team Approvals or HR on the request pages): attendance is
 * brought in line and the employee is told. Kept in one place so the two
 * routes to a decision can't drift apart.
 *
 * Leave is the exception: App\Support\LeaveApproval already does both for it.
 */
class RequestOutcome
{
    /** Attendance statuses a work-from-home day must not overwrite: the person was on leave, or it was a day off. */
    protected const WFH_KEEPS = ['on_leave', 'holiday', 'weekend'];

    /** Statuses that already say "worked"; a corrected punch keeps them rather than resetting to present. */
    protected const WORKED = ['present', 'half_day', 'wfh'];

    public static function approved(Model $record): void
    {
        match (true) {
            $record instanceof RegularizationRequest => self::applyCorrection($record),
            $record instanceof WfhRequest => self::applyWorkFromHome($record),
            default => null,
        };

        self::tell($record, true);
    }

    public static function rejected(Model $record): void
    {
        self::tell($record, false);
    }

    /**
     * Put the corrected punch on the day's attendance. Only the times that were
     * asked for are changed: a request to fix the check-out leaves the check-in alone.
     */
    protected static function applyCorrection(RegularizationRequest $request): void
    {
        $date = $request->date->toDateString();
        $existing = AttendanceRecord::withoutGlobalScopes()->where('employee_id', $request->employee_id)->whereDate('date', $date)->first();

        $in = $request->requested_check_in ?: $existing?->check_in_time;
        $out = $request->requested_check_out ?: $existing?->check_out_time;

        AttendanceRecord::upsertForDate($request->employee_id, $request->company_id, $date, [
            'status' => in_array($existing?->status, self::WORKED, true) ? $existing->status : 'present',
            'check_in_time' => $in,
            'check_out_time' => $out,
            // Hours stored against the old times would now be wrong; work them out again, or leave them to be worked out.
            'work_hours' => $in && $out ? round(ShiftTime::spanMinutes($in, $out) / 60, 2) : null,
            'source' => $existing?->source ?? 'manual',
        ]);
    }

    /**
     * Mark the working days of an approved work-from-home stretch. Weekly offs,
     * holidays and days the person is on leave are left as they are.
     */
    protected static function applyWorkFromHome(WfhRequest $request): void
    {
        $employee = Employee::withoutGlobalScopes()->find($request->employee_id);
        $calendar = LeavePolicy::for((int) $request->company_id, $employee?->branch_id);
        $leaves = LeaveRequest::withoutGlobalScopes()->where('employee_id', $request->employee_id)->where('status', 'approved')
            ->whereDate('start_date', '<=', $request->end_date)->whereDate('end_date', '>=', $request->start_date)->get();
        $existing = AttendanceRecord::withoutGlobalScopes()->where('employee_id', $request->employee_id)
            ->whereDate('date', '>=', $request->start_date)->whereDate('date', '<=', $request->end_date)->get()->keyBy(fn ($r) => $r->date->toDateString());

        for ($date = $request->start_date->copy(); $date->lte($request->end_date); $date->addDay()) {
            $key = $date->toDateString();
            $record = $existing[$key] ?? null;

            if ($calendar->isWeekend($date) || $calendar->holidayOn($date)) {
                continue;
            }
            if (in_array($record?->status, self::WFH_KEEPS, true) || $leaves->contains(fn (LeaveRequest $l) => $l->start_date->toDateString() <= $key && $l->end_date->toDateString() >= $key)) {
                continue;
            }

            AttendanceRecord::upsertForDate($request->employee_id, $request->company_id, $key, ['status' => 'wfh', 'source' => $record?->source ?? 'manual']);
        }
    }

    /** Let the employee know, with a link to where they can see it. */
    protected static function tell(Model $record, bool $approved): void
    {
        [$what, $route] = match (true) {
            $record instanceof RegularizationRequest => ['attendance correction for '.$record->date->format('M j, Y'), 'admin.my-requests.index'],
            $record instanceof WfhRequest => ['work-from-home request for '.$record->start_date->format('M j').($record->end_date->isSameDay($record->start_date) ? '' : ' – '.$record->end_date->format('M j')), 'admin.my-requests.index'],
            $record instanceof OvertimeRequest => ['overtime request for '.$record->date->format('M j, Y'), 'admin.my-requests.index'],
            $record instanceof TravelRequest => ['trip to '.$record->to_location.' ('.$record->start_date->format('M j').')', 'admin.my-travel.index'],
            $record instanceof ExpenseClaim => ['expense claim of '.number_format((float) $record->amount, 2), 'admin.my-expenses.index'],
            default => [null, null],
        };

        if ($what === null) {
            return;
        }
        $user = Employee::withoutGlobalScopes()->with('user')->find($record->employee_id)?->user;

        Notifier::notifyUser(
            $user,
            $approved ? 'Request approved' : 'Request rejected',
            'Your '.$what.' was '.($approved ? 'approved.' : 'rejected.'.(filled($record->remarks) ? ' Reason: '.$record->remarks : '')),
            route($route),
            $approved ? 'ti ti-circle-check' : 'ti ti-circle-x',
        );
    }
}
