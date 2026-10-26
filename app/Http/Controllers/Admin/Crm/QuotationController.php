<?php

namespace App\Http\Controllers\Admin\Crm;

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
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuotationController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const SORTABLE = ['quotation_number', 'total', 'valid_until', 'created_at'];

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $query = $this->companyQuery()->with(['account', 'owner']);

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('quotation_number', 'like', "%{$search}%")->orWhereHas('account', fn ($a) => $a->where('name', 'like', "%{$search}%")));
        }

        return view('admin.crm.quotations.index', [
            'quotations' => $query->orderBy($sort, $direction)->paginate(25)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
            'total' => $this->companyQuery()->count(),
            'pendingApprovalCount' => $this->companyQuery()->where('status', 'pending_approval')->count(),
            'acceptedValue' => $this->companyQuery()->where('status', 'accepted')->sum('total'),
        ]);
    }

    public function create(): View
    {
        return view('admin.crm.quotations.form', [
            'quotation' => new Quotation,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $this->validated($request);
        $data['quotation_number'] = Quotation::nextNumber($companyId);

        $quotation = Quotation::create($data + ['company_id' => $companyId, 'status' => 'draft']);

        return redirect()->route('admin.crm.quotations.edit', $quotation)->with('status', 'Quotation created - add line items below.');
    }

    public function edit(Quotation $quotation): View
    {
        $this->ownCompany($quotation);

        return view('admin.crm.quotations.form', [
            'quotation' => $quotation->load(['lines.product', 'discountApproval']),
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Quotation $quotation): RedirectResponse
    {
        $this->ownCompany($quotation);

        $quotation->update($this->validated($request));
        $quotation->recalculateTotals();

        $this->evaluateDiscountApproval($quotation->fresh());

        return redirect()->route('admin.crm.quotations.edit', $quotation)->with('status', 'Quotation updated.');
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        $this->ownCompany($quotation);
        $quotation->delete();

        return redirect()->route('admin.crm.quotations.index')->with('status', 'Quotation deleted.');
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

        return back()->with('status', 'Quotation accepted.');
    }

    public function reject(Quotation $quotation): RedirectResponse
    {
        $this->ownCompany($quotation);
        $quotation->update(['status' => 'rejected']);

        return back()->with('status', 'Quotation rejected.');
    }

    /**
     * Opens an edit-only draft if the overall discount crosses the
     * threshold and no decision has been made yet on the current request -
     * approving/rejecting later resolves it without creating duplicates.
     */
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
            'discountThreshold' => \App\Models\Sales\SalesSetting::forCompany($companyId)->discount_approval_threshold_percent,
            'employees' => Employee::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('name')
                ->pluck('name', 'id'),
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
