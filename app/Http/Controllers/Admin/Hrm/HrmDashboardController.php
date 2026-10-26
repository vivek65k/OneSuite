<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Support\BranchContext;
use App\Support\DataScope;
use App\Support\Dashboard\DashboardKit;
use App\Support\Dashboard\HrDashboard;
use App\Support\Dashboard\MyDashboard;
use App\Support\Dashboard\TeamDashboard;
use App\Support\LeavePolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HrmDashboardController extends Controller
{
    /**
     * Routes the signed-in user to whichever dashboard fits them: HR (can
     * see all employees, i.e. "all" data access), Manager (has a team) or Employee
     * (everyone else, viewing their own record). Company and branch are
     * resolved once here and passed to the dashboard builders.
     */
    public function index(): View
    {
        $user = Auth::user();
        $companyId = Company::current()->id;
        $branchId = BranchContext::isRestricted() ? BranchContext::currentBranchId() : null;

        // The user's own employee record, only if it belongs to the current company.
        $employee = $user->employee && (int) $user->employee->company_id === (int) $companyId ? $user->employee : null;
        $hasTeam = $employee && $employee->directReports()->whereNotIn('status', DashboardKit::INACTIVE)->exists();

        // The HR dashboard is company/branch-wide figures, so it is only for
        // someone whose role reaches every employee. A manager or employee
        // role that happens to include "view employees" (with team or own
        // data access) gets their own dashboard below instead.
        if ($user->can('view_any_employee') && DataScope::scopeFor($user, 'view_any_employee') === DataScope::ALL) {
            return view('admin.hrm.dashboard-hr', [
                'data' => (new HrDashboard($user, $companyId, $branchId))->build(),
                'branchName' => $branchId ? Branch::whereKey($branchId)->value('name') : null,
                'myTeam' => $hasTeam ? (new TeamDashboard($user, $employee, $companyId))->strip() : null,
            ]);
        }

        if ($hasTeam) {
            return view('admin.hrm.dashboard-manager', [
                'manager' => $employee,
                'data' => (new TeamDashboard($user, $employee, $companyId))->build(),
                'me' => (new MyDashboard($employee))->build(),
            ]);
        }

        return view('admin.hrm.dashboard-employee', [
            'employee' => $employee,
            'data' => $employee ? (new MyDashboard($employee))->build() : null,
            'holidays' => $employee ? null : DashboardKit::upcomingHolidays(LeavePolicy::for($companyId, $branchId), 4),
        ]);
    }
}
