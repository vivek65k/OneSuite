<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Opportunity;
use App\Models\Crm\SalesStage;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesPipelineController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $ownerId = $request->query('owner_employee_id');

        $stages = SalesStage::where('company_id', $companyId)->orderBy('sort_order')->get();

        $openQuery = Opportunity::where('company_id', $companyId)->where('status', 'open');

        if ($ownerId) {
            $openQuery->where('owner_employee_id', $ownerId);
        }

        $open = $openQuery->get(['sales_stage_id', 'amount', 'probability']);

        $byStage = $stages->map(function ($stage) use ($open) {
            $inStage = $open->where('sales_stage_id', $stage->id);

            return [
                'stage' => $stage,
                'count' => $inStage->count(),
                'amount' => $inStage->sum('amount'),
                'weighted' => $inStage->sum(fn ($o) => (float) $o->amount * ($o->probability ?? $stage->win_probability) / 100),
            ];
        });

        return view('admin.crm.sales-pipeline.index', [
            'byStage' => $byStage,
            'ownerId' => $ownerId,
            'owners' => Employee::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'totalCount' => $open->count(),
            'totalAmount' => $open->sum('amount'),
            'totalWeighted' => $byStage->sum('weighted'),
            'avgDealSize' => $open->count() ? $open->sum('amount') / $open->count() : 0,
        ]);
    }
}
