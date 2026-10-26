@extends('layouts.admin')

@section('title', 'SMS / WhatsApp Campaigns')

@section('content')
    <x-page-title title="SMS / WhatsApp Campaigns" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="SMS Campaigns" :value="$smsCount" icon="ti-message-circle" tone="indigo" />
        <x-kpi-card label="WhatsApp Campaigns" :value="$whatsappCount" icon="ti-brand-whatsapp" tone="success" />
        <x-kpi-card label="Messages Sent" :value="$totalSent" icon="ti-send" tone="sky" />
        <x-kpi-card label="Responses" :value="$totalResponded" icon="ti-message" tone="amber" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.sms-campaigns.index') }}">
            <div class="list-toolbar">
                <div class="segmented" role="group" aria-label="Channel">
                    <a href="{{ request()->fullUrlWithQuery(['channel' => null]) }}" class="segmented-item {{ $channelFilter === '' ? 'active' : '' }}">All</a>
                    <a href="{{ request()->fullUrlWithQuery(['channel' => 'sms']) }}" class="segmented-item {{ $channelFilter === 'sms' ? 'active' : '' }}">SMS</a>
                    <a href="{{ request()->fullUrlWithQuery(['channel' => 'whatsapp']) }}" class="segmented-item {{ $channelFilter === 'whatsapp' ? 'active' : '' }}">WhatsApp</a>
                </div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search campaigns" aria-label="Search">
                    </div>
                    <a href="{{ route('admin.crm.campaigns.create', ['type' => 'sms']) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Campaign</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Name</th><th>Channel</th><th>Status</th><th>Sent</th><th>Responded</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.campaigns.show', $campaign) }}">{{ $campaign->name }}</a></td>
                            <td><span class="tag tone-{{ $campaign->type === 'whatsapp' ? 'success' : 'indigo' }}"><i class="ti {{ $campaign->type === 'whatsapp' ? 'ti-brand-whatsapp' : 'ti-message-circle' }}"></i> {{ ucfirst($campaign->type) }}</span></td>
                            <td><span class="tag tone-{{ $campaign->status === 'active' ? 'success' : ($campaign->status === 'completed' ? 'sky' : ($campaign->status === 'paused' ? 'amber' : 'slate')) }}">{{ \App\Models\Crm\Campaign::STATUSES[$campaign->status] ?? $campaign->status }}</span></td>
                            <td>{{ $campaign->sent_count }}</td>
                            <td>{{ $campaign->responded_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.campaigns.edit', $campaign) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.campaigns.destroy', $campaign) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-message-circle font-24 d-block mb-2"></i>No SMS/WhatsApp campaigns yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($campaigns->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $campaigns->links() }}</div></div>
        @endif
    </div>
@endsection
