@extends('layouts.admin')

@section('title', 'Email Marketing')

@section('content')
    <x-page-title title="Email Marketing" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Emails Sent" :value="$totalSent" icon="ti-send" tone="indigo" />
        <x-kpi-card label="Avg Open Rate" value="{{ $avgOpenRate }}%" icon="ti-mail-opened" tone="sky" />
        <x-kpi-card label="Avg Click Rate" value="{{ $avgClickRate }}%" icon="ti-click" tone="amber" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.email-marketing.index') }}">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search campaigns" aria-label="Search">
                    </div>
                    <a href="{{ route('admin.crm.campaigns.create', ['type' => 'email']) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Email Campaign</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Name</th><th>Subject</th><th>Status</th><th>Sent</th><th>Open Rate</th><th>Click Rate</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.campaigns.show', $campaign) }}">{{ $campaign->name }}</a></td>
                            <td class="text-muted font-13">{{ $campaign->subject ?: '—' }}</td>
                            <td><span class="tag tone-{{ $campaign->status === 'active' ? 'success' : ($campaign->status === 'completed' ? 'sky' : ($campaign->status === 'paused' ? 'amber' : 'slate')) }}">{{ $statuses[$campaign->status] ?? $campaign->status }}</span></td>
                            <td>{{ $campaign->sent_count }}</td>
                            <td>{{ $campaign->open_rate }}%</td>
                            <td>{{ $campaign->click_rate }}%</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.campaigns.edit', $campaign) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.campaigns.destroy', $campaign) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ti ti-mail font-24 d-block mb-2"></i>No email campaigns yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($campaigns->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $campaigns->links() }}</div></div>
        @endif
    </div>
@endsection
