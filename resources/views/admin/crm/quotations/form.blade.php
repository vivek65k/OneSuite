@extends('layouts.admin')

@php
    $isEditing = $quotation->exists;
    $cancelUrl = route('admin.crm.quotations.index');
@endphp

@section('title', $isEditing ? $quotation->quotation_number : 'New Quotation')

@section('content')
    <x-page-title :title="$isEditing ? $quotation->quotation_number : 'New Quotation'" :breadcrumbs="['Quotations' => $cancelUrl]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    @if ($isEditing && $quotation->status === 'pending_approval')
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="ti ti-alert-triangle font-18"></i>
            <div>This quote's {{ $quotation->discount_percent }}% discount needs approval before it can be sent. <a href="{{ route('admin.crm.discount-approvals.index') }}">View discount approvals</a>.</div>
        </div>
    @endif

    <form method="POST" action="{{ $isEditing ? route('admin.crm.quotations.update', $quotation) : route('admin.crm.quotations.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card form-section tone-indigo">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-file-invoice"></i></span>
                        <div>
                            <h5 class="form-section-title">Quotation details</h5>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="account_id">Account</label>
                                <select class="form-select" id="account_id" name="account_id">
                                    <option value="">— None —</option>
                                    @foreach ($accounts as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('account_id', $quotation->account_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="contact_id">Contact</label>
                                <select class="form-select" id="contact_id" name="contact_id">
                                    <option value="">— None —</option>
                                    @foreach ($contacts as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('contact_id', $quotation->contact_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="opportunity_id">Opportunity</label>
                                <select class="form-select" id="opportunity_id" name="opportunity_id">
                                    <option value="">— None —</option>
                                    @foreach ($opportunities as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('opportunity_id', $quotation->opportunity_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="price_book_id">Price book</label>
                                <select class="form-select" id="price_book_id" name="price_book_id">
                                    <option value="">— Catalog prices —</option>
                                    @foreach ($priceBooks as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('price_book_id', $quotation->price_book_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="valid_until">Valid until</label>
                                <input type="date" class="form-control" id="valid_until" name="valid_until" value="{{ old('valid_until', optional($quotation->valid_until)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="owner_employee_id">Owner</label>
                                <select class="form-select" id="owner_employee_id" name="owner_employee_id">
                                    <option value="">— Unassigned —</option>
                                    @foreach ($employees as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('owner_employee_id', $quotation->owner_employee_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="notes">Notes / Terms</label>
                                <textarea name="notes" id="notes" rows="2" class="form-control">{{ old('notes', $quotation->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($isEditing)
                    <div class="card list-view tone-teal mb-3">
                        <div class="list-toolbar">
                            <div class="list-toolbar-title"><i class="ti ti-box"></i> Line items <span class="list-tab-count">{{ $quotation->lines->count() }}</span></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover list-table mb-0">
                                <thead class="thead-light">
                                    <tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Disc %</th><th class="text-end">Total</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($quotation->lines as $line)
                                        <tr>
                                            <td>{{ $line->description }}</td>
                                            <td class="text-end">{{ rtrim(rtrim($line->quantity, '0'), '.') }}</td>
                                            <td class="text-end">{{ number_format($line->unit_price, 2) }}</td>
                                            <td class="text-end">{{ $line->discount_percent }}%</td>
                                            <td class="text-end fw-semibold">{{ number_format($line->line_total, 2) }}</td>
                                            <td class="text-end">
                                                <form action="{{ route('admin.crm.quotations.lines.destroy', $line) }}" method="POST" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-3">No line items yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="list-filter-panel">
                            <form method="POST" action="{{ route('admin.crm.quotations.lines.store', $quotation) }}" class="row g-2 align-items-end" id="line-form">
                                @csrf
                                <div class="col-md-3">
                                    <label class="form-label">Product (optional)</label>
                                    <select name="product_id" class="form-select form-select-sm" id="line-product">
                                        <option value="">Custom line</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" data-price="{{ $product->unit_price }}" data-name="{{ $product->name }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3"><label class="form-label">Description</label><input type="text" name="description" id="line-description" class="form-control form-control-sm" required></div>
                                <div class="col-md-1"><label class="form-label">Qty</label><input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-sm" value="1" required></div>
                                <div class="col-md-2"><label class="form-label">Unit price</label><input type="number" step="0.01" min="0" name="unit_price" id="line-price" class="form-control form-control-sm" required></div>
                                <div class="col-md-1"><label class="form-label">Disc %</label><input type="number" min="0" max="100" name="discount_percent" class="form-control form-control-sm" value="0"></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add line</button></div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="record-sidebar">
                    <div class="card form-section tone-amber">
                        <div class="form-section-head form-section-head-compact">
                            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-percentage"></i></span>
                            <h5 class="form-section-title">Pricing</h5>
                        </div>
                        <div class="form-section-body">
                            <label class="form-label" for="discount_percent">Overall discount %</label>
                            <input type="number" min="0" max="100" class="form-control" id="discount_percent" name="discount_percent" value="{{ old('discount_percent', $quotation->discount_percent ?? 0) }}">
                            <div class="form-hint"><i class="ti ti-info-circle"></i> Over {{ $discountThreshold }}% needs approval before sending.</div>

                            <label class="form-label mt-3" for="tax_percent">Tax %</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" id="tax_percent" name="tax_percent" value="{{ old('tax_percent', $quotation->tax_percent ?? 0) }}">

                            @if ($isEditing)
                                <hr class="my-3">
                                <dl class="record-meta mb-0">
                                    <dt>Subtotal</dt><dd>{{ number_format($quotation->subtotal, 2) }}</dd>
                                    <dt>Discount</dt><dd>-{{ number_format($quotation->discount_amount, 2) }}</dd>
                                    <dt>Total</dt><dd class="fw-bold">{{ number_format($quotation->total, 2) }}</dd>
                                </dl>
                            @endif
                        </div>
                    </div>

                    @if ($isEditing)
                        <div class="card form-section tone-violet">
                            <div class="form-section-head form-section-head-compact">
                                <span class="form-section-icon form-section-icon-sm"><i class="ti ti-flag"></i></span>
                                <h5 class="form-section-title">Status: {{ \App\Models\Crm\Quotation::STATUSES[$quotation->status] ?? $quotation->status }}</h5>
                            </div>
                            <div class="form-section-body d-grid gap-2">
                                @if ($quotation->status === 'draft')
                                    <button type="submit" form="send-form" class="btn btn-de-primary btn-sm text-light"><i class="ti ti-send font-14 me-1"></i> Mark as Sent</button>
                                @endif
                                @if ($quotation->status === 'sent')
                                    <button type="submit" form="accept-form" class="btn btn-sm review-btn is-approve"><i class="ti ti-check"></i> Mark Accepted</button>
                                    <button type="submit" form="reject-form" class="btn btn-sm review-btn is-reject"><i class="ti ti-x"></i> Mark Rejected</button>
                                @endif
                                @if ($quotation->status === 'accepted')
                                    <a href="{{ route('admin.crm.sales-orders.create', ['quotation_id' => $quotation->id]) }}" class="btn btn-light btn-sm"><i class="ti ti-shopping-cart font-14 me-1"></i> Convert to Sales Order</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="record-footer">
            <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
            <button type="submit" class="btn btn-de-primary text-light px-4">
                <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create quotation' }}
            </button>
        </div>
    </form>

    @if ($isEditing)
        <form id="send-form" action="{{ route('admin.crm.quotations.send', $quotation) }}" method="POST" class="d-none">@csrf</form>
        <form id="accept-form" action="{{ route('admin.crm.quotations.accept', $quotation) }}" method="POST" class="d-none">@csrf</form>
        <form id="reject-form" action="{{ route('admin.crm.quotations.reject', $quotation) }}" method="POST" class="d-none">@csrf</form>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var productSelect = document.getElementById('line-product');
            if (!productSelect) return;
            productSelect.addEventListener('change', function () {
                var opt = productSelect.options[productSelect.selectedIndex];
                document.getElementById('line-price').value = opt.dataset.price || '';
                if (opt.dataset.name) document.getElementById('line-description').value = opt.dataset.name;
            });
        })();
    </script>
@endpush
