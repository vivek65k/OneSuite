<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\QualityInspection;
use App\Models\Inventory\QuarantineStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QualityInspectionController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.quality-inspections.index', [
            'pending' => QualityInspection::where('company_id', $companyId)->where('status', 'pending')
                ->with(['item', 'warehouse', 'grnLine.grn'])->orderBy('created_at')->get(),
            'completed' => QualityInspection::where('company_id', $companyId)->where('status', 'completed')
                ->with(['item', 'warehouse'])->orderByDesc('inspected_at')->limit(20)->get(),
            'quarantineTotal' => QuarantineStock::where('company_id', $companyId)->sum('quantity'),
        ]);
    }

    /**
     * Inspection is the only release valve out of quarantine: accepted
     * quantity becomes real on-hand stock (and a putaway task); rejected
     * quantity is recorded but never reaches inv_stock.
     */
    public function inspect(Request $request, QualityInspection $qualityInspection): RedirectResponse
    {
        abort_unless($qualityInspection->company_id === Company::current()->id, 404);
        abort_unless($qualityInspection->status === 'pending', 422, 'This inspection is already completed.');

        $quarantine = QuarantineStock::where('grn_line_id', $qualityInspection->grn_line_id)->first();
        $available = $quarantine?->quantity ?? 0;

        $data = $request->validate([
            'checklist_notes' => ['nullable', 'string', 'max:1000'],
            'sample_size' => ['nullable', 'integer', 'min:0'],
            'accepted_qty' => ['required', 'numeric', 'min:0', 'max:'.max($available, 0.01)],
            'rejected_qty' => ['required', 'numeric', 'min:0'],
            'defect_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $data['rejected_qty'] == 0 ? 'pass' : ($data['accepted_qty'] == 0 ? 'fail' : 'partial');

        $qualityInspection->update($data + ['status' => 'completed', 'result' => $result, 'inspected_at' => now()]);
        $qualityInspection->grnLine->update(['inspection_status' => $result === 'pass' ? 'passed' : ($result === 'fail' ? 'failed' : 'passed')]);

        if ($data['accepted_qty'] > 0) {
            GrnController::addStockAndPutaway(
                $qualityInspection->company_id, $qualityInspection->warehouse_id, $qualityInspection->item_id,
                $data['accepted_qty'], "QC release - {$qualityInspection->grnLine->grn->grn_number}", 'QualityInspection'
            );
        }

        if ($quarantine) {
            $remaining = $quarantine->quantity - $data['accepted_qty'] - $data['rejected_qty'];
            $remaining > 0 ? $quarantine->update(['quantity' => $remaining]) : $quarantine->delete();
        }

        return back()->with('status', "Inspection recorded: {$data['accepted_qty']} accepted, {$data['rejected_qty']} rejected.");
    }
}
