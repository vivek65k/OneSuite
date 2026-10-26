<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Opportunity;
use App\Models\Crm\OpportunityProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityProductController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $opportunityId = $request->query('opportunity_id');

        $query = OpportunityProduct::where('company_id', $companyId)->with('opportunity');

        if ($opportunityId) {
            $query->where('opportunity_id', $opportunityId);
        }

        if ($search !== '') {
            $query->where('product_name', 'like', "%{$search}%");
        }

        $products = $query->latest()->paginate(25)->withQueryString();

        return view('admin.crm.opportunity-products.index', [
            'products' => $products,
            'search' => $search,
            'opportunityId' => $opportunityId,
            'opportunities' => Opportunity::where('company_id', $companyId)->where('status', 'open')->orderBy('name')->pluck('name', 'id'),
            'totalLineValue' => OpportunityProduct::where('company_id', $companyId)->get()->sum(fn ($p) => $p->quantity * $p->unit_price),
            'totalLines' => OpportunityProduct::where('company_id', $companyId)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'opportunity_id' => ['required', 'exists:crm_opportunities,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        abort_unless(Opportunity::where('id', $data['opportunity_id'])->where('company_id', $companyId)->exists(), 404);

        OpportunityProduct::create($data + ['company_id' => $companyId]);

        return back()->with('status', 'Product line added.');
    }

    public function destroy(OpportunityProduct $opportunityProduct): RedirectResponse
    {
        abort_unless($opportunityProduct->company_id === Company::current()->id, 404);
        $opportunityProduct->delete();

        return back()->with('status', 'Product line removed.');
    }
}
