<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockReservation;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockReservationController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.stock-reservations.index', [
            'reservations' => StockReservation::where('company_id', $companyId)->with(['item', 'warehouse'])->orderByDesc('created_at')->paginate(20),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'items' => Item::where('company_id', $companyId)->where('is_variant', false)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Reserving stock only reduces AVAILABLE quantity (quantity - reserved_quantity);
     * the physical on-hand quantity is untouched until dispatch.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'item_id' => ['required', 'exists:inv_items,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $reservation = StockReservation::reserve($companyId, $data['warehouse_id'], $data['item_id'], $data['quantity'], $data['reference'] ?? null);

        if (! $reservation) {
            $available = Stock::where('warehouse_id', $data['warehouse_id'])->where('item_id', $data['item_id'])->first()?->available_quantity ?? 0;

            return back()->withErrors(['quantity' => "Only {$available} unit(s) are available to reserve."]);
        }

        return redirect()->route('admin.inventory.stock-reservations.index')->with('status', 'Stock reserved - available quantity reduced, on-hand stock unchanged.');
    }

    public function release(StockReservation $stockReservation): RedirectResponse
    {
        $this->authorizeReservation($stockReservation);
        abort_unless($stockReservation->status === 'reserved', 422, 'Only an active reservation can be released.');

        $stockReservation->releaseReservation();

        return back()->with('status', 'Reservation released - stock is available again.');
    }

    protected function authorizeReservation(StockReservation $reservation): void
    {
        abort_unless($reservation->company_id === Company::current()->id, 404);
    }
}
