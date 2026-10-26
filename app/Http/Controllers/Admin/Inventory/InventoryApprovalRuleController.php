<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\ApprovalRule;
use App\Models\Inventory\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InventoryApprovalRuleController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.approval-rules.index', [
            'rules' => ApprovalRule::where('company_id', $companyId)->orderByDesc('created_at')->get(),
            'actionTypes' => ApprovalRule::ACTION_TYPES,
        ]);
    }

    /**
     * These thresholds are enforced live by StockAdjustmentController: an
     * adjustment at or under the configured quantity threshold auto-posts,
     * anything above goes to manual approval - this is the matrix that
     * decision actually reads.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'action_type' => ['required', 'in:'.implode(',', array_keys(ApprovalRule::ACTION_TYPES))],
            'threshold_qty' => ['nullable', 'numeric', 'min:0'],
            'threshold_value' => ['nullable', 'numeric', 'min:0'],
            'approver_role' => ['nullable', 'string', 'max:100'],
        ]);

        $rule = ApprovalRule::create($data + ['company_id' => $companyId, 'is_active' => true]);
        AuditLog::record($companyId, Auth::user()?->employee?->id, 'created', 'ApprovalRule', $rule->id, "Approval rule for {$rule->action_type} created.");

        return redirect()->route('admin.inventory.approval-rules.index')->with('status', 'Approval rule saved.');
    }

    public function destroy(ApprovalRule $approvalRule): RedirectResponse
    {
        abort_unless($approvalRule->company_id === Company::current()->id, 404);
        AuditLog::record($approvalRule->company_id, Auth::user()?->employee?->id, 'deleted', 'ApprovalRule', $approvalRule->id, "Approval rule for {$approvalRule->action_type} removed.");
        $approvalRule->delete();

        return back()->with('status', 'Approval rule removed.');
    }
}
