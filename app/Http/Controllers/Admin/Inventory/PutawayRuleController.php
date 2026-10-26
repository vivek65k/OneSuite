<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Category;
use App\Models\Inventory\Item;
use App\Models\Inventory\Location;
use App\Models\Inventory\PutawayRule;
use App\Models\Inventory\PutawayTask;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PutawayRuleController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.putaway-rules.index', [
            'rules' => PutawayRule::where('company_id', $companyId)->with(['warehouse', 'category', 'fixedLocation'])->get(),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'categories' => Category::where('company_id', $companyId)->orderBy('name')->get(),
            'locations' => Location::where('company_id', $companyId)->where('level', 'bin')->get(),
            'items' => Item::where('company_id', $companyId)->where('is_variant', false)->orderBy('name')->get(['id', 'name']),
            'tasks' => PutawayTask::where('company_id', $companyId)->with(['item', 'warehouse', 'suggestedLocation', 'confirmedLocation'])->orderByDesc('created_at')->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'category_id' => ['nullable', 'exists:inv_categories,id'],
            'strategy' => ['required', 'in:'.implode(',', array_keys(PutawayRule::STRATEGIES))],
            'fixed_location_id' => ['nullable', 'exists:inv_locations,id'],
            'is_hazardous' => ['nullable', 'boolean'],
            'is_temp_controlled' => ['nullable', 'boolean'],
        ]);

        PutawayRule::create($data + [
            'company_id' => Company::current()->id, 'is_active' => true,
            'is_hazardous' => $request->boolean('is_hazardous'),
            'is_temp_controlled' => $request->boolean('is_temp_controlled'),
        ]);

        return back()->with('status', 'Putaway rule added.');
    }

    public function destroy(PutawayRule $putawayRule): RedirectResponse
    {
        abort_unless($putawayRule->company_id === Company::current()->id, 404);
        $putawayRule->delete();

        return back()->with('status', 'Rule removed.');
    }

    public function createTask(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'item_id' => ['required', 'exists:inv_items,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $item = Item::find($data['item_id']);
        $rule = PutawayRule::where('company_id', $companyId)->where('warehouse_id', $data['warehouse_id'])
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('category_id')->orWhere('category_id', $item->category_id))
            ->orderByRaw('category_id IS NULL')
            ->first();

        PutawayTask::create($data + [
            'company_id' => $companyId,
            'suggested_location_id' => $rule?->fixed_location_id,
            'status' => 'pending',
            'source_reference' => 'Manual',
        ]);

        return back()->with('status', 'Putaway task created'.($rule?->fixed_location_id ? ' - suggested bin assigned.' : ' - no matching rule, confirm a bin manually.'));
    }

    public function completeTask(Request $request, PutawayTask $putawayTask): RedirectResponse
    {
        abort_unless($putawayTask->company_id === Company::current()->id, 404);

        $data = $request->validate(['confirmed_location_id' => ['required', 'exists:inv_locations,id']]);

        $location = Location::find($data['confirmed_location_id']);
        if ($location->is_blocked) {
            return back()->with('status', 'That location is blocked - choose another bin.');
        }

        $putawayTask->update($data + ['status' => 'completed', 'completed_at' => now()]);

        return back()->with('status', 'Putaway completed.');
    }
}
