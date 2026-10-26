@extends('layouts.admin')

@section('title', 'Customer Returns')

@section('content')
    <x-page-title title="Customer Returns" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Raise a customer return</div></div>
        <div class="list-filter-panel">
            <p class="text-muted font-13 mb-2"><i class="ti ti-info-circle"></i> Stock only increases once the return is approved and received back - not at request time.</p>
            <form method="POST" action="{{ route('admin.inventory.customer-returns.store') }}">
                @csrf
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-select form-select-sm" required>
                            @foreach ($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Against Dispatch (optional)</label>
                        <select name="dispatch_id" class="form-select form-select-sm">
                            <option value="">None</option>
                            @foreach ($dispatches as $dispatch)<option value="{{ $dispatch->id }}">{{ $dispatch->dispatch_number }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Reason</label><input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Wrong size, customer changed mind"></div>
                </div>

                <label class="form-label d-flex justify-content-between align-items-center">
                    <span>Return items</span>
                    <button type="button" class="btn btn-light btn-sm" id="addLineBtn"><i class="ti ti-plus font-14 me-1"></i> Add Line</button>
                </label>
                <div id="linesBox"></div>
                <button type="submit" class="btn btn-de-primary text-light px-4 mt-2">Save as Draft</button>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-arrow-back-up"></i> All Returns <span class="list-tab-count">{{ $returns->total() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Return #</th><th>Warehouse</th><th>Against Dispatch</th><th>Items</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($returns as $return)
                        <tr>
                            <td class="font-monospace font-13">{{ $return->return_number }}</td>
                            <td class="text-muted font-13">{{ $return->warehouse?->name }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $return->dispatch?->dispatch_number ?? '—' }}</td>
                            <td class="text-muted font-13">
                                @foreach ($return->lines as $line)
                                    <div>{{ $line->item?->name }} — {{ $line->quantity }}</div>
                                @endforeach
                            </td>
                            <td><span class="tag tone-{{ $return->status === 'received' ? 'success' : ($return->status === 'approved' ? 'sky' : 'amber') }}">{{ \App\Models\Inventory\CustomerReturn::STATUSES[$return->status] }}</span></td>
                            <td class="text-end">
                                @if ($return->status === 'draft')
                                    <form action="{{ route('admin.inventory.customer-returns.approve', $return) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-light btn-sm">Approve</button>
                                    </form>
                                @elseif ($return->status === 'approved')
                                    <form action="{{ route('admin.inventory.customer-returns.receive', $return) }}" method="POST" class="d-inline" onsubmit="return confirm('Receive this return? Stock will be increased.');">
                                        @csrf
                                        <button type="submit" class="btn btn-de-primary text-light btn-sm">Receive</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-arrow-back-up font-24 d-block mb-2"></i>No customer returns yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($returns->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $returns->links() }}</div></div>
        @endif
    </div>

    <script>
        (function () {
            const items = @json($items);
            const box = document.getElementById('linesBox');
            function rowHtml() {
                const options = items.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
                return `<div class="row g-2 align-items-end mb-2 line-row">
                    <div class="col-md-5"><select name="item_id[]" class="form-select form-select-sm">${options}</select></div>
                    <div class="col-md-3"><input type="number" step="0.01" min="0.01" name="quantity[]" class="form-control form-control-sm" placeholder="Qty" required></div>
                    <div class="col-md-3"><input type="text" name="batch_number[]" class="form-control form-control-sm" placeholder="Batch # (optional)"></div>
                    <div class="col-md-1"><button type="button" class="btn-icon btn-icon-danger remove-line"><i class="ti ti-trash font-16"></i></button></div>
                </div>`;
            }
            document.getElementById('addLineBtn').addEventListener('click', () => box.insertAdjacentHTML('beforeend', rowHtml()));
            box.addEventListener('click', (e) => { const btn = e.target.closest('.remove-line'); if (btn) btn.closest('.line-row').remove(); });
            box.insertAdjacentHTML('beforeend', rowHtml());
        })();
    </script>
@endsection
