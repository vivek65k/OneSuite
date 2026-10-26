@extends('layouts.admin')

@section('title', 'Replenishment')

@section('content')
    <x-page-title title="Replenishment" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Set a min/max replenishment rule</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.replenishment.rules.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select form-select-sm" required>
                        @foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Item</label>
                    <select name="item_id" class="form-select form-select-sm" required>
                        @foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Picking Bin</label>
                    <select name="bin_location_id" class="form-select form-select-sm" required>
                        @foreach ($locations as $location)<option value="{{ $location->id }}">{{ $location->full_code }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Source Bin</label>
                    <select name="source_location_id" class="form-select form-select-sm">
                        <option value="">None</option>
                        @foreach ($locations as $location)<option value="{{ $location->id }}">{{ $location->full_code }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-1"><label class="form-label">Min</label><input type="number" step="0.01" min="0" name="min_qty" class="form-control form-control-sm" required></div>
                <div class="col-md-1"><label class="form-label">Max</label><input type="number" step="0.01" min="0" name="max_qty" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Save Rule</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-adjustments-horizontal"></i> Replenishment Rules <span class="list-tab-count">{{ $rules->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Warehouse</th><th>Picking Bin</th><th>Source Bin</th><th>Min</th><th>Max</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td class="font-13">{{ $rule->item?->name }}</td>
                            <td class="text-muted font-13">{{ $rule->warehouse?->name }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $rule->binLocation?->full_code }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $rule->sourceLocation?->full_code ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $rule->min_qty }}</td>
                            <td class="text-muted font-13">{{ $rule->max_qty }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.replenishment.rules.tasks.store', $rule) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-light btn-sm">Generate Task</button>
                                </form>
                                <form action="{{ route('admin.inventory.replenishment.rules.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this rule?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No replenishment rules yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-forklift"></i> Pending Replenishment Tasks <span class="list-tab-count">{{ $tasks->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Warehouse</th><th>From</th><th>To</th><th>Suggested Qty</th><th>Priority</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($tasks as $task)
                        <tr>
                            <td class="font-13">{{ $task->item?->name }}</td>
                            <td class="text-muted font-13">{{ $task->warehouse?->name }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $task->fromLocation?->full_code ?? '—' }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $task->toLocation?->full_code ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $task->suggested_qty }}</td>
                            <td><span class="tag tone-amber font-11">{{ \App\Models\Inventory\ReplenishmentTask::PRIORITIES[$task->priority] }}</span></td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.replenishment.tasks.complete', $task) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-de-primary text-light btn-sm">Mark Moved</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-forklift font-24 d-block mb-2"></i>No pending replenishment tasks.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
