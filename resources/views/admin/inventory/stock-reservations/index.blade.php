@extends('layouts.admin')

@section('title', 'Stock Reservations')

@section('content')
    <x-page-title title="Stock Reservations" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Reserve stock</div></div>
        <div class="list-filter-panel">
            <p class="text-muted font-13 mb-2"><i class="ti ti-info-circle"></i> A reservation reduces available stock only. On-hand quantity is reduced later, at dispatch.</p>
            <form method="POST" action="{{ route('admin.inventory.stock-reservations.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select form-select-sm" required>
                        @foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Item</label>
                    <select name="item_id" class="form-select form-select-sm" required>
                        @foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Quantity</label><input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-sm" required></div>
                <div class="col-md-3"><label class="form-label">Reference</label><input type="text" name="reference" class="form-control form-control-sm" placeholder="Sales order / customer ref"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Reserve</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-stack-2"></i> All Reservations <span class="list-tab-count">{{ $reservations->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Reservation #</th><th>Item</th><th>Warehouse</th><th>Reference</th><th>Qty</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($reservations as $reservation)
                        <tr>
                            <td class="font-monospace font-13">{{ $reservation->reservation_number }}</td>
                            <td class="font-13">{{ $reservation->item?->name }}</td>
                            <td class="text-muted font-13">{{ $reservation->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $reservation->reference ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $reservation->quantity }}</td>
                            <td><span class="tag tone-{{ $reservation->status === 'fulfilled' ? 'success' : ($reservation->status === 'released' ? 'slate' : 'amber') }}">{{ \App\Models\Inventory\StockReservation::STATUSES[$reservation->status] }}</span></td>
                            <td class="text-end">
                                @if ($reservation->status === 'reserved')
                                    <form action="{{ route('admin.inventory.stock-reservations.release', $reservation) }}" method="POST" class="d-inline" onsubmit="return confirm('Release this reservation?');">
                                        @csrf
                                        <button type="submit" class="btn btn-light btn-sm">Release</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-stack-2 font-24 d-block mb-2"></i>No reservations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reservations->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $reservations->links() }}</div></div>
        @endif
    </div>
@endsection
