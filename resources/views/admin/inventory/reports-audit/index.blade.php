@extends('layouts.admin')

@section('title', 'Reports & Audit Logs')

@section('content')
    <x-page-title title="Reports & Audit Logs" />

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-file-report"></i> Reports</div></div>
        <div class="list-filter-panel">
            <div class="row g-2">
                <div class="col-md-3"><a href="{{ route('admin.inventory.stock-ledger.index') }}" class="btn btn-light w-100"><i class="ti ti-history font-14 me-1"></i> Stock Ledger</a></div>
                <div class="col-md-3"><a href="{{ route('admin.inventory.valuation.index') }}" class="btn btn-light w-100"><i class="ti ti-calculator font-14 me-1"></i> Inventory Valuation</a></div>
                <div class="col-md-3"><a href="{{ route('admin.inventory.slow-dead-stock.index') }}" class="btn btn-light w-100"><i class="ti ti-clock font-14 me-1"></i> Slow / Dead Stock</a></div>
                <div class="col-md-3"><a href="{{ route('admin.inventory.abc-xyz.index') }}" class="btn btn-light w-100"><i class="ti ti-chart-pie font-14 me-1"></i> ABC / XYZ Analysis</a></div>
            </div>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-shield-check"></i> Audit Log <span class="list-tab-count">{{ $logs->total() }}</span></div></div>
        <div class="list-filter-panel">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <select name="entity_type" class="form-select form-select-sm">
                        <option value="">All record types</option>
                        @foreach ($entityTypes as $type)<option value="{{ $type }}" @selected(request('entity_type') === $type)>{{ $type }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Date/Time</th><th>User</th><th>Action</th><th>Record Type</th><th>Description</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-muted font-13">{{ $log->created_at->format('M j, Y H:i') }}</td>
                            <td class="text-muted font-13">{{ $log->employee?->name ?? 'System' }}</td>
                            <td><span class="tag tone-{{ $log->action === 'deleted' || $log->action === 'rejected' ? 'danger' : ($log->action === 'approved' ? 'success' : 'sky') }} font-11">{{ ucfirst($log->action) }}</span></td>
                            <td class="text-muted font-13">{{ $log->entity_type }}</td>
                            <td class="text-muted font-13">{{ $log->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-shield-check font-24 d-block mb-2"></i>No configuration changes logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $logs->links() }}</div></div>
        @endif
    </div>
@endsection
