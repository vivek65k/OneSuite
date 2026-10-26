@extends('layouts.admin')

@section('title', 'Packing')

@section('content')
    <x-page-title title="Packing" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Pack a completed pick list</div></div>
        <div class="list-filter-panel">
            @if ($pickedLists->isEmpty())
                <p class="text-muted font-13 mb-0">No fully-picked lists waiting to be packed.</p>
            @else
                <form method="POST" action="{{ route('admin.inventory.packing.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Picked List</label>
                        <select name="pick_list_id" class="form-select form-select-sm" required>
                            @foreach ($pickedLists as $pickList)
                                <option value="{{ $pickList->id }}">{{ $pickList->pick_list_number }} — {{ $pickList->lines->pluck('item.name')->filter()->implode(', ') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-select form-select-sm" required>
                            @foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Weight (kg)</label><input type="number" step="0.01" min="0" name="weight" class="form-control form-control-sm"></div>
                    <div class="col-md-2"><label class="form-label">Box Type</label><input type="text" name="box_type" class="form-control form-control-sm" placeholder="e.g. Medium carton"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Pack</button></div>
                </form>
            @endif
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-box"></i> All Packages <span class="list-tab-count">{{ $packages->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Package #</th><th>Warehouse</th><th>From Pick List</th><th>Items</th><th>Weight</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($packages as $package)
                        <tr>
                            <td class="font-monospace font-13">{{ $package->package_number }}</td>
                            <td class="text-muted font-13">{{ $package->warehouse?->name }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $package->pickList?->pick_list_number }}</td>
                            <td class="text-muted font-13">
                                @foreach ($package->lines as $line)
                                    <div>{{ $line->item?->name }} — {{ $line->quantity }}</div>
                                @endforeach
                            </td>
                            <td class="text-muted font-13">{{ $package->weight ?? '—' }}</td>
                            <td><span class="tag tone-{{ $package->status === 'dispatched' ? 'success' : 'amber' }}">{{ \App\Models\Inventory\InventoryPackage::STATUSES[$package->status] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-box font-24 d-block mb-2"></i>No packages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($packages->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $packages->links() }}</div></div>
        @endif
    </div>
@endsection
