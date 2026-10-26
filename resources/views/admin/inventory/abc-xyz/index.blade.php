@extends('layouts.admin')

@section('title', 'ABC / XYZ Analysis')

@section('content')
    <x-page-title title="ABC / XYZ Analysis" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-filter-panel d-flex justify-content-between align-items-center">
            <div class="font-13 text-muted">
                <strong>ABC</strong> classifies items by value contribution (80% / 95% / 100% cumulative cutoffs). <strong>XYZ</strong> classifies by demand variability over the last 6 months (stable / variable / erratic).
            </div>
            <form action="{{ route('admin.inventory.abc-xyz.recalculate') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-de-primary text-light btn-sm"><i class="ti ti-refresh font-14 me-1"></i> Recalculate</button>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-chart-pie"></i> Classification <span class="list-tab-count">{{ $items->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Qty on Hand</th><th>Cost Price</th><th>Stock Value</th><th>ABC Class</th><th>XYZ Class</th><th>Combined</th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td class="font-13">{{ $item->name }}</td>
                            <td class="text-muted font-13">{{ $item->total_quantity }}</td>
                            <td class="text-muted font-13">{{ number_format($item->cost_price, 2) }}</td>
                            <td class="text-muted font-13">{{ number_format($item->total_quantity * $item->cost_price, 2) }}</td>
                            <td>
                                @if ($item->abc_class)
                                    <span class="tag font-11 {{ $item->abc_class === 'A' ? 'tone-success' : ($item->abc_class === 'B' ? 'tone-amber' : 'tone-slate') }}">Class {{ $item->abc_class }}</span>
                                @else
                                    <span class="text-muted font-11">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($item->xyz_class)
                                    <span class="tag font-11 {{ $item->xyz_class === 'X' ? 'tone-success' : ($item->xyz_class === 'Y' ? 'tone-amber' : 'tone-danger') }}">Class {{ $item->xyz_class }}</span>
                                @else
                                    <span class="text-muted font-11">—</span>
                                @endif
                            </td>
                            <td class="text-muted font-13 font-monospace">{{ $item->abc_class && $item->xyz_class ? $item->abc_class.$item->xyz_class : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No items yet. Click Recalculate once stock exists.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
