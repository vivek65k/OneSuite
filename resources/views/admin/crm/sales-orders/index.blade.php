@extends('layouts.admin')

@section('title', 'Sales Orders')

@php
    $statusTone = fn ($status) => match ($status) {
        'fulfilled' => 'success',
        'cancelled' => 'rose',
        'confirmed' => 'sky',
        default => 'amber',
    };
@endphp

@section('content')
    <x-page-title title="Sales Orders" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Orders" :value="$total" icon="ti-shopping-cart" tone="indigo" />
        <x-kpi-card label="Confirmed/Fulfilled Value" :value="number_format($totalValue)" icon="ti-currency-rupee" tone="success" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.sales-orders.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-shopping-cart"></i> Orders</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search order number" aria-label="Search orders">
                    </div>
                    <select name="status" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Status">
                        <option value="">Any status</option>
                        @foreach (\App\Models\Crm\SalesOrder::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.crm.sales-orders.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Order</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Order #</th><th>Account</th><th>Status</th><th class="text-end">Total</th><th>Owner</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a href="{{ route('admin.crm.sales-orders.edit', $order) }}" class="fw-semibold">{{ $order->order_number }}</a></td>
                            <td>{{ $order->account?->name ?? '—' }}</td>
                            <td><span class="tag tone-{{ $statusTone($order->status) }}">{{ \App\Models\Crm\SalesOrder::STATUSES[$order->status] ?? $order->status }}</span></td>
                            <td class="text-end salary-figure">{{ number_format($order->total, 2) }}</td>
                            <td>{{ $order->owner?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.sales-orders.edit', $order) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.sales-orders.destroy', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this order?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-shopping-cart font-24 d-block mb-2"></i>No sales orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $orders->links() }}</div></div>
        @endif
    </div>
@endsection
