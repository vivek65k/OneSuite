<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\ExpectedReceiptLine;
use App\Models\Inventory\Item;
use App\Models\Inventory\PurchaseSuggestion;
use App\Models\Inventory\ReorderRule;
use App\Models\Inventory\Stock;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReorderController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.inventory.reorder.index', [
            'rules' => ReorderRule::where('company_id', $companyId)->with(['item', 'warehouse'])->orderByDesc('created_at')->get(),
            'suggestions' => PurchaseSuggestion::where('company_id', $companyId)->where('status', 'suggested')->with(['item', 'warehouse'])->orderByDesc('created_at')->get(),
            'approvedSuggestions' => PurchaseSuggestion::where('company_id', $companyId)->where('status', 'approved')->with(['item', 'warehouse'])->orderByDesc('approved_at')->limit(20)->get(),
            'warehouses' => Warehouse::where('company_id', $companyId)->orderBy('name')->get(),
            'items' => Item::where('company_id', $companyId)->where('is_variant', false)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:inv_warehouses,id'],
            'item_id' => ['required', 'exists:inv_items,id'],
            'reorder_point' => ['required', 'numeric', 'min:0'],
            'target_stock_level' => ['required', 'numeric', 'gt:reorder_point'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'safety_stock' => ['nullable', 'numeric', 'min:0'],
            'auto_create_po' => ['nullable', 'boolean'],
        ]);

        ReorderRule::create($data + ['company_id' => $companyId, 'auto_create_po' => $request->boolean('auto_create_po'), 'is_active' => true]);

        return redirect()->route('admin.inventory.reorder.index')->with('status', 'Reorder rule saved.');
    }

    public function destroyRule(ReorderRule $reorderRule): RedirectResponse
    {
        abort_unless($reorderRule->company_id === Company::current()->id, 404);
        $reorderRule->delete();

        return back()->with('status', 'Reorder rule removed.');
    }

    /**
     * Suggested qty = target stock level - available stock - incoming
     * stock already on order, matching the worked example (target 300 -
     * available 85 = suggested 215) with incoming receipts folded in so a
     * shipment already expected isn't double-ordered.
     */
    public function generateSuggestions(): RedirectResponse
    {
        $companyId = Company::current()->id;
        $generated = 0;

        foreach (ReorderRule::where('company_id', $companyId)->where('is_active', true)->get() as $rule) {
            $stock = Stock::where('warehouse_id', $rule->warehouse_id)->where('item_id', $rule->item_id)->first();
            $onHand = (float) ($stock->quantity ?? 0);
            $reserved = (float) ($stock->reserved_quantity ?? 0);
            $available = $onHand - $reserved;

            $incoming = (float) ExpectedReceiptLine::whereHas('receipt', fn ($q) => $q->where('warehouse_id', $rule->warehouse_id)->where('status', 'pending'))
                ->where('item_id', $rule->item_id)->sum('expected_qty');

            if ($available + $incoming >= $rule->reorder_point) {
                continue;
            }

            $alreadySuggested = PurchaseSuggestion::where('warehouse_id', $rule->warehouse_id)->where('item_id', $rule->item_id)->where('status', 'suggested')->exists();
            if ($alreadySuggested) {
                continue;
            }

            $suggestedQty = max(0, $rule->target_stock_level + $rule->safety_stock - $available - $incoming);

            PurchaseSuggestion::create([
                'company_id' => $companyId, 'warehouse_id' => $rule->warehouse_id, 'item_id' => $rule->item_id, 'reorder_rule_id' => $rule->id,
                'on_hand_qty' => $onHand, 'reserved_qty' => $reserved, 'available_qty' => $available, 'incoming_qty' => $incoming,
                'suggested_qty' => $suggestedQty, 'status' => 'suggested',
            ]);
            $generated++;
        }

        return back()->with('status', $generated > 0 ? "{$generated} purchase suggestion(s) generated." : 'No items are below their reorder point right now.');
    }

    public function approveSuggestion(PurchaseSuggestion $purchaseSuggestion): RedirectResponse
    {
        $this->authorizeSuggestion($purchaseSuggestion);
        abort_unless($purchaseSuggestion->status === 'suggested', 422, 'This suggestion has already been actioned.');

        $purchaseSuggestion->update(['status' => 'approved', 'approved_by_employee_id' => $this->currentEmployee()?->id, 'approved_at' => now()]);

        return back()->with('status', 'Suggestion approved - ready for the buyer to generate a purchase order.');
    }

    public function dismissSuggestion(PurchaseSuggestion $purchaseSuggestion): RedirectResponse
    {
        $this->authorizeSuggestion($purchaseSuggestion);
        $purchaseSuggestion->update(['status' => 'dismissed']);

        return back()->with('status', 'Suggestion dismissed.');
    }

    protected function authorizeSuggestion(PurchaseSuggestion $suggestion): void
    {
        abort_unless($suggestion->company_id === Company::current()->id, 404);
    }
}
