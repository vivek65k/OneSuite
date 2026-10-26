@extends('layouts.admin')

@section('title', $contact->name)

@php
    $typeIcon = fn ($type) => match ($type) {
        'call' => 'ti-phone',
        'meeting' => 'ti-video',
        'email' => 'ti-mail',
        'note' => 'ti-notes',
        default => 'ti-list-check',
    };
@endphp

@section('content')
    <x-page-title title="Contact Details" :breadcrumbs="['Contacts' => route('admin.crm.contacts.index')]" />

    <div class="record-header">
        <a href="{{ route('admin.crm.contacts.index') }}" class="record-back btn-icon" title="Back"><i class="ti ti-arrow-left font-16"></i></a>
        <x-avatar :person="$contact" class="record-avatar" />
        <div class="record-header-text">
            <div class="record-header-title">{{ $contact->name }}</div>
            <div class="record-header-meta">
                @if ($contact->job_title)<span>{{ $contact->job_title }}</span>@endif
                @if ($contact->account)<span>at {{ $contact->account->name }}</span>@endif
                @if (session('status'))<span class="text-success"><i class="ti ti-check"></i> {{ session('status') }}</span>@endif
            </div>
        </div>
        <div class="record-header-actions">
            <a href="{{ route('admin.crm.opportunities.create') }}" class="btn btn-light btn-sm"><i class="ti ti-briefcase font-14 me-1"></i> New Deal</a>
            <a href="{{ route('admin.crm.contacts.edit', $contact) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-pencil font-14 me-1"></i> Edit</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-4"><div class="text-muted font-12">Email</div><div class="fw-semibold">{{ $contact->email ?: '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted font-12">Phone</div><div class="fw-semibold">{{ $contact->phone ?: '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted font-12">Owner</div><div class="fw-semibold">{{ $contact->owner?->name ?? '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted font-12">Account</div><div class="fw-semibold">{{ $contact->account?->name ?? $contact->company_name ?? '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted font-12">Segment</div><div class="fw-semibold">{{ $contact->segment?->name ?? '—' }}</div></div>
                    </div>
                </div>
            </div>

            <div class="card list-view mb-3">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-briefcase"></i> Related Deals <span class="list-tab-count">{{ $opportunities->count() }}</span></div></div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @forelse ($opportunities as $opp)
                                <tr>
                                    <td class="fw-semibold"><a href="{{ route('admin.crm.opportunities.edit', $opp) }}">{{ $opp->name }}</a></td>
                                    <td class="text-muted font-13">{{ $opp->stage?->name ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($opp->amount, 0) }}</td>
                                </tr>
                            @empty
                                <tr><td class="text-center text-muted py-4">No deals linked to this contact.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card list-view">
                <div class="list-toolbar"><div class="list-toolbar-title"><i class="ti ti-history"></i> Activity Timeline <span class="list-tab-count">{{ $timeline->count() }}</span></div></div>
                <div class="p-3">
                    @forelse ($timeline as $event)
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="avatar-box avatar-md bg-soft-primary rounded-circle flex-shrink-0">
                                <span class="avatar-title bg-transparent"><i class="ti {{ $typeIcon($event['type']) }} font-18"></i></span>
                            </span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $event['subject'] }}</div>
                                @if ($event['detail'])<div class="text-muted font-13">{{ \Illuminate\Support\Str::limit($event['detail'], 160) }}</div>@endif
                                <div class="text-muted font-12 mt-1">{{ $event['owner'] ?? 'Unknown' }} &middot; {{ $event['when']?->format('M j, Y g:i A') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <span class="empty-state-icon tone-indigo"><i class="ti ti-history"></i></span>
                            <h5>No activity yet</h5>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
