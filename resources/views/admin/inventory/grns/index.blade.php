@extends('layouts.admin')

@section('title', 'Goods Receipt (GRN)')

@section('content')
    <x-page-title title="Goods Receipt (GRN)" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Create GRN (draft)</div></div>
        <div class="list-filter-panel">
            <p class="text-muted font-13 mb-2"><i class="ti ti-info-circle"></i> A draft GRN does not change stock. Stock only moves when you post it below.</p>
            <form method="POST" action="{{ route('admin.inventory.grns.store') }}">
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
                    <div class="col-md-6">
                        <label class="form-label">Against Expected Receipt (optional)</label>
                        <select name="expected_receipt_id" class="form-select form-select-sm">
                            <option value="">None</option>
                            @foreach ($expectedReceipts as $receipt)<option value="{{ $receipt->id }}">{{ $receipt->reference_number }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <label class="form-label d-flex justify-content-between align-items-center">
                    <span>Received lines</span>
                    <button type="button" class="btn btn-light btn-sm" id="addLineBtn"><i class="ti ti-plus font-14 me-1"></i> Add Line</button>
                </label>
                <div id="linesBox"></div>
                <button type="submit" class="btn btn-de-primary text-light px-4 mt-2">Save as Draft</button>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-receipt"></i> All GRNs <span class="list-tab-count">{{ $grns->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>GRN #</th><th>Supplier</th><th>Warehouse</th><th>Lines</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($grns as $grn)
                        <tr>
                            <td class="font-monospace font-13">{{ $grn->grn_number }}</td>
                            <td class="text-muted font-13">{{ $grn->supplier?->name ?? '—' }}</td>
                            <td class="text-muted font-13">{{ $grn->warehouse?->name }}</td>
                            <td class="text-muted font-13">
                                @foreach ($grn->lines as $line)
                                    <div>{{ $line->item?->name }} — recv {{ $line->received_qty }}{{ $line->damaged_qty ? ' (dmg '.$line->damaged_qty.')' : '' }}
                                        @if ($line->inspection_required)<span class="tag tone-amber font-11">QC {{ $line->inspection_status }}</span>@endif
                                    </div>
                                @endforeach
                            </td>
                            <td><span class="tag tone-{{ $grn->status === 'posted' ? 'success' : 'amber' }}">{{ ucfirst($grn->status) }}</span></td>
                            <td class="text-end">
                                @if ($grn->status === 'draft')
                                    <form action="{{ route('admin.inventory.grns.post', $grn) }}" method="POST" class="d-inline" onsubmit="return confirm('Post this GRN? Stock will be updated.');">
                                        @csrf
                                        <button type="submit" class="btn btn-de-primary text-light btn-sm">Post GRN</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-receipt font-24 d-block mb-2"></i>No GRNs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($grns->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $grns->links() }}</div></div>
        @endif
    </div>

    <script>
        (function () {
            const items = @json($items);
            const box = document.getElementById('linesBox');
            function rowHtml() {
                const options = items.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
                return `<div class="row g-2 align-items-end mb-2 line-row border-bottom pb-2">
                    <div class="col-md-3"><select name="item_id[]" class="form-select form-select-sm">${options}</select></div>
                    <div class="col-md-2"><input type="number" step="0.01" min="0" name="received_qty[]" class="form-control form-control-sm" placeholder="Received qty" required></div>
                    <div class="col-md-2"><input type="number" step="0.01" min="0" name="damaged_qty[]" class="form-control form-control-sm" placeholder="Damaged qty"></div>
                    <div class="col-md-2"><input type="text" name="batch_number[]" class="form-control form-control-sm" placeholder="Batch #"></div>
                    <div class="col-md-2"><input type="date" name="expiry_date[]" class="form-control form-control-sm"></div>
                    <div class="col-md-1 d-flex align-items-center justify-content-between">
                        <div class="form-check" title="Inspection required">
                            <input class="form-check-input inspection-checkbox" type="checkbox" value="1">
                        </div>
                        <button type="button" class="btn-icon btn-icon-danger remove-line"><i class="ti ti-trash font-16"></i></button>
                    </div>
                </div>`;
            }
            function reindex() {
                box.querySelectorAll('.line-row').forEach((row, i) => {
                    row.querySelector('.inspection-checkbox').name = `inspection_required[${i}]`;
                });
            }
            document.getElementById('addLineBtn').addEventListener('click', () => { box.insertAdjacentHTML('beforeend', rowHtml()); reindex(); });
            box.addEventListener('click', (e) => { const btn = e.target.closest('.remove-line'); if (btn) { btn.closest('.line-row').remove(); reindex(); } });
            box.insertAdjacentHTML('beforeend', rowHtml());
            reindex();
        })();
    </script>
@endsection
