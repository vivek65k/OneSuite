<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryValuationController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        $items = Item::where('company_id', $companyId)->where('is_variant', false)->with('stock', 'batches')->orderBy('name')->get();

        return view('admin.inventory.valuation.index', [
            'items' => $items,
            'totalValue' => $items->sum('stock_value'),
        ]);
    }

    public function updateCostingMethod(Request $request, Item $item): RedirectResponse
    {
        abort_unless($item->company_id === Company::current()->id, 404);
        $data = $request->validate(['costing_method' => ['required', 'in:moving_avg,fifo']]);
        $item->update($data);

        return back()->with('status', "Costing method for {$item->name} updated.");
    }
}
