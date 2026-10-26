@extends('layouts.admin')

@section('title', 'Units of Measure')

@section('content')
    <x-page-title title="Units of Measure" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a unit</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.uoms.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4"><label class="form-label">Name</label><input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Carton" required></div>
                <div class="col-md-3"><label class="form-label">Code</label><input type="text" name="code" class="form-control form-control-sm" placeholder="e.g. CTN" required></div>
                <div class="col-md-3"><label class="form-label">Decimal Precision</label><input type="number" name="decimal_precision" min="0" max="4" value="0" class="form-control form-control-sm"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-ruler"></i> All Units <span class="list-tab-count">{{ $uoms->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Code</th><th>Precision</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($uoms as $uom)
                        <tr>
                            <td class="fw-semibold">{{ $uom->name }}</td>
                            <td class="text-muted font-13">{{ $uom->code }}</td>
                            <td>{{ $uom->decimal_precision }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.uoms.destroy', $uom) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this unit?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5"><i class="ti ti-ruler font-24 d-block mb-2"></i>No units yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a conversion rule</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.inventory.uoms.conversions.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Product</label>
                    <select name="item_id" class="form-select form-select-sm" required>
                        <option value="">Select product</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Unit</label>
                    <select name="from_uom_id" class="form-select form-select-sm" required>
                        @foreach ($uoms as $uom)<option value="{{ $uom->id }}">{{ $uom->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Unit</label>
                    <select name="to_uom_id" class="form-select form-select-sm" required>
                        @foreach ($uoms as $uom)<option value="{{ $uom->id }}">{{ $uom->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Factor</label><input type="number" step="0.0001" min="0.0001" name="conversion_factor" class="form-control form-control-sm" placeholder="e.g. 24" required></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
            <p class="font-12 text-muted mt-2 mb-0">Example: 1 Carton = 24 Pieces &rarr; From "Carton" To "Pieces", factor 24. Receiving 10 cartons then increases stock by 240 pieces.</p>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-ruler-2"></i> Conversion Rules <span class="list-tab-count">{{ $conversions->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Product</th><th>Rule</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($conversions as $conversion)
                        <tr>
                            <td class="fw-semibold">{{ $conversion->item?->name }}</td>
                            <td class="text-muted font-13">1 {{ $conversion->fromUom?->name }} = {{ rtrim(rtrim($conversion->conversion_factor, '0'), '.') }} {{ $conversion->toUom?->name }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.inventory.uoms.conversions.destroy', $conversion) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this rule?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-5"><i class="ti ti-ruler-2 font-24 d-block mb-2"></i>No conversion rules yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
