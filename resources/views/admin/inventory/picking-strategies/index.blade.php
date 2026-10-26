@extends('layouts.admin')

@section('title', 'Picking Strategies')

@section('content')
    <x-page-title title="Picking Strategies" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted font-13 mb-3">Each warehouse has one default picking strategy. It governs which stock is picked first when fulfilling an order.</p>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card p-3 h-100"><div class="fw-semibold mb-1"><i class="ti ti-route text-primary"></i> FIFO</div><div class="text-muted font-13">First In, First Out - the oldest stock received is picked first.</div></div></div>
        <div class="col-md-4"><div class="card p-3 h-100"><div class="fw-semibold mb-1"><i class="ti ti-calendar-time text-primary"></i> FEFO</div><div class="text-muted font-13">First Expiry, First Out - the soonest-to-expire batch is picked first.</div></div></div>
        <div class="col-md-4"><div class="card p-3 h-100"><div class="fw-semibold mb-1"><i class="ti ti-map-pin text-primary"></i> Nearest-Bin</div><div class="text-muted font-13">Picks from the closest available bin to minimize travel distance.</div></div></div>
        <div class="col-md-4"><div class="card p-3 h-100"><div class="fw-semibold mb-1"><i class="ti ti-box-multiple text-primary"></i> Batch Picking</div><div class="text-muted font-13">Multiple orders are picked together in one pass through the warehouse.</div></div></div>
        <div class="col-md-4"><div class="card p-3 h-100"><div class="fw-semibold mb-1"><i class="ti ti-layout-grid text-primary"></i> Zone Picking</div><div class="text-muted font-13">Pickers are assigned to a zone and only pick items within it.</div></div></div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-route"></i> Warehouse Strategy Assignment</div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Warehouse</th><th>Current Strategy</th><th class="text-end">Change</th></tr></thead>
                <tbody>
                    @forelse ($warehouses as $warehouse)
                        <tr>
                            <td class="fw-semibold">{{ $warehouse->name }}</td>
                            <td><span class="tag tone-indigo">{{ \App\Models\Inventory\Warehouse::PICKING_STRATEGIES[$warehouse->picking_strategy] ?? $warehouse->picking_strategy }}</span></td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.picking-strategies.update', $warehouse) }}" method="POST" class="d-inline-flex gap-1 justify-content-end">
                                    @csrf @method('PUT')
                                    <select name="picking_strategy" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                        @foreach (\App\Models\Inventory\Warehouse::PICKING_STRATEGIES as $key => $label)
                                            <option value="{{ $key }}" @selected($warehouse->picking_strategy === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-5">No warehouses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
