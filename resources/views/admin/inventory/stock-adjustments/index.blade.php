@extends('layouts.admin')

@section('title', 'Stock Adjustments')

@section('content')
    <x-page-title title="Stock Adjustments" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Submit an adjustment</div></div>
        <div class="list-filter-panel">
            <p class="text-muted font-13 mb-2"><i class="ti ti-info-circle"></i> Stock is not changed on submit - only once an approver signs off below.</p>
            <form method="POST" action="{{ route('admin.inventory.stock-adjustments.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
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
                <div class="col-md-2"><label class="form-label">Physical Qty Counted</label><input type="number" step="0.01" min="0" name="physical_qty" class="form-control form-control-sm" required></div>
                <div class="col-md-2">
                    <label class="form-label">Reason Code</label>
                    <select name="reason_code" class="form-select form-select-sm" required>
                        @foreach ($reasonCodes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Notes</label><input type="text" name="reason_notes" class="form-control form-control-sm"></div>
                <div class="col-md-12"><button type="submit" class="btn btn-de-primary text-light px-4 mt-1">Submit for Approval</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-adjustments"></i> All Adjustments <span class="list-tab-count">{{ $adjustments->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Adjustment #</th><th>Item</th><th>Warehouse</th><th>System</th><th>Physical</th><th>Adjustment</th><th>Reason</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($adjustments as $adjustment)
                        <tr>
                            <td class="font-monospace font-13">{{ $adjustment->adjustment_number }}</td>
                            <td class="font-13">{{ $adjustment->item?->name }}</td>
                            <td class="text-muted font-13">{{ $adjustment->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $adjustment->system_qty }}</td>
                            <td class="text-muted font-13">{{ $adjustment->physical_qty }}</td>
                            <td class="font-13 fw-semibold {{ $adjustment->adjustment_qty >= 0 ? 'text-success' : 'text-danger' }}">{{ $adjustment->adjustment_qty >= 0 ? '+' : '' }}{{ $adjustment->adjustment_qty }}</td>
                            <td class="text-muted font-13">{{ $reasonCodes[$adjustment->reason_code] ?? $adjustment->reason_code }}</td>
                            <td><span class="tag tone-{{ $adjustment->status === 'approved' ? 'success' : ($adjustment->status === 'rejected' ? 'danger' : 'amber') }}">{{ \App\Models\Inventory\StockAdjustment::STATUSES[$adjustment->status] }}</span></td>
                            <td class="text-end">
                                @if ($adjustment->status === 'pending_approval')
                                    <form action="{{ route('admin.inventory.stock-adjustments.approve', $adjustment) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve this adjustment? Stock will be posted immediately.');">
                                        @csrf
                                        <button type="submit" class="btn btn-de-primary text-light btn-sm">Approve</button>
                                    </form>
                                    <form action="{{ route('admin.inventory.stock-adjustments.reject', $adjustment) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-light btn-sm">Reject</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-5"><i class="ti ti-adjustments font-24 d-block mb-2"></i>No adjustments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($adjustments->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $adjustments->links() }}</div></div>
        @endif
    </div>
@endsection
