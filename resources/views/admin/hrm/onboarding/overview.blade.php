@extends('layouts.admin')

@section('title', 'Onboarding')

@php
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $initialsOf = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

@section('content')
    <x-page-title title="Onboarding" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="New hires onboarding" :value="$kpis['new_hires']" icon="ti-rocket" tone="indigo" hint="With open tasks" />
        <x-kpi-card label="Open tasks" :value="$kpis['open']" icon="ti-list-check" tone="sky"
                    :hint="$kpis['done_this_week'].' completed this week'" />
        <x-kpi-card label="Overdue tasks" :value="$kpis['overdue']" icon="ti-alert-triangle" tone="{{ $kpis['overdue'] ? 'danger' : 'success' }}"
                    :hint="$kpis['overdue'] ? 'Need attention' : 'All on track'" />
        <x-kpi-card label="Probation reviews" :value="$kpis['reviews_due']" icon="ti-shield-check" tone="lime" hint="Due in the next 30 days"
                    :href="route('admin.probation-records.index', ['review' => 'next_30', 'per_page' => 25])" />
    </div>

    {{-- Per-category progress --}}
    <div class="category-grid">
        @foreach (\App\Models\OnboardingTask::CATEGORIES as $key => $label)
            @php
                $meta = \App\Support\Onboarding::meta($key);
                $row = $byCategory->get($key);
                $total = (int) ($row->total ?? 0);
                $done = (int) ($row->done ?? 0);
                $overdue = (int) ($row->overdue ?? 0);
                $percent = $total ? (int) round($done / $total * 100) : 0;
            @endphp
            <a href="{{ route('admin.onboarding.category.index', $key) }}" class="category-card tone-{{ $meta['tone'] }}">
                <div class="category-card-top">
                    <span class="category-card-icon"><i class="ti {{ $meta['icon'] }}"></i></span>
                    <div class="progress-ring progress-ring-sm" style="--value: {{ $percent }}" aria-hidden="true"><span>{{ $percent }}<small>%</small></span></div>
                </div>
                <div class="category-card-title">{{ $label }}</div>
                <div class="category-card-meta">
                    @if ($total)
                        {{ $done }} / {{ $total }} done
                        @if ($overdue)
                            <span class="text-danger fw-semibold">· {{ $overdue }} overdue</span>
                        @endif
                    @else
                        No tasks yet
                    @endif
                </div>
                <div class="category-card-bar"><span style="width: {{ $percent }}%"></span></div>
            </a>
        @endforeach
    </div>

    <div class="row g-3">
        {{-- New hires and their progress --}}
        <div class="col-xl-8">
            <div class="card list-view h-100">
                <div class="list-toolbar">
                    <div class="list-toolbar-title">
                        <span class="list-toolbar-title-icon tone-indigo"><i class="ti ti-users"></i></span>
                        <div>
                            <div class="fw-semibold">New hire progress</div>
                            <div class="text-muted font-12">Least complete first</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Employee</th>
                                <th>Checklist</th>
                                <th style="min-width: 160px">Progress</th>
                                <th>Next up</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($hires as $hire)
                                @php $employee = $hire->employee; @endphp
                                <tr>
                                    <td>
                                        <div class="list-user">
                                            <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($employee->name) }}</span>
                                            <div class="list-user-text">
                                                <div class="list-user-name"><a href="{{ route('admin.employees.onboarding.index', $employee) }}">{{ $employee->name }}</a></div>
                                                <div class="list-user-meta">
                                                    {{ $employee->department?->name ?? $employee->designation ?? $employee->employee_code }}
                                                    @if ($employee->joining_date)
                                                        <span class="list-meta-sep">&middot;</span> joined {{ $employee->joining_date->diffForHumans() }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="category-dots">
                                            @foreach ($hire->categories as $key => $state)
                                                @php $meta = \App\Support\Onboarding::meta($key); @endphp
                                                <span class="category-dot tone-{{ $meta['tone'] }} {{ $state ? 'is-'.$state : 'is-none' }}"
                                                      title="{{ $meta['short'] }}: {{ $state === 'done' ? 'complete' : ($state === 'open' ? 'in progress' : 'no tasks') }}">
                                                    <i class="ti {{ $state === 'done' ? 'ti-check' : $meta['icon'] }}"></i>
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mini-progress">
                                            <div class="mini-progress-bar {{ $hire->percent === 100 ? 'is-complete' : '' }}"><span style="width: {{ $hire->percent }}%"></span></div>
                                            <span class="mini-progress-label">{{ $hire->done }}/{{ $hire->total }}</span>
                                        </div>
                                        @if ($hire->overdue)
                                            <div class="list-subtext text-danger fw-semibold mt-1"><i class="ti ti-alert-triangle"></i> {{ $hire->overdue }} overdue</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($hire->next)
                                            <div class="font-13 text-truncate" style="max-width: 220px">{{ $hire->next->title }}</div>
                                            <div class="list-subtext">{{ $hire->next->due_date ? 'Due '.$hire->next->due_date->format('M j') : 'No due date' }}</div>
                                        @else
                                            <span class="countdown tone-success"><i class="ti ti-circle-check"></i> All done</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="ti ti-rocket font-24 d-block mb-2"></i>
                                        <div class="fw-semibold text-body mb-1">No onboarding in progress</div>
                                        <span class="font-13">Add tasks from any checklist above to start onboarding a new hire.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4 d-flex flex-column gap-3">
            {{-- Overdue tasks --}}
            <div class="card side-list tone-danger">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-alert-triangle"></i></span>
                    <span class="fw-semibold">Overdue tasks</span>
                    <span class="side-list-count">{{ $kpis['overdue'] }}</span>
                </div>
                @forelse ($overdueTasks as $task)
                    @php $meta = \App\Support\Onboarding::meta($task->category); @endphp
                    <a href="{{ route('admin.onboarding.category.index', ['category' => $task->category, 'status' => 'overdue', 'per_page' => 25]) }}" class="side-list-item">
                        <span class="kanban-dot tone-{{ $meta['tone'] }}"></span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="text-truncate fw-semibold font-13">{{ $task->title }}</div>
                            <div class="list-subtext text-truncate">{{ $task->employee?->name }} · {{ $meta['short'] }}</div>
                        </div>
                        <span class="countdown tone-danger">{{ (int) $task->due_date->diffInDays(now()->startOfDay()) }}d</span>
                    </a>
                @empty
                    <div class="side-list-empty"><i class="ti ti-circle-check"></i> Nothing overdue</div>
                @endforelse
            </div>

            {{-- Probation reviews --}}
            <div class="card side-list tone-lime">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-shield-check"></i></span>
                    <span class="fw-semibold">Probation reviews due</span>
                    <span class="side-list-count">{{ $kpis['reviews_due'] }}</span>
                </div>
                @forelse ($reviews as $record)
                    @php $due = \App\Support\Onboarding::probationDueDate($record); @endphp
                    <a href="{{ route('admin.probation-records.edit', $record) }}" class="side-list-item">
                        <span class="list-avatar list-avatar-{{ $avatarTones[crc32($record->employee?->employee_code.$record->employee?->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initialsOf($record->employee?->name) }}</span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="text-truncate fw-semibold font-13">{{ $record->employee?->name }}</div>
                            <div class="list-subtext">{{ $record->status === 'extended' ? 'Extended review' : 'Review' }} {{ $due->format('M j, Y') }}</div>
                        </div>
                        @if ($due->isPast() && ! $due->isToday())
                            <span class="countdown tone-danger">Overdue</span>
                        @else
                            <span class="countdown tone-lime">{{ $due->isToday() ? 'Today' : 'in '.(int) now()->startOfDay()->diffInDays($due).'d' }}</span>
                        @endif
                    </a>
                @empty
                    <div class="side-list-empty"><i class="ti ti-circle-check"></i> No reviews due soon</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
