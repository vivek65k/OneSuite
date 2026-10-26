@extends('layouts.admin')

@section('title', 'Invoices & Payments')

@section('content')
    <x-page-title title="Invoices & Payments" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <p class="text-muted font-13 mb-3"><i class="ti ti-info-circle"></i> There is no Finance app yet - this tracks Sales' side of billing only: requesting an invoice and recording payment status, not an official tax invoice or accounting entry. Marking an invoice raised or a payment received updates the customer's outstanding balance under Customers &amp; Accounts.</p>

    <div class="kpi-strip mb-3">
        <x-kpi-card label="Total Requested" :value="number_format($totalRequested, 2)" icon="ti-receipt" />
        <x-kpi-card label="Outstanding" :value="number_format($totalOutstanding, 2)" icon="ti-currency-rupee" tone="amber" />
        <x-kpi-card label="Overdue" :value="$overdueCount" icon="ti-alert-triangle" tone="danger" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Request an invoice</div></div>
        <div class="list-filter-panel">
            @if ($invoiceableOrders->isEmpty())
                <p class="text-muted font-13 mb-0">No orders are ready to invoice - an order needs to be confirmed (or further along) with no existing active request.</p>
            @else
                <form method="POST" action="{{ route('admin.sales.invoices.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <select name="sales_order_id" class="form-select form-select-sm" id="order-select" required>
                            @foreach ($invoiceableOrders as $order)
                                <option value="{{ $order->id }}" data-total="{{ $order->total }}">{{ $order->order_number }} — {{ $order->account?->name ?? 'No account' }} ({{ number_format($order->total, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><input type="number" step="0.01" min="0.01" name="requested_amount" id="amount-input" class="form-control form-control-sm" placeholder="Amount" required></div>
                    <div class="col-md-2"><input type="date" name="due_date" class="form-control form-control-sm" placeholder="Due date"></div>
                    <div class="col-md-3"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Request</button></div>
                </form>
            @endif
        </div>
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.sales.invoices.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-receipt"></i> Invoice Requests <span class="list-tab-count">{{ $requests->total() }}</span></div>
                <div class="list-toolbar-actions">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">Any status</option>
                        @foreach (\App\Models\Sales\InvoiceRequest::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Order</th><th>Account</th><th>Requested</th><th>Paid</th><th>Outstanding</th><th>Due</th><th>Status</th><th class="text-end" style="min-width:260px"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($requests as $req)
                        <tr>
                            <td class="font-monospace font-13">{{ $req->salesOrder?->order_number }}</td>
                            <td class="text-muted font-13">{{ $req->salesOrder?->account?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ number_format($req->requested_amount, 2) }}</td>
                            <td class="text-muted font-13">{{ number_format($req->amount_paid, 2) }}</td>
                            <td class="font-13 fw-semibold {{ $req->outstanding_amount > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($req->outstanding_amount, 2) }}</td>
                            <td class="text-muted font-13">
                                {{ $req->due_date?->format('M j, Y') ?? '—' }}
                                @if ($req->is_overdue)<span class="tag tone-danger font-11">Overdue</span>@endif
                            </td>
                            <td><span class="tag font-11 {{ $req->status === 'paid' ? 'tone-success' : ($req->status === 'cancelled' ? 'tone-slate' : ($req->status === 'partially_paid' ? 'tone-amber' : 'tone-sky')) }}">{{ \App\Models\Sales\InvoiceRequest::STATUSES[$req->status] }}</span></td>
                            <td class="text-end">
                                @if ($req->status === 'requested')
                                    <form action="{{ route('admin.sales.invoices.invoiced', $req) }}" method="POST" class="d-inline-flex gap-1">
                                        @csrf
                                        <input type="text" name="invoice_reference" class="form-control form-control-sm" placeholder="Invoice #" style="width:100px" required>
                                        <button type="submit" class="btn btn-light btn-sm">Mark Raised</button>
                                    </form>
                                @elseif (in_array($req->status, ['invoiced', 'partially_paid']))
                                    <form action="{{ route('admin.sales.invoices.payment', $req) }}" method="POST" class="d-inline-flex gap-1">
                                        @csrf
                                        <input type="number" step="0.01" min="0.01" max="{{ $req->outstanding_amount }}" name="amount" class="form-control form-control-sm" placeholder="Amount" style="width:90px" required>
                                        <button type="submit" class="btn btn-light btn-sm">Record Payment</button>
                                    </form>
                                @endif
                                @if ($req->status !== 'paid' && $req->status !== 'cancelled')
                                    <form action="{{ route('admin.sales.invoices.cancel', $req) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Cancel"><i class="ti ti-x font-16"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-receipt font-24 d-block mb-2"></i>No invoice requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $requests->links() }}</div></div>
        @endif
    </div>

    <script>
        (function () {
            var select = document.getElementById('order-select');
            var amount = document.getElementById('amount-input');
            function sync() {
                var opt = select.options[select.selectedIndex];
                if (opt) amount.value = opt.dataset.total || '';
            }
            if (select) { select.addEventListener('change', sync); sync(); }
        })();
    </script>
@endsection
