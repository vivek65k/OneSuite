<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\PayrollRun;
use App\Support\BranchContext;
use App\Support\Dashboard\PayrollDashboard;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PayrollRun::class);

        $companyId = Company::current()->id;
        $branchId = BranchContext::isRestricted() ? BranchContext::currentBranchId() : null;

        return view('admin.hrm.payroll.dashboard', [
            'data' => (new PayrollDashboard($request->user(), $companyId, $branchId))->build(),
            'branchName' => $branchId ? Branch::whereKey($branchId)->value('name') : null,
        ]);
    }
}
