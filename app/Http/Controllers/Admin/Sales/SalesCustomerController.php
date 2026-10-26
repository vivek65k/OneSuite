<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Employee;
use App\Models\Sales\CustomerProfile;
use App\Models\Sales\PriceList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesCustomerController extends Controller
{
    /**
     * Customers here ARE crm_accounts - the shared customer master. This
     * screen only adds the commercial layer (credit limit, payment terms,
     * price list, assigned salesperson) on top, via sales_customer_profiles.
     */
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));

        $query = Account::where('company_id', $companyId)->with(['salesProfile.priceList', 'salesProfile.salesperson']);
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        return view('admin.sales.customers.index', [
            'accounts' => $query->orderBy('name')->paginate(25)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function edit(Account $account): View
    {
        abort_unless($account->company_id === Company::current()->id, 404);

        return view('admin.sales.customers.form', [
            'account' => $account,
            'profile' => CustomerProfile::forAccount($account->company_id, $account->id),
            'priceLists' => PriceList::where('company_id', $account->company_id)->where('is_active', true)->orderBy('name')->get(),
            'employees' => Employee::where('company_id', $account->company_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        abort_unless($account->company_id === Company::current()->id, 404);
        $data = $request->validate([
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'payment_terms_days' => ['required', 'integer', 'min:0'],
            'price_list_id' => ['nullable', 'exists:sales_price_lists,id'],
            'salesperson_employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(CustomerProfile::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $profile = CustomerProfile::forAccount($account->company_id, $account->id);
        $profile->update($data);

        return redirect()->route('admin.sales.customers.edit', $account)->with('status', 'Customer commercial profile updated.');
    }
}
