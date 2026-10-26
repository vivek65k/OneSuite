@extends('layouts.admin')

@php
    $isEditing = $webForm->exists;
    $fields = old('fields_json', $webForm->fields_json ?? []);
@endphp

@section('title', $isEditing ? 'Edit Web Form' : 'New Web Form')

@section('content')
    <x-page-title :title="$isEditing ? 'Edit Web Form' : 'New Web Form'" :breadcrumbs="['Web Forms' => route('admin.crm.web-forms.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 760px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-forms"></i></span>
            <div>
                <h5 class="form-section-title">{{ $isEditing ? 'Edit form' : 'New form' }}</h5>
                <p class="form-section-sub">Fields you define here render on the public form and become lead properties.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ $isEditing ? route('admin.crm.web-forms.update', $webForm) : route('admin.crm.web-forms.store') }}" id="webFormForm">
                @csrf
                @if ($isEditing) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $webForm->name) }}" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Campaign</label>
                        <select name="campaign_id" class="form-select">
                            <option value="">None</option>
                            @foreach ($campaigns as $campaign)
                                <option value="{{ $campaign->id }}" @selected(old('campaign_id', $webForm->campaign_id) == $campaign->id)>{{ $campaign->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1" @checked(old('is_active', $webForm->is_active ?? true))>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Redirect URL (optional)</label>
                        <input type="url" class="form-control" name="redirect_url" value="{{ old('redirect_url', $webForm->redirect_url) }}" placeholder="https://example.com/thank-you">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>Form Fields</span>
                            <button type="button" class="btn btn-light btn-sm" id="addFieldBtn"><i class="ti ti-plus font-14 me-1"></i> Add Field</button>
                        </label>
                        <div id="fieldsBuilder"></div>
                        <input type="hidden" name="fields_json" id="fieldsJsonInput">
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4">{{ $isEditing ? 'Save changes' : 'Create form' }}</button>
                    <a href="{{ route('admin.crm.web-forms.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const builder = document.getElementById('fieldsBuilder');
            const addBtn = document.getElementById('addFieldBtn');
            const hiddenInput = document.getElementById('fieldsJsonInput');
            const form = document.getElementById('webFormForm');
            const initial = @json($fields ?: []);

            function rowHtml(field) {
                field = field || { name: '', label: '', type: 'text', required: false };
                return `<div class="row g-2 align-items-end mb-2 field-row">
                    <div class="col-md-3"><input type="text" class="form-control form-control-sm field-name" placeholder="field name" value="${field.name}"></div>
                    <div class="col-md-3"><input type="text" class="form-control form-control-sm field-label" placeholder="Label" value="${field.label}"></div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm field-type">
                            <option value="text" ${field.type === 'text' ? 'selected' : ''}>Text</option>
                            <option value="email" ${field.type === 'email' ? 'selected' : ''}>Email</option>
                            <option value="tel" ${field.type === 'tel' ? 'selected' : ''}>Phone</option>
                            <option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>Textarea</option>
                        </select>
                    </div>
                    <div class="col-md-2 form-check form-switch ms-2">
                        <input class="form-check-input field-required" type="checkbox" role="switch" ${field.required ? 'checked' : ''}>
                        <label class="form-check-label font-13">Required</label>
                    </div>
                    <div class="col-md-1"><button type="button" class="btn-icon btn-icon-danger remove-field" title="Remove"><i class="ti ti-trash font-16"></i></button></div>
                </div>`;
            }

            function addRow(field) {
                builder.insertAdjacentHTML('beforeend', rowHtml(field));
            }

            builder.addEventListener('click', function (e) {
                const btn = e.target.closest('.remove-field');
                if (btn) btn.closest('.field-row').remove();
            });

            addBtn.addEventListener('click', function () { addRow(); });

            (initial.length ? initial : []).forEach(addRow);

            form.addEventListener('submit', function () {
                const rows = [...builder.querySelectorAll('.field-row')].map(row => ({
                    name: row.querySelector('.field-name').value.trim(),
                    label: row.querySelector('.field-label').value.trim(),
                    type: row.querySelector('.field-type').value,
                    required: row.querySelector('.field-required').checked,
                })).filter(f => f.name);
                hiddenInput.value = JSON.stringify(rows);
            });
        })();
    </script>
@endsection
