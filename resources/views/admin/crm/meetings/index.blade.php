@extends('layouts.admin')

@section('title', 'Meetings')

@section('content')
    <x-page-title title="Meetings" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Total Meetings" :value="$total" icon="ti-video" tone="indigo" />
        <x-kpi-card label="Upcoming" :value="$upcomingCount" icon="ti-calendar-event" tone="success" :href="route('admin.crm.calendar.index')" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.meetings.index') }}">
            <div class="list-toolbar">
                <div class="segmented" role="group" aria-label="When">
                    <a href="{{ route('admin.crm.meetings.index', ['when' => 'upcoming']) }}" class="segmented-item {{ $when === 'upcoming' ? 'active' : '' }}">Upcoming</a>
                    <a href="{{ route('admin.crm.meetings.index', ['when' => 'past']) }}" class="segmented-item {{ $when === 'past' ? 'active' : '' }}">Past</a>
                    <a href="{{ route('admin.crm.meetings.index', ['when' => 'all']) }}" class="segmented-item {{ $when === 'all' ? 'active' : '' }}">All</a>
                </div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search subject" aria-label="Search meetings">
                    </div>
                    <a href="{{ route('admin.crm.meetings.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Schedule Meeting</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr><th>Subject</th><th>Related To</th><th>Start</th><th>End</th><th>Owner</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($meetings as $meeting)
                        <tr>
                            <td><a href="{{ route('admin.crm.meetings.edit', $meeting) }}" class="fw-semibold">{{ $meeting->subject }}</a></td>
                            <td>{{ $meeting->relatedLabel ?? '—' }}</td>
                            <td>{{ $meeting->start_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td>{{ $meeting->end_at?->format('g:i A') ?? '—' }}</td>
                            <td>{{ $meeting->owner?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.crm.meetings.edit', $meeting) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.meetings.destroy', $meeting) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this meeting?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Cancel"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-video font-24 d-block mb-2"></i>No meetings found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($meetings->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $meetings->links() }}</div></div>
        @endif
    </div>
@endsection
