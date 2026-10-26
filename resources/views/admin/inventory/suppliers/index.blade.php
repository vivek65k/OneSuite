@extends('layouts.admin')

@section('title', 'Suppliers & Product Mapping')

@section('content')
    <x-page-title title="Suppliers &amp; Product Mapping" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a supplier</div></div>
                <div class="list-filter-panel">
                    <form method="POST" action="{{ route('admin.inventory.suppliers.store') }}" class="row g-2">
                        @csrf
                        <div class="col-md-6"><label class="form-label">Name</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                        <div class="col-md-6"><label class="form-label">Contact Person</label><input type="text" name="contact_person" class="form-control form-control-sm"></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control form-control-sm"></div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control form-control-sm"></div>
                        <div class="col-md-12"><button type="submit" class="btn btn-de-primary btn-sm text-light px-3">Add Supplier</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-truck"></i> Suppliers <span class="list-tab-count">{{ $suppliers->count() }}</span></div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Name</th><th>Contact</th><th class="text-end">Products</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                        <tbody>
                            @forelse ($suppliers as $supplier)
                                <tr>
                                    <td class="fw-semibold">{{ $supplier->name }}</td>
                                    <td class="text-muted font-13">{{ $supplier->contact_person ?? $supplier->email ?? '—' }}</td>
                                    <td class="text-end">{{ $supplier->products_count }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.inventory.suppliers.destroy', $supplier) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this supplier?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-5">No suppliers yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Map a product to a supplier</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.suppliers.mappings.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select form-select-sm" required>
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Product</label>
                    <select name="item_id" class="form-select form-select-sm" required>
                        <option value="">Select product</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Supplier SKU</label><input type="text" name="supplier_sku" class="form-control form-control-sm"></div>
                <div class="col-md-1"><label class="form-label">Cost</label><input type="number" step="0.01" min="0" name="cost_price" class="form-control form-control-sm"></div>
                <div class="col-md-1"><label class="form-label">Lead (days)</label><input type="number" min="0" name="lead_time_days" class="form-control form-control-sm"></div>
                <div class="col-md-1 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_preferred" value="1">
                    <label class="form-check-label font-12">Preferred</label>
                </div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Map</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-link"></i> Supplier &rarr; Product Mappings <span class="list-tab-count">{{ $mappings->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Supplier</th><th>Product</th><th>Supplier SKU</th><th class="text-end">Cost</th><th class="text-end">Lead Time</th><th>Preferred</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($mappings as $mapping)
                        <tr>
                            <td class="fw-semibold">{{ $mapping->supplier?->name }}</td>
                            <td>{{ $mapping->item?->name }}</td>
                            <td class="text-muted font-13">{{ $mapping->supplier_sku ?? '—' }}</td>
                            <td class="text-end">{{ number_format($mapping->cost_price, 2) }}</td>
                            <td class="text-end">{{ $mapping->lead_time_days }}d</td>
                            <td>{{ $mapping->is_preferred ? '⭐' : '—' }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.suppliers.mappings.destroy', $mapping) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this mapping?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No product mappings yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
