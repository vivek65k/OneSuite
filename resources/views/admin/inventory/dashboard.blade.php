@extends('layouts.admin')

@section('title', 'Inventory Dashboard')

@section('content')
    <x-page-title title="Inventory Dashboard" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-indigo" aria-hidden="true"><i class="ti ti-building-warehouse font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $totalItems }} items across {{ $totalWarehouses }} warehouse(s)</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-info">{{ number_format($totalUnits, 0) }} units in stock</span>
                @if ($lowStockCount > 0)
                    <span class="status-dot status-dot-warning">{{ $lowStockCount }} low stock</span>
                @endif
            </div>
        </div>
        <div class="record-header-actions">
            <a href="{{ route('admin.inventory.stock-overview.index') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Item</a>
            <a href="{{ route('admin.inventory.warehouse-dashboard.index') }}" class="btn btn-light btn-sm"><i class="ti ti-building-warehouse font-14 me-1"></i> Add Warehouse</a>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Total Items" :value="$totalItems" icon="ti-package" tone="indigo" :href="route('admin.inventory.stock-overview.index')" />
        <x-kpi-card label="Warehouses" :value="$totalWarehouses" icon="ti-building-warehouse" tone="teal" :href="route('admin.inventory.warehouse-dashboard.index')" />
        <x-kpi-card label="Units in Stock" value="{{ number_format($totalUnits, 0) }}" icon="ti-stack-2" tone="violet" />
        <x-kpi-card label="Stock Value" value="{{ number_format($totalStockValue, 0) }}" icon="ti-cash" tone="success" />
        <x-kpi-card label="Low Stock Items" :value="$lowStockCount" icon="ti-alert-triangle" tone="rose" />
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-stack-2"></i> Stock Value by Category</div></div>
                <div class="p-3">
                    @php $maxVal = $byCategory->max('value') ?: 1; @endphp
                    @forelse ($byCategory as $row)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="font-13 text-truncate" style="width: 120px;" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                            <div class="mini-progress flex-grow-1"><span style="width: {{ $maxVal > 0 ? round($row['value'] / $maxVal * 100) : 0 }}%;" class="bg-primary"></span></div>
                            <span class="font-13 text-muted" style="width: 90px; text-align:right;">{{ number_format($row['value'], 0) }}</span>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4 mb-0">No items yet. <a href="{{ route('admin.inventory.stock-overview.index') }}">Add your first item</a>.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-alert-triangle"></i> Low Stock Alerts</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($lowStockItems as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td class="text-muted font-13">{{ $item->sku ?? '—' }}</td>
                                    <td class="text-end"><span class="tag tone-rose">Reorder at {{ $item->reorder_level }}</span></td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-muted py-4">Nothing low on stock right now.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
