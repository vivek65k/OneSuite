@extends('layouts.admin')

@section('title', 'Categories & Brands')

@section('content')
    <x-page-title title="Categories &amp; Brands" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card list-view mb-3">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a category</div></div>
                <div class="list-filter-panel">
                    <form method="POST" action="{{ route('admin.inventory.categories.store') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                        <div class="col-md-3">
                            <label class="form-label">Parent</label>
                            <select name="parent_id" class="form-select form-select-sm">
                                <option value="">Top level</option>
                                @foreach ($parents as $parent)
                                    <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Default Storage</label><input type="text" name="default_storage_type" class="form-control form-control-sm" placeholder="e.g. Ambient, Cold"></div>
                        <div class="col-md-2"><label class="form-label">Handling Rules</label><input type="text" name="handling_rules" class="form-control form-control-sm"></div>
                        <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
                    </form>
                </div>
            </div>

            <div class="card list-view">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-hierarchy"></i> Categories <span class="list-tab-count">{{ $categories->count() }}</span></div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Name</th><th>Parent</th><th class="text-end">Products</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr>
                                    <td class="fw-semibold">{{ $category->name }}</td>
                                    <td class="text-muted font-13">{{ $category->parent?->name ?? '—' }}</td>
                                    <td class="text-end">{{ $category->items_count }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.inventory.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this category?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-5"><i class="ti ti-hierarchy font-24 d-block mb-2"></i>No categories yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card list-view mb-3">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a brand</div></div>
                <div class="list-filter-panel">
                    <form method="POST" action="{{ route('admin.inventory.categories.brands.store') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-6"><label class="form-label">Brand Name</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                        <div class="col-md-5"><label class="form-label">Manufacturer</label><input type="text" name="manufacturer" class="form-control form-control-sm"></div>
                        <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
                    </form>
                </div>
            </div>

            <div class="card list-view">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-tag"></i> Brands <span class="list-tab-count">{{ $brands->count() }}</span></div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Brand</th><th>Manufacturer</th><th class="text-end">Products</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                        <tbody>
                            @forelse ($brands as $brand)
                                <tr>
                                    <td class="fw-semibold">{{ $brand->name }}</td>
                                    <td class="text-muted font-13">{{ $brand->manufacturer ?? '—' }}</td>
                                    <td class="text-end">{{ $brand->items_count }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.inventory.categories.brands.destroy', $brand) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this brand?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-5"><i class="ti ti-tag font-24 d-block mb-2"></i>No brands yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
