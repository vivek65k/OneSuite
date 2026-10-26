@extends('layouts.admin')

@section('title', 'Opportunity Products')

@section('content')
    <x-page-title title="Opportunity Products" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Product Lines" :value="$totalLines" icon="ti-box" tone="indigo" />
        <x-kpi-card label="Total Line Value" :value="number_format($totalLineValue)" icon="ti-currency-rupee" tone="success" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a line to an opportunity</div>
        </div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.opportunity-products.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Opportunity</label>
                    <select name="opportunity_id" class="form-select form-select-sm" required>
                        <option value="">Select opportunity</option>
                        @foreach ($opportunities as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Product</label><input type="text" name="product_name" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="form-label">Qty</label><input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-sm" value="1" required></div>
                <div class="col-md-2"><label class="form-label">Unit price</label><input type="number" step="0.01" min="0" name="unit_price" class="form-control form-control-sm" required></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.opportunity-products.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-box"></i> All product lines</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search product name" aria-label="Search products">
                    </div>
                    <select name="opportunity_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Opportunity">
                        <option value="">Any opportunity</option>
                        @foreach ($opportunities as $id => $name)
                            <option value="{{ $id }}" @selected((string) $opportunityId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Product</th>
                        <th>Opportunity</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="fw-semibold">{{ $product->product_name }}</td>
                            <td>
                                @if ($product->opportunity)
                                    <a href="{{ route('admin.crm.opportunities.edit', $product->opportunity) }}">{{ $product->opportunity->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">{{ rtrim(rtrim($product->quantity, '0'), '.') }}</td>
                            <td class="text-end">{{ number_format($product->unit_price, 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($product->total, 2) }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.opportunity-products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this product line?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-box font-24 d-block mb-2"></i>No product lines yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $products->links() }}</div></div>
        @endif
    </div>
@endsection
