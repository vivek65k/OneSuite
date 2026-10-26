<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Item;
use App\Models\Inventory\Uom;
use App\Models\Inventory\UomConversion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UomController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.uoms.index', [
            'uoms' => Uom::where('company_id', $companyId)->orderBy('name')->get(),
            'items' => Item::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'conversions' => UomConversion::whereHas('item', fn ($q) => $q->where('company_id', $companyId))
                ->with(['item', 'fromUom', 'toUom'])->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'decimal_precision' => ['required', 'integer', 'min:0', 'max:4'],
        ]);

        Uom::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'Unit added.');
    }

    public function destroy(Uom $uom): RedirectResponse
    {
        abort_unless($uom->company_id === Company::current()->id, 404);
        $uom->delete();

        return back()->with('status', 'Unit removed.');
    }

    public function storeConversion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:inv_items,id'],
            'from_uom_id' => ['required', 'exists:inv_uoms,id'],
            'to_uom_id' => ['required', 'exists:inv_uoms,id', 'different:from_uom_id'],
            'conversion_factor' => ['required', 'numeric', 'min:0.0001'],
        ]);

        UomConversion::create($data);

        return back()->with('status', 'Conversion rule added.');
    }

    public function destroyConversion(UomConversion $uomConversion): RedirectResponse
    {
        abort_unless($uomConversion->item->company_id === Company::current()->id, 404);
        $uomConversion->delete();

        return back()->with('status', 'Conversion rule removed.');
    }
}
