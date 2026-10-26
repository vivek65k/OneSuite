@extends('layouts.admin')

@section('title', 'New Custom Report')

@section('content')
    <x-page-title title="New Custom Report" :breadcrumbs="['Custom Reports' => route('admin.crm.custom-reports.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 680px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-table"></i></span>
            <div>
                <h5 class="form-section-title">Build a report</h5>
                <p class="form-section-sub">Pick a data source, then a plain table, a pivot table, or a chart.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ route('admin.crm.custom-reports.store') }}" id="reportForm">
                @csrf

                <label class="form-label">Report Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control mb-3 @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus>
                @error('name')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Source <span class="text-danger">*</span></label>
                        <select name="source" id="sourceSelect" class="form-select">
                            @foreach ($sources as $key => $label)
                                <option value="{{ $key }}" @selected(old('source') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Report Type <span class="text-danger">*</span></label>
                        <select name="report_type" id="reportTypeSelect" class="form-select">
                            @foreach ($reportTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('report_type', 'table') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="tableSection">
                    <label class="form-label">Columns <span class="text-danger">*</span></label>
                    <div id="columnsBox" class="mb-3"></div>
                    @error('columns')<div class="text-danger font-13 mb-3">{{ $message }}</div>@enderror
                </div>

                <div id="pivotSection" class="d-none">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Row grouping <span class="text-danger">*</span></label>
                            <select name="pivot_row" class="form-select group-field"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Column grouping (optional)</label>
                            <select name="pivot_col" class="form-select group-field">
                                <option value="">— None (single column) —</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Value field <span class="text-danger">*</span></label>
                            <select name="pivot_value" class="form-select measure-field"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Aggregate</label>
                            <select name="pivot_agg" class="form-select">
                                @foreach ($aggregates as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div id="chartSection" class="d-none">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Chart type</label>
                            <select name="chart_type" class="form-select">
                                @foreach ($chartTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Group by (X axis) <span class="text-danger">*</span></label>
                            <select name="chart_x" class="form-select group-field"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Measure (Y axis) <span class="text-danger">*</span></label>
                            <select name="chart_y" class="form-select measure-field"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Aggregate</label>
                            <select name="chart_agg" class="form-select">
                                @foreach ($aggregates as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-de-primary text-light px-4">Create Report</button>
                <a href="{{ route('admin.crm.custom-reports.index') }}" class="btn btn-light">Cancel</a>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const sourceColumns = @json($sourceColumns);
            const groupFields = @json($groupFields);
            const measureFields = @json($measureFields);
            const sourceSelect = document.getElementById('sourceSelect');
            const typeSelect = document.getElementById('reportTypeSelect');
            const columnsBox = document.getElementById('columnsBox');
            const sections = { table: document.getElementById('tableSection'), pivot: document.getElementById('pivotSection'), chart: document.getElementById('chartSection') };

            function renderColumns() {
                const cols = sourceColumns[sourceSelect.value] || [];
                columnsBox.innerHTML = cols.map(c => `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="columns[]" value="${c}" id="col_${c}" checked>
                        <label class="form-check-label font-13" for="col_${c}">${c}</label>
                    </div>
                `).join('');
            }

            function renderGroupMeasure() {
                const groups = groupFields[sourceSelect.value] || [];
                const measures = measureFields[sourceSelect.value] || [];
                document.querySelectorAll('.group-field').forEach(sel => {
                    const keepBlank = sel.querySelector('option[value=""]');
                    sel.innerHTML = (keepBlank ? keepBlank.outerHTML : '') + groups.map(g => `<option value="${g}">${g}</option>`).join('');
                });
                document.querySelectorAll('.measure-field').forEach(sel => {
                    sel.innerHTML = measures.map(m => `<option value="${m}">${m}</option>`).join('');
                });
            }

            function syncType() {
                Object.entries(sections).forEach(([key, el]) => el.classList.toggle('d-none', key !== typeSelect.value));
            }

            sourceSelect.addEventListener('change', function () { renderColumns(); renderGroupMeasure(); });
            typeSelect.addEventListener('change', syncType);

            renderColumns();
            renderGroupMeasure();
            syncType();
        })();
    </script>
@endsection
