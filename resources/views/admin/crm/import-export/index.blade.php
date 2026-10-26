@extends('layouts.admin')

@section('title', 'Import / Export')

@section('content')
    <x-page-title title="Import / Export" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-download"></i> Export Data</div></div>
        <div class="p-3 d-flex flex-wrap gap-2">
            @foreach ($entities as $key => $label)
                <a href="{{ route('admin.crm.import-export.export', $key) }}" class="btn btn-light btn-sm"><i class="ti ti-download font-14 me-1"></i> Export {{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-file-import"></i> Import Leads (CSV)</div></div>
        <div class="p-3">
            <p class="text-muted font-13">CSV columns expected: <code>name, email, phone, company</code>.</p>
            <form method="POST" action="{{ route('admin.crm.import-export.import-leads') }}" enctype="multipart/form-data" class="d-flex gap-2 align-items-end">
                @csrf
                <input type="file" name="file" accept=".csv" class="form-control" style="max-width: 360px;" required>
                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-upload font-14 me-1"></i> Import</button>
            </form>
        </div>
    </div>
@endsection
