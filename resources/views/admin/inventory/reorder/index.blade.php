@extends('layouts.admin')

@section('title', 'Reorder Management')

@section('content')
    <x-page-title title="Reorder Management" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Set a reorder rule</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.reorder.rules.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <select name="warehouse_id" class="form-select form-select-sm" required>
                        @foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="item_id" class="form-select form-select-sm" required>
                        @foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label font-11 mb-0">Reorder Point</label><input type="number" step="0.01" min="0" name="reorder_point" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="form-label font-11 mb-0">Target Level</label><input type="number" step="0.01" min="0" name="target_stock_level" class="form-control form-control-sm" required></div>
                <div class="col-md-1"><label class="form-label font-11 mb-0">Lead (days)</label><input type="number" min="0" name="lead_time_days" class="form-control form-control-sm"></div>
                <div class="col-md-1"><label class="form-label font-11 mb-0">Safety</label><input type="number" step="0.01" min="0" name="safety_stock" class="form-control form-control-sm"></div>
                <div class="col-md-2 form-check form-switch align-self-center">
                    <input class="form-check-input" type="checkbox" name="auto_create_po" value="1">
                    <label class="form-check-label font-13">Auto-create PO</label>
                </div>
                <div class="col-md-12"><button type="submit" class="btn btn-de-primary text-light px-4 mt-1">Save Rule</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-list-check"></i> Reorder Rules <span class="list-tab-count">{{ $rules->count() }}</span></div>
            <form action="{{ route('admin.inventory.reorder.suggestions.generate') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-de-primary text-light btn-sm"><i class="ti ti-refresh font-14 me-1"></i> Generate Suggestions</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Warehouse</th><th>Reorder Point</th><th>Target Level</th><th>Lead Time</th><th>Safety Stock</th><th>Auto PO</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td class="font-13">{{ $rule->item?->name }}</td>
                            <td class="text-muted font-13">{{ $rule->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $rule->reorder_point }}</td>
                            <td class="text-muted font-13">{{ $rule->target_stock_level }}</td>
                            <td class="text-muted font-13">{{ $rule->lead_time_days }} days</td>
                            <td class="text-muted font-13">{{ $rule->safety_stock }}</td>
                            <td>{!! $rule->auto_create_po ? '<span class="tag tone-success font-11">Yes</span>' : '<span class="tag tone-slate font-11">No - manual review</span>' !!}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.reorder.rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Remove this rule?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">No reorder rules yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-shopping-cart"></i> Purchase Suggestions <span class="list-tab-count">{{ $suggestions->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Warehouse</th><th>On-hand</th><th>Reserved</th><th>Available</th><th>Incoming</th><th>Suggested Qty</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($suggestions as $suggestion)
                        <tr>
                            <td class="font-13">{{ $suggestion->item?->name }}</td>
                            <td class="text-muted font-13">{{ $suggestion->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $suggestion->on_hand_qty }}</td>
                            <td class="text-muted font-13">{{ $suggestion->reserved_qty }}</td>
                            <td class="text-muted font-13">{{ $suggestion->available_qty }}</td>
                            <td class="text-muted font-13">{{ $suggestion->incoming_qty }}</td>
                            <td class="font-13 fw-semibold text-success">{{ $suggestion->suggested_qty }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.reorder.suggestions.approve', $suggestion) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-de-primary text-light btn-sm">Approve</button>
                                </form>
                                <form action="{{ route('admin.inventory.reorder.suggestions.dismiss', $suggestion) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-light btn-sm">Dismiss</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-shopping-cart font-24 d-block mb-2"></i>No pending suggestions. Everything is above its reorder point.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
