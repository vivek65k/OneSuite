<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\DealApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealApprovalController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(): View
    {
        $companyId = Company::current()->id;

        $approvals = DealApproval::where('company_id', $companyId)
            ->with(['opportunity.account', 'rule', 'requestedBy'])
            ->latest()
            ->paginate(25);

        return view('admin.crm.deal-approvals.index', [
            'approvals' => $approvals,
            'pendingCount' => DealApproval::where('company_id', $companyId)->where('status', 'pending')->count(),
            'approvedCount' => DealApproval::where('company_id', $companyId)->where('status', 'approved')->count(),
            'rejectedCount' => DealApproval::where('company_id', $companyId)->where('status', 'rejected')->count(),
        ]);
    }

    public function approve(DealApproval $dealApproval): RedirectResponse
    {
        abort_unless($dealApproval->company_id === Company::current()->id, 404);

        $dealApproval->update([
            'status' => 'approved',
            'approver_employee_id' => $this->currentEmployee()->id,
            'decided_at' => now(),
        ]);

        return back()->with('status', 'Deal approved. If every required level is now approved, the deal can be marked Won.');
    }

    public function reject(Request $request, DealApproval $dealApproval): RedirectResponse
    {
        abort_unless($dealApproval->company_id === Company::current()->id, 404);

        $dealApproval->update([
            'status' => 'rejected',
            'approver_employee_id' => $this->currentEmployee()->id,
            'decided_at' => now(),
            'reason' => $request->input('reason'),
        ]);

        return back()->with('status', 'Deal approval rejected.');
    }
}
