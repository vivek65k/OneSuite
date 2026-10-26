@extends('layouts.admin')

@php
    $isEditing = $product->exists;
@endphp

@section('title', $isEditing ? 'Edit Product' : 'Add Product')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Product' : 'Add Product'" :breadcrumbs="['Products & Services' => route('admin.crm.products.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 640px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-box"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? 'Edit product' : 'New product' }}</h5>
                <p class="form-section-sub">Catalog item used on quotes, orders, and price books.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.products.update', $product) : route('admin.crm.products.store') }}">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="sku">SKU</label>
                        <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
                        @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="unit_price">Unit price <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control @error('unit_price') is-invalid @enderror" id="unit_price" name="unit_price" value="{{ old('unit_price', $product->unit_price) }}" required>
                        @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $isEditing ? $product->is_active : true))>
                            <label class="form-check-label" for="is_active">Active (available to add to quotes/orders)</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create product' }}</button>
                    <a href="{{ route('admin.crm.products.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
