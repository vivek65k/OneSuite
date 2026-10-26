<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\DiscountApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscountApprovalController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(): View
    {
        $companyId = Company::current()->id;

        $approvals = DiscountApproval::where('company_id', $companyId)
            ->with(['quotation.account', 'requestedBy'])
            ->latest()
            ->paginate(25);

        return view('admin.crm.discount-approvals.index', [
            'approvals' => $approvals,
            'pendingCount' => DiscountApproval::where('company_id', $companyId)->where('status', 'pending')->count(),
            'approvedCount' => DiscountApproval::where('company_id', $companyId)->where('status', 'approved')->count(),
            'rejectedCount' => DiscountApproval::where('company_id', $companyId)->where('status', 'rejected')->count(),
        ]);
    }

    public function approve(DiscountApproval $discountApproval): RedirectResponse
    {
        abort_unless($discountApproval->company_id === Company::current()->id, 404);

        $discountApproval->update([
            'status' => 'approved',
            'approver_employee_id' => $this->currentEmployee()->id,
            'decided_at' => now(),
        ]);

        $discountApproval->quotation->update(['status' => 'draft']);

        return back()->with('status', 'Discount approved - the quote is back in draft, ready to send.');
    }

    public function reject(Request $request, DiscountApproval $discountApproval): RedirectResponse
    {
        abort_unless($discountApproval->company_id === Company::current()->id, 404);

        $discountApproval->update([
            'status' => 'rejected',
            'approver_employee_id' => $this->currentEmployee()->id,
            'decided_at' => now(),
            'reason' => $request->input('reason'),
        ]);

        return back()->with('status', 'Discount rejected. The owner will need to lower it before sending.');
    }
}
