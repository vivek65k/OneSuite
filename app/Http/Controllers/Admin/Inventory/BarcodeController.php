<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Barcode;
use App\Models\Inventory\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarcodeController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));

        $barcodes = Barcode::whereHas('item', fn ($q) => $q->where('company_id', $companyId))
            ->when($search !== '', fn ($q) => $q->where('barcode', 'like', "%{$search}%")->orWhereHas('item', fn ($q2) => $q2->where('name', 'like', "%{$search}%")))
            ->with('item')->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('admin.inventory.barcodes.index', [
            'barcodes' => $barcodes,
            'search' => $search,
            'items' => Item::where('company_id', $companyId)->orderBy('name')->get(['id', 'name', 'sku']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'item_id' => ['required', 'exists:inv_items,id'],
            'barcode' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:'.implode(',', array_keys(Barcode::TYPES))],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_primary')) {
            Barcode::where('item_id', $data['item_id'])->update(['is_primary' => false]);
        }

        Barcode::create($data + ['company_id' => $companyId, 'is_primary' => $request->boolean('is_primary')]);

        return back()->with('status', 'Barcode added.');
    }

    public function destroy(Barcode $barcode): RedirectResponse
    {
        abort_unless($barcode->company_id === Company::current()->id, 404);
        $barcode->delete();

        return back()->with('status', 'Barcode removed.');
    }

    public function label(Barcode $barcode): View
    {
        abort_unless($barcode->company_id === Company::current()->id, 404);

        return view('admin.inventory.barcodes.label', ['barcode' => $barcode->load('item')]);
    }

    public function scan(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate(['barcode' => ['required', 'string']]);

        $barcode = Barcode::whereHas('item', fn ($q) => $q->where('company_id', $companyId))
            ->where('barcode', $data['barcode'])->with('item')->first();

        if (! $barcode) {
            return back()->with('status', "No item found for barcode \"{$data['barcode']}\".");
        }

        return back()->with('status', "Scanned: {$barcode->item->name} (SKU {$barcode->item->sku}).");
    }
}
