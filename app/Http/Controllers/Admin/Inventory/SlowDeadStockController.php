<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockLedger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlowDeadStockController extends Controller
{
    /**
     * Ageing is measured from the last ledger movement for that item and
     * warehouse (any transaction type) - no movement at all falls back to
     * the stock row's own created_at so new, never-touched lines don't
     * show as infinitely aged.
     */
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $slowDays = (int) $request->input('slow_days', 60);
        $deadDays = (int) $request->input('dead_days', 90);

        $rows = Stock::where('company_id', $companyId)->where('quantity', '>', 0)->with(['item', 'warehouse'])->get()
            ->map(function (Stock $stock) {
                $lastMovement = StockLedger::where('company_id', $stock->company_id)
                    ->where('warehouse_id', $stock->warehouse_id)->where('item_id', $stock->item_id)
                    ->max('created_at');

                $since = $lastMovement ? \Carbon\Carbon::parse($lastMovement) : $stock->created_at;

                return [
                    'stock' => $stock,
                    'last_movement' => $lastMovement,
                    'ageing_days' => $since ? $since->diffInDays(now()) : 0,
                ];
            })
            ->sortByDesc('ageing_days')->values();

        return view('admin.inventory.slow-dead-stock.index', [
            'rows' => $rows,
            'slowDays' => $slowDays,
            'deadDays' => $deadDays,
        ]);
    }
}
