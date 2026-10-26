@extends('layouts.admin')

@section('title', 'Follow-up Reminders')

@php
    $typeIcon = fn ($type) => match ($type) {
        'call' => 'ti-phone',
        'meeting' => 'ti-video',
        'email' => 'ti-mail',
        default => 'ti-list-check',
    };
@endphp

@section('content')
    <x-page-title title="Follow-up Reminders" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Due Now" :value="$dueCount" icon="ti-bell" tone="rose" />
        <x-kpi-card label="Upcoming" :value="$upcomingCount" icon="ti-clock" tone="amber" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="segmented" role="group" aria-label="When">
                <a href="{{ route('admin.crm.follow-up-reminders.index', ['when' => 'due']) }}" class="segmented-item {{ $when === 'due' ? 'active' : '' }}">Due Now</a>
                <a href="{{ route('admin.crm.follow-up-reminders.index', ['when' => 'upcoming']) }}" class="segmented-item {{ $when === 'upcoming' ? 'active' : '' }}">Upcoming</a>
                <a href="{{ route('admin.crm.follow-up-reminders.index', ['when' => 'all']) }}" class="segmented-item {{ $when === 'all' ? 'active' : '' }}">All</a>
            </div>
        </div>

        @if ($reminders->isEmpty())
            <div class="empty-state">
                <span class="empty-state-icon tone-success"><i class="ti ti-circle-check"></i></span>
                <h5>All caught up</h5>
                <p>No follow-up reminders waiting.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr><th>Subject</th><th>Related To</th><th>Reminder</th><th>Owner</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($reminders as $reminder)
                            <tr>
                                <td>
                                    <span class="tag tone-indigo"><i class="ti {{ $typeIcon($reminder->type) }}"></i> {{ \App\Models\Crm\Activity::TYPES[$reminder->type] ?? $reminder->type }}</span>
                                    {{ $reminder->subject }}
                                </td>
                                <td>{{ $reminder->relatedLabel ?? '—' }}</td>
                                <td class="{{ $reminder->reminder_at->isPast() ? 'text-danger fw-semibold' : '' }}">{{ $reminder->reminder_at->format('M j, Y g:i A') }}</td>
                                <td>{{ $reminder->owner?->name ?? '—' }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.crm.follow-up-reminders.complete', $reminder) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-icon btn-icon-success" title="Mark complete"><i class="ti ti-check font-16"></i></button>
                                    </form>
                                    <form action="{{ route('admin.crm.follow-up-reminders.snooze', $reminder) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="days" value="1">
                                        <button type="submit" class="btn-icon" title="Snooze 1 day"><i class="ti ti-clock font-16"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($reminders->hasPages())
                <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $reminders->links() }}</div></div>
            @endif
        @endif
    </div>
@endsection
