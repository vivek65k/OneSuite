<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Opportunity;
use App\Models\Crm\SalesStage;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PipelineBoardController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $ownerId = $request->query('owner_employee_id');
        $accountId = $request->query('account_id');

        $stages = SalesStage::where('company_id', $companyId)->orderBy('sort_order')->get();

        $query = Opportunity::where('company_id', $companyId)->where('status', 'open')->with(['account', 'owner']);

        if ($ownerId) {
            $query->where('owner_employee_id', $ownerId);
        }

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        $opportunities = $query->get()->groupBy('sales_stage_id');

        $weightedByStage = $stages->mapWithKeys(fn ($stage) => [
            $stage->id => $opportunities->get($stage->id, collect())->sum(fn ($o) => (float) $o->amount * ($o->probability ?? $stage->win_probability) / 100),
        ]);

        return view('admin.crm.pipeline-board.index', [
            'stages' => $stages,
            'opportunities' => $opportunities,
            'weightedByStage' => $weightedByStage,
            'ownerId' => $ownerId,
            'accountId' => $accountId,
            'owners' => Employee::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'accounts' => Account::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    /**
     * Drag-and-drop card move - the board's JS posts here on drop with no
     * page reload. Probability isn't touched: an override stays whatever
     * the user set it to, it just stops being read while amount/forecast
     * math falls back to the new stage's default via the accessor.
     */
    public function moveStage(Request $request, Opportunity $opportunity): JsonResponse
    {
        abort_unless($opportunity->company_id === Company::current()->id, 404);

        $data = $request->validate(['sales_stage_id' => ['required', 'exists:crm_sales_stages,id']]);

        abort_unless(SalesStage::where('id', $data['sales_stage_id'])->where('company_id', $opportunity->company_id)->exists(), 404);

        $stage = SalesStage::find($data['sales_stage_id']);
        $opportunity->sales_stage_id = $stage->id;

        if ($stage->is_won) {
            $opportunity->status = 'won';
            $opportunity->closed_at = now();
        } elseif ($stage->is_lost) {
            $opportunity->status = 'lost';
            $opportunity->closed_at = now();
        }

        $opportunity->save();

        return response()->json(['status' => 'ok', 'opportunityStatus' => $opportunity->status]);
    }
}
