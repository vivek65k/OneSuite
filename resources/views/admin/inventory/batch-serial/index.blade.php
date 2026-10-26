@extends('layouts.admin')

@section('title', 'Batch & Serial Profiles')

@section('content')
    <x-page-title title="Batch &amp; Serial Profiles" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Expiring Soon (30 days)" :value="$expiringSoonCount" icon="ti-calendar-time" tone="amber" />
        <x-kpi-card label="Expired Batches" :value="$expiredCount" icon="ti-alert-triangle" tone="rose" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="segmented" role="group" aria-label="Tab">
                <a href="{{ route('admin.inventory.batch-serial.index', ['tab' => 'batches']) }}" class="segmented-item {{ $tab === 'batches' ? 'active' : '' }}">Batches / Lots</a>
                <a href="{{ route('admin.inventory.batch-serial.index', ['tab' => 'serials']) }}" class="segmented-item {{ $tab === 'serials' ? 'active' : '' }}">Serial Numbers</a>
            </div>
        </div>
    </div>

    @if ($tab === 'batches')
        <div class="card list-view mb-3">
            <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Record a batch</div></div>
            <div class="list-filter-panel">
                <form method="POST" action="{{ route('admin.inventory.batch-serial.batches.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-2">
                        <label class="form-label">Product</label>
                        <select name="item_id" class="form-select form-select-sm" required>
                            <option value="">Select</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">None</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Batch No.</label><input type="text" name="batch_number" class="form-control form-control-sm" required></div>
                    <div class="col-md-2"><label class="form-label">Mfg Date</label><input type="date" name="manufacture_date" class="form-control form-control-sm"></div>
                    <div class="col-md-2"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" class="form-control form-control-sm"></div>
                    <div class="col-md-1"><label class="form-label">Qty</label><input type="number" step="0.01" min="0" name="quantity" class="form-control form-control-sm" required></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
                    <div class="col-md-3"><label class="form-label">Supplier Lot</label><input type="text" name="supplier_lot" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><label class="form-label">Warranty (months)</label><input type="number" min="0" name="warranty_months" class="form-control form-control-sm"></div>
                </form>
            </div>
        </div>

        <div class="card list-view">
            <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-package"></i> Batches <span class="list-tab-count">{{ $batches->total() }}</span></div></div>
            <div class="table-responsive">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light"><tr><th>Product</th><th>Batch No.</th><th>Warehouse</th><th>Expiry</th><th class="text-end">Qty</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                        @forelse ($batches as $batch)
                            <tr>
                                <td class="fw-semibold">{{ $batch->item?->name }}</td>
                                <td class="font-monospace">{{ $batch->batch_number }}</td>
                                <td class="text-muted font-13">{{ $batch->warehouse?->name ?? '—' }}</td>
                                <td>
                                    @if ($batch->expiry_date)
                                        <span class="tag tone-{{ $batch->is_expired ? 'rose' : ($batch->is_expiring_soon ? 'amber' : 'slate') }}">{{ $batch->expiry_date->format('M j, Y') }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($batch->quantity, 2) }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.inventory.batch-serial.batches.destroy', $batch) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this batch?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-package font-24 d-block mb-2"></i>No batches recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($batches->hasPages())
                <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $batches->withQueryString()->links() }}</div></div>
            @endif
        </div>
    @else
        <div class="card list-view mb-3">
            <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Record a serial number</div></div>
            <div class="list-filter-panel">
                <form method="POST" action="{{ route('admin.inventory.batch-serial.serials.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Product</label>
                        <select name="item_id" class="form-select form-select-sm" required>
                            <option value="">Select</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">None</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">Serial Number</label><input type="text" name="serial_number" class="form-control form-control-sm" required></div>
                    <div class="col-md-2"><label class="form-label">Warranty Expiry</label><input type="date" name="warranty_expiry" class="form-control form-control-sm"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
                </form>
            </div>
        </div>

        <div class="card list-view">
            <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-fingerprint"></i> Serial Numbers <span class="list-tab-count">{{ $serials->total() }}</span></div></div>
            <div class="table-responsive">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light"><tr><th>Product</th><th>Serial No.</th><th>Warehouse</th><th>Status</th><th>Warranty</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                        @forelse ($serials as $serial)
                            <tr>
                                <td class="fw-semibold">{{ $serial->item?->name }}</td>
                                <td class="font-monospace">{{ $serial->serial_number }}</td>
                                <td class="text-muted font-13">{{ $serial->warehouse?->name ?? '—' }}</td>
                                <td>
                                    <form action="{{ route('admin.inventory.batch-serial.serials.status', $serial) }}" method="POST" class="d-inline">
                                        @csrf @method('PATCH')
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            @foreach (\App\Models\Inventory\Serial::STATUSES as $key => $label)
                                                <option value="{{ $key }}" @selected($serial->status === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="text-muted font-13">{{ $serial->warranty_expiry?->format('M j, Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.inventory.batch-serial.serials.destroy', $serial) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this serial?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-fingerprint font-24 d-block mb-2"></i>No serial numbers recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($serials->hasPages())
                <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $serials->withQueryString()->links() }}</div></div>
            @endif
        </div>
    @endif
@endsection
