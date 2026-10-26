<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PickingStrategyController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.picking-strategies.index', [
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        abort_unless($warehouse->company_id === Company::current()->id, 404);
        $data = $request->validate(['picking_strategy' => ['required', 'in:'.implode(',', array_keys(Warehouse::PICKING_STRATEGIES))]]);
        $warehouse->update($data);

        return back()->with('status', "Picking strategy for {$warehouse->name} updated.");
    }
}
