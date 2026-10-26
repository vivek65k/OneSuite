@extends('layouts.admin')

@section('title', 'Putaway Rules')

@section('content')
    <x-page-title title="Putaway Rules" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a rule</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.putaway-rules.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select form-select-sm" required>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">Any category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Strategy</label>
                    <select name="strategy" class="form-select form-select-sm">
                        @foreach (\App\Models\Inventory\PutawayRule::STRATEGIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fixed Bin</label>
                    <select name="fixed_location_id" class="form-select form-select-sm">
                        <option value="">None</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->full_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 form-check form-switch"><input class="form-check-input" type="checkbox" name="is_hazardous" value="1"><label class="form-check-label font-12">Hazardous</label></div>
                <div class="col-md-2 form-check form-switch"><input class="form-check-input" type="checkbox" name="is_temp_controlled" value="1"><label class="form-check-label font-12">Temp-controlled</label></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-stairs"></i> All Rules <span class="list-tab-count">{{ $rules->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Warehouse</th><th>Category</th><th>Strategy</th><th>Bin</th><th>Flags</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td class="fw-semibold">{{ $rule->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $rule->category?->name ?? 'Any' }}</td>
                            <td><span class="tag tone-indigo">{{ \App\Models\Inventory\PutawayRule::STRATEGIES[$rule->strategy] ?? $rule->strategy }}</span></td>
                            <td class="font-monospace font-13">{{ $rule->fixedLocation?->full_code ?? '—' }}</td>
                            <td>
                                @if ($rule->is_hazardous)<span class="tag tone-rose font-11">Hazardous</span>@endif
                                @if ($rule->is_temp_controlled)<span class="tag tone-sky font-11">Temp</span>@endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.putaway-rules.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this rule?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-stairs font-24 d-block mb-2"></i>No putaway rules configured yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Create a putaway task</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.putaway-rules.tasks.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select form-select-sm" required>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Item</label>
                    <select name="item_id" class="form-select form-select-sm" required>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Quantity</label><input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-sm" required></div>
                <div class="col-md-1"><button type="submit" class="btn btn-light btn-sm w-100">Create</button></div>
            </form>
            <p class="font-12 text-muted mt-2 mb-0">A real putaway task will also be created automatically from GRNs once Inbound Operations is built.</p>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-scan"></i> Putaway Task Queue <span class="list-tab-count">{{ $tasks->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Warehouse</th><th class="text-end">Qty</th><th>Suggested Bin</th><th>Confirm Bin</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($tasks as $task)
                        <tr>
                            <td class="fw-semibold">{{ $task->item?->name }}</td>
                            <td class="text-muted font-13">{{ $task->warehouse?->name }}</td>
                            <td class="text-end">{{ number_format($task->quantity, 2) }}</td>
                            <td class="font-monospace font-13">{{ $task->suggestedLocation?->full_code ?? '—' }}</td>
                            <td>
                                @if ($task->status === 'pending')
                                    <form action="{{ route('admin.inventory.putaway-rules.tasks.complete', $task) }}" method="POST" class="d-flex gap-1">
                                        @csrf
                                        <select name="confirmed_location_id" class="form-select form-select-sm" required>
                                            <option value="">Scan / select bin</option>
                                            @foreach ($locations as $location)
                                                <option value="{{ $location->id }}" @selected($task->suggested_location_id === $location->id)>{{ $location->full_code }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-light btn-sm flex-shrink-0">Confirm</button>
                                    </form>
                                @else
                                    <span class="font-monospace font-13">{{ $task->confirmedLocation?->full_code ?? '—' }}</span>
                                @endif
                            </td>
                            <td><span class="tag tone-{{ $task->status === 'completed' ? 'success' : ($task->status === 'exception' ? 'rose' : 'amber') }}">{{ \App\Models\Inventory\PutawayTask::STATUSES[$task->status] ?? $task->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-scan font-24 d-block mb-2"></i>No putaway tasks yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tasks->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $tasks->links() }}</div></div>
        @endif
    </div>
@endsection
