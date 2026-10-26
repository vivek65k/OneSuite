@extends('layouts.admin')

@php
    $isEditing = $order->exists;
    $cancelUrl = route('admin.crm.sales-orders.index');
@endphp

@section('title', $isEditing ? $order->order_number : 'New Sales Order')

@section('content')
    <x-page-title :title="$isEditing ? $order->order_number : 'New Sales Order'" :breadcrumbs="['Sales Orders' => $cancelUrl]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    @if ($fromQuotation)
        <div class="alert alert-info">Creating this order from quotation <strong>{{ $fromQuotation->quotation_number }}</strong> - its {{ $fromQuotation->lines->count() }} line items will be copied over.</div>
        <input type="hidden" name="quotation_id" value="{{ $fromQuotation->id }}" form="order-form">
    @endif

    <form method="POST" action="{{ $isEditing ? route('admin.crm.sales-orders.update', $order) : route('admin.crm.sales-orders.store') }}" id="order-form">
        @csrf
        @if ($isEditing) @method('PUT') @endif
        @if ($fromQuotation)<input type="hidden" name="quotation_id" value="{{ $fromQuotation->id }}">@endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card form-section tone-indigo">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-shopping-cart"></i></span>
                        <div><h5 class="form-section-title">Order details</h5></div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="account_id">Account</label>
                                <select class="form-select" id="account_id" name="account_id">
                                    <option value="">— None —</option>
                                    @foreach ($accounts as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('account_id', $order->account_id ?? $fromQuotation?->account_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    @foreach (\App\Models\Crm\SalesOrder::STATUSES as $key => $label)
                                        <option value="{{ $key }}" @selected(old('status', $order->status ?? 'pending') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="order_date">Order date</label>
                                <input type="date" class="form-control" id="order_date" name="order_date" value="{{ old('order_date', optional($order->order_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="owner_employee_id">Owner</label>
                                <select class="form-select" id="owner_employee_id" name="owner_employee_id">
                                    <option value="">— Unassigned —</option>
                                    @foreach ($employees as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('owner_employee_id', $order->owner_employee_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($isEditing)
                    <div class="card list-view tone-teal mb-3">
                        <div class="list-toolbar">
                            <div class="list-toolbar-title"><i class="ti ti-box"></i> Line items <span class="list-tab-count">{{ $order->lines->count() }}</span></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover list-table mb-0">
                                <thead class="thead-light"><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Total</th></tr></thead>
                                <tbody>
                                    @forelse ($order->lines as $line)
                                        <tr>
                                            <td>{{ $line->description }}</td>
                                            <td class="text-end">{{ rtrim(rtrim($line->quantity, '0'), '.') }}</td>
                                            <td class="text-end">{{ number_format($line->unit_price, 2) }}</td>
                                            <td class="text-end fw-semibold">{{ number_format($line->line_total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">No line items yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="list-filter-panel">
                            <form method="POST" action="{{ route('admin.crm.sales-orders.lines.store', $order) }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-5"><label class="form-label">Description</label><input type="text" name="description" class="form-control form-control-sm" required></div>
                                <div class="col-md-2"><label class="form-label">Qty</label><input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-sm" value="1" required></div>
                                <div class="col-md-3"><label class="form-label">Unit price</label><input type="number" step="0.01" min="0" name="unit_price" class="form-control form-control-sm" required></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                @if ($isEditing)
                    <div class="card form-section tone-amber">
                        <div class="form-section-head form-section-head-compact">
                            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-currency-rupee"></i></span>
                            <h5 class="form-section-title">Total</h5>
                        </div>
                        <div class="form-section-body">
                            <div class="fs-3 fw-bold">{{ number_format($order->total, 2) }}</div>
                        </div>
                    </div>

                    <div class="card form-section tone-violet">
                        <div class="form-section-head form-section-head-compact">
                            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-file-text"></i></span>
                            <h5 class="form-section-title">Next step</h5>
                        </div>
                        <div class="form-section-body">
                            <a href="{{ route('admin.crm.contracts.create', ['sales_order_id' => $order->id]) }}" class="btn btn-light btn-sm w-100"><i class="ti ti-file-invoice font-14 me-1"></i> Create Contract</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="record-footer">
            <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
            <button type="submit" class="btn btn-de-primary text-light px-4">
                <i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create order' }}
            </button>
        </div>
    </form>
@endsection
