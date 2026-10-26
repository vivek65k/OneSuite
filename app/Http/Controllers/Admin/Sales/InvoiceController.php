<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Crm\SalesOrder;
use App\Models\Sales\CustomerProfile;
use App\Models\Sales\InvoiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * There is no Finance app yet, so this is Sales' side of the boundary
 * only: raising a request to invoice an order and tracking payment
 * status back for the sales team. It is not an official tax invoice,
 * ledger entry or accounting record - invoice_reference is whatever
 * number Finance would eventually assign. The one real integration
 * this does maintain is the customer's outstanding_balance on their
 * commercial profile (Sales → Customers & Accounts), so credit control
 * has real numbers to work with until Finance exists.
 */
class InvoiceController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(Request $request): View
    {
        $companyId = Company::current()->id;
        $status = (string) $request->query('status', '');

        $query = InvoiceRequest::where('company_id', $companyId)->with(['salesOrder.account', 'contract']);
        if ($status !== '') {
            $query->where('status', $status);
        }

        $requests = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('admin.sales.invoices.index', [
            'requests' => $requests,
            'status' => $status,
            'totalRequested' => InvoiceRequest::where('company_id', $companyId)->sum('requested_amount'),
            'totalOutstanding' => InvoiceRequest::where('company_id', $companyId)->whereIn('status', ['invoiced', 'partially_paid'])
                ->get()->sum('outstanding_amount'),
            'overdueCount' => InvoiceRequest::where('company_id', $companyId)->whereIn('status', ['invoiced', 'partially_paid'])
                ->whereNotNull('due_date')->where('due_date', '<', now())->count(),
            'invoiceableOrders' => SalesOrder::where('company_id', $companyId)
                ->whereIn('status', ['confirmed', 'partially_fulfilled', 'fulfilled', 'closed'])
                ->whereDoesntHave('invoiceRequests', fn ($q) => $q->whereNotIn('status', ['cancelled']))
                ->with('account')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Company::current()->id;
        $data = $request->validate([
            'sales_order_id' => ['required', 'exists:crm_sales_orders,id'],
            'requested_amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = SalesOrder::where('company_id', $companyId)->findOrFail($data['sales_order_id']);
        abort_if($order->invoiceRequests()->whereNotIn('status', ['cancelled'])->exists(), 422, 'This order already has an active invoice request.');

        InvoiceRequest::create($data + [
            'company_id' => $companyId, 'contract_id' => $order->contracts()->first()?->id,
            'status' => 'requested', 'requested_by_employee_id' => $this->currentEmployee()?->id,
        ]);

        return redirect()->route('admin.sales.invoices.index')->with('status', "Invoice requested for {$order->order_number}.");
    }

    /**
     * Marking an invoice as raised is the point the amount is added to
     * the customer's outstanding_balance - this is deliberately manual
     * since there is no Finance app yet to confirm it actually happened.
     */
    public function markInvoiced(Request $request, InvoiceRequest $invoiceRequest): RedirectResponse
    {
        $this->ownCompany($invoiceRequest);
        abort_unless($invoiceRequest->status === 'requested', 422, 'Only a requested invoice can be marked as raised.');

        $data = $request->validate([
            'invoice_reference' => ['required', 'string', 'max:100'],
            'due_date' => ['nullable', 'date'],
        ]);

        $invoiceRequest->update($data + ['status' => 'invoiced']);
        $this->adjustCustomerBalance($invoiceRequest, (float) $invoiceRequest->requested_amount);

        return back()->with('status', "Invoice {$data['invoice_reference']} recorded - customer's outstanding balance updated.");
    }

    public function recordPayment(Request $request, InvoiceRequest $invoiceRequest): RedirectResponse
    {
        $this->ownCompany($invoiceRequest);
        abort_unless(in_array($invoiceRequest->status, ['invoiced', 'partially_paid'], true), 422, 'Only an invoiced order can record a payment.');

        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01', 'max:'.$invoiceRequest->outstanding_amount]]);

        $newPaid = (float) $invoiceRequest->amount_paid + $data['amount'];
        $invoiceRequest->update([
            'amount_paid' => $newPaid,
            'status' => $newPaid >= (float) $invoiceRequest->requested_amount ? 'paid' : 'partially_paid',
        ]);
        $this->adjustCustomerBalance($invoiceRequest, -$data['amount']);

        return back()->with('status', 'Payment recorded.');
    }

    public function cancel(InvoiceRequest $invoiceRequest): RedirectResponse
    {
        $this->ownCompany($invoiceRequest);
        abort_if($invoiceRequest->status === 'paid', 422, 'A fully paid invoice request cannot be cancelled.');

        if (in_array($invoiceRequest->status, ['invoiced', 'partially_paid'], true)) {
            $this->adjustCustomerBalance($invoiceRequest, -(float) $invoiceRequest->outstanding_amount);
        }

        $invoiceRequest->update(['status' => 'cancelled']);

        return back()->with('status', 'Invoice request cancelled.');
    }

    protected function adjustCustomerBalance(InvoiceRequest $invoiceRequest, float $delta): void
    {
        $accountId = $invoiceRequest->salesOrder?->account_id;
        if (! $accountId) {
            return;
        }

        $profile = CustomerProfile::forAccount($invoiceRequest->company_id, $accountId);
        $profile->update(['outstanding_balance' => max(0, (float) $profile->outstanding_balance + $delta)]);
    }

    protected function ownCompany(InvoiceRequest $invoiceRequest): void
    {
        abort_unless($invoiceRequest->company_id === Company::current()->id, 404);
    }
}
