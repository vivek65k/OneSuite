@extends('layouts.admin')

@section('title', 'Stock Ledger')

@section('content')
    <x-page-title title="Stock Ledger" />

    <div class="card list-view mb-3">
        <div class="list-filter-panel">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Item</label>
                    <select name="item_id" class="form-select form-select-sm">
                        <option value="">All items</option>
                        @foreach ($items as $item)<option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">All warehouses</option>
                        @foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Transaction Type</label>
                    <select name="transaction_type" class="form-select form-select-sm">
                        <option value="">All types</option>
                        @foreach ($types as $key => $label)<option value="{{ $key }}" @selected(request('transaction_type') == $key)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Filter</button></div>
                <div class="col-md-1"><a href="{{ route('admin.inventory.stock-ledger.index') }}" class="btn btn-light btn-sm w-100">Reset</a></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-history"></i> Transaction History <span class="list-tab-count">{{ $entries->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Date/Time</th><th>Item</th><th>Warehouse</th><th>Type</th><th>Before</th><th>Change</th><th>After</th>
                        <th>Location</th><th>Batch/Serial</th><th>Source</th><th>User</th><th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="text-muted font-13">{{ $entry->created_at->format('M j, Y H:i') }}</td>
                            <td class="font-13">{{ $entry->item?->name }}</td>
                            <td class="text-muted font-13">{{ $entry->warehouse?->name }}</td>
                            <td><span class="tag tone-sky font-11">{{ $types[$entry->transaction_type] ?? $entry->transaction_type }}</span></td>
                            <td class="text-muted font-13">{{ $entry->quantity_before }}</td>
                            <td class="font-13 fw-semibold {{ $entry->quantity_change >= 0 ? 'text-success' : 'text-danger' }}">{{ $entry->quantity_change >= 0 ? '+' : '' }}{{ $entry->quantity_change }}</td>
                            <td class="text-muted font-13">{{ $entry->quantity_after }}</td>
                            <td class="text-muted font-13">{{ $entry->location?->full_code ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $entry->batch_number ?? $entry->serial_number ?? '—' }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $entry->source_type }} · {{ $entry->source_reference }}</td>
                            <td class="text-muted font-13">{{ $entry->employee?->name ?? 'System' }}</td>
                            <td class="text-muted font-13">{{ $entry->reason ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center text-muted py-5"><i class="ti ti-history font-24 d-block mb-2"></i>No stock movements recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($entries->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $entries->links() }}</div></div>
        @endif
    </div>
@endsection
