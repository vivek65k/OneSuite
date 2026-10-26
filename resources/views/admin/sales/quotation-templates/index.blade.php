@extends('layouts.admin')

@section('title', 'Quotation Templates')

@section('content')
    <x-page-title title="Quotation Templates" :breadcrumbs="['Quotations' => route('admin.sales.quotations.index')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Create a template</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.sales.quotation-templates.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3"><input type="text" name="name" class="form-control form-control-sm" placeholder="Template name" required></div>
                <div class="col-md-3"><input type="text" name="description" class="form-control form-control-sm" placeholder="Description"></div>
                <div class="col-md-2"><input type="number" min="0" max="100" name="discount_percent" class="form-control form-control-sm" placeholder="Default discount %"></div>
                <div class="col-md-2"><input type="number" step="0.01" min="0" max="100" name="tax_percent" class="form-control form-control-sm" placeholder="Default tax %"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-de-primary text-light btn-sm w-100">Save</button></div>
            </form>
        </div>
    </div>

    @foreach ($templates as $template)
        <div class="card list-view mb-3">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-file-text"></i> {{ $template->name }} <span class="text-muted font-11">({{ $template->discount_percent }}% disc, {{ $template->tax_percent }}% tax)</span></div>
                <div class="list-toolbar-actions">
                    <a href="{{ route('admin.sales.quotations.create', ['template_id' => $template->id]) }}" class="btn btn-light btn-sm">Use Template</a>
                    <form action="{{ route('admin.sales.quotation-templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Delete this template?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                    </form>
                </div>
            </div>
            <div class="list-filter-panel">
                <form method="POST" action="{{ route('admin.sales.quotation-templates.lines.store', $template) }}" class="row g-2 align-items-end mb-2">
                    @csrf
                    <div class="col-md-4">
                        <select name="product_id" class="form-select form-select-sm">
                            <option value="">Custom line</option>
                            @foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><input type="text" name="description" class="form-control form-control-sm" placeholder="Description" required></div>
                    <div class="col-md-1"><input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-sm" value="1" required></div>
                    <div class="col-md-2"><input type="number" step="0.01" min="0" name="unit_price" class="form-control form-control-sm" placeholder="Unit price" required></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-light btn-sm w-100">Add</button></div>
                </form>
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light"><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                        @forelse ($template->lines as $line)
                            <tr>
                                <td class="font-13">{{ $line->description }}</td>
                                <td class="text-muted font-13">{{ $line->quantity }}</td>
                                <td class="text-muted font-13">{{ number_format($line->unit_price, 2) }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.sales.quotation-templates.lines.destroy', $line) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No lines in this template yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    @if ($templates->isEmpty())
        <p class="text-center text-muted py-5">No templates yet - create one above.</p>
    @endif
@endsection
