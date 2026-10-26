@extends('layouts.admin')

@section('title', 'Product Variants')

@section('content')
    <x-page-title title="Product Variants" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add attribute</div></div>
                <div class="list-filter-panel">
                    <form method="POST" action="{{ route('admin.inventory.variants.attributes.store') }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Size, Color, Model" required>
                        <button type="submit" class="btn btn-de-primary btn-sm text-light px-3 flex-shrink-0">Add</button>
                    </form>
                </div>
                <div class="p-3">
                    @forelse ($attributes as $attribute)
                        <div class="mb-3">
                            <div class="fw-semibold font-13 mb-1">{{ $attribute->name }}</div>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                @foreach ($attribute->values as $value)
                                    <span class="tag tone-indigo">{{ $value->value }}</span>
                                @endforeach
                            </div>
                            <form method="POST" action="{{ route('admin.inventory.variants.attributes.values.store', $attribute) }}" class="d-flex gap-2">
                                @csrf
                                <input type="text" name="value" class="form-control form-control-sm" placeholder="Add a value">
                                <button type="submit" class="btn btn-light btn-sm flex-shrink-0">Add Value</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">No variant attributes yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-git-fork"></i> Generate variant matrix</div></div>
                <div class="p-3">
                    <form method="POST" action="{{ route('admin.inventory.variants.generate') }}">
                        @csrf
                        <label class="form-label">Product</label>
                        <select name="item_id" class="form-select form-select-sm mb-3" required>
                            <option value="">Select product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->variants_count }} variants)</option>
                            @endforeach
                        </select>

                        @foreach ($attributes as $attribute)
                            <label class="form-label font-13">{{ $attribute->name }}</label>
                            <select name="value_ids[{{ $attribute->id }}][]" class="form-select form-select-sm mb-3" multiple size="3">
                                @foreach ($attribute->values as $value)
                                    <option value="{{ $value->id }}">{{ $value->value }}</option>
                                @endforeach
                            </select>
                        @endforeach

                        <button type="submit" class="btn btn-de-primary text-light w-100" {{ $attributes->isEmpty() ? 'disabled' : '' }}><i class="ti ti-git-fork font-14 me-1"></i> Generate Variants</button>
                    </form>
                    <p class="font-12 text-muted mt-2 mb-0">Hold Ctrl/Cmd to pick multiple values per attribute. Every combination becomes its own SKU with its own stock.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-package"></i> Products &amp; Their Variants</div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Product</th><th>Variants</th></tr></thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="fw-semibold">{{ $product->name }}</td>
                            <td>
                                @forelse ($product->variants as $variant)
                                    <span class="tag tone-violet me-1 mb-1 d-inline-flex align-items-center gap-1">
                                        {{ $variant->variant_label }}
                                        <form action="{{ route('admin.inventory.variants.destroy', $variant) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" style="width:16px;height:16px;" title="Remove"><i class="ti ti-x font-12"></i></button>
                                        </form>
                                    </span>
                                @empty
                                    <span class="text-muted font-13">No variants generated.</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-5">No products yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
