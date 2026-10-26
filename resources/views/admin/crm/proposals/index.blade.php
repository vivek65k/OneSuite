@extends('layouts.admin')

@section('title', 'Proposal Builder')

@php
    $statusTone = fn ($status) => match ($status) {
        'accepted' => 'success',
        'declined' => 'rose',
        'sent' => 'sky',
        default => 'slate',
    };
@endphp

@section('content')
    <x-page-title title="Proposal Builder" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Proposals" :value="$total" icon="ti-file-text" tone="indigo" />
        <x-kpi-card label="Sent" :value="$sentCount" icon="ti-send" tone="sky" />
        <x-kpi-card label="Accepted" :value="$acceptedCount" icon="ti-circle-check" tone="success" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.proposals.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-file-text"></i> Proposals</div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search title" aria-label="Search proposals">
                    </div>
                    <select name="status" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Status">
                        <option value="">Any status</option>
                        @foreach (\App\Models\Crm\Proposal::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.crm.proposals.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Proposal</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Title</th><th>Account</th><th>Status</th><th>Owner</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($proposals as $proposal)
                        <tr>
                            <td><a href="{{ route('admin.crm.proposals.edit', $proposal) }}" class="fw-semibold">{{ $proposal->title }}</a></td>
                            <td>{{ $proposal->account?->name ?? '—' }}</td>
                            <td><span class="tag tone-{{ $statusTone($proposal->status) }}">{{ \App\Models\Crm\Proposal::STATUSES[$proposal->status] ?? $proposal->status }}</span></td>
                            <td>{{ $proposal->owner?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.proposals.edit', $proposal) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.proposals.destroy', $proposal) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this proposal?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="ti ti-file-text font-24 d-block mb-2"></i>No proposals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($proposals->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $proposals->links() }}</div></div>
        @endif
    </div>
@endsection
