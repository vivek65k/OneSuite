@extends('layouts.admin')

@section('title', 'Products & Services')

@section('content')
    <x-page-title title="Products & Services" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Products" :value="$total" icon="ti-box" tone="indigo" />
        <x-kpi-card label="Active" :value="$activeCount" icon="ti-circle-check" tone="success" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.products.index') }}">
            @if ($sort !== 'name')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'asc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-box"></i> Catalog</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search name or SKU" aria-label="Search products">
                    </div>
                    <a href="{{ route('admin.crm.products.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Product</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Name" />
                        <x-th-sort column="sku" :sort="$sort" :direction="$direction" label="SKU" />
                        <x-th-sort column="unit_price" :sort="$sort" :direction="$direction" label="Unit Price" />
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.products.edit', $product) }}">{{ $product->name }}</a></td>
                            <td>{{ $product->sku ?? '—' }}</td>
                            <td>{{ number_format($product->unit_price, 2) }}</td>
                            <td><span class="tag tone-{{ $product->is_active ? 'success' : 'slate' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.products.edit', $product) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-box font-24 d-block mb-2"></i>
                                No products yet.
                                <div><a href="{{ route('admin.crm.products.create') }}">Add your first one</a>.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $products->links() }}</div></div>
        @endif
    </div>
@endsection
