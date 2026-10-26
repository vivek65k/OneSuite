@extends('layouts.admin')

@section('title', 'Custom Fields')

@section('content')
    <x-page-title title="Custom Fields" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add a custom field</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.custom-fields.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label">Entity</label>
                    <select name="entity" class="form-select form-select-sm">
                        @foreach (\App\Models\Crm\CustomField::ENTITIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Field Name</label><input type="text" name="field_name" class="form-control form-control-sm" placeholder="e.g. vat_number" required></div>
                <div class="col-md-2"><label class="form-label">Label</label><input type="text" name="field_label" class="form-control form-control-sm" placeholder="VAT Number" required></div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="field_type" class="form-select form-select-sm">
                        @foreach (\App\Models\Crm\CustomField::FIELD_TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Options (if Dropdown)</label><input type="text" name="options" class="form-control form-control-sm" placeholder="A, B, C"></div>
                <div class="col-md-1 form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_required">
                    <label class="form-check-label font-13">Required</label>
                </div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-forms"></i> All Fields <span class="list-tab-count">{{ $fields->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Entity</th><th>Field Name</th><th>Label</th><th>Type</th><th>Required</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($fields as $field)
                        <tr>
                            <td><span class="tag tone-indigo">{{ \App\Models\Crm\CustomField::ENTITIES[$field->entity] ?? $field->entity }}</span></td>
                            <td class="font-13 text-muted">{{ $field->field_name }}</td>
                            <td class="fw-semibold">{{ $field->field_label }}</td>
                            <td>{{ \App\Models\Crm\CustomField::FIELD_TYPES[$field->field_type] ?? $field->field_type }}</td>
                            <td>{{ $field->is_required ? 'Yes' : 'No' }}</td>
                            <td>
                                <form action="{{ route('admin.crm.custom-fields.toggle', $field) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="switch-btn {{ $field->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $field->is_active ? 'true' : 'false' }}">
                                        <span class="visually-hidden">Toggle {{ $field->field_label }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.custom-fields.destroy', $field) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this field?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-forms font-24 d-block mb-2"></i>No custom fields yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
