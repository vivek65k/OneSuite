@extends('layouts.admin')

@section('title', 'Zones, Racks & Bins')

@section('content')
    <x-page-title title="Zones, Racks &amp; Bins" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-filter-panel">
            <form method="GET" action="{{ route('admin.inventory.locations.index') }}" class="d-flex gap-2 align-items-end">
                <div class="flex-fill">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @forelse ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) $warehouseId === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                        @empty
                            <option value="">No warehouses yet</option>
                        @endforelse
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Locations" :value="$locations->count()" icon="ti-layout-grid" tone="indigo" />
        <x-kpi-card label="Blocked" :value="$blockedCount" icon="ti-ban" tone="rose" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a location</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.locations.store') }}" class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="warehouse_id" value="{{ $warehouseId }}">
                <div class="col-md-2">
                    <label class="form-label">Level</label>
                    <select name="level" class="form-select form-select-sm">
                        @foreach (\App\Models\Inventory\Location::LEVELS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Code</label><input type="text" name="code" class="form-control form-control-sm" placeholder="e.g. A-01" required></div>
                <div class="col-md-3">
                    <label class="form-label">Parent (optional)</label>
                    <select name="parent_id" class="form-select form-select-sm">
                        <option value="">Top level</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->full_code }} ({{ $parent->level }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Storage Type</label>
                    <select name="storage_type" class="form-select form-select-sm">
                        <option value="">—</option>
                        @foreach (\App\Models\Inventory\Location::STORAGE_TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1"><label class="form-label">Max Wt</label><input type="number" step="0.01" name="max_weight" class="form-control form-control-sm"></div>
                <div class="col-md-1"><label class="form-label">Max Vol</label><input type="number" step="0.01" name="max_volume" class="form-control form-control-sm"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
            <p class="font-12 text-muted mt-2 mb-0">Example: WH-KOL / A-01 / R-03 / B-12 pinpoints the exact bin a picker should go to.</p>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-layout-grid"></i> All Locations <span class="list-tab-count">{{ $locations->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Full Code</th><th>Level</th><th>Storage Type</th><th>Max Wt/Vol</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($locations as $location)
                        <tr>
                            <td class="fw-semibold font-monospace">{{ $location->full_code }}</td>
                            <td><span class="tag tone-indigo">{{ \App\Models\Inventory\Location::LEVELS[$location->level] ?? $location->level }}</span></td>
                            <td class="text-muted font-13">{{ \App\Models\Inventory\Location::STORAGE_TYPES[$location->storage_type] ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $location->max_weight ?? '—' }} / {{ $location->max_volume ?? '—' }}</td>
                            <td>
                                <form action="{{ route('admin.inventory.locations.toggle', $location) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <span class="tag tone-{{ $location->is_blocked ? 'rose' : 'success' }}">
                                        <button type="submit" class="btn btn-link p-0 text-reset font-12 text-decoration-none">{{ $location->is_blocked ? 'Blocked' : 'Available' }}</button>
                                    </span>
                                </form>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.locations.destroy', $location) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this location?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-layout-grid font-24 d-block mb-2"></i>No locations defined yet for this warehouse.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
