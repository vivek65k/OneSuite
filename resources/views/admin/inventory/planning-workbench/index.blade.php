@extends('layouts.admin')

@section('title', 'Inventory Planning Workbench')

@section('content')
    <x-page-title title="Inventory Planning Workbench" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <p class="text-muted font-13 mb-3"><i class="ti ti-info-circle"></i> One review surface for everything waiting on your approval across Reorder Management, Replenishment and Warehouse Transfers.</p>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-shopping-cart"></i> Purchase Suggestions <span class="list-tab-count">{{ $purchaseSuggestions->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Warehouse</th><th>Available</th><th>Suggested Qty</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($purchaseSuggestions as $suggestion)
                        <tr>
                            <td class="font-13">{{ $suggestion->item?->name }}</td>
                            <td class="text-muted font-13">{{ $suggestion->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $suggestion->available_qty }}</td>
                            <td class="font-13 fw-semibold text-success">{{ $suggestion->suggested_qty }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.reorder.suggestions.approve', $suggestion) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-de-primary text-light btn-sm">Approve</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Nothing to purchase right now.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-forklift"></i> Replenishment Tasks <span class="list-tab-count">{{ $replenishmentTasks->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Warehouse</th><th>From</th><th>To</th><th>Qty</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($replenishmentTasks as $task)
                        <tr>
                            <td class="font-13">{{ $task->item?->name }}</td>
                            <td class="text-muted font-13">{{ $task->warehouse?->name }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $task->fromLocation?->full_code ?? '—' }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $task->toLocation?->full_code ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $task->suggested_qty }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.replenishment.tasks.complete', $task) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-de-primary text-light btn-sm">Mark Moved</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No pending replenishment tasks.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-arrows-right-left"></i> Pending Warehouse Transfers <span class="list-tab-count">{{ $pendingTransfers->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Transfer #</th><th>From</th><th>To</th><th>Items</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($pendingTransfers as $transfer)
                        <tr>
                            <td class="font-monospace font-13">{{ $transfer->transfer_number }}</td>
                            <td class="text-muted font-13">{{ $transfer->sourceWarehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $transfer->destWarehouse?->name }}</td>
                            <td class="text-muted font-13">
                                @foreach ($transfer->lines as $line)
                                    <div>{{ $line->item?->name }} — {{ $line->requested_qty }}</div>
                                @endforeach
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.warehouse-transfers.approve', $transfer) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-de-primary text-light btn-sm">Approve</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No pending transfer requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
