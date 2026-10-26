<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockOverviewController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $warehouseId = $request->query('warehouse_id');
        $category = (string) $request->query('category', '');

        $query = Item::where('company_id', $companyId)->with(['stock.warehouse']);

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }
        if ($category !== '') {
            $query->where('category', $category);
        }

        $items = $query->orderBy('name')->paginate(25)->withQueryString();

        $items->through(function ($item) use ($warehouseId) {
            $stockRows = $warehouseId ? $item->stock->where('warehouse_id', $warehouseId) : $item->stock;
            $item->filteredQuantity = $stockRows->sum('quantity');
            $item->stockValue = $item->filteredQuantity * $item->cost_price;

            return $item;
        });

        return view('admin.inventory.stock-overview.index', [
            'items' => $items,
            'allItems' => Item::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'search' => $search,
            'warehouseId' => $warehouseId,
            'category' => $category,
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'categories' => Item::where('company_id', $companyId)->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'totalItems' => Item::where('company_id', $companyId)->count(),
            'totalValue' => Stock::where('company_id', $companyId)->get()->sum(fn ($s) => $s->quantity * ($s->item?->cost_price ?? 0)),
        ]);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:20'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
        ]);

        Item::create($data + ['company_id' => Company::current()->id, 'is_active' => true]);

        return back()->with('status', 'Item added.');
    }

    public function adjustStock(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'item_id' => ['required', 'exists:inv_items,id'],
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'quantity' => ['required', 'numeric', 'min:0'],
        ]);

        Stock::updateOrCreate(
            ['item_id' => $data['item_id'], 'warehouse_id' => $data['warehouse_id']],
            ['company_id' => $companyId, 'quantity' => $data['quantity']]
        );

        return back()->with('status', 'Stock updated.');
    }
}
