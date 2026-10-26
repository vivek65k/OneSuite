<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\DealApprovalRule;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealApprovalMatrixController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.crm.deal-approval-matrix.index', [
            'rules' => DealApprovalRule::where('company_id', $companyId)->with('approver')->orderBy('level')->get(),
            'employees' => Employee::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:10'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'approver_employee_id' => ['required', 'exists:employees,id'],
        ]);

        DealApprovalRule::create($data + ['company_id' => Company::current()->id, 'is_active' => true]);

        return back()->with('status', 'Approval rule added.');
    }

    public function toggle(DealApprovalRule $dealApprovalRule): RedirectResponse
    {
        abort_unless($dealApprovalRule->company_id === Company::current()->id, 404);
        $dealApprovalRule->update(['is_active' => ! $dealApprovalRule->is_active]);

        return back()->with('status', $dealApprovalRule->is_active ? 'Rule activated.' : 'Rule paused.');
    }

    public function destroy(DealApprovalRule $dealApprovalRule): RedirectResponse
    {
        abort_unless($dealApprovalRule->company_id === Company::current()->id, 404);
        $dealApprovalRule->delete();

        return back()->with('status', 'Rule removed.');
    }
}
