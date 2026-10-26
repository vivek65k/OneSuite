@extends('layouts.admin')

@section('title', 'Quality Inspection')

@section('content')
    <x-page-title title="Quality Inspection" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="kpi-strip mb-3">
        <x-kpi-card label="Pending Inspections" :value="$pending->count()" icon="ti ti-flask" />
        <x-kpi-card label="Qty in Quarantine" :value="number_format($quarantineTotal, 2)" icon="ti ti-corner-down-left" />
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-flask"></i> Pending Inspections <span class="list-tab-count">{{ $pending->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Warehouse</th><th>Source GRN</th><th>Quarantine Qty</th><th style="min-width:420px">Record Inspection</th></tr></thead>
                <tbody>
                    @forelse ($pending as $inspection)
                        @php($qtyAvailable = \App\Models\Inventory\QuarantineStock::where('grn_line_id', $inspection->grn_line_id)->value('quantity') ?? 0)
                        <tr>
                            <td class="font-13">{{ $inspection->item?->name }}</td>
                            <td class="text-muted font-13">{{ $inspection->warehouse?->name }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $inspection->grnLine?->grn?->grn_number }}</td>
                            <td class="text-muted font-13">{{ number_format($qtyAvailable, 2) }}</td>
                            <td>
                                <form action="{{ route('admin.inventory.quality-inspections.inspect', $inspection) }}" method="POST" class="d-flex gap-2 align-items-end flex-wrap">
                                    @csrf
                                    <div><label class="form-label font-11 mb-0">Accept</label><input type="number" step="0.01" min="0" max="{{ $qtyAvailable }}" name="accepted_qty" class="form-control form-control-sm" style="width:90px" required></div>
                                    <div><label class="form-label font-11 mb-0">Reject</label><input type="number" step="0.01" min="0" name="rejected_qty" class="form-control form-control-sm" style="width:90px" required></div>
                                    <div><label class="form-label font-11 mb-0">Defect reason</label><input type="text" name="defect_reason" class="form-control form-control-sm" style="width:140px"></div>
                                    <button type="submit" class="btn btn-de-primary text-light btn-sm">Submit</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-flask font-24 d-block mb-2"></i>No pending inspections.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-clipboard-check"></i> Recently Completed</div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Item</th><th>Warehouse</th><th>Accepted</th><th>Rejected</th><th>Result</th><th>Inspected At</th></tr></thead>
                <tbody>
                    @forelse ($completed as $inspection)
                        <tr>
                            <td class="font-13">{{ $inspection->item?->name }}</td>
                            <td class="text-muted font-13">{{ $inspection->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $inspection->accepted_qty }}</td>
                            <td class="text-muted font-13">{{ $inspection->rejected_qty }}</td>
                            <td><span class="tag tone-{{ $inspection->result === 'pass' ? 'success' : ($inspection->result === 'fail' ? 'danger' : 'amber') }}">{{ ucfirst($inspection->result) }}</span></td>
                            <td class="text-muted font-13">{{ $inspection->inspected_at?->format('M j, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No completed inspections yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
