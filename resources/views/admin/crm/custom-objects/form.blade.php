@extends('layouts.admin')

@php
    $isEditing = $object->exists;
@endphp

@section('title', $isEditing ? 'Edit Custom Object' : 'New Custom Object')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Custom Object' : 'New Custom Object'" :breadcrumbs="['Custom Objects' => route('admin.crm.custom-objects.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 760px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-box"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? 'Edit object' : 'New object' }}</h5>
                <p class="form-section-sub">Define a custom record type and the fields it carries.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.custom-objects.update', $object) : route('admin.crm.custom-objects.store') }}" id="objectForm">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Internal Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $object->name) }}" placeholder="e.g. vendor_asset" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Display Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('label') is-invalid @enderror" name="label" value="{{ old('label', $object->label) }}" placeholder="Vendor Asset" required>
                        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2">{{ old('description', $object->description) }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>Fields</span>
                            <button type="button" class="btn btn-light btn-sm" id="addFieldBtn"><i class="ti ti-plus font-14 me-1"></i> Add Field</button>
                        </label>
                        <div id="fieldsBuilder"></div>
                        <input type="hidden" name="fields_json" id="fieldsJsonInput">
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create object' }}</button>
                    <a href="{{ route('admin.crm.custom-objects.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const builder = document.getElementById('fieldsBuilder');
            const addBtn = document.getElementById('addFieldBtn');
            const hiddenInput = document.getElementById('fieldsJsonInput');
            const form = document.getElementById('objectForm');
            const initial = @json($object->fields_json ?: []);

            function rowHtml(field) {
                field = field || { name: '', type: 'text' };
                return `<div class="row g-2 align-items-end mb-2 field-row">
                    <div class="col-md-6"><input type="text" class="form-control form-control-sm field-name" placeholder="Field name" value="${field.name || ''}"></div>
                    <div class="col-md-5">
                        <select class="form-select form-select-sm field-type">
                            <option value="text" ${field.type === 'text' ? 'selected' : ''}>Text</option>
                            <option value="number" ${field.type === 'number' ? 'selected' : ''}>Number</option>
                            <option value="date" ${field.type === 'date' ? 'selected' : ''}>Date</option>
                        </select>
                    </div>
                    <div class="col-md-1"><button type="button" class="btn-icon btn-icon-danger remove-field" title="Remove"><i class="ti ti-trash font-16"></i></button></div>
                </div>`;
            }

            function addRow(field) { builder.insertAdjacentHTML('beforeend', rowHtml(field)); }

            builder.addEventListener('click', function (e) {
                const btn = e.target.closest('.remove-field');
                if (btn) btn.closest('.field-row').remove();
            });

            addBtn.addEventListener('click', function () { addRow(); });
            (initial.length ? initial : []).forEach(addRow);

            form.addEventListener('submit', function () {
                const rows = [...builder.querySelectorAll('.field-row')].map(row => ({
                    name: row.querySelector('.field-name').value.trim(),
                    type: row.querySelector('.field-type').value,
                })).filter(f => f.name);
                hiddenInput.value = JSON.stringify(rows);
            });
        })();
    </script>
@endsection
