<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Location;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $warehouses = Warehouse::where('company_id', $companyId)->orderBy('name')->get();
        $warehouseId = $request->query('warehouse_id') ?: $warehouses->first()?->id;

        $locations = $warehouseId
            ? Location::where('warehouse_id', $warehouseId)->orderBy('level')->orderBy('code')->get()
            : collect();

        return view('admin.inventory.locations.index', [
            'warehouses' => $warehouses,
            'warehouseId' => $warehouseId,
            'locations' => $locations,
            'parents' => $locations,
            'blockedCount' => $locations->where('is_blocked', true)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'parent_id' => ['nullable', 'exists:inv_locations,id'],
            'level' => ['required', 'in:'.implode(',', array_keys(Location::LEVELS))],
            'code' => ['required', 'string', 'max:50'],
            'max_weight' => ['nullable', 'numeric', 'min:0'],
            'max_volume' => ['nullable', 'numeric', 'min:0'],
            'storage_type' => ['nullable', 'in:'.implode(',', array_keys(Location::STORAGE_TYPES))],
        ]);

        $location = Location::create($data + ['company_id' => $companyId, 'is_blocked' => false]);
        $location->update(['full_code' => $location->computeFullCode()]);

        return redirect()->route('admin.inventory.locations.index', ['warehouse_id' => $data['warehouse_id']])->with('status', "Location \"{$location->full_code}\" created.");
    }

    public function toggle(Location $location): RedirectResponse
    {
        abort_unless($location->company_id === Company::current()->id, 404);
        $location->update(['is_blocked' => ! $location->is_blocked]);

        return back()->with('status', $location->is_blocked ? 'Location blocked.' : 'Location unblocked.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        abort_unless($location->company_id === Company::current()->id, 404);
        $warehouseId = $location->warehouse_id;
        $location->delete();

        return redirect()->route('admin.inventory.locations.index', ['warehouse_id' => $warehouseId])->with('status', 'Location removed.');
    }
}
