@extends('layouts.admin')

@section('title', 'Campaigns')

@section('content')
    <x-page-title title="Campaigns" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Active Campaigns" :value="$activeCount" icon="ti-speakerphone" tone="indigo" />
        <x-kpi-card label="Total Budget" value="{{ number_format($totalBudget, 0) }}" icon="ti-wallet" tone="violet" />
        <x-kpi-card label="Total Spend" value="{{ number_format($totalSpend, 0) }}" icon="ti-receipt" tone="amber" />
        <x-kpi-card label="Messages Sent" :value="$totalSent" icon="ti-send" tone="sky" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.campaigns.index') }}">
            @if ($sort !== 'start_date')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'desc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-speakerphone"></i> All Campaigns <span class="list-tab-count">{{ $campaigns->total() }}</span></div>
                <div class="list-toolbar-actions">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">All Types</option>
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}" @selected($typeFilter === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" @selected($statusFilter === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search campaigns" aria-label="Search campaigns">
                    </div>
                    <a href="{{ route('admin.crm.campaigns.export') }}" class="btn-icon" title="Export CSV"><i class="ti ti-download font-16"></i></a>
                    <a href="{{ route('admin.crm.campaigns.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> New Campaign</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="name" :sort="$sort" :direction="$direction" label="Name" />
                        <x-th-sort column="type" :sort="$sort" :direction="$direction" label="Type" />
                        <x-th-sort column="status" :sort="$sort" :direction="$direction" label="Status" />
                        <x-th-sort column="start_date" :sort="$sort" :direction="$direction" label="Dates" />
                        <th>Members</th>
                        <x-th-sort column="sent_count" :sort="$sort" :direction="$direction" label="Sent" align="end" />
                        <x-th-sort column="budget" :sort="$sort" :direction="$direction" label="Budget" align="end" />
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.crm.campaigns.show', $campaign) }}">{{ $campaign->name }}</a></td>
                            <td><span class="tag tone-indigo">{{ $types[$campaign->type] ?? $campaign->type }}</span></td>
                            <td><span class="tag tone-{{ $campaign->status === 'active' ? 'success' : ($campaign->status === 'completed' ? 'sky' : ($campaign->status === 'paused' ? 'amber' : 'slate')) }}">{{ $statuses[$campaign->status] ?? $campaign->status }}</span></td>
                            <td class="font-13 text-muted">{{ $campaign->start_date?->format('M j, Y') ?? '—' }} @if($campaign->end_date) &rarr; {{ $campaign->end_date->format('M j, Y') }} @endif</td>
                            <td><span class="tag tone-violet">{{ $campaign->members_count }}</span></td>
                            <td class="text-end">{{ $campaign->sent_count }}</td>
                            <td class="text-end">{{ number_format($campaign->budget, 0) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.campaigns.edit', $campaign) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                                <form action="{{ route('admin.crm.campaigns.destroy', $campaign) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="ti ti-speakerphone font-24 d-block mb-2"></i>
                                No campaigns yet.
                                <div><a href="{{ route('admin.crm.campaigns.create') }}">Create your first one</a>.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($campaigns->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $campaigns->links() }}</div></div>
        @endif
    </div>
@endsection
