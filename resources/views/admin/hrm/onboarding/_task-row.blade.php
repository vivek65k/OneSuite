{{--
    One onboarding task as a checklist row.
    Expects: $task; optional $showEmployee, $showCategory, $showAssignee (bools), $avatarTones.
--}}
@php
    $showEmployee = $showEmployee ?? true;
    $showCategory = $showCategory ?? false;
    $showAssignee = $showAssignee ?? true;
    $avatarTones = $avatarTones ?? ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];

    $due = $task->due_date;
    $isDone = $task->is_completed;
    $isOverdue = ! $isDone && $due && $due->isPast() && ! $due->isToday();
    $catMeta = \App\Support\Onboarding::meta($task->category);
    $canToggle = $task->assigned_to_user_id === auth()->id() || auth()->user()->can('update_onboarding_task');
    $employee = $task->employee;
    $initials = collect(preg_split('/\s+/', trim((string) $employee?->name)))
        ->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<tr class="check-row {{ $isDone ? 'is-done' : '' }} {{ $isOverdue ? 'is-overdue' : '' }}">
    <td class="check-cell">
        @if ($canToggle)
            <form action="{{ route('admin.onboarding.tasks.toggle', $task) }}" method="POST">
                @csrf
                <button type="submit" class="check-toggle tone-{{ $catMeta['tone'] }}" title="{{ $isDone ? 'Reopen task' : 'Mark complete' }}" aria-label="{{ $isDone ? 'Reopen' : 'Complete' }}: {{ $task->title }}">
                    <i class="ti ti-check"></i>
                </button>
            </form>
        @else
            <span class="check-toggle is-static tone-{{ $catMeta['tone'] }}" aria-hidden="true"><i class="ti ti-check"></i></span>
        @endif
    </td>
    <td>
        <div class="check-title">{{ $task->title }}</div>
        @if ($task->description)
            <div class="list-subtext check-desc">{{ Str::limit($task->description, 110) }}</div>
        @endif
        @if ($isDone && $task->completed_at)
            <div class="list-subtext text-success"><i class="ti ti-circle-check"></i> Done {{ $task->completed_at->diffForHumans() }}@if ($task->completedBy) by {{ $task->completedBy->name }}@endif</div>
        @endif
    </td>
    @if ($showCategory)
        <td><span class="tag tone-{{ $catMeta['tone'] }}"><i class="ti {{ $catMeta['icon'] }}"></i>{{ $catMeta['short'] }}</span></td>
    @endif
    @if ($showEmployee)
        <td>
            @if ($employee)
                <div class="list-user">
                    <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                    <div class="list-user-text">
                        <div class="list-user-name"><a href="{{ route('admin.employees.onboarding.index', $employee) }}">{{ $employee->name }}</a></div>
                        <div class="list-user-meta">{{ $employee->department?->name ?? $employee->designation ?? $employee->employee_code }}</div>
                    </div>
                </div>
            @else
                <span class="text-muted">&mdash;</span>
            @endif
        </td>
    @endif
    @if ($showAssignee)
        <td>
            @if ($task->assigned_role)
                <span class="tag tag-outline tone-{{ $task->assigned_role === 'hr' ? 'rose' : 'sky' }}">{{ \App\Models\OnboardingTask::ROLES[$task->assigned_role] ?? $task->assigned_role }}</span>
            @endif
            @if ($task->assignedTo)
                <div class="list-subtext mt-1"><i class="ti ti-user"></i> {{ $task->assignedTo->name }}</div>
            @elseif (! $task->assigned_role)
                <span class="text-muted font-12">Unassigned</span>
            @endif
        </td>
    @endif
    <td class="text-nowrap">
        @if ($due)
            <div class="{{ $isDone ? 'text-muted' : '' }}">{{ $due->format('M j, Y') }}</div>
            @unless ($isDone)
                @if ($isOverdue)
                    <span class="countdown tone-danger"><i class="ti ti-alert-triangle"></i> {{ (int) $due->diffInDays(now()->startOfDay()) }}d overdue</span>
                @elseif ($due->isToday())
                    <span class="countdown tone-amber"><i class="ti ti-clock"></i> Due today</span>
                @elseif ($due->lte(now()->addDays(7)))
                    <span class="countdown tone-sky"><i class="ti ti-clock"></i> in {{ (int) now()->startOfDay()->diffInDays($due) }}d</span>
                @endif
            @endunless
        @else
            <span class="text-muted font-12">No due date</span>
        @endif
    </td>
    <td class="text-end text-nowrap">
        <div class="list-row-actions">
            @can('delete', $task)
                <form action="{{ route('admin.onboarding.tasks.destroy', $task) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this task?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                </form>
            @endcan
        </div>
    </td>
</tr>
