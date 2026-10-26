@extends('layouts.admin')

@section('title', 'Numbering & Documents')

@section('content')
    <x-page-title title="Numbering & Documents" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-hash"></i> Numbering Series</div></div>
        <div class="list-filter-panel">
            <p class="text-muted font-13 mb-2"><i class="ti ti-info-circle"></i> Leave a document type unconfigured to keep its default PREFIX-YEAR-00001 format.</p>
            <form method="POST" action="{{ route('admin.inventory.numbering-documents.series.store') }}" class="row g-2 align-items-end mb-3">
                @csrf
                <div class="col-md-3">
                    <select name="document_type" class="form-select form-select-sm" required>
                        @foreach ($documentTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label font-11 mb-0">Prefix</label><input type="text" name="prefix" class="form-control form-control-sm" placeholder="e.g. GRN" required></div>
                <div class="col-md-2"><label class="form-label font-11 mb-0">Next Number</label><input type="number" min="1" name="next_number" class="form-control form-control-sm" value="1" required></div>
                <div class="col-md-2"><label class="form-label font-11 mb-0">Padding Digits</label><input type="number" min="1" max="10" name="padding_digits" class="form-control form-control-sm" value="5" required></div>
                <div class="col-md-2"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Save</button></div>
            </form>
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Document Type</th><th>Prefix</th><th>Next Number</th><th>Padding</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($series as $s)
                        <tr>
                            <td class="font-13">{{ $documentTypes[$s->document_type] ?? $s->document_type }}</td>
                            <td class="text-muted font-13 font-monospace">{{ $s->prefix }}</td>
                            <td class="text-muted font-13">{{ $s->next_number }}</td>
                            <td class="text-muted font-13">{{ $s->padding_digits }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.numbering-documents.series.destroy', $s) }}" method="POST" onsubmit="return confirm('Reset this document type to its default format?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">All document types use their default numbering format.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-forms"></i> Custom Fields (Products)</div></div>
        <div class="list-filter-panel">
            <p class="text-muted font-13 mb-2"><i class="ti ti-info-circle"></i> Fields added here appear immediately on the product create/edit form.</p>
            <form method="POST" action="{{ route('admin.inventory.numbering-documents.fields.store') }}" class="row g-2 align-items-end mb-3">
                @csrf
                <div class="col-md-2"><label class="form-label font-11 mb-0">Field Key</label><input type="text" name="field_key" class="form-control form-control-sm" placeholder="e.g. hsn_code" required></div>
                <div class="col-md-3"><label class="form-label font-11 mb-0">Label</label><input type="text" name="label" class="form-control form-control-sm" placeholder="e.g. HSN Code" required></div>
                <div class="col-md-2">
                    <label class="form-label font-11 mb-0">Type</label>
                    <select name="field_type" class="form-select form-select-sm" required>
                        @foreach ($fieldTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label font-11 mb-0">Options (for Select, comma-separated)</label><input type="text" name="options" class="form-control form-control-sm" placeholder="A,B,C"></div>
                <div class="col-md-1 form-check form-switch align-self-center"><input class="form-check-input" type="checkbox" name="is_required" value="1"><label class="form-check-label font-11">Required</label></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Add</button></div>
            </form>
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Key</th><th>Label</th><th>Type</th><th>Required</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($fields as $field)
                        <tr>
                            <td class="font-13 font-monospace">{{ $field->field_key }}</td>
                            <td class="text-muted font-13">{{ $field->label }}</td>
                            <td class="text-muted font-13">{{ $fieldTypes[$field->field_type] }}</td>
                            <td>{!! $field->is_required ? '<span class="tag tone-amber font-11">Yes</span>' : '<span class="tag tone-slate font-11">No</span>' !!}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.numbering-documents.fields.destroy', $field) }}" method="POST" onsubmit="return confirm('Remove this field? Saved values will stay orphaned.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No custom product fields yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-file-import"></i> Import / Export</div></div>
        <div class="list-filter-panel">
            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="font-13 fw-semibold">Bulk Import Products</h6>
                    <p class="text-muted font-11">CSV/XLSX columns: sku, name, unit, cost_price, sale_price. Existing SKUs are updated.</p>
                    <form method="POST" action="{{ route('admin.inventory.numbering-documents.import.items') }}" enctype="multipart/form-data" class="d-flex gap-2">
                        @csrf
                        <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.txt,.xlsx" required>
                        <button type="submit" class="btn btn-de-primary text-light btn-sm">Import</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <h6 class="font-13 fw-semibold">Import Opening Balances</h6>
                    <p class="text-muted font-11">CSV/XLSX columns: sku, warehouse_code, quantity. Sets on-hand stock and logs an opening ledger entry.</p>
                    <form method="POST" action="{{ route('admin.inventory.numbering-documents.import.opening-balances') }}" enctype="multipart/form-data" class="d-flex gap-2">
                        @csrf
                        <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.txt,.xlsx" required>
                        <button type="submit" class="btn btn-de-primary text-light btn-sm">Import</button>
                    </form>
                </div>
            </div>
            <hr>
            <a href="{{ route('admin.inventory.products.index') }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export Products (open list, use browser print/export)</a>
        </div>
    </div>
@endsection
