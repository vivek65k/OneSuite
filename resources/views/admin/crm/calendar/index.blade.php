@extends('layouts.admin')

@section('title', 'Calendar')

@php
    $typeTone = fn ($type) => match ($type) {
        'call' => 'sky',
        'meeting' => 'violet',
        'email' => 'teal',
        default => 'indigo',
    };
    $typeIcon = fn ($type) => match ($type) {
        'call' => 'ti-phone',
        'meeting' => 'ti-video',
        'email' => 'ti-mail',
        default => 'ti-list-check',
    };
@endphp

@section('content')
    <x-page-title title="Calendar" />

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title">
                <a href="{{ route('admin.crm.calendar.index', ['month' => $prevMonth]) }}" class="btn-icon" title="Previous month"><i class="ti ti-chevron-left font-16"></i></a>
                <span class="mx-2">{{ $month->format('F Y') }}</span>
                <a href="{{ route('admin.crm.calendar.index', ['month' => $nextMonth]) }}" class="btn-icon" title="Next month"><i class="ti ti-chevron-right font-16"></i></a>
            </div>
            <div class="list-toolbar-actions">
                <a href="{{ route('admin.crm.calendar.index') }}" class="btn btn-light btn-sm">Today</a>
                <a href="{{ route('admin.crm.tasks.create') }}" class="btn btn-light btn-sm"><i class="ti ti-list-check font-14 me-1"></i> Task</a>
                <a href="{{ route('admin.crm.calls.create') }}" class="btn btn-light btn-sm"><i class="ti ti-phone font-14 me-1"></i> Call</a>
                <a href="{{ route('admin.crm.meetings.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-video font-14 me-1"></i> Meeting</a>
            </div>
        </div>
        <div class="calendar-grid">
            <div class="calendar-grid-head">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                    <div>{{ $day }}</div>
                @endforeach
            </div>
            @foreach ($weeks as $week)
                <div class="calendar-grid-row">
                    @foreach ($week as $day)
                        <div class="calendar-cell {{ $day['date']->month !== $month->month ? 'is-muted' : '' }} {{ $day['date']->toDateString() === $todayStr ? 'is-today' : '' }}">
                            <div class="calendar-cell-date">{{ $day['date']->day }}</div>
                            @foreach ($day['items']->take(3) as $item)
                                <a href="{{ route('admin.crm.'.($item->type === 'task' ? 'tasks' : ($item->type === 'call' ? 'calls' : 'meetings')).'.edit', $item) }}"
                                   class="calendar-chip tone-{{ $typeTone($item->type) }}" title="{{ $item->subject }}">
                                    <i class="ti {{ $typeIcon($item->type) }}"></i> {{ \Illuminate\Support\Str::limit($item->subject, 16) }}
                                </a>
                            @endforeach
                            @if ($day['items']->count() > 3)
                                <div class="font-11 text-muted">+{{ $day['items']->count() - 3 }} more</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .calendar-grid-head, .calendar-grid-row { display: grid; grid-template-columns: repeat(7, 1fr); }
        .calendar-grid-head > div { padding: 0.5rem; font-size: 0.75rem; font-weight: 600; color: var(--text-3); text-align: center; border-bottom: 1px solid var(--border-subtle); }
        .calendar-cell { min-height: 92px; padding: 0.375rem; border: 1px solid var(--border-subtle); border-top: none; border-left: none; }
        .calendar-grid-row:first-child .calendar-cell { border-top: none; }
        .calendar-cell.is-muted { background-color: var(--surface-2); }
        .calendar-cell.is-muted .calendar-cell-date { color: var(--text-3); }
        .calendar-cell.is-today { background-color: var(--accent-subtle); }
        .calendar-cell.is-today .calendar-cell-date { color: var(--accent); font-weight: 700; }
        .calendar-cell-date { font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.25rem; }
        .calendar-chip { display: block; font-size: 0.6875rem; padding: 0.0625rem 0.3125rem; border-radius: 4px; background-color: var(--tone-bg); color: var(--tone); text-decoration: none; margin-bottom: 0.125rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
@endpush
