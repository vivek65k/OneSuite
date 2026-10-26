<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\PickList;
use App\Models\Inventory\PickListLine;
use App\Models\Inventory\StockReservation;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PickListController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.pick-lists.index', [
            'pickLists' => PickList::where('company_id', $companyId)->with(['warehouse', 'lines.item'])->orderByDesc('created_at')->paginate(20),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'reservations' => StockReservation::where('company_id', $companyId)->where('status', 'reserved')
                ->whereDoesntHave('pickListLines')->with('item')->orderBy('created_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'strategy' => ['nullable', 'string', 'max:50'],
            'reservation_id' => ['required', 'array', 'min:1'],
            'reservation_id.*' => ['exists:inv_stock_reservations,id'],
        ]);

        $pickList = PickList::create([
            'company_id' => $companyId,
            'warehouse_id' => $data['warehouse_id'],
            'pick_list_number' => PickList::nextNumber($companyId),
            'strategy' => $data['strategy'] ?? null,
            'status' => 'pending',
        ]);

        foreach ($data['reservation_id'] as $reservationId) {
            $reservation = StockReservation::where('company_id', $companyId)->where('status', 'reserved')->find($reservationId);
            if (! $reservation) {
                continue;
            }

            PickListLine::create([
                'pick_list_id' => $pickList->id,
                'reservation_id' => $reservation->id,
                'item_id' => $reservation->item_id,
                'requested_qty' => $reservation->quantity,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('admin.inventory.pick-lists.index')->with('status', "Pick list {$pickList->pick_list_number} created.");
    }

    public function assignPicker(Request $request, PickList $pickList): RedirectResponse
    {
        $this->authorizePickList($pickList);
        $data = $request->validate(['picker_employee_id' => ['nullable', 'exists:employees,id']]);
        $pickList->update($data + ['status' => 'picking']);

        return back()->with('status', 'Picker assigned.');
    }

    /**
     * Picking records what was physically collected from bins, but does not
     * move inv_stock - only dispatch reduces on-hand quantity.
     */
    public function pickLine(Request $request, PickListLine $pickListLine): RedirectResponse
    {
        $this->authorizePickList($pickListLine->pickList);
        $data = $request->validate(['picked_qty' => ['required', 'numeric', 'min:0', 'max:'.$pickListLine->requested_qty]]);

        $pickListLine->update($data + ['status' => 'picked']);

        $pickList = $pickListLine->pickList;
        if ($pickList->lines()->where('status', '!=', 'picked')->doesntExist()) {
            $pickList->update(['status' => 'picked']);
        }

        return back()->with('status', 'Line marked as picked.');
    }

    protected function authorizePickList(PickList $pickList): void
    {
        abort_unless($pickList->company_id === Company::current()->id, 404);
    }
}
