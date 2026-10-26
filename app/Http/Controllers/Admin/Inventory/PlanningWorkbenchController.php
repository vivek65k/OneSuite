<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\PurchaseSuggestion;
use App\Models\Inventory\ReplenishmentTask;
use App\Models\Inventory\WarehouseTransfer;
use Illuminate\View\View;

class PlanningWorkbenchController extends Controller
{
    /**
     * A single review surface over everything already generated elsewhere
     * in Planning & Valuation and Stock Operations - it reuses those
     * screens' own approve/complete endpoints rather than duplicating the
     * posting logic here.
     */
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.planning-workbench.index', [
            'purchaseSuggestions' => PurchaseSuggestion::where('company_id', $companyId)->where('status', 'suggested')->with(['item', 'warehouse'])->get(),
            'replenishmentTasks' => ReplenishmentTask::where('company_id', $companyId)->where('status', 'pending')->with(['item', 'warehouse', 'fromLocation', 'toLocation'])->get(),
            'pendingTransfers' => WarehouseTransfer::where('company_id', $companyId)->where('status', 'requested')->with(['sourceWarehouse', 'destWarehouse', 'lines.item'])->get(),
        ]);
    }
}
