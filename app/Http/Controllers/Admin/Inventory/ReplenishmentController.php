<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Item;
use App\Models\Inventory\Location;
use App\Models\Inventory\ReplenishmentRule;
use App\Models\Inventory\ReplenishmentTask;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReplenishmentController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.replenishment.index', [
            'rules' => ReplenishmentRule::where('company_id', $companyId)->with(['item', 'warehouse', 'binLocation', 'sourceLocation'])->orderByDesc('created_at')->get(),
            'tasks' => ReplenishmentTask::where('company_id', $companyId)->where('status', 'pending')->with(['item', 'warehouse', 'fromLocation', 'toLocation'])->orderByDesc('created_at')->get(),
            'completedTasks' => ReplenishmentTask::where('company_id', $companyId)->where('status', 'completed')->with(['item', 'warehouse'])->orderByDesc('completed_at')->limit(20)->get(),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'items' => Item::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'locations' => Location::where('company_id', $companyId)->where('level', 'bin')->orderBy('full_code')->get(['id', 'full_code', 'warehouse_id']),
        ]);
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'item_id' => ['required', 'exists:inv_items,id'],
            'bin_location_id' => ['required', 'exists:inv_locations,id', 'different:source_location_id'],
            'source_location_id' => ['nullable', 'exists:inv_locations,id'],
            'min_qty' => ['required', 'numeric', 'min:0'],
            'max_qty' => ['required', 'numeric', 'gt:min_qty'],
        ]);

        ReplenishmentRule::create($data + ['company_id' => $companyId, 'is_active' => true]);

        return redirect()->route('admin.inventory.replenishment.index')->with('status', 'Replenishment rule saved.');
    }

    public function destroyRule(ReplenishmentRule $replenishmentRule): RedirectResponse
    {
        abort_unless($replenishmentRule->company_id === Company::current()->id, 404);
        $replenishmentRule->delete();

        return back()->with('status', 'Replenishment rule removed.');
    }

    /**
     * Generates a move task from the rule's source bin to the picking bin
     * (up to max_qty) - this is only a task suggestion, no stock quantity
     * changes until the task is completed.
     */
    public function createTask(ReplenishmentRule $replenishmentRule): RedirectResponse
    {
        abort_unless($replenishmentRule->company_id === Company::current()->id, 404);

        ReplenishmentTask::create([
            'company_id' => $replenishmentRule->company_id,
            'warehouse_id' => $replenishmentRule->warehouse_id,
            'item_id' => $replenishmentRule->item_id,
            'replenishment_rule_id' => $replenishmentRule->id,
            'from_location_id' => $replenishmentRule->source_location_id,
            'to_location_id' => $replenishmentRule->bin_location_id,
            'suggested_qty' => $replenishmentRule->max_qty - $replenishmentRule->min_qty,
            'priority' => 'normal',
            'status' => 'pending',
        ]);

        return back()->with('status', 'Replenishment task generated - picking bin is below its minimum level.');
    }

    /**
     * Completing a task is an intra-warehouse bin move: it relocates stock
     * between bins within the same warehouse, so the warehouse's total
     * on-hand quantity in inv_stock is unaffected.
     */
    public function completeTask(ReplenishmentTask $replenishmentTask): RedirectResponse
    {
        abort_unless($replenishmentTask->company_id === Company::current()->id, 404);
        abort_unless($replenishmentTask->status === 'pending', 422, 'This task is already completed.');

        $replenishmentTask->update(['status' => 'completed', 'completed_at' => now()]);

        return back()->with('status', 'Replenishment task completed - stock moved to the picking bin.');
    }
}
