@extends('layouts.admin')

@section('title', $lead->name)

@php
    $statusTones = ['new' => 'sky', 'contacted' => 'amber', 'qualified' => 'indigo', 'unqualified' => 'rose', 'converted' => 'success'];
    $typeIcon = fn ($type) => match ($type) {
        'call' => 'ti-phone',
        'meeting' => 'ti-video',
        'email' => 'ti-mail',
        'note' => 'ti-notes',
        default => 'ti-list-check',
    };
@endphp

@section('content')
    <x-page-title title="Lead Details" :breadcrumbs="['Leads' => route('admin.crm.leads.index')]" />

    <div class="record-header">
        <a href="{{ route('admin.crm.leads.index') }}" class="record-back btn-icon" title="Back"><i class="ti ti-arrow-left font-16"></i></a>
        <x-avatar :person="$lead" class="record-avatar" />
        <div class="record-header-text">
            <div class="record-header-title">{{ $lead->name }}</div>
            <div class="record-header-meta">
                <span class="tag tone-{{ $statusTones[$lead->status] ?? 'slate' }}">{{ \App\Models\Crm\Lead::STATUSES[$lead->status] ?? $lead->status }}</span>
                @if ($lead->source)<span>via {{ $lead->source->name }}</span>@endif
                @if ($lead->branch)<span>&middot; {{ $lead->branch->name }} branch</span>@endif
                @if (session('status'))<span class="text-success"><i class="ti ti-check"></i> {{ session('status') }}</span>@endif
            </div>
        </div>
        <div class="record-header-actions">
            <a href="{{ route('admin.crm.leads.edit', $lead) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-pencil font-14 me-1"></i> Edit</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-4"><div class="text-muted font-12">Email</div><div class="fw-semibold">{{ $lead->email ?: '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted font-12">Phone</div><div class="fw-semibold">{{ $lead->phone ?: '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted font-12">Company</div><div class="fw-semibold">{{ $lead->company_name ?: '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted font-12">Assigned To</div><div class="fw-semibold">{{ $lead->assignedTo?->name ?? '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted font-12">Score</div><div class="fw-semibold">{{ $lead->score }}</div></div>
                        <div class="col-md-4"><div class="text-muted font-12">Campaign</div><div class="fw-semibold">{{ $lead->campaign?->name ?? '—' }}</div></div>
                    </div>
                    @if ($lead->notes)
                        <hr>
                        <div class="text-muted font-12 mb-1">Notes</div>
                        <div style="white-space: pre-wrap;">{{ $lead->notes }}</div>
                    @endif
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
                            <p>Calls, emails, meetings and notes logged against this lead will show up here.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card form-section tone-violet">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-list-check"></i></span>
                    <h5 class="form-section-title">Qualification (BANT)</h5>
                </div>
                <div class="form-section-body">
                    <dl class="record-meta mb-0">
                        <dt>Budget</dt><dd>{{ $lead->budget ?: '—' }}</dd>
                        <dt>Need confirmed</dt><dd>{{ $lead->has_need ? 'Yes' : 'No' }}</dd>
                        <dt>Authority confirmed</dt><dd>{{ $lead->has_authority ? 'Yes' : 'No' }}</dd>
                        <dt>Timeline</dt><dd>{{ \App\Models\Crm\Lead::TIMELINES[$lead->timeline] ?? '—' }}</dd>
                    </dl>
                    <a href="{{ route('admin.crm.lead-qualification.index') }}" class="btn btn-light btn-sm w-100 mt-2">Edit Qualification</a>
                </div>
            </div>
        </div>
    </div>
@endsection
