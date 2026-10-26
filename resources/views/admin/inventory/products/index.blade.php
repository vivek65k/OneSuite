@extends('layouts.admin')

@section('title', 'Products / SKUs')

@section('content')
    <x-page-title title="Products / SKUs" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Products" :value="$total" icon="ti-package" tone="indigo" />
        <x-kpi-card label="Stock Tracked" :value="$tracked" icon="ti-stack-2" tone="success" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.inventory.products.index') }}">
            @if ($sort !== 'name')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'asc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-package"></i> All Products <span class="list-tab-count">{{ $products->total() }}</span></div>
                <div class="list-toolbar-actions">
                    <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) $categoryId === (string) $cat->id)>{{ $cat->full_path }}</option>
                        @endforeach
                    </select>
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name or SKU" aria-label="Search">
                    </div>
                    <a href="{{ route('admin.inventory.products.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Product</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="sku" :sort="$sort" :direction="$direction" label="SKU" />
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Name" />
                        <th>Category</th>
                        <th>Brand</th>
                        <x-th-sort column="cost_price" :sort="$sort" :direction="$direction" label="Cost" align="end" />
                        <x-th-sort column="sale_price" :sort="$sort" :direction="$direction" label="Price" align="end" />
                        <th>Tracking</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="text-muted font-13">{{ $product->sku ?? '—' }}</td>
                            <td class="fw-semibold"><a href="{{ route('admin.inventory.products.edit', $product) }}">{{ $product->name }}</a></td>
                            <td class="text-muted font-13">{{ $product->category?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $product->brand?->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($product->cost_price, 2) }}</td>
                            <td class="text-end">{{ number_format($product->sale_price, 2) }}</td>
                            <td>
                                @if ($product->track_batch)<span class="tag tone-amber font-11">Batch</span>@endif
                                @if ($product->track_serial)<span class="tag tone-violet font-11">Serial</span>@endif
                                @if ($product->track_expiry)<span class="tag tone-rose font-11">Expiry</span>@endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.inventory.products.edit', $product) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.inventory.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-package font-24 d-block mb-2"></i>No products yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $products->links() }}</div></div>
        @endif
    </div>
@endsection
