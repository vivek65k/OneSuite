@extends('layouts.admin')

@section('title', 'Integrations')

@section('content')
    <x-page-title title="Integrations" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif

    <p class="text-muted font-13 mb-3"><i class="ti ti-info-circle"></i> These are configuration placeholders only - no outbound calls are made. Enabling an integration marks it ready for a connector to be wired to it.</p>

    <div class="row g-3">
        @foreach ($integrations as $key => $integration)
            <div class="col-md-6">
                <div class="card list-view h-100">
                    <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plug"></i> {{ $keys[$key] }}</div></div>
                    <div class="list-filter-panel">
                        <form method="POST" action="{{ route('admin.inventory.integrations.update', $key) }}">
                            @csrf
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_enabled" value="1" @checked($integration->is_enabled)>
                                <label class="form-check-label">Enabled</label>
                            </div>
                            <label class="form-label font-11 mb-0">Endpoint URL</label>
                            <input type="url" name="endpoint_url" class="form-control form-control-sm mb-2" value="{{ $integration->endpoint_url }}" placeholder="https://...">
                            <label class="form-label font-11 mb-0">API Key</label>
                            <input type="text" name="api_key" class="form-control form-control-sm mb-2" value="{{ $integration->api_key }}" placeholder="Secret key">
                            <label class="form-label font-11 mb-0">Notes</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm mb-2">{{ $integration->notes }}</textarea>
                            <button type="submit" class="btn btn-de-primary text-light btn-sm">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
