<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Inventory\Warehouse;
use Illuminate\View\View;

class InventoryDashboardController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        $items = Item::where('company_id', $companyId)->get();
        $warehouses = Warehouse::where('company_id', $companyId)->get();
        $stock = Stock::where('company_id', $companyId)->with('item')->get();

        $totalStockValue = $stock->sum(fn ($row) => (float) $row->quantity * (float) ($row->item?->cost_price ?? 0));

        $qtyByItem = $stock->groupBy('item_id')->map(fn ($rows) => $rows->sum('quantity'));
        $lowStockItems = $items->where('is_active', true)->filter(fn ($item) => $item->reorder_level > 0 && ($qtyByItem[$item->id] ?? 0) <= $item->reorder_level);

        $byCategory = $items->groupBy(fn ($item) => $item->category ?: 'Uncategorized')
            ->map(fn ($group, $category) => [
                'label' => $category,
                'count' => $group->count(),
                'value' => $group->sum(fn ($item) => ($qtyByItem[$item->id] ?? 0) * $item->cost_price),
            ])->values();

        return view('admin.inventory.dashboard', [
            'totalItems' => $items->count(),
            'totalWarehouses' => $warehouses->count(),
            'totalStockValue' => $totalStockValue,
            'totalUnits' => $stock->sum('quantity'),
            'lowStockCount' => $lowStockItems->count(),
            'lowStockItems' => $lowStockItems->take(8)->values(),
            'byCategory' => $byCategory,
        ]);
    }
}
