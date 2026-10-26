@extends('layouts.admin')

@section('title', 'Expected Receipts / ASN')

@section('content')
    <x-page-title title="Expected Receipts / ASN" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Log an expected receipt</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.expected-receipts.store') }}" id="asnForm">
                @csrf
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-select form-select-sm" required>
                            @foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" class="form-select form-select-sm">
                            <option value="">None</option>
                            @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">PO Reference</label><input type="text" name="po_reference" class="form-control form-control-sm"></div>
                    <div class="col-md-2"><label class="form-label">Expected Date</label><input type="date" name="expected_date" class="form-control form-control-sm"></div>
                    <div class="col-md-2 form-check form-switch align-self-end"><input class="form-check-input" type="checkbox" name="is_asn" value="1"><label class="form-check-label font-13">This is an ASN</label></div>
                    <div class="col-md-3"><label class="form-label">Vehicle Number</label><input type="text" name="vehicle_number" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><label class="form-label">Tracking Number</label><input type="text" name="tracking_number" class="form-control form-control-sm"></div>
                </div>

                <label class="form-label d-flex justify-content-between align-items-center">
                    <span>Expected items</span>
                    <button type="button" class="btn btn-light btn-sm" id="addLineBtn"><i class="ti ti-plus font-14 me-1"></i> Add Line</button>
                </label>
                <div id="linesBox"></div>
                <button type="submit" class="btn btn-de-primary text-light px-4 mt-2">Save Expected Receipt</button>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-truck-delivery"></i> All Expected Receipts <span class="list-tab-count">{{ $receipts->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Reference</th><th>Supplier</th><th>Warehouse</th><th>Expected</th><th>Items</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($receipts as $receipt)
                        <tr>
                            <td class="font-monospace font-13">{{ $receipt->reference_number }} @if($receipt->is_asn)<span class="tag tone-sky font-11">ASN</span>@endif</td>
                            <td class="text-muted font-13">{{ $receipt->supplier?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $receipt->warehouse?->name }}</td>
                            <td class="text-muted font-13">{{ $receipt->expected_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $receipt->lines->count() }} item(s)</td>
                            <td><span class="tag tone-{{ $receipt->status === 'received' ? 'success' : ($receipt->status === 'cancelled' ? 'slate' : 'amber') }}">{{ \App\Models\Inventory\ExpectedReceipt::STATUSES[$receipt->status] ?? $receipt->status }}</span></td>
                            <td class="text-end">
                                @if ($receipt->status === 'pending')
                                    <a href="{{ route('admin.inventory.grns.index') }}" class="btn btn-light btn-sm">Create GRN</a>
                                    <form action="{{ route('admin.inventory.expected-receipts.cancel', $receipt) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this expected receipt?');">
                                        @csrf
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Cancel"><i class="ti ti-x font-16"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-truck-delivery font-24 d-block mb-2"></i>No expected receipts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($receipts->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $receipts->links() }}</div></div>
        @endif
    </div>

    <script>
        (function () {
            const items = @json($items);
            const box = document.getElementById('linesBox');
            function rowHtml() {
                const options = items.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
                return `<div class="row g-2 align-items-end mb-2 line-row">
                    <div class="col-md-7"><select name="item_id[]" class="form-select form-select-sm">${options}</select></div>
                    <div class="col-md-3"><input type="number" step="0.01" min="0.01" name="expected_qty[]" class="form-control form-control-sm" placeholder="Qty" required></div>
                    <div class="col-md-2"><button type="button" class="btn-icon btn-icon-danger remove-line"><i class="ti ti-trash font-16"></i></button></div>
                </div>`;
            }
            document.getElementById('addLineBtn').addEventListener('click', () => box.insertAdjacentHTML('beforeend', rowHtml()));
            box.addEventListener('click', (e) => { const btn = e.target.closest('.remove-line'); if (btn) btn.closest('.line-row').remove(); });
            box.insertAdjacentHTML('beforeend', rowHtml());
        })();
    </script>
@endsection
