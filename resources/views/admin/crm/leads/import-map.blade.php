@extends('layouts.admin')

@section('title', 'Import Leads - Map Fields')

@section('content')
    <x-page-title title="Import Leads" :breadcrumbs="['Leads' => route('admin.crm.leads.index')]" />

    <div class="card form-section tone-indigo" style="max-width: 760px;">
        <div class="form-section-head">
            <span class="form-section-icon"><i class="ti ti-file-import"></i></span>
            <div>
                <h5 class="form-section-title">Map your columns</h5>
                <p class="form-section-sub">Match each lead field to a column from your file. Leave a field blank to skip it.</p>
            </div>
        </div>
        <div class="form-section-body">
            <form method="POST" action="{{ route('admin.crm.leads.import.confirm') }}">
                @csrf
                <div class="row g-3">
                    @foreach ($targetFields as $field => $label)
                        <div class="col-md-6">
                            <label class="form-label">{{ $label }}</label>
                            <select name="mapping[{{ $field }}]" class="form-select form-select-sm">
                                <option value="">— Skip —</option>
                                @foreach ($header as $column)
                                    <option value="{{ $column }}" @selected(strtolower((string) $column) === strtolower($field) || ($field === 'company_name' && strtolower((string) $column) === 'company'))>{{ $column }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>

                <hr class="my-3">
                <div class="fw-semibold font-13 mb-2">Preview (first {{ count($sampleRows) }} rows)</div>
                <div class="table-responsive">
                    <table class="table table-sm list-table mb-0">
                        <thead class="thead-light"><tr>@foreach ($header as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($sampleRows as $row)
                                <tr>@foreach ($header as $i => $column)<td class="font-12">{{ $row[$i] ?? '' }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-upload font-14 me-1"></i> Import Leads</button>
                    <a href="{{ route('admin.crm.leads.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
