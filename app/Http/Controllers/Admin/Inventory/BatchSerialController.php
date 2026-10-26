<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Batch;
use App\Models\Inventory\Item;
use App\Models\Inventory\Serial;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BatchSerialController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $tab = (string) $request->query('tab', 'batches');

        $batches = Batch::whereHas('item', fn ($q) => $q->where('company_id', $companyId))->with(['item', 'warehouse'])->orderByDesc('created_at')->paginate(20, ['*'], 'batches_page');
        $serials = Serial::whereHas('item', fn ($q) => $q->where('company_id', $companyId))->with(['item', 'warehouse'])->orderByDesc('created_at')->paginate(20, ['*'], 'serials_page');

        return view('admin.inventory.batch-serial.index', [
            'tab' => $tab,
            'batches' => $batches,
            'serials' => $serials,
            'items' => Item::where('company_id', $companyId)->orderBy('name')->get(['id', 'name', 'track_batch', 'track_serial']),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'expiringSoonCount' => $batches->getCollection()->filter(fn ($b) => $b->is_expiring_soon)->count(),
            'expiredCount' => Batch::whereHas('item', fn ($q) => $q->where('company_id', $companyId))->whereNotNull('expiry_date')->where('expiry_date', '<', now())->count(),
        ]);
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:inv_items,id'],
            'warehouse_id' => ['nullable', 'exists:inv_warehouses,id'],
            'batch_number' => ['required', 'string', 'max:100'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'supplier_lot' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'warranty_months' => ['nullable', 'integer', 'min:0'],
        ]);

        Batch::create($data + ['company_id' => Company::current()->id]);

        return back()->with('status', 'Batch recorded.');
    }

    public function destroyBatch(Batch $batch): RedirectResponse
    {
        abort_unless($batch->company_id === Company::current()->id, 404);
        $batch->delete();

        return back()->with('status', 'Batch removed.');
    }

    public function storeSerial(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:inv_items,id'],
            'warehouse_id' => ['nullable', 'exists:inv_warehouses,id'],
            'serial_number' => ['required', 'string', 'max:100'],
            'warranty_expiry' => ['nullable', 'date'],
        ]);

        Serial::create($data + ['company_id' => Company::current()->id, 'status' => 'in_stock']);

        return back()->with('status', 'Serial recorded.');
    }

    public function updateSerialStatus(Request $request, Serial $serial): RedirectResponse
    {
        abort_unless($serial->company_id === Company::current()->id, 404);
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', array_keys(Serial::STATUSES))]]);
        $serial->update($data);

        return back()->with('status', 'Serial status updated.');
    }

    public function destroySerial(Serial $serial): RedirectResponse
    {
        abort_unless($serial->company_id === Company::current()->id, 404);
        $serial->delete();

        return back()->with('status', 'Serial removed.');
    }
}
