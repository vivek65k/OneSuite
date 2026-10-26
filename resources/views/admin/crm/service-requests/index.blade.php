@extends('layouts.admin')

@section('title', 'Service Requests')

@section('content')
    <x-page-title title="Service Requests" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Open Requests" :value="$openCount" icon="ti-headset" tone="indigo" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.service-requests.index') }}">
            <div class="list-toolbar">
                <div class="segmented" role="group" aria-label="Status">
                    <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="segmented-item {{ $statusFilter === '' ? 'active' : '' }}">All</a>
                    @foreach ($statuses as $key => $label)
                        <a href="{{ request()->fullUrlWithQuery(['status' => $key]) }}" class="segmented-item {{ $statusFilter === $key ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search requests" aria-label="Search">
                    </div>
                    <a href="{{ route('admin.crm.cases.create', ['type' => 'service_request']) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Request</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Case #</th><th>Subject</th><th>Account / Contact</th><th>Status</th><th>Assigned To</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($requests as $req)
                        <tr>
                            <td class="font-13 text-muted">{{ $req->case_number }}</td>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.cases.edit', $req) }}">{{ $req->subject }}</a></td>
                            <td class="text-muted font-13">{{ $req->account?->name ?? $req->contact?->name ?? '—' }}</td>
                            <td><span class="tag tone-{{ $req->status === 'resolved' || $req->status === 'closed' ? 'success' : 'sky' }}">{{ $statuses[$req->status] ?? $req->status }}</span></td>
                            <td class="text-muted font-13">{{ $req->assignedTo?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.cases.edit', $req) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.cases.destroy', $req) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this request?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-headset font-24 d-block mb-2"></i>No service requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $requests->links() }}</div></div>
        @endif
    </div>
@endsection
