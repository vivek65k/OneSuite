<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Sales\PriceList;
use App\Models\Sales\SalesSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Company-wide Sales rules: the discount approval threshold (read by
 * Quotation::exceedsDiscountThreshold(), shared with CRM's Quotation
 * screen), default payment terms (used as the default when setting up a
 * new customer's commercial profile), and credit control (enforced on
 * SalesOrderController::confirm()). Document numbering for Quotations/
 * Sales Orders/Contracts is configured in Inventory's Numbering &
 * Documents screen (the same engine, extended with these document
 * types) rather than duplicated here.
 */
class SettingController extends Controller
{
    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.sales.settings.index', [
            'settings' => SalesSetting::forCompany($companyId),
            'priceLists' => PriceList::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'discount_approval_threshold_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'default_payment_terms_days' => ['required', 'integer', 'min:0'],
            'require_credit_check' => ['nullable', 'boolean'],
            'block_order_over_credit_limit' => ['nullable', 'boolean'],
            'default_price_list_id' => ['nullable', 'exists:sales_price_lists,id'],
        ]);

        $settings = SalesSetting::forCompany($companyId);
        $settings->update($data + [
            'require_credit_check' => $request->boolean('require_credit_check'),
            'block_order_over_credit_limit' => $request->boolean('block_order_over_credit_limit'),
        ]);

        return back()->with('status', 'Sales settings saved.');
    }
}
