@extends('layouts.admin')

@section('title', 'Tasks')

@php
    $priorityTone = fn ($p) => match ($p) {
        'high' => 'rose',
        'low' => 'slate',
        default => 'amber',
    };
@endphp

@section('content')
    <x-page-title title="Tasks" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Open Tasks" :value="$total" icon="ti-list-check" tone="indigo" />
        <x-kpi-card label="Overdue" :value="$overdueCount" icon="ti-alert-triangle" tone="rose" />
        <x-kpi-card label="Due Today" :value="$dueTodayCount" icon="ti-calendar-event" tone="amber" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.crm.tasks.index') }}">
            @if ($sort !== 'due_at')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            @if ($direction !== 'asc')<input type="hidden" name="direction" value="{{ $direction }}">@endif
            <div class="list-toolbar">
                <div class="segmented" role="group" aria-label="Scope">
                    <a href="{{ route('admin.crm.tasks.index', array_merge(request()->query(), ['scope' => null])) }}" class="segmented-item {{ $scope === 'all' ? 'active' : '' }}">All</a>
                    <a href="{{ route('admin.crm.tasks.index', array_merge(request()->query(), ['scope' => 'mine'])) }}" class="segmented-item {{ $scope === 'mine' ? 'active' : '' }}">My Tasks</a>
                    <a href="{{ route('admin.crm.tasks.index', array_merge(request()->query(), ['scope' => 'team'])) }}" class="segmented-item {{ $scope === 'team' ? 'active' : '' }}">Team Tasks</a>
                </div>
                @if ($scope === 'team' && $teams->isNotEmpty())
                    <select name="team_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                        <option value="">My team(s)</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" @selected((string) $teamId === (string) $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                @endif
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Search subject" aria-label="Search tasks">
                    </div>
                    <select name="status" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Status">
                        <option value="pending" @selected($status === 'pending')>Pending</option>
                        <option value="completed" @selected($status === 'completed')>Completed</option>
                        <option value="" @selected($status === '')>All statuses</option>
                    </select>
                    <a href="{{ route('admin.crm.tasks.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add Task</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <x-th-sort column="subject" :sort="$sort" :direction="$direction" label="Subject" />
                        <th>Related To</th>
                        <x-th-sort column="priority" :sort="$sort" :direction="$direction" label="Priority" />
                        <x-th-sort column="due_at" :sort="$sort" :direction="$direction" label="Due" />
                        <th>Owner</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tasks as $task)
                        <tr>
                            <td>
                                <a href="{{ route('admin.crm.tasks.edit', $task) }}" class="fw-semibold">{{ $task->subject }}</a>
                                @if ($task->is_recurring)<span class="tag tone-indigo ms-1" title="Recurring"><i class="ti ti-repeat"></i></span>@endif
                                @if ($task->status === 'completed')<div><span class="tag tone-success">Completed</span></div>@endif
                            </td>
                            <td>{{ $task->relatedLabel ?? '—' }}</td>
                            <td><span class="tag tone-{{ $priorityTone($task->priority) }}">{{ \App\Models\Crm\Activity::PRIORITIES[$task->priority] ?? $task->priority }}</span></td>
                            <td class="{{ $task->due_at && $task->due_at->isPast() && $task->status === 'pending' ? 'text-danger fw-semibold' : '' }}">{{ $task->due_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td>{{ $task->owner?->name ?? '—' }}</td>
                            <td class="text-end">
                                @if ($task->status === 'pending')
                                    <form action="{{ route('admin.crm.tasks.complete', $task) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-icon btn-icon-success" title="Mark complete"><i class="ti ti-check font-16"></i></button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.crm.tasks.edit', $task) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-edit font-16"></i></a>
                                <form action="{{ route('admin.crm.tasks.destroy', $task) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this task?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-list-check font-24 d-block mb-2"></i>No tasks found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tasks->hasPages())
            <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $tasks->links() }}</div></div>
        @endif
    </div>
@endsection
