@extends('layouts.admin')

@section('title', 'Expiry Management')

@section('content')
    <x-page-title title="Expiry Management" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip mb-3">
        <x-kpi-card label="Expired Batches" :value="$expired->count()" icon="ti ti-alert-triangle" />
        <x-kpi-card label="Expiring Within 30 Days" :value="$expiringSoon->count()" icon="ti ti-clock" />
        <x-kpi-card label="Pending Disposals" :value="$disposals->count()" icon="ti ti-trash" />
    </div>

    <p class="text-muted font-13 mb-3"><i class="ti ti-info-circle"></i> FEFO (first-expiry-first-out) picking is enforced by setting a warehouse's Picking Strategy to FEFO under Warehouse Management.</p>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-alert-triangle"></i> Expired Batches</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Item</th><th>Batch</th><th>Qty</th><th>Expired On</th><th style="min-width:230px">Request Disposal</th></tr></thead>
                        <tbody>
                            @forelse ($expired as $batch)
                                <tr>
                                    <td class="font-13">{{ $batch->item?->name }}</td>
                                    <td class="text-muted font-13 font-monospace">{{ $batch->batch_number }}</td>
                                    <td class="text-muted font-13">{{ $batch->quantity }}</td>
                                    <td class="text-danger font-13">{{ $batch->expiry_date?->format('M j, Y') }}</td>
                                    <td>
                                        <form action="{{ route('admin.inventory.expiry-management.dispose', $batch) }}" method="POST" class="d-flex gap-1">
                                            @csrf
                                            <input type="number" step="0.01" min="0.01" max="{{ $batch->quantity }}" name="quantity" class="form-control form-control-sm" style="width:80px" value="{{ $batch->quantity }}" required>
                                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason">
                                            <button type="submit" class="btn btn-light btn-sm">Dispose</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No expired batches.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card list-view h-100">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-clock"></i> Expiring Within 30 Days</div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light"><tr><th>Item</th><th>Batch</th><th>Qty</th><th>Expires On</th></tr></thead>
                        <tbody>
                            @forelse ($expiringSoon as $batch)
                                <tr>
                                    <td class="font-13">{{ $batch->item?->name }}</td>
                                    <td class="text-muted font-13 font-monospace">{{ $batch->batch_number }}</td>
                                    <td class="text-muted font-13">{{ $batch->quantity }}</td>
                                    <td class="text-amber font-13">{{ $batch->expiry_date?->format('M j, Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Nothing expiring soon.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view mt-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-trash"></i> Pending Disposal Approvals <span class="list-tab-count">{{ $disposals->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Batch</th><th>Qty</th><th>Reason</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($disposals as $disposal)
                        <tr>
                            <td class="font-13">{{ $disposal->item?->name }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $disposal->batch?->batch_number }}</td>
                            <td class="text-muted font-13">{{ $disposal->quantity }}</td>
                            <td class="text-muted font-13">{{ $disposal->reason ?? '—' }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.expiry-management.disposals.approve', $disposal) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve this disposal? Stock will be reduced immediately.');">
                                    @csrf
                                    <button type="submit" class="btn btn-de-primary text-light btn-sm">Approve</button>
                                </form>
                                <form action="{{ route('admin.inventory.expiry-management.disposals.reject', $disposal) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-light btn-sm">Reject</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No pending disposal requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
