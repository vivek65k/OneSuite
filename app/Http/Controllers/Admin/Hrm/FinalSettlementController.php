<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinalSettlement;
use App\Models\LeaveEncashment;
use App\Models\Resignation;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FinalSettlementController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', FinalSettlement::class);

        $resignations = Resignation::query()
            ->where('company_id', Company::current()->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())))
            ->with(['employee', 'finalSettlement'])
            ->orderByDesc('last_working_day')
            ->get();

        return view('admin.hrm.separation.final-settlements', ['resignations' => $resignations]);
    }

    public function process(Request $request, Resignation $resignation): RedirectResponse
    {
        $existing = $resignation->finalSettlement;
        $this->authorize($existing ? 'update' : 'create', $existing ?? FinalSettlement::class);
        $this->denyOwnRequest($resignation->employee);

        abort_unless($resignation->company_id === Company::current()->id, 404);

        $data = $request->validate([
            'deductions' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $grossDues = LeaveEncashment::query()
            ->where('employee_id', $resignation->employee_id)
            ->where('status', 'approved')
            ->sum('amount');

        FinalSettlement::updateOrCreate(
            ['resignation_id' => $resignation->id],
            [
                'company_id' => $resignation->company_id,
                'employee_id' => $resignation->employee_id,
                'gross_dues' => $grossDues,
                'deductions' => $data['deductions'],
                'net_payable' => $grossDues - $data['deductions'],
                'status' => 'processed',
                'processed_by_user_id' => Auth::id(),
                'processed_at' => now(),
                'remarks' => $data['remarks'] ?? null,
            ]
        );

        return back()->with('status', 'Final settlement processed.');
    }

    public function markPaid(FinalSettlement $settlement): RedirectResponse
    {
        $this->authorize('update', $settlement);

        abort_unless($settlement->status === 'processed', 422, 'Only a processed settlement can be marked paid.');

        $settlement->update(['status' => 'paid']);

        return back()->with('status', 'Settlement marked as paid.');
    }
}
