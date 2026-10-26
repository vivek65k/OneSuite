{{--
    One probation record row with a start -> review timeline.
    Expects: $record; optional $showEmployee (bool, default true).
--}}
@php
    $showEmployee = $showEmployee ?? true;
    $meta = \App\Support\Onboarding::PROBATION_META[$record->status] ?? ['tone' => 'slate', 'dot' => 'muted', 'icon' => 'ti-point'];
    $label = \App\Models\ProbationRecord::STATUSES[$record->status] ?? Str::headline($record->status);
    $isActive = in_array($record->status, ['on_probation', 'extended'], true);
    $due = \App\Support\Onboarding::probationDueDate($record);
    $start = $record->start_date;

    $totalDays = max(1, $start->diffInDays($due));
    $elapsed = $isActive ? (int) min(100, max(0, round($start->diffInDays(now(), false) / $totalDays * 100))) : 100;
    $isOverdue = $isActive && $due->isPast() && ! $due->isToday();
    $daysLeft = (int) now()->startOfDay()->diffInDays($due, false);

    $employee = $record->employee;
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $initials = collect(preg_split('/\s+/', trim((string) $employee?->name)))
        ->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<tr>
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
    <td>
        <div class="period-bar tone-{{ $isOverdue ? 'danger' : $meta['tone'] }}" title="{{ $elapsed }}% of the probation period elapsed">
            <span class="period-bar-fill" style="width: {{ $elapsed }}%"></span>
            @if ($isActive && ! $isOverdue)
                <span class="period-bar-now" style="left: {{ $elapsed }}%"></span>
            @endif
        </div>
        <div class="period-bar-labels">
            <span>{{ $start->format('M j, Y') }}</span>
            <span>
                @if ($record->status === 'extended' && $record->extended_to)
                    <s class="text-muted">{{ $record->end_date->format('M j') }}</s>
                @endif
                {{ $due->format('M j, Y') }}
            </span>
        </div>
    </td>
    <td class="text-nowrap">
        @if ($isActive)
            @if ($isOverdue)
                <span class="countdown tone-danger"><i class="ti ti-alert-triangle"></i> {{ abs($daysLeft) }}d overdue</span>
            @elseif ($daysLeft === 0)
                <span class="countdown tone-amber"><i class="ti ti-clock"></i> Review today</span>
            @else
                <span class="countdown tone-{{ $daysLeft <= 30 ? 'amber' : 'sky' }}"><i class="ti ti-clock"></i> {{ $daysLeft }}d left</span>
            @endif
        @elseif ($record->reviewed_at)
            <div class="font-13">{{ $record->reviewed_at->format('M j, Y') }}</div>
            <div class="list-subtext">{{ $record->reviewedBy ? 'by '.$record->reviewedBy->name : 'Reviewed' }}</div>
        @else
            <span class="text-muted font-12">&mdash;</span>
        @endif
        @if ($record->remarks)
            <div class="list-subtext mt-1 text-truncate" style="max-width: 200px" title="{{ $record->remarks }}"><i class="ti ti-notes"></i> {{ $record->remarks }}</div>
        @endif
    </td>
    <td>
        <span class="tag tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i>{{ $label }}</span>
    </td>
    <td class="text-end text-nowrap">
        <div class="list-row-actions">
            @can('update', $record)
                @if ($isActive)
                    <div class="dropdown d-inline-block">
                        <button class="btn-icon btn-icon-success" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Record decision">
                            <i class="ti ti-circle-check font-16"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Probation decision</h6></li>
                            <li>
                                <form method="POST" action="{{ route('admin.probation-records.status', $record) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="confirmed">
                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2"><span class="kanban-dot tone-success"></span> Confirm employee</button>
                                </form>
                            </li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.probation-records.edit', $record) }}#extend"><span class="kanban-dot tone-amber"></span> Extend probation…</a></li>
                            <li>
                                <form method="POST" action="{{ route('admin.probation-records.status', $record) }}" onsubmit="return confirm('Record this probation as terminated?');">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="terminated">
                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger"><span class="kanban-dot tone-danger"></span> Terminate</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endif
                <a href="{{ route('admin.probation-records.edit', $record) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
            @endcan
            @can('delete', $record)
                <form action="{{ route('admin.probation-records.destroy', $record) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this probation record?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                </form>
            @endcan
        </div>
    </td>
</tr>
