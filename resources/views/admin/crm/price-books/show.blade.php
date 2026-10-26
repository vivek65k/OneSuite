@extends('layouts.admin')

@section('title', $priceBook->name)

@section('content')
    <x-page-title :title="$priceBook->name" :breadcrumbs="['Price Books' => route('admin.crm.price-books.index')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card form-section tone-indigo mb-3">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-book"></i></span>
            <div>
                <h5 class="form-section-title">Details</h5>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ route('admin.crm.price-books.update', $priceBook) }}" class="row g-3 align-items-end">
                @csrf @method('PUT')
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ $priceBook->name }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" class="form-control" value="{{ $priceBook->currency }}" required>
                </div>
                <div class="col-md-2">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_default" value="1" @checked($priceBook->is_default)>
                        <label class="form-check-label">Default</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked($priceBook->is_active)>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-currency-rupee"></i> Product prices</div>
        </div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.price-books.set-price', $priceBook) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-select form-select-sm" required>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} (catalog: {{ number_format($product->unit_price, 2) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Price in this book</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Set</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Product</th><th class="text-end">Catalog Price</th><th class="text-end">Price in This Book</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        @php $override = $entries->get($product->id); @endphp
                        <tr>
                            <td class="fw-semibold">{{ $product->name }}</td>
                            <td class="text-end text-muted">{{ number_format($product->unit_price, 2) }}</td>
                            <td class="text-end">
                                @if ($override)
                                    <span class="salary-figure">{{ number_format($override->pivot->price, 2) }}</span>
                                @else
                                    <span class="text-muted">uses catalog price</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($override)
                                    <form action="{{ route('admin.crm.price-books.remove-price', [$priceBook, $product]) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Remove override"><i class="ti ti-trash font-16"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">No active products in the catalog yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
