<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\Account;
use App\Models\Crm\Contact;
use App\Models\Crm\DiscountApproval;
use App\Models\Crm\Opportunity;
use App\Models\Crm\PriceBook;
use App\Models\Crm\Product;
use App\Models\Crm\Quotation;
use App\Models\Crm\QuotationLine;
use App\Models\Crm\QuotationTemplate;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Quotations live in crm_quotations - the Sales app does not keep a
 * second copy. This is the richer, Sales-native surface for them
 * (templates, version history, convert-to-order); the simpler CRM
 * screens remain available too, reading/writing the same records.
 */
class QuotationController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $query = $this->companyQuery()->with(['account', 'owner']);

        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('quotation_number', 'like', "%{$search}%")->orWhereHas('account', fn ($a) => $a->where('name', 'like', "%{$search}%")));
        }

        return view('admin.sales.quotations.index', [
            'quotations' => $query->orderByDesc('created_at')->paginate(25)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'total' => $this->companyQuery()->count(),
            'pendingApprovalCount' => $this->companyQuery()->where('status', 'pending_approval')->count(),
            'acceptedValue' => $this->companyQuery()->where('status', 'accepted')->sum('total'),
        ]);
    }

    public function create(Request $request): View
    {
        $quotation = new Quotation(['discount_percent' => 0, 'tax_percent' => 0]);
        $template = null;

        if ($request->filled('template_id')) {
            $template = QuotationTemplate::where('company_id', Company::current()->id)->with('lines')->find($request->query('template_id'));
            if ($template) {
                $quotation->discount_percent = $template->discount_percent;
                $quotation->tax_percent = $template->tax_percent;
                $quotation->notes = $template->notes;
            }
        }

        return view('admin.sales.quotations.form', ['quotation' => $quotation, 'template' => $template, ...$this->formOptions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $this->validated($request);
        $data['quotation_number'] = Quotation::nextNumber($companyId);

        $quotation = Quotation::create($data + ['company_id' => $companyId, 'status' => 'draft']);

        if ($request->filled('template_id')) {
            $template = QuotationTemplate::where('company_id', $companyId)->with('lines')->find($request->input('template_id'));
            foreach ($template?->lines ?? [] as $line) {
                $quotation->lines()->create([
                    'product_id' => $line->product_id, 'description' => $line->description,
                    'quantity' => $line->quantity, 'unit_price' => $line->unit_price, 'discount_percent' => 0,
                    'line_total' => $line->quantity * $line->unit_price,
                ]);
            }
            $quotation->recalculateTotals();
        }

        return redirect()->route('admin.sales.quotations.edit', $quotation)->with('status', 'Quotation created - add line items below.');
    }

    public function edit(Quotation $quotation): View
    {
        $this->ownCompany($quotation);

        return view('admin.sales.quotations.form', [
            'quotation' => $quotation->load(['lines.product', 'discountApproval', 'versions.changedBy', 'salesOrders']),
            'template' => null,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Quotation $quotation): RedirectResponse
    {
        $this->ownCompany($quotation);

        $quotation->update($this->validated($request));
        $quotation->recalculateTotals();
        $this->evaluateDiscountApproval($quotation->fresh());

        return redirect()->route('admin.sales.quotations.edit', $quotation)->with('status', 'Quotation updated.');
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        $this->ownCompany($quotation);
        $quotation->delete();

        return redirect()->route('admin.sales.quotations.index')->with('status', 'Quotation deleted.');
    }

    public function addLine(Request $request, Quotation $quotation): RedirectResponse
    {
        $this->ownCompany($quotation);

        $data = $request->validate([
            'product_id' => ['nullable', 'exists:crm_products,id'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);
        $data['discount_percent'] = $data['discount_percent'] ?? 0;
        $data['line_total'] = $data['quantity'] * $data['unit_price'] * (1 - $data['discount_percent'] / 100);

        $quotation->lines()->create($data);
        $quotation->recalculateTotals();
        $this->evaluateDiscountApproval($quotation->fresh());

        return back()->with('status', 'Line added.');
    }

    public function removeLine(QuotationLine $quotationLine): RedirectResponse
    {
        $quotation = $quotationLine->quotation;
        $this->ownCompany($quotation);

        $quotationLine->delete();
        $quotation->recalculateTotals();

        return back()->with('status', 'Line removed.');
    }

    public function send(Quotation $quotation): RedirectResponse
    {
        $this->ownCompany($quotation);
        abort_if($quotation->status === 'pending_approval', 422, 'This quote needs discount approval before it can be sent.');
        $quotation->update(['status' => 'sent']);

        return back()->with('status', 'Quotation marked as sent.');
    }

    public function accept(Quotation $quotation): RedirectResponse
    {
        $this->ownCompany($quotation);
        $quotation->update(['status' => 'accepted']);

        return back()->with('status', 'Quotation accepted - ready to convert to a sales order.');
    }

    public function reject(Quotation $quotation): RedirectResponse
    {
        $this->ownCompany($quotation);
        $quotation->update(['status' => 'rejected']);

        return back()->with('status', 'Quotation rejected.');
    }

    /**
     * Creates the sales order directly (rather than redirecting to a
     * prefilled create form) so "Convert to Order" is a single click from
     * an accepted quote, per the workflow spec.
     */
    public function convertToOrder(Quotation $quotation): RedirectResponse
    {
        $this->ownCompany($quotation);
        abort_unless($quotation->status === 'accepted', 422, 'Only an accepted quotation can be converted to a sales order.');

        $order = \App\Models\Crm\SalesOrder::create([
            'company_id' => $quotation->company_id,
            'order_number' => \App\Models\Crm\SalesOrder::nextNumber($quotation->company_id),
            'quotation_id' => $quotation->id,
            'account_id' => $quotation->account_id,
            'status' => 'draft',
            'order_date' => now(),
            'total' => $quotation->total,
            'owner_employee_id' => $quotation->owner_employee_id,
        ]);

        foreach ($quotation->lines as $line) {
            $order->lines()->create([
                'product_id' => $line->product_id, 'description' => $line->description,
                'quantity' => $line->quantity, 'unit_price' => $line->unit_price, 'line_total' => $line->line_total,
            ]);
        }

        return redirect()->route('admin.sales.orders.edit', $order)->with('status', "Converted to Sales Order {$order->order_number}.");
    }

    protected function evaluateDiscountApproval(Quotation $quotation): void
    {
        $existing = $quotation->discountApproval;

        if ($quotation->exceedsDiscountThreshold()) {
            if (! $existing || $existing->status !== 'pending') {
                DiscountApproval::create([
                    'company_id' => $quotation->company_id,
                    'quotation_id' => $quotation->id,
                    'requested_discount_percent' => $quotation->discount_percent,
                    'requested_by_employee_id' => optional(auth()->user()->employee)->id,
                    'status' => 'pending',
                ]);
                $quotation->update(['status' => 'pending_approval']);
            }
        }
    }

    protected function formOptions(): array
    {
        $companyId = Company::current()->id;

        return [
            'accounts' => Account::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
            'contacts' => Contact::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id'),
            'opportunities' => Opportunity::where('company_id', $companyId)->where('status', 'open')->orderBy('name')->pluck('name', 'id'),
            'priceBooks' => PriceBook::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'products' => Product::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit_price']),
            'employees' => Employee::query()->where('company_id', $companyId)->where('status', 'active')
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')->pluck('name', 'id'),
            'templates' => QuotationTemplate::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'discountThreshold' => \App\Models\Sales\SalesSetting::forCompany($companyId)->discount_approval_threshold_percent,
        ];
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'account_id' => ['nullable', 'exists:crm_accounts,id'],
            'contact_id' => ['nullable', 'exists:crm_contacts,id'],
            'opportunity_id' => ['nullable', 'exists:crm_opportunities,id'],
            'price_book_id' => ['nullable', 'exists:crm_price_books,id'],
            'valid_until' => ['nullable', 'date'],
            'owner_employee_id' => ['nullable', 'exists:employees,id'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]) + ['discount_percent' => $request->input('discount_percent', 0), 'tax_percent' => $request->input('tax_percent', 0)];
    }

    protected function companyQuery()
    {
        return Quotation::query()->where('company_id', Company::current()->id);
    }

    protected function ownCompany(Quotation $quotation): void
    {
        abort_unless($quotation->company_id === Company::current()->id, 404);
    }
}
