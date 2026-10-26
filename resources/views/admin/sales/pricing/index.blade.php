@extends('layouts.admin')

@section('title', 'Products & Pricing')

@section('content')
    <x-page-title title="Products & Pricing" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <p class="text-muted font-13 mb-3"><i class="ti ti-info-circle"></i> Products are the shared Inventory item master. This screen manages Sales price lists and customer-specific overrides on top of it - product masters are edited in Inventory → Products.</p>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Create a price list</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.sales.pricing.lists.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3"><input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Wholesale" required></div>
                <div class="col-md-2"><input type="text" name="currency" class="form-control form-control-sm" value="INR" required></div>
                <div class="col-md-2"><input type="date" name="valid_from" class="form-control form-control-sm" placeholder="Valid from"></div>
                <div class="col-md-2"><input type="date" name="valid_to" class="form-control form-control-sm" placeholder="Valid to"></div>
                <div class="col-md-2 form-check form-switch align-self-center"><input class="form-check-input" type="checkbox" name="is_default" value="1"><label class="form-check-label font-13">Default</label></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Save</button></div>
            </form>
        </div>
    </div>

    @foreach ($priceLists as $priceList)
        <div class="card list-view mb-3">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-tag"></i> {{ $priceList->name }} ({{ $priceList->currency }}) @if($priceList->is_default)<span class="tag tone-success font-11">Default</span>@endif</div>
                <form action="{{ route('admin.sales.pricing.lists.destroy', $priceList) }}" method="POST" onsubmit="return confirm('Delete this price list?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                </form>
            </div>
            <div class="list-filter-panel">
                <form method="POST" action="{{ route('admin.sales.pricing.lists.items.store', $priceList) }}" class="row g-2 align-items-end mb-2">
                    @csrf
                    <div class="col-md-5">
                        <select name="item_id" class="form-select form-select-sm" required>
                            @foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><input type="number" step="0.01" min="0" name="unit_price" class="form-control form-control-sm" placeholder="Unit price" required></div>
                    <div class="col-md-3"><input type="number" step="0.01" min="0" name="min_qty" class="form-control form-control-sm" placeholder="Min qty (optional)"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-light btn-sm w-100">Add</button></div>
                </form>
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light"><tr><th>Item</th><th>Unit Price</th><th>Min Qty</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                        @forelse ($priceList->items as $line)
                            <tr>
                                <td class="font-13">{{ $line->item?->name }}</td>
                                <td class="text-muted font-13">{{ $line->unit_price }}</td>
                                <td class="text-muted font-13">{{ $line->min_qty ?? '—' }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.sales.pricing.lists.items.destroy', $line) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No rates set on this price list yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-cash"></i> Customer-Specific Rates</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.sales.pricing.customer-prices.store') }}" class="row g-2 align-items-end mb-2">
                @csrf
                <div class="col-md-4">
                    <select name="account_id" class="form-select form-select-sm" required>
                        @foreach ($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="item_id" class="form-select form-select-sm" required>
                        @foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3"><input type="number" step="0.01" min="0" name="unit_price" class="form-control form-control-sm" placeholder="Unit price" required></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Save</button></div>
            </form>
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Customer</th><th>Item</th><th>Unit Price</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($customerPrices as $price)
                        <tr>
                            <td class="font-13">{{ $price->account?->name }}</td>
                            <td class="text-muted font-13">{{ $price->item?->name }}</td>
                            <td class="text-muted font-13">{{ $price->unit_price }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.sales.pricing.customer-prices.destroy', $price) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No customer-specific rates yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
