@extends('layouts.admin')

@section('title', 'Pick Lists')

@section('content')
    <x-page-title title="Pick Lists" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Create a pick list from reservations</div></div>
        <div class="list-filter-panel">
            @if ($reservations->isEmpty())
                <p class="text-muted font-13 mb-0">No unpicked reservations available. Reserve stock first.</p>
            @else
                <form method="POST" action="{{ route('admin.inventory.pick-lists.store') }}">
                    @csrf
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label">Warehouse</label>
                            <select name="warehouse_id" class="form-select form-select-sm" required>
                                @foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Picking Strategy</label>
                            <select name="strategy" class="form-select form-select-sm">
                                <option value="">None</option>
                                <option value="fifo">FIFO</option>
                                <option value="fefo">FEFO</option>
                                <option value="nearest_bin">Nearest Bin</option>
                                <option value="batch">Batch</option>
                                <option value="zone">Zone</option>
                            </select>
                        </div>
                    </div>
                    <label class="form-label">Pick these reservations</label>
                    <div class="list-group mb-2">
                        @foreach ($reservations as $reservation)
                            <label class="list-group-item d-flex align-items-center gap-2">
                                <input type="checkbox" name="reservation_id[]" value="{{ $reservation->id }}" class="form-check-input m-0">
                                <span class="font-13">{{ $reservation->reservation_number }} — {{ $reservation->item?->name }} (qty {{ $reservation->quantity }}) {{ $reservation->reference ? '· '.$reservation->reference : '' }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-de-primary text-light px-4">Create Pick List</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-clipboard-list"></i> All Pick Lists <span class="list-tab-count">{{ $pickLists->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Pick List #</th><th>Warehouse</th><th>Lines</th><th>Status</th><th style="min-width:260px">Pick Lines</th></tr></thead>
                <tbody>
                    @forelse ($pickLists as $pickList)
                        <tr>
                            <td class="font-monospace font-13">{{ $pickList->pick_list_number }}</td>
                            <td class="text-muted font-13">{{ $pickList->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $pickList->lines->count() }}</td>
                            <td><span class="tag tone-{{ $pickList->status === 'picked' ? 'success' : ($pickList->status === 'cancelled' ? 'slate' : 'amber') }}">{{ \App\Models\Inventory\PickList::STATUSES[$pickList->status] }}</span></td>
                            <td>
                                @foreach ($pickList->lines as $line)
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="font-13">{{ $line->item?->name }} ({{ $line->requested_qty }})</span>
                                        @if ($line->status === 'picked')
                                            <span class="tag tone-success font-11">Picked {{ $line->picked_qty }}</span>
                                        @else
                                            <form action="{{ route('admin.inventory.pick-lists.lines.pick', $line) }}" method="POST" class="d-flex gap-1">
                                                @csrf
                                                <input type="number" step="0.01" min="0" max="{{ $line->requested_qty }}" name="picked_qty" class="form-control form-control-sm" style="width:70px" value="{{ $line->requested_qty }}" required>
                                                <button type="submit" class="btn btn-light btn-sm">Pick</button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-clipboard-list font-24 d-block mb-2"></i>No pick lists yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pickLists->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $pickLists->links() }}</div></div>
        @endif
    </div>
@endsection
