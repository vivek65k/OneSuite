<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\LeavePolicySetting;
use App\Models\LeaveRequest;
use App\Support\BranchContext;
use App\Support\LeavePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Company/branch leave policy. It is part of leave configuration, so it uses
 * the leave type permissions: view to see it, update to change it.
 */
class LeavePolicyController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('view_any_leave_type'), 403);

        $companyId = Company::current()->id;
        $branchId = BranchContext::currentBranchId();
        $setting = LeavePolicySetting::firstOrNew(['company_id' => $companyId, 'branch_id' => $branchId]);
        if (! $setting->exists) {
            $setting->fill(LeavePolicySetting::RECOMMENDED);
        }

        $policy = LeavePolicy::for($companyId, $branchId);

        // Pending/upcoming leave counted before the policy existed (or under the old rules).
        $upcoming = LeaveRequest::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('end_date', '>=', today())
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', $branchId)))
            ->count();

        return view('admin.hrm.leave.policy', [
            'setting' => $setting,
            'policy' => $policy,
            'canEdit' => $request->user()->can('update_leave_type'),
            'branchName' => $branchId ? Branch::whereKey($branchId)->value('name') : null,
            'holidaysThisYear' => $policy->holidays->filter(fn ($h) => $h->date->year === now()->year || ($h->is_recurring && $h->date->year <= now()->year))->count(),
            'upcoming' => $upcoming,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('update_leave_type'), 403);

        $data = $request->validate([
            'min_notice_days' => ['required', 'integer', 'min:0', 'max:90'],
            'max_backdated_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        foreach (['exclude_weekends', 'exclude_holidays', 'sandwich_rule', 'allow_half_day'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        // The sandwich rule only means something when off-days are being skipped.
        if (! $data['exclude_weekends'] && ! $data['exclude_holidays']) {
            $data['sandwich_rule'] = false;
        }

        LeavePolicySetting::updateOrCreate(
            ['company_id' => Company::current()->id, 'branch_id' => BranchContext::currentBranchId()],
            $data
        );

        return redirect()->route('admin.leave-policies.index')->with('status', 'Leave policy saved. New leave requests are counted with these rules.');
    }
}
