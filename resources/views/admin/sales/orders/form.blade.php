@extends('layouts.admin')

@php
    $isEditing = $order->exists;
    $cancelUrl = route('admin.sales.orders.index');
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
    @endif

    @if ($isEditing && $order->status === 'cancelled')
        <div class="alert alert-danger"><i class="ti ti-alert-triangle font-18 me-1"></i> This order was cancelled{{ $order->cancelled_reason ? ': '.$order->cancelled_reason : '' }}.</div>
    @endif

    <form method="POST" action="{{ $isEditing ? route('admin.sales.orders.update', $order) : route('admin.sales.orders.store') }}" id="order-form">
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
                                <label class="form-label">Account</label>
                                <select class="form-select" name="account_id">
                                    <option value="">— None —</option>
                                    @foreach ($accounts as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('account_id', $order->account_id ?? $fromQuotation?->account_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" required @if($isEditing) disabled @endif>
                                    @foreach (\App\Models\Crm\SalesOrder::STATUSES as $key => $label)
                                        <option value="{{ $key }}" @selected(old('status', $order->status ?? 'draft') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if ($isEditing)
                                    <input type="hidden" name="status" value="{{ $order->status }}">
                                    <p class="form-text font-11 mb-0">Use the status actions on the right - status isn't edited directly once created.</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Order date</label>
                                <input type="date" class="form-control" name="order_date" value="{{ old('order_date', optional($order->order_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fulfilment Warehouse</label>
                                <select class="form-select" name="warehouse_id">
                                    <option value="">— None —</option>
                                    @foreach ($warehouses as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('warehouse_id', $order->warehouse_id) === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                                <p class="form-text font-11 mb-0">Required to auto-reserve Inventory stock on confirm.</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Owner</label>
                                <select class="form-select" name="owner_employee_id">
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
                        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-box"></i> Line items <span class="list-tab-count">{{ $order->lines->count() }}</span></div></div>
                        <div class="table-responsive">
                            <table class="table table-hover list-table mb-0">
                                <thead class="thead-light"><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Total</th><th>Inventory Reservation</th><th class="text-end">Fulfilled</th><th class="text-end">Backorder</th>@if(in_array($order->status,['confirmed','partially_fulfilled']))<th style="min-width:160px">Record Fulfilment</th>@endif</tr></thead>
                                <tbody>
                                    @forelse ($order->lines as $line)
                                        <tr>
                                            <td class="font-13">{{ $line->description }}</td>
                                            <td class="text-end font-13">{{ rtrim(rtrim($line->quantity, '0'), '.') }}</td>
                                            <td class="text-end font-13">{{ number_format($line->unit_price, 2) }}</td>
                                            <td class="text-end fw-semibold font-13">{{ number_format($line->line_total, 2) }}</td>
                                            <td class="font-13">
                                                @if ($line->reservation)
                                                    <span class="tag font-11 {{ $line->reservation->status === 'fulfilled' ? 'tone-success' : ($line->reservation->status === 'released' ? 'tone-slate' : 'tone-sky') }}">{{ $line->reservation->reservation_number }}</span>
                                                @elseif (! $line->product?->inventory_item_id)
                                                    <span class="text-muted font-11">No linked Inventory item</span>
                                                @else
                                                    <span class="text-muted font-11">Not yet reserved</span>
                                                @endif
                                            </td>
                                            <td class="text-end font-13">{{ $line->fulfilled_qty }}</td>
                                            <td class="text-end font-13 {{ $line->backorder_qty > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $line->backorder_qty }}</td>
                                            @if (in_array($order->status, ['confirmed', 'partially_fulfilled']))
                                                <td>
                                                    @if ($line->reservation?->status === 'reserved')
                                                        <span class="text-muted font-11">Dispatch from Inventory to fulfil</span>
                                                    @else
                                                        <form action="{{ route('admin.sales.orders.lines.fulfill', $line) }}" method="POST" class="d-flex gap-1">
                                                            @csrf
                                                            <input type="number" step="0.01" min="0" max="{{ $line->quantity }}" name="fulfilled_qty" class="form-control form-control-sm" value="{{ $line->quantity }}" style="width:90px" required>
                                                            <button type="submit" class="btn btn-light btn-sm">Save</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted py-3">No line items yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if (! in_array($order->status, ['cancelled', 'closed']))
                            <div class="list-filter-panel">
                                <form method="POST" action="{{ route('admin.sales.orders.lines.store', $order) }}" class="row g-2 align-items-end">
                                    @csrf
                                    <div class="col-md-5"><label class="form-label">Description</label><input type="text" name="description" class="form-control form-control-sm" required></div>
                                    <div class="col-md-2"><label class="form-label">Qty</label><input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-sm" value="1" required></div>
                                    <div class="col-md-3"><label class="form-label">Unit price</label><input type="number" step="0.01" min="0" name="unit_price" class="form-control form-control-sm" required></div>
                                    <div class="col-md-2"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
                                </form>
                            </div>
                        @endif
                    </div>

                    <div class="card list-view">
                        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-history"></i> Order Timeline</div></div>
                        <div class="table-responsive">
                            <table class="table table-hover list-table mb-0">
                                <thead class="thead-light"><tr><th>Date</th><th>Type</th><th>Description</th><th>User</th></tr></thead>
                                <tbody>
                                    @forelse ($order->events as $event)
                                        <tr>
                                            <td class="text-muted font-13">{{ $event->created_at->format('M j, Y H:i') }}</td>
                                            <td><span class="tag tone-sky font-11">{{ \App\Models\Crm\SalesOrderEvent::TYPES[$event->event_type] ?? $event->event_type }}</span></td>
                                            <td class="text-muted font-13">{{ $event->description }}</td>
                                            <td class="text-muted font-13">{{ $event->employee?->name ?? 'System' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">No events recorded yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="record-sidebar">
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
                                <span class="form-section-icon form-section-icon-sm"><i class="ti ti-flag"></i></span>
                                <h5 class="form-section-title">Status: {{ \App\Models\Crm\SalesOrder::STATUSES[$order->status] ?? $order->status }}</h5>
                            </div>
                            <div class="form-section-body d-grid gap-2">
                                @if (in_array($order->status, ['draft', 'pending']))
                                    <button type="submit" form="confirm-form" class="btn btn-de-primary btn-sm text-light"><i class="ti ti-check font-14 me-1"></i> Confirm Order</button>
                                @endif
                                @if ($order->status === 'fulfilled')
                                    <button type="submit" form="close-form" class="btn btn-light btn-sm"><i class="ti ti-lock font-14 me-1"></i> Close Order</button>
                                @endif
                                @if (! in_array($order->status, ['partially_fulfilled', 'fulfilled', 'closed', 'cancelled']))
                                    <button type="button" class="btn btn-sm review-btn is-reject" onclick="document.getElementById('cancel-reason-box').classList.toggle('d-none')"><i class="ti ti-x"></i> Cancel Order</button>
                                    <div id="cancel-reason-box" class="d-none">
                                        <form action="{{ route('admin.sales.orders.cancel', $order) }}" method="POST" class="d-flex gap-1 mt-1">
                                            @csrf
                                            <input type="text" name="cancelled_reason" class="form-control form-control-sm" placeholder="Reason" required>
                                            <button type="submit" class="btn btn-light btn-sm">Confirm</button>
                                        </form>
                                    </div>
                                @endif
                                @if ($order->quotation)
                                    <a href="{{ route('admin.sales.quotations.edit', $order->quotation) }}" class="btn btn-light btn-sm"><i class="ti ti-file-text font-14 me-1"></i> View Source Quotation</a>
                                @endif
                                <a href="{{ route('admin.crm.contracts.create', ['sales_order_id' => $order->id]) }}" class="btn btn-light btn-sm"><i class="ti ti-file-invoice font-14 me-1"></i> Create Contract</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="record-footer">
            <a href="{{ $cancelUrl }}" class="btn btn-light px-4">Cancel</a>
            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> {{ $isEditing ? 'Save changes' : 'Create order' }}</button>
        </div>
    </form>

    @if ($isEditing)
        <form id="confirm-form" action="{{ route('admin.sales.orders.confirm', $order) }}" method="POST" class="d-none">@csrf</form>
        <form id="close-form" action="{{ route('admin.sales.orders.close', $order) }}" method="POST" class="d-none">@csrf</form>
    @endif
@endsection
