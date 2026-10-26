@extends('layouts.admin')

@php
    $isEditing = $product->exists;
@endphp

@section('title', $isEditing ? 'Edit Product' : 'New Product')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Product' : 'New Product'" :breadcrumbs="['Products' => route('admin.inventory.products.index')]" />

    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <form method="POST" action="{{ $isEditing ? route('admin.inventory.products.update', $product) : route('admin.inventory.products.store') }}">
        @csrf
        @if ($isEditing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card form-section tone-indigo">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-package"></i></span>
                        <div>
                            <h5 class="form-section-title">Product</h5>
                            <p class="form-section-sub">SKU code, name, description, category and pricing.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">SKU Code</label>
                                <input type="text" class="form-control" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="Auto if blank">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" value="{{ old('name', $product->name) }}" required autofocus>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="2" class="form-control">{{ old('description', $product->description) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select">
                                    <option value="">— None —</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" @selected((string) old('category_id', $product->category_id) === (string) $cat->id)>{{ $cat->full_path }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Brand</label>
                                <select name="brand_id" class="form-select">
                                    <option value="">— None —</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Purchase Unit</label>
                                <select name="purchase_uom_id" class="form-select">
                                    <option value="">— None —</option>
                                    @foreach ($uoms as $uom)
                                        <option value="{{ $uom->id }}" @selected((string) old('purchase_uom_id', $product->purchase_uom_id) === (string) $uom->id)>{{ $uom->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock Unit</label>
                                <select name="stock_uom_id" class="form-select">
                                    <option value="">— None —</option>
                                    @foreach ($uoms as $uom)
                                        <option value="{{ $uom->id }}" @selected((string) old('stock_uom_id', $product->stock_uom_id) === (string) $uom->id)>{{ $uom->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sales Unit</label>
                                <select name="sales_uom_id" class="form-select">
                                    <option value="">— None —</option>
                                    @foreach ($uoms as $uom)
                                        <option value="{{ $uom->id }}" @selected((string) old('sales_uom_id', $product->sales_uom_id) === (string) $uom->id)>{{ $uom->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cost Price</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Selling Price</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tax Category</label>
                                <select name="tax_category" class="form-select">
                                    @foreach (\App\Models\Inventory\Item::TAX_CATEGORIES as $key => $label)
                                        <option value="{{ $key }}" @selected(old('tax_category', $product->tax_category) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card form-section tone-violet">
                    <div class="form-section-head form-section-head-compact">
                        <span class="form-section-icon form-section-icon-sm"><i class="ti ti-fingerprint"></i></span>
                        <h5 class="form-section-title">Tracking &amp; Policy</h5>
                    </div>
                    <div class="form-section-body">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="track_stock" id="track_stock" value="1" @checked(old('track_stock', $product->exists ? $product->track_stock : true))>
                            <label class="form-check-label" for="track_stock">Track stock</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="track_batch" id="track_batch" value="1" @checked(old('track_batch', $product->track_batch))>
                            <label class="form-check-label" for="track_batch">Track by batch / lot</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="track_serial" id="track_serial" value="1" @checked(old('track_serial', $product->track_serial))>
                            <label class="form-check-label" for="track_serial">Track by serial number</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" name="track_expiry" id="track_expiry" value="1" @checked(old('track_expiry', $product->track_expiry))>
                            <label class="form-check-label" for="track_expiry">Track expiry date</label>
                        </div>

                        <label class="form-label">Default Warehouse</label>
                        <select name="default_warehouse_id" class="form-select mb-3">
                            <option value="">— None —</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected((string) old('default_warehouse_id', $product->default_warehouse_id) === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>

                        <label class="form-label">Reorder Level</label>
                        <input type="number" min="0" class="form-control" name="reorder_level" value="{{ old('reorder_level', $product->reorder_level) }}">

                        @if ($isEditing)
                            <hr class="my-3">
                            <a href="{{ route('admin.inventory.stock-overview.index') }}" class="btn btn-light btn-sm w-100 mb-2">View Stock</a>
                            <a href="{{ route('admin.inventory.barcodes.index') }}" class="btn btn-light btn-sm w-100 mb-2">Manage Barcodes</a>
                            <a href="{{ route('admin.inventory.variants.index') }}" class="btn btn-light btn-sm w-100">Manage Variants</a>
                        @endif
                    </div>
                </div>

                @if ($customFields->isNotEmpty())
                    <div class="card form-section tone-teal mt-3">
                        <div class="form-section-head">
                            <span class="form-section-icon"><i class="ti ti-forms"></i></span>
                            <div>
                                <h5 class="form-section-title">Custom Fields</h5>
                                <p class="form-section-sub">Defined under Inventory → Numbering & Documents.</p>
                            </div>
                        </div>
                        <div class="form-section-body">
                            <div class="row g-3">
                                @foreach ($customFields as $field)
                                    <div class="col-md-6">
                                        <label class="form-label">{{ $field->label }} @if($field->is_required)<span class="text-danger">*</span>@endif</label>
                                        @if ($field->field_type === 'select')
                                            <select name="custom_field[{{ $field->field_key }}]" class="form-select" @required($field->is_required)>
                                                <option value="">— Select —</option>
                                                @foreach ($field->option_list as $option)
                                                    <option value="{{ $option }}" @selected(old('custom_field.'.$field->field_key, $customValues[$field->id] ?? '') === $option)>{{ $option }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="{{ $field->field_type === 'number' ? 'number' : ($field->field_type === 'date' ? 'date' : 'text') }}" class="form-control" name="custom_field[{{ $field->field_key }}]" value="{{ old('custom_field.'.$field->field_key, $customValues[$field->id] ?? '') }}" @required($field->is_required)>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="record-footer">
            <a href="{{ route('admin.inventory.products.index') }}" class="btn btn-light px-4">Cancel</a>
            <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create product' }}</button>
        </div>
    </form>
@endsection
