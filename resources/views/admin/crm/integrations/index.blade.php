@extends('layouts.admin')

@section('title', 'Integrations')

@section('content')
    <x-page-title title="Integrations" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card list-view mb-3">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plus"></i> Add integration</div></div>
        <div class="list-filter-panel">
            <form method="POST" action="{{ route('admin.crm.integrations.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4"><label class="form-label">Name</label><input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. SendGrid" required></div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        @foreach (\App\Models\Crm\Integration::TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Webhook URL (optional)</label><input type="url" name="webhook_url" class="form-control form-control-sm"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-de-primary btn-sm text-light w-100">Add</button></div>
            </form>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-plug"></i> All Integrations <span class="list-tab-count">{{ $integrations->count() }}</span></div></div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light"><tr><th>Name</th><th>Type</th><th>Status</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($integrations as $integration)
                        <tr>
                            <td class="fw-semibold">{{ $integration->name }}</td>
                            <td><span class="tag tone-indigo">{{ \App\Models\Crm\Integration::TYPES[$integration->type] ?? $integration->type }}</span></td>
                            <td>
                                <form action="{{ route('admin.crm.integrations.toggle', $integration) }}" method="POST" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="switch-btn {{ $integration->is_connected ? 'is-on' : '' }}" role="switch" aria-checked="{{ $integration->is_connected ? 'true' : 'false' }}" title="{{ $integration->is_connected ? 'Connected' : 'Disconnected' }}">
                                        <span class="visually-hidden">Toggle {{ $integration->name }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <form action="{{ route('admin.crm.integrations.destroy', $integration) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this integration?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Remove"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5"><i class="ti ti-plug font-24 d-block mb-2"></i>No integrations configured yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
