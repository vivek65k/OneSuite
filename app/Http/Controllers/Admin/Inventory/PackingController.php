<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\InventoryPackage;
use App\Models\Inventory\InventoryPackageLine;
use App\Models\Inventory\PickList;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackingController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.packing.index', [
            'packages' => InventoryPackage::where('company_id', $companyId)->with(['warehouse', 'pickList', 'lines.item'])->orderByDesc('created_at')->paginate(20),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'pickedLists' => PickList::where('company_id', $companyId)->where('status', 'picked')->whereDoesntHave('package')->with('lines.item')->get(),
        ]);
    }

    /**
     * Packing bundles already-picked items into a shippable package; it is
     * purely organizational and never changes inv_stock.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'pick_list_id' => ['required', 'exists:inv_pick_lists,id'],
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'box_type' => ['nullable', 'string', 'max:50'],
        ]);

        $pickList = PickList::where('company_id', $companyId)->where('status', 'picked')->findOrFail($data['pick_list_id']);

        $package = InventoryPackage::create([
            'company_id' => $companyId,
            'warehouse_id' => $data['warehouse_id'],
            'pick_list_id' => $pickList->id,
            'package_number' => InventoryPackage::nextNumber($companyId),
            'weight' => $data['weight'] ?? null,
            'box_type' => $data['box_type'] ?? null,
            'status' => 'packed',
        ]);

        foreach ($pickList->lines as $line) {
            InventoryPackageLine::create([
                'package_id' => $package->id,
                'item_id' => $line->item_id,
                'quantity' => $line->picked_qty,
            ]);
        }

        return redirect()->route('admin.inventory.packing.index')->with('status', "Package {$package->package_number} created from {$pickList->pick_list_number}.");
    }
}
