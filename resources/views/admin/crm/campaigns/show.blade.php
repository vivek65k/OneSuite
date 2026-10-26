@extends('layouts.admin')

@section('title', $campaign->name)

@section('content')
    <x-page-title :title="$campaign->name" :breadcrumbs="['Campaigns' => route('admin.crm.campaigns.index')]" />

    <div class="record-header">
        <a href="{{ route('admin.crm.campaigns.index') }}" class="record-back btn-icon" title="Back to campaigns"><i class="ti ti-arrow-left font-16"></i></a>
        <span class="avatar-box record-avatar bg-soft-primary rounded-circle flex-shrink-0">
            <span class="avatar-title bg-transparent"><i class="ti ti-speakerphone"></i></span>
        </span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $campaign->name }}</div>
            <div class="record-header-meta">
                <span class="tag tone-indigo">{{ \App\Models\Crm\Campaign::TYPES[$campaign->type] ?? $campaign->type }}</span>
                <span class="tag tone-{{ $campaign->status === 'active' ? 'success' : ($campaign->status === 'completed' ? 'sky' : ($campaign->status === 'paused' ? 'amber' : 'slate')) }}">{{ \App\Models\Crm\Campaign::STATUSES[$campaign->status] ?? $campaign->status }}</span>
                @if ($campaign->start_date) <span>{{ $campaign->start_date->format('M j, Y') }}@if($campaign->end_date) &rarr; {{ $campaign->end_date->format('M j, Y') }}@endif</span>@endif
                @if (session('status'))<span class="text-success"><i class="ti ti-check"></i> {{ session('status') }}</span>@endif
            </div>
        </div>
        <div class="record-header-actions">
            <form action="{{ route('admin.crm.campaigns.send', $campaign) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-send font-14 me-1"></i> Send to Pending</button>
            </form>
            <a href="{{ route('admin.crm.campaigns.edit', $campaign) }}" class="btn btn-light btn-sm"><i class="ti ti-pencil font-14 me-1"></i> Edit</a>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Members" :value="$campaign->members_count" icon="ti-users" tone="violet" />
        <x-kpi-card label="Sent" :value="$campaign->sent_count" icon="ti-send" tone="indigo" />
        <x-kpi-card label="Open Rate" value="{{ $campaign->open_rate }}%" icon="ti-mail-opened" tone="sky" />
        <x-kpi-card label="Click Rate" value="{{ $campaign->click_rate }}%" icon="ti-click" tone="amber" />
        <x-kpi-card label="Converted" :value="$campaign->converted_count" icon="ti-target" tone="success" />
    </div>

    @if ($campaign->message_body)
        <div class="card mb-3">
            <div class="p-3">
                <div class="fw-semibold mb-1">{{ $campaign->subject ?: 'Message' }}</div>
                <div class="text-muted font-13" style="white-space: pre-wrap;">{{ $campaign->message_body }}</div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-users"></i> Campaign Members <span class="list-tab-count">{{ $members->total() }}</span></div>
            <div class="list-toolbar-actions">
                <a href="{{ route('admin.crm.campaign-members.index', ['campaign_id' => $campaign->id]) }}" class="btn btn-light btn-sm"><i class="ti ti-settings font-14 me-1"></i> Manage Members</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Name</th><th>Type</th><th>Status</th><th>Added</th></tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td class="fw-semibold">{{ $member->member?->name ?? '—' }}</td>
                            <td><span class="tag tone-indigo">{{ array_search($member->member_type, \App\Models\Crm\CampaignMember::MEMBERABLE) ?: 'member' }}</span></td>
                            <td><span class="tag tone-{{ $member->status === 'converted' ? 'success' : ($member->status === 'responded' ? 'sky' : 'slate') }}">{{ \App\Models\Crm\CampaignMember::STATUSES[$member->status] ?? $member->status }}</span></td>
                            <td class="text-muted font-13">{{ $member->added_at?->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-5"><i class="ti ti-users font-24 d-block mb-2"></i>No members yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($members->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $members->links() }}</div></div>
        @endif
    </div>
@endsection
