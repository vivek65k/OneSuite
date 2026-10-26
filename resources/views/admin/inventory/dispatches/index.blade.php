@extends('layouts.admin')

@section('title', 'Dispatch')

@section('content')
    <x-page-title title="Dispatch" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Dispatch a package</div></div>
        <div class="list-filter-panel">
            <p class="text-muted font-13 mb-2"><i class="ti ti-info-circle"></i> On-hand stock is reduced here - this is the configured dispatch transaction, separate from the earlier reservation.</p>
            @if ($packedPackages->isEmpty())
                <p class="text-muted font-13 mb-0">No packed packages waiting to be dispatched.</p>
            @else
                <form method="POST" action="{{ route('admin.inventory.dispatches.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Package</label>
                        <select name="package_id" class="form-select form-select-sm" required>
                            @foreach ($packedPackages as $package)
                                <option value="{{ $package->id }}">{{ $package->package_number }} — {{ $package->lines->pluck('item.name')->filter()->implode(', ') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-select form-select-sm" required>
                            @foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Carrier</label><input type="text" name="carrier" class="form-control form-control-sm"></div>
                    <div class="col-md-2"><label class="form-label">Tracking #</label><input type="text" name="tracking_number" class="form-control form-control-sm"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100" onclick="return confirm('Dispatch this package? Stock will be reduced.');">Dispatch</button></div>
                </form>
            @endif
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-truck"></i> In Transit <span class="list-tab-count">{{ $dispatches->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Dispatch #</th><th>Package</th><th>Warehouse</th><th>Carrier</th><th>Tracking #</th><th>Dispatched</th></tr></thead>
                <tbody>
                    @forelse ($dispatches as $dispatch)
                        <tr>
                            <td class="font-monospace font-13">{{ $dispatch->dispatch_number }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $dispatch->package?->package_number }}</td>
                            <td class="text-muted font-13">{{ $dispatch->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $dispatch->carrier ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $dispatch->tracking_number ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $dispatch->dispatched_at?->format('M j, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-truck font-24 d-block mb-2"></i>Nothing in transit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($dispatches->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $dispatches->links() }}</div></div>
        @endif
    </div>
@endsection
