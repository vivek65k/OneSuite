@extends('layouts.admin')

@section('title', 'Warehouse Transfers')

@section('content')
    <x-page-title title="Warehouse Transfers" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> New transfer request</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.warehouse-transfers.store') }}" id="transferForm">
                @csrf
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label">Source Warehouse</label>
                        <select name="source_warehouse_id" class="form-select form-select-sm" required>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Destination Warehouse</label>
                        <select name="dest_warehouse_id" class="form-select form-select-sm" required>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control form-control-sm"></div>
                </div>

                <label class="form-label d-flex justify-content-between align-items-center">
                    <span>Items to transfer</span>
                    <button type="button" class="btn btn-light btn-sm" id="addLineBtn"><i class="ti ti-plus font-14 me-1"></i> Add Line</button>
                </label>
                <div id="linesBox"></div>

                <button type="submit" class="btn btn-de-primary text-light px-4 mt-2"><i class="ti ti-arrows-right-left font-14 me-1"></i> Request Transfer</button>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-arrows-right-left"></i> All Transfers <span class="list-tab-count">{{ $transfers->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>No.</th><th>Route</th><th>Items</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($transfers as $transfer)
                        <tr>
                            <td class="font-monospace font-13">{{ $transfer->transfer_number }}</td>
                            <td class="fw-semibold">{{ $transfer->sourceWarehouse?->name }} <i class="ti ti-arrow-right font-12 text-muted"></i> {{ $transfer->destWarehouse?->name }}</td>
                            <td class="text-muted font-13">
                                @foreach ($transfer->lines as $line)
                                    <div>{{ $line->item?->name }} &mdash; req {{ rtrim(rtrim($line->requested_qty,'0'),'.') }}
                                        @if ($line->dispatched_qty !== null) / disp {{ rtrim(rtrim($line->dispatched_qty,'0'),'.') }} @endif
                                        @if ($line->received_qty !== null) / recv {{ rtrim(rtrim($line->received_qty,'0'),'.') }} @endif
                                        @if ($line->discrepancy_notes)<span class="text-danger"> ({{ $line->discrepancy_notes }})</span>@endif
                                    </div>
                                @endforeach
                            </td>
                            <td><span class="tag tone-{{ $transfer->status === 'received' ? 'success' : ($transfer->status === 'cancelled' ? 'slate' : ($transfer->status === 'in_transit' ? 'amber' : 'sky')) }}">{{ \App\Models\Inventory\WarehouseTransfer::STATUSES[$transfer->status] ?? $transfer->status }}</span></td>
                            <td class="text-end">
                                @if ($transfer->status === 'requested')
                                    <form action="{{ route('admin.inventory.warehouse-transfers.approve', $transfer) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-light btn-sm">Approve</button></form>
                                @elseif ($transfer->status === 'approved')
                                    <form action="{{ route('admin.inventory.warehouse-transfers.pick', $transfer) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-light btn-sm">Mark Picked</button></form>
                                @elseif ($transfer->status === 'picked')
                                    <form action="{{ route('admin.inventory.warehouse-transfers.dispatch', $transfer) }}" method="POST" class="d-inline">
                                        @csrf
                                        @foreach ($transfer->lines as $line)
                                            <input type="hidden" name="dispatched_qty[{{ $line->id }}]" value="{{ $line->requested_qty }}">
                                        @endforeach
                                        <button type="submit" class="btn btn-de-primary btn-sm text-light">Dispatch</button>
                                    </form>
                                @elseif ($transfer->status === 'in_transit')
                                    <form action="{{ route('admin.inventory.warehouse-transfers.receive', $transfer) }}" method="POST" class="d-inline">
                                        @csrf
                                        @foreach ($transfer->lines as $line)
                                            <input type="hidden" name="received_qty[{{ $line->id }}]" value="{{ $line->dispatched_qty }}">
                                        @endforeach
                                        <button type="submit" class="btn btn-de-primary btn-sm text-light">Receive</button>
                                    </form>
                                @endif
                                @if (! in_array($transfer->status, ['in_transit', 'received', 'cancelled']))
                                    <form action="{{ route('admin.inventory.warehouse-transfers.cancel', $transfer) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this transfer?');">
                                        @csrf
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Cancel"><i class="ti ti-x font-16"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-arrows-right-left font-24 d-block mb-2"></i>No transfers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transfers->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $transfers->links() }}</div></div>
        @endif
    </div>

    <script>
        (function () {
            const items = @json($items);
            const box = document.getElementById('linesBox');
            const addBtn = document.getElementById('addLineBtn');

            function rowHtml() {
                const options = items.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
                return `<div class="row g-2 align-items-end mb-2 line-row">
                    <div class="col-md-7"><select name="item_id[]" class="form-select form-select-sm">${options}</select></div>
                    <div class="col-md-3"><input type="number" step="0.01" min="0.01" name="quantity[]" class="form-control form-control-sm" placeholder="Qty" required></div>
                    <div class="col-md-2"><button type="button" class="btn-icon btn-icon-danger remove-line"><i class="ti ti-trash font-16"></i></button></div>
                </div>`;
            }

            addBtn.addEventListener('click', () => box.insertAdjacentHTML('beforeend', rowHtml()));
            box.addEventListener('click', (e) => { const btn = e.target.closest('.remove-line'); if (btn) btn.closest('.line-row').remove(); });
            box.insertAdjacentHTML('beforeend', rowHtml());
        })();
    </script>
@endsection
