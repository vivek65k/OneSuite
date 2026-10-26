@extends('layouts.admin')

@section('title', 'Calls')

@section('content')
    <x-page-title title="Calls" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Calls" :value="$total" icon="ti-phone" tone="indigo" />
        <x-kpi-card label="Inbound" :value="$inboundCount" icon="ti-phone-incoming" tone="success" />
        <x-kpi-card label="Outbound" :value="$outboundCount" icon="ti-phone-outgoing" tone="sky" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.calls.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-phone"></i> Calls</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search subject" aria-label="Search calls">
                    </div>
                    <select name="direction" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Direction">
                        <option value="">Any direction</option>
                        <option value="inbound" @selected($direction === 'inbound')>Inbound</option>
                        <option value="outbound" @selected($direction === 'outbound')>Outbound</option>
                    </select>
                    <a href="{{ route('admin.crm.calls.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Log Call</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Subject</th><th>Related To</th><th>Direction</th><th>When</th><th>Owner</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($calls as $call)
                        <tr>
                            <td><a href="{{ route('admin.crm.calls.edit', $call) }}" class="fw-semibold">{{ $call->subject }}</a></td>
                            <td>{{ $call->relatedLabel ?? '—' }}</td>
                            <td><span class="tag tone-{{ $call->direction === 'inbound' ? 'success' : 'sky' }}"><i class="ti ti-{{ $call->direction === 'inbound' ? 'phone-incoming' : 'phone-outgoing' }}"></i> {{ ucfirst($call->direction) }}</span></td>
                            <td>{{ $call->due_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td>{{ $call->owner?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.calls.edit', $call) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.calls.destroy', $call) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this call log?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-phone font-24 d-block mb-2"></i>No calls logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($calls->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $calls->links() }}</div></div>
        @endif
    </div>
@endsection
