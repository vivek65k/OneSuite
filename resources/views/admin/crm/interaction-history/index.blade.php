@extends('layouts.admin')

@section('title', 'Interaction History')

@php
    $typeStyle = fn ($type) => match ($type) {
        'call' => ['icon' => 'ti-phone', 'tone' => 'sky'],
        'meeting' => ['icon' => 'ti-video', 'tone' => 'violet'],
        'email' => ['icon' => 'ti-mail', 'tone' => 'teal'],
        'note' => ['icon' => 'ti-notes', 'tone' => 'amber'],
        default => ['icon' => 'ti-list-check', 'tone' => 'indigo'],
    };
@endphp

@section('content')
    <x-page-title title="Interaction History" />

    <div class="card list-view mb-3">
        <div class="list-filter-panel">
            <form method="GET" action="{{ route('admin.crm.interaction-history.index') }}" class="d-flex gap-2 align-items-end">
                <div class="flex-fill">
                    <label class="form-label">Record</label>
                    <select name="relatable" class="form-select form-select-sm" onchange="this.form.submit()">
                        @forelse ($options as $key => $label)
                            <option value="{{ $key }}" @selected($selected === $key)>{{ $label }}</option>
                        @empty
                            <option value="">No records yet</option>
                        @endforelse
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if (! $label)
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-indigo"><i class="ti ti-history"></i></span>
                <h5>No record selected</h5>
                <p>Pick a lead, account, contact, or opportunity to see its full history.</p>
            </div>
        </div>
    @else
        <div class="card list-view">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-history"></i> {{ $label }} <span class="list-tab-count">{{ $events->count() }}</span></div>
            </div>
            <div class="p-3">
                @forelse ($events as $event)
                    @php $style = $typeStyle($event['type']); @endphp
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="avatar-box avatar-md bg-soft-{{ $style['tone'] === 'indigo' ? 'primary' : ($style['tone'] === 'sky' ? 'info' : ($style['tone'] === 'violet' ? 'purple' : ($style['tone'] === 'teal' ? 'success' : 'warning'))) }} rounded-circle flex-shrink-0">
                            <span class="avatar-title bg-transparent"><i class="ti {{ $style['icon'] }} font-18"></i></span>
                        </span>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">{{ $event['subject'] }}</span>
                                @if ($event['status'])
                                    <span class="tag tone-{{ $event['status'] === 'completed' ? 'success' : ($event['status'] === 'cancelled' ? 'rose' : 'amber') }}">{{ \App\Models\Crm\Activity::STATUSES[$event['status']] ?? $event['status'] }}</span>
                                @endif
                            </div>
                            @if ($event['detail'])
                                <div class="text-muted font-13">{{ \Illuminate\Support\Str::limit($event['detail'], 160) }}</div>
                            @endif
                            <div class="text-muted font-12 mt-1">{{ $event['owner'] ?? 'Unknown' }} · {{ $event['date']?->format('M j, Y g:i A') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <span class="empty-state-icon tone-indigo"><i class="ti ti-history"></i></span>
                        <h5>No activity yet</h5>
                        <p>Nothing has been logged against this record yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif
@endsection
