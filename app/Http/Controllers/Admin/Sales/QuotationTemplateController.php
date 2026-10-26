<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Product;
use App\Models\Crm\QuotationTemplate;
use App\Models\Crm\QuotationTemplateLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuotationTemplateController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(): View
    {
        $companyId = Company::current()->id;

        return view('admin.sales.quotation-templates.index', [
            'templates' => QuotationTemplate::where('company_id', $companyId)->with('lines.product')->orderBy('name')->get(),
            'products' => Product::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit_price']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $template = QuotationTemplate::create($data + [
            'company_id' => $companyId, 'created_by_employee_id' => $this->currentEmployee()?->id, 'is_active' => true,
        ]);

        return redirect()->route('admin.sales.quotation-templates.index')->with('status', "Template \"{$template->name}\" created - add line items below.");
    }

    public function storeLine(Request $request, QuotationTemplate $quotationTemplate): RedirectResponse
    {
        abort_unless($quotationTemplate->company_id === Company::current()->id, 404);
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:crm_products,id'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $quotationTemplate->lines()->create($data);

        return back()->with('status', 'Line added to template.');
    }

    public function destroyLine(QuotationTemplateLine $quotationTemplateLine): RedirectResponse
    {
        abort_unless($quotationTemplateLine->template->company_id === Company::current()->id, 404);
        $quotationTemplateLine->delete();

        return back()->with('status', 'Line removed from template.');
    }

    public function destroy(QuotationTemplate $quotationTemplate): RedirectResponse
    {
        abort_unless($quotationTemplate->company_id === Company::current()->id, 404);
        $quotationTemplate->delete();

        return back()->with('status', 'Template removed.');
    }
}
