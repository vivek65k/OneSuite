<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Item;
use App\Models\Inventory\Supplier;
use App\Models\Inventory\SupplierProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierProductController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.suppliers.index', [
            'suppliers' => Supplier::where('company_id', $companyId)->withCount('products')->orderBy('name')->get(),
            'mappings' => SupplierProduct::whereHas('item', fn ($q) => $q->where('company_id', $companyId))->with(['supplier', 'item'])->orderByDesc('created_at')->get(),
            'items' => Item::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
        ]);

        Supplier::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'Supplier added.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        abort_unless($supplier->company_id === Company::current()->id, 404);
        $supplier->delete();

        return back()->with('status', 'Supplier removed.');
    }

    public function storeMapping(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:inv_suppliers,id'],
            'item_id' => ['required', 'exists:inv_items,id'],
            'supplier_sku' => ['nullable', 'string', 'max:100'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'is_preferred' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_preferred')) {
            SupplierProduct::where('item_id', $data['item_id'])->update(['is_preferred' => false]);
        }

        SupplierProduct::updateOrCreate(
            ['supplier_id' => $data['supplier_id'], 'item_id' => $data['item_id']],
            $data + ['company_id' => Company::current()->id, 'is_preferred' => $request->boolean('is_preferred')]
        );

        return back()->with('status', 'Mapping saved.');
    }

    public function destroyMapping(SupplierProduct $supplierProduct): RedirectResponse
    {
        abort_unless($supplierProduct->company_id === Company::current()->id, 404);
        $supplierProduct->delete();

        return back()->with('status', 'Mapping removed.');
    }
}
