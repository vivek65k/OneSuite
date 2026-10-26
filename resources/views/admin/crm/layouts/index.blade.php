@extends('layouts.admin')

@section('title', 'Layout & Forms')

@section('content')
    <x-page-title title="Layout &amp; Forms" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-filter-panel">
            <form method="GET" action="{{ route('admin.crm.layouts.index') }}" class="d-flex gap-2 align-items-end">
                <div class="flex-fill">
                    <label class="form-label">Entity</label>
                    <select name="entity" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach ($entities as $key => $label)
                            <option value="{{ $key }}" @selected($entity === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card form-section tone-indigo" style="max-width: 640px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-layout"></i></span>
            <div>
                <h5 class="form-section-title">{{ $entities[$entity] }} form fields</h5>
                <p class="form-section-sub">Toggle which fields show on the {{ strtolower($entities[$entity]) }} create/edit form.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ route('admin.crm.layouts.update') }}" id="layoutForm">
                @csrf
                <input type="hidden" name="entity" value="{{ $entity }}">
                <div id="fieldsBuilder">
                    @foreach ($fields as $i => $field)
                        <div class="form-check form-switch mb-2 field-row">
                            <input class="form-check-input field-visible" type="checkbox" role="switch" @checked($field['visible']) data-field="{{ $field['field'] }}">
                            <label class="form-check-label">{{ $field['field'] }}</label>
                        </div>
                    @endforeach
                </div>
                <input type="hidden" name="fields_json" id="fieldsJsonInput">
                <button type="submit" class="btn btn-de-primary text-light px-4 mt-2">Save Layout</button>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('layoutForm');
            form.addEventListener('submit', function () {
                const rows = [...document.querySelectorAll('.field-row')].map(row => ({
                    field: row.querySelector('.field-visible').dataset.field,
                    visible: row.querySelector('.field-visible').checked,
                }));
                document.getElementById('fieldsJsonInput').value = JSON.stringify(rows);
            });
        })();
    </script>
@endsection
