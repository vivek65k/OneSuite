<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Item;
use App\Models\Inventory\StockLedger;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockLedgerController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;

        $entries = StockLedger::where('company_id', $companyId)->with(['item', 'warehouse', 'location', 'employee'])
            ->when($request->filled('item_id'), fn ($q) => $q->where('item_id', $request->input('item_id')))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->input('warehouse_id')))
            ->when($request->filled('transaction_type'), fn ($q) => $q->where('transaction_type', $request->input('transaction_type')))
            ->orderByDesc('created_at')->paginate(30)->withQueryString();

        return view('admin.inventory.stock-ledger.index', [
            'entries' => $entries,
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'items' => Item::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'types' => StockLedger::TYPES,
        ]);
    }
}
