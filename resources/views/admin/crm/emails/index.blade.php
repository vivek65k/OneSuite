@extends('layouts.admin')

@section('title', 'Emails')

@section('content')
    <x-page-title title="Emails" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Logged" :value="$total" icon="ti-mail" tone="indigo" />
        <x-kpi-card label="Sent" :value="$sentCount" icon="ti-mail-forward" tone="sky" />
        <x-kpi-card label="Received" :value="$receivedCount" icon="ti-mail" tone="success" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.emails.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-mail"></i> Emails</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search subject" aria-label="Search emails">
                    </div>
                    <select name="direction" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Direction">
                        <option value="">Any direction</option>
                        <option value="outbound" @selected($direction === 'outbound')>Sent</option>
                        <option value="inbound" @selected($direction === 'inbound')>Received</option>
                    </select>
                    <a href="{{ route('admin.crm.emails.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Log Email</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Subject</th><th>Related To</th><th>Direction</th><th>When</th><th>Owner</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($emails as $email)
                        <tr>
                            <td><a href="{{ route('admin.crm.emails.edit', $email) }}" class="fw-semibold">{{ $email->subject }}</a></td>
                            <td>{{ $email->relatedLabel ?? '—' }}</td>
                            <td><span class="tag tone-{{ $email->direction === 'inbound' ? 'success' : 'sky' }}">{{ $email->direction === 'inbound' ? 'Received' : 'Sent' }}</span></td>
                            <td>{{ $email->due_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td>{{ $email->owner?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.emails.edit', $email) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.emails.destroy', $email) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this email log?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-mail font-24 d-block mb-2"></i>No emails logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($emails->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $emails->links() }}</div></div>
        @endif
    </div>
@endsection
