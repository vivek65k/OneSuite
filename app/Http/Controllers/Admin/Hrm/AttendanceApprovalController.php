<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OvertimeRequest;
use App\Models\RegularizationRequest;
use App\Models\WfhRequest;
use App\Support\BranchContext;
use Illuminate\View\View;

class AttendanceApprovalController extends Controller
{
    /**
     * One combined pending queue across the three attendance-adjacent
     * request types. Approve/Reject post straight to the existing
     * per-type admin routes (regularization-requests / overtime-requests /
     * wfh-requests) - this page is a read+dispatch view, not new business
     * logic, since those routes are already company-wide.
     */
    public function index(): View
    {
        $this->authorize('viewAny', RegularizationRequest::class);

        $companyId = Company::current()->id;
        $items = collect();

        foreach ($this->scoped(RegularizationRequest::query(), $companyId)->where('status', 'pending')->with('employee.department')->get() as $r) {
            $items->push([
                'type' => 'Regularization', 'icon' => 'ti-clock', 'tone' => 'indigo',
                'employee' => $r->employee, 'date' => $r->date, 'created_at' => $r->created_at,
                'detail' => trim(($r->requested_check_in ?? '—').' - '.($r->requested_check_out ?? '—')), 'reason' => $r->reason,
                'approve_route' => route('admin.regularization-requests.approve', $r),
                'reject_route' => route('admin.regularization-requests.reject', $r),
            ]);
        }

        foreach ($this->scoped(OvertimeRequest::query(), $companyId)->where('status', 'pending')->with('employee.department')->get() as $r) {
            $items->push([
                'type' => 'Overtime', 'icon' => 'ti-alarm', 'tone' => 'amber',
                'employee' => $r->employee, 'date' => $r->date, 'created_at' => $r->created_at,
                'detail' => "{$r->hours} hrs", 'reason' => $r->reason,
                'approve_route' => route('admin.overtime-requests.approve', $r),
                'reject_route' => route('admin.overtime-requests.reject', $r),
            ]);
        }

        foreach ($this->scoped(WfhRequest::query(), $companyId)->where('status', 'pending')->with('employee.department')->get() as $r) {
            $items->push([
                'type' => 'Work From Home', 'icon' => 'ti-home', 'tone' => 'teal',
                'employee' => $r->employee, 'date' => $r->start_date, 'created_at' => $r->created_at,
                'detail' => $r->start_date->format('M j').' - '.$r->end_date->format('M j'), 'reason' => $r->reason,
                'approve_route' => route('admin.wfh-requests.approve', $r),
                'reject_route' => route('admin.wfh-requests.reject', $r),
            ]);
        }

        $items = $items->sortBy('date')->values();

        return view('admin.hrm.approvals.attendance-approval', [
            'items' => $items,
            'counts' => $items->countBy('type'),
            'oldest' => $items->min('created_at'),
        ]);
    }

    protected function scoped($query, int $companyId)
    {
        return $query
            ->where('company_id', $companyId)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())));
    }
}
