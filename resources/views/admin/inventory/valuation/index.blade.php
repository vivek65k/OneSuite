@extends('layouts.admin')

@section('title', 'Inventory Valuation')

@section('content')
    <x-page-title title="Inventory Valuation" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip mb-3">
        <x-kpi-card label="Total Stock Value" :value="number_format($totalValue, 2)" icon="ti ti-calculator" />
        <x-kpi-card label="Items Valued" :value="$items->count()" icon="ti ti-package" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-calculator"></i> Stock Valuation</div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Qty on Hand</th><th>Cost Price</th><th style="min-width:160px">Costing Method</th><th>Stock Value</th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td class="font-13">{{ $item->name }}</td>
                            <td class="text-muted font-13">{{ $item->total_quantity }}</td>
                            <td class="text-muted font-13">{{ number_format($item->cost_price, 2) }}</td>
                            <td>
                                <form action="{{ route('admin.inventory.valuation.costing-method', $item) }}" method="POST" class="d-flex gap-1">
                                    @csrf
                                    <select name="costing_method" class="form-select form-select-sm" onchange="this.form.requestSubmit()">
                                        @foreach (\App\Models\Inventory\Item::COSTING_METHODS as $key => $label)
                                            <option value="{{ $key }}" @selected($item->costing_method === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="font-13 fw-semibold">{{ number_format($item->stock_value, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No items to value yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
