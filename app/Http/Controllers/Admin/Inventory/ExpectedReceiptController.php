<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\ExpectedReceipt;
use App\Models\Inventory\ExpectedReceiptLine;
use App\Models\Inventory\Item;
use App\Models\Inventory\Supplier;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpectedReceiptController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.expected-receipts.index', [
            'receipts' => ExpectedReceipt::where('company_id', $companyId)->with(['warehouse', 'supplier', 'lines.item'])->orderByDesc('created_at')->paginate(20),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'suppliers' => Supplier::where('company_id', $companyId)->orderBy('name')->get(),
            'items' => Item::where('company_id', $companyId)->where('is_variant', false)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'supplier_id' => ['nullable', 'exists:inv_suppliers,id'],
            'po_reference' => ['nullable', 'string', 'max:100'],
            'expected_date' => ['nullable', 'date'],
            'vehicle_number' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'is_asn' => ['nullable', 'boolean'],
            'item_id' => ['required', 'array', 'min:1'],
            'item_id.*' => ['exists:inv_items,id'],
            'expected_qty' => ['required', 'array', 'min:1'],
            'expected_qty.*' => ['numeric', 'min:0.01'],
        ]);

        $receipt = ExpectedReceipt::create([
            'company_id' => $companyId,
            'reference_number' => ExpectedReceipt::nextNumber($companyId),
            'warehouse_id' => $data['warehouse_id'],
            'supplier_id' => $data['supplier_id'] ?? null,
            'po_reference' => $data['po_reference'] ?? null,
            'expected_date' => $data['expected_date'] ?? null,
            'vehicle_number' => $data['vehicle_number'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
            'is_asn' => $request->boolean('is_asn'),
            'status' => 'pending',
        ]);

        foreach ($data['item_id'] as $i => $itemId) {
            ExpectedReceiptLine::create(['receipt_id' => $receipt->id, 'item_id' => $itemId, 'expected_qty' => $data['expected_qty'][$i]]);
        }

        return redirect()->route('admin.inventory.expected-receipts.index')->with('status', "Expected receipt {$receipt->reference_number} created.");
    }

    public function cancel(ExpectedReceipt $expectedReceipt): RedirectResponse
    {
        abort_unless($expectedReceipt->company_id === Company::current()->id, 404);
        $expectedReceipt->update(['status' => 'cancelled']);

        return back()->with('status', 'Expected receipt cancelled.');
    }
}
