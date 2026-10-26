@extends('layouts.admin')

@section('title', 'Fulfilment & Delivery')

@section('content')
    <x-page-title title="Fulfilment & Delivery" />

    <p class="text-muted font-13 mb-3"><i class="ti ti-info-circle"></i> Picking, packing and dispatch happen in Inventory & Warehouse - confirming an order here reserves the stock automatically; this screen tracks each order's fulfilment status and links out to the warehouse screens for the next step.</p>

    <div class="kpi-strip mb-3">
        <x-kpi-card label="Orders Awaiting Fulfilment" :value="$orders->count()" icon="ti-truck-delivery" />
        <x-kpi-card label="Lines Without Inventory Link" :value="$unlinkedCount" icon="ti-unlink" tone="amber" />
        <x-kpi-card label="Orders Missing a Warehouse" :value="$noWarehouseCount" icon="ti-building-warehouse" tone="danger" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-truck-delivery"></i> Confirmed Orders <span class="list-tab-count">{{ $orders->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Order #</th><th>Account</th><th>Warehouse</th><th>Status</th><th style="min-width:360px">Line Fulfilment</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="font-monospace font-13">{{ $order->order_number }}</td>
                            <td class="text-muted font-13">{{ $order->account?->name ?? '—' }}</td>
                            <td class="text-muted font-13">
                                @if ($order->warehouse)
                                    {{ $order->warehouse->name }}
                                @else
                                    <span class="text-danger">Not set</span>
                                @endif
                            </td>
                            <td><span class="tag font-11 {{ $order->status === 'partially_fulfilled' ? 'tone-amber' : 'tone-sky' }}">{{ \App\Models\Crm\SalesOrder::STATUSES[$order->status] }}</span></td>
                            <td>
                                @foreach ($order->lines as $line)
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="font-13" style="min-width:140px">{{ $line->description }}</span>
                                        <span class="font-11 text-muted">{{ $line->fulfilled_qty }}/{{ $line->quantity }}</span>
                                        @if ($line->reservation)
                                            <span class="tag font-11 {{ $line->reservation->status === 'fulfilled' ? 'tone-success' : 'tone-sky' }}">{{ $line->reservation->reservation_number }} · {{ ucfirst($line->reservation->status) }}</span>
                                        @elseif (! $line->product?->inventory_item_id)
                                            <span class="tag tone-slate font-11">No Inventory link</span>
                                        @else
                                            <span class="tag tone-danger font-11">Shortage - not reserved</span>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.sales.orders.edit', $order) }}" class="btn btn-light btn-sm">Order</a>
                                <a href="{{ route('admin.inventory.pick-lists.index') }}" class="btn btn-light btn-sm">Pick Lists</a>
                                <a href="{{ route('admin.inventory.dispatches.index') }}" class="btn btn-light btn-sm">Dispatch</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-truck-delivery font-24 d-block mb-2"></i>No orders are currently confirmed and awaiting fulfilment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
