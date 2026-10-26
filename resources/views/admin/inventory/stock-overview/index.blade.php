@extends('layouts.admin')

@section('title', 'Stock Overview')

@section('content')
    <x-page-title title="Stock Overview" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Items" :value="$totalItems" icon="ti-package" tone="indigo" />
        <x-kpi-card label="Total Stock Value" value="{{ number_format($totalValue, 0) }}" icon="ti-cash" tone="success" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add an item</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.stock-overview.items.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2"><label class="form-label">SKU</label><input type="text" name="sku" class="form-control form-control-sm"></div>
                <div class="col-md-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="form-label">Category</label><input type="text" name="category" class="form-control form-control-sm"></div>
                <div class="col-md-1">
                    <label class="form-label">Unit</label>
                    <select name="unit" class="form-select form-select-sm">
                        @foreach (\App\Models\Inventory\Item::UNITS as $key => $label)
                            <option value="{{ $key }}">{{ $key }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1"><label class="form-label">Cost</label><input type="number" step="0.01" name="cost_price" class="form-control form-control-sm"></div>
                <div class="col-md-1"><label class="form-label">Price</label><input type="number" step="0.01" name="sale_price" class="form-control form-control-sm"></div>
                <div class="col-md-1"><label class="form-label">Reorder</label><input type="number" name="reorder_level" class="form-control form-control-sm" value="0"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-adjustments"></i> Adjust stock</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.stock-overview.adjust') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Item</label>
                    <select name="item_id" class="form-select form-select-sm" required>
                        <option value="">Select item</option>
                        @foreach ($allItems as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select form-select-sm" required>
                        <option value="">Select warehouse</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Quantity on hand</label><input type="number" step="0.01" min="0" name="quantity" class="form-control form-control-sm" required></div>
                <div class="col-md-1"><button type="submit" class="btn btn-light btn-sm w-100">Set</button></div>
            </form>
            @if ($warehouses->isEmpty())
                <p class="font-12 text-muted mt-2 mb-0">No warehouses yet - <a href="{{ route('admin.inventory.warehouse-dashboard.index') }}">add one</a> before setting stock.</p>
            @endif
        </div>
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.inventory.stock-overview.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-package"></i> All Items <span class="list-tab-count">{{ $items->total() }}</span></div>
                <div class="list-toolbar-actions">
                    @if ($categories->isNotEmpty())
                        <select name="category" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                            <option value="">All Categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    @endif
                    <select name="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">All Warehouses</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) $warehouseId === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name or SKU" aria-label="Search items">
                    </div>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Category</th><th>Unit</th><th class="text-end">Qty on Hand</th><th class="text-end">Cost</th><th class="text-end">Stock Value</th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item->name }} <span class="text-muted font-12">{{ $item->sku }}</span></td>
                            <td class="text-muted font-13">{{ $item->category ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $item->unit }}</td>
                            <td class="text-end">
                                <span class="tag tone-{{ $item->reorder_level > 0 && $item->filteredQuantity <= $item->reorder_level ? 'rose' : 'indigo' }}">{{ number_format($item->filteredQuantity, 2) }}</span>
                            </td>
                            <td class="text-end">{{ number_format($item->cost_price, 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($item->stockValue, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-package font-24 d-block mb-2"></i>No items yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($items->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $items->links() }}</div></div>
        @endif
    </div>
@endsection
