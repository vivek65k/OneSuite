<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmployeeAsset;
use App\Models\Resignation;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeparationAssetReturnController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', EmployeeAsset::class);

        $companyId = Company::current()->id;

        $separatingEmployeeIds = Resignation::query()
            ->where('company_id', $companyId)
            ->where('status', 'accepted')
            ->pluck('employee_id');

        $assets = EmployeeAsset::query()
            ->where('company_id', $companyId)
            ->where('status', 'assigned')
            ->whereIn('employee_id', $separatingEmployeeIds)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', BranchContext::currentBranchId())))
            ->with('employee')
            ->orderBy('assigned_date')
            ->get();

        return view('admin.hrm.separation.asset-returns', ['assets' => $assets]);
    }

    public function update(Request $request, EmployeeAsset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);

        $data = $request->validate([
            'condition_at_return' => ['required', Rule::in(['good', 'damaged', 'lost'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['status'] = 'returned';
        $data['returned_date'] = now()->toDateString();

        $asset->update($data);

        return back()->with('status', 'Asset marked as returned.');
    }
}
