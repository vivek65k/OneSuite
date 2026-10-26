<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Inventory\Item;
use App\Models\Sales\CustomerPrice;
use App\Models\Sales\PriceList;
use App\Models\Sales\PriceListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceListController extends Controller
{
    /**
     * Products here are inv_items - the shared product master from the
     * Inventory app. This screen only manages Sales-specific price lists
     * and customer-specific overrides on top of it.
     */
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.sales.pricing.index', [
            'priceLists' => PriceList::where('company_id', $companyId)->with('items.item')->orderBy('name')->get(),
            'customerPrices' => CustomerPrice::where('company_id', $companyId)->with(['account', 'item'])->orderByDesc('created_at')->limit(30)->get(),
            'items' => Item::where('company_id', $companyId)->where('is_variant', false)->orderBy('name')->get(['id', 'name', 'sale_price']),
            'accounts' => Account::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeList(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'currency' => ['required', 'string', 'max:10'],
            'is_default' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        if ($request->boolean('is_default')) {
            PriceList::where('company_id', $companyId)->update(['is_default' => false]);
        }

        PriceList::create($data + ['company_id' => $companyId, 'is_default' => $request->boolean('is_default'), 'is_active' => true]);

        return redirect()->route('admin.sales.pricing.index')->with('status', 'Price list created.');
    }

    public function destroyList(PriceList $priceList): RedirectResponse
    {
        abort_unless($priceList->company_id === Company::current()->id, 404);
        $priceList->delete();

        return back()->with('status', 'Price list removed.');
    }

    public function storeListItem(Request $request, PriceList $priceList): RedirectResponse
    {
        abort_unless($priceList->company_id === Company::current()->id, 404);
        $data = $request->validate([
            'item_id' => ['required', 'exists:inv_items,id'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'min_qty' => ['nullable', 'numeric', 'min:0'],
        ]);

        PriceListItem::updateOrCreate(
            ['price_list_id' => $priceList->id, 'item_id' => $data['item_id'], 'min_qty' => $data['min_qty'] ?? null],
            ['unit_price' => $data['unit_price']]
        );

        return back()->with('status', 'Price list rate saved.');
    }

    public function destroyListItem(PriceListItem $priceListItem): RedirectResponse
    {
        abort_unless($priceListItem->priceList->company_id === Company::current()->id, 404);
        $priceListItem->delete();

        return back()->with('status', 'Price list rate removed.');
    }

    public function storeCustomerPrice(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'account_id' => ['required', 'exists:crm_accounts,id'],
            'item_id' => ['required', 'exists:inv_items,id'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        CustomerPrice::updateOrCreate(
            ['account_id' => $data['account_id'], 'item_id' => $data['item_id']],
            ['company_id' => $companyId, 'unit_price' => $data['unit_price']]
        );

        return back()->with('status', 'Customer-specific rate saved.');
    }

    public function destroyCustomerPrice(CustomerPrice $customerPrice): RedirectResponse
    {
        abort_unless($customerPrice->company_id === Company::current()->id, 404);
        $customerPrice->delete();

        return back()->with('status', 'Customer-specific rate removed.');
    }
}
