@extends('layouts.admin')

@section('title', 'Warehouse Dashboard')

@section('content')
    <x-page-title title="Warehouse Dashboard" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Warehouses" :value="$totalWarehouses" icon="ti-building-warehouse" tone="indigo" />
        <x-kpi-card label="Total Stock Value" value="{{ number_format($totalValue, 0) }}" icon="ti-cash" tone="success" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a warehouse</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.warehouse-dashboard.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2"><label class="form-label">Name</label><input type="text" name="name" class="form-control form-control-sm" required></div>
                <div class="col-md-2"><label class="form-label">Code</label><input type="text" name="code" class="form-control form-control-sm"></div>
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">Company-wide</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Manager</label>
                    <select name="manager_employee_id" class="form-select form-select-sm">
                        <option value="">None</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        @foreach (\App\Models\Inventory\Warehouse::TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1"><label class="form-label">Hours</label><input type="text" name="operating_hours" class="form-control form-control-sm" placeholder="9-18"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
                <div class="col-md-8"><label class="form-label">Address</label><input type="text" name="address" class="form-control form-control-sm"></div>
                <div class="col-md-4">
                    <label class="form-label">Picking Strategy</label>
                    <select name="picking_strategy" class="form-select form-select-sm">
                        @foreach (\App\Models\Inventory\Warehouse::PICKING_STRATEGIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-building-warehouse"></i> All Warehouses <span class="list-tab-count">{{ $rows->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Type</th><th>Branch</th><th>Manager</th><th class="text-end">Items</th><th class="text-end">Value</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.inventory.warehouse-dashboard.index', ['warehouse_id' => $row['warehouse']->id]) }}">{{ $row['warehouse']->name }}</a></td>
                            <td><span class="tag tone-indigo">{{ \App\Models\Inventory\Warehouse::TYPES[$row['warehouse']->type] ?? $row['warehouse']->type }}</span></td>
                            <td class="text-muted font-13">{{ $row['warehouse']->branch?->name ?? 'Company-wide' }}</td>
                            <td class="text-muted font-13">{{ $row['warehouse']->manager?->name ?? '—' }}</td>
                            <td class="text-end">{{ $row['itemCount'] }}</td>
                            <td class="text-end">{{ number_format($row['totalValue'], 0) }}</td>
                            <td>
                                <form action="{{ route('admin.inventory.warehouse-dashboard.toggle', $row['warehouse']) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="switch-btn {{ $row['warehouse']->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $row['warehouse']->is_active ? 'true' : 'false' }}">
                                        <span class="visually-hidden">Toggle {{ $row['warehouse']->name }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.inventory.warehouse-dashboard.index', ['warehouse_id' => $row['warehouse']->id]) }}" class="btn-icon" title="View stock"><i class="ti ti-eye font-16"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="ti ti-building-warehouse font-24 d-block mb-2"></i>No warehouses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($selectedWarehouse)
        <div class="card list-view mb-3">
            <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-settings"></i> {{ $selectedWarehouse->name }} settings</div></div>
            <div class="list-filter-panel">
                <form method="POST" action="{{ route('admin.inventory.warehouse-dashboard.update', $selectedWarehouse) }}" class="row g-2 align-items-end">
                    @csrf @method('PUT')
                    <div class="col-md-3">
                        <label class="form-label">Manager</label>
                        <select name="manager_employee_id" class="form-select form-select-sm">
                            <option value="">None</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected($selectedWarehouse->manager_employee_id === $employee->id)>{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select form-select-sm">
                            @foreach (\App\Models\Inventory\Warehouse::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected($selectedWarehouse->type === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Operating Hours</label><input type="text" name="operating_hours" class="form-control form-control-sm" value="{{ $selectedWarehouse->operating_hours }}"></div>
                    <div class="col-md-4">
                        <label class="form-label">Picking Strategy</label>
                        <select name="picking_strategy" class="form-select form-select-sm">
                            @foreach (\App\Models\Inventory\Warehouse::PICKING_STRATEGIES as $key => $label)
                                <option value="{{ $key }}" @selected($selectedWarehouse->picking_strategy === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1"><button type="submit" class="btn btn-light btn-sm w-100">Save</button></div>
                </form>
            </div>
        </div>

        <div class="card list-view">
            <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-package"></i> Stock at {{ $selectedWarehouse->name }} <span class="list-tab-count">{{ $selectedRows->count() }}</span></div></div>
            <div class="table-responsive">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light"><tr><th>Item</th><th class="text-end">Quantity</th><th class="text-end">Value</th></tr></thead>
                    <tbody>
                        @forelse ($selectedRows as $stockRow)
                            <tr>
                                <td class="fw-semibold">{{ $stockRow->item?->name ?? '—' }}</td>
                                <td class="text-end">{{ number_format($stockRow->quantity, 2) }}</td>
                                <td class="text-end">{{ number_format($stockRow->quantity * ($stockRow->item?->cost_price ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No stock recorded at this warehouse yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
