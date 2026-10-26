@extends('layouts.admin')

@section('title', 'Complaints')

@section('content')
    <x-page-title title="Complaints" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Open Complaints" :value="$openCount" icon="ti-message-report" tone="rose" />
        <x-kpi-card label="Overdue" :value="$overdueCount" icon="ti-alert-triangle" tone="amber" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.complaints.index') }}">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search complaints" aria-label="Search">
                    </div>
                    <a href="{{ route('admin.crm.cases.create', ['type' => 'complaint']) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Log Complaint</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Case #</th><th>Subject</th><th>Account / Contact</th><th>Status</th><th>Assigned To</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($complaints as $complaint)
                        <tr>
                            <td class="font-13 text-muted">{{ $complaint->case_number }}</td>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.cases.edit', $complaint) }}">{{ $complaint->subject }}</a></td>
                            <td class="text-muted font-13">{{ $complaint->account?->name ?? $complaint->contact?->name ?? '—' }}</td>
                            <td><span class="tag tone-{{ $complaint->status === 'resolved' || $complaint->status === 'closed' ? 'success' : 'rose' }}">{{ $statuses[$complaint->status] ?? $complaint->status }}</span></td>
                            <td class="text-muted font-13">{{ $complaint->assignedTo?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.cases.edit', $complaint) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.cases.destroy', $complaint) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this complaint?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-message-report font-24 d-block mb-2"></i>No complaints logged.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($complaints->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $complaints->links() }}</div></div>
        @endif
    </div>
@endsection
