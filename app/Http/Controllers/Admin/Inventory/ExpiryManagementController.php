<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\Batch;
use App\Models\Inventory\BatchDisposal;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpiryManagementController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(): View
    {
        $companyId = Company::current()->id;

        $batches = Batch::where('company_id', $companyId)->where('status', 'active')->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')->with(['item', 'warehouse'])->orderBy('expiry_date')->get();

        return view('admin.inventory.expiry-management.index', [
            'expired' => $batches->filter(fn ($b) => $b->is_expired)->values(),
            'expiringSoon' => $batches->filter(fn ($b) => $b->is_expiring_soon)->values(),
            'disposals' => BatchDisposal::where('company_id', $companyId)->where('status', 'pending_approval')->with(['batch', 'item', 'warehouse'])->get(),
        ]);
    }

    /**
     * Requesting a disposal only records the intent; stock and the batch
     * are untouched until an approver signs off, same approval-gated
     * pattern as manual stock adjustments.
     */
    public function requestDisposal(Request $request, Batch $batch): RedirectResponse
    {
        abort_unless($batch->company_id === Company::current()->id, 404);
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:'.$batch->quantity],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        BatchDisposal::create($data + [
            'company_id' => $batch->company_id, 'batch_id' => $batch->id, 'item_id' => $batch->item_id, 'warehouse_id' => $batch->warehouse_id,
            'status' => 'pending_approval', 'requested_by_employee_id' => $this->currentEmployee()?->id,
        ]);

        return back()->with('status', 'Disposal requested - pending approval.');
    }

    public function approveDisposal(BatchDisposal $batchDisposal): RedirectResponse
    {
        abort_unless($batchDisposal->company_id === Company::current()->id, 404);
        abort_unless($batchDisposal->status === 'pending_approval', 422, 'This disposal has already been actioned.');

        $batch = $batchDisposal->batch;
        $batch->update(['quantity' => max(0, $batch->quantity - $batchDisposal->quantity), 'status' => $batch->quantity - $batchDisposal->quantity <= 0 ? 'disposed' : 'active']);

        $stock = Stock::firstOrCreate(
            ['warehouse_id' => $batchDisposal->warehouse_id, 'item_id' => $batchDisposal->item_id],
            ['company_id' => $batchDisposal->company_id, 'quantity' => 0]
        );
        $before = (float) $stock->quantity;
        $after = max(0, $before - $batchDisposal->quantity);
        $stock->update(['quantity' => $after]);

        StockLedger::record([
            'company_id' => $batchDisposal->company_id, 'warehouse_id' => $batchDisposal->warehouse_id, 'item_id' => $batchDisposal->item_id,
            'batch_number' => $batch->batch_number,
            'transaction_type' => 'adjustment', 'quantity_before' => $before, 'quantity_change' => -(float) $batchDisposal->quantity, 'quantity_after' => $after,
            'source_type' => 'BatchDisposal', 'source_reference' => "Disposal #{$batchDisposal->id}",
            'employee_id' => $this->currentEmployee()?->id, 'reason' => $batchDisposal->reason ?? 'Expiry disposal',
        ]);

        $batchDisposal->update(['status' => 'approved', 'approved_by_employee_id' => $this->currentEmployee()?->id, 'approved_at' => now()]);

        return back()->with('status', 'Disposal approved - stock reduced accordingly.');
    }

    public function rejectDisposal(BatchDisposal $batchDisposal): RedirectResponse
    {
        abort_unless($batchDisposal->company_id === Company::current()->id, 404);
        abort_unless($batchDisposal->status === 'pending_approval', 422, 'This disposal has already been actioned.');

        $batchDisposal->update(['status' => 'rejected', 'approved_by_employee_id' => $this->currentEmployee()?->id, 'approved_at' => now()]);

        return back()->with('status', 'Disposal rejected - stock unchanged.');
    }
}
