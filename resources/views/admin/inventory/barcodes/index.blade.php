@extends('layouts.admin')

@section('title', 'Barcode & QR Codes')

@section('content')
    <x-page-title title="Barcode &amp; QR Codes" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-scan"></i> Scan to find item</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.barcodes.scan') }}" class="d-flex gap-2">
                @csrf
                <input type="text" name="barcode" class="form-control form-control-sm" placeholder="Scan or type a barcode..." autofocus>
                <button type="submit" class="btn btn-de-primary btn-sm text-light px-3 flex-shrink-0"><i class="ti ti-scan font-14 me-1"></i> Look Up</button>
            </form>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Assign a barcode</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.barcodes.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Product</label>
                    <select name="item_id" class="form-select form-select-sm" required>
                        <option value="">Select product</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} @if($item->sku)({{ $item->sku }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Barcode / QR Value</label><input type="text" name="barcode" class="form-control form-control-sm" required></div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        @foreach (\App\Models\Inventory\Barcode::TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_primary" value="1">
                    <label class="form-check-label font-13">Primary</label>
                </div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.inventory.barcodes.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-barcode"></i> All Barcodes <span class="list-tab-count">{{ $barcodes->total() }}</span></div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search barcode or product" aria-label="Search">
                    </div>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Barcode</th><th>Type</th><th>Product</th><th>Primary</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($barcodes as $barcode)
                        <tr>
                            <td class="font-monospace">{{ $barcode->barcode }}</td>
                            <td><span class="tag tone-indigo">{{ \App\Models\Inventory\Barcode::TYPES[$barcode->type] ?? $barcode->type }}</span></td>
                            <td class="fw-semibold">{{ $barcode->item?->name ?? '—' }}</td>
                            <td>{{ $barcode->is_primary ? 'Yes' : '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.inventory.barcodes.label', $barcode) }}" target="_blank" class="btn-icon" title="Print label"><i class="ti ti-printer font-16"></i></a>
                                <form action="{{ route('admin.inventory.barcodes.destroy', $barcode) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this barcode?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-barcode font-24 d-block mb-2"></i>No barcodes assigned yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($barcodes->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $barcodes->links() }}</div></div>
        @endif
    </div>
@endsection
