@extends('admin.hrm.employees.layout')

@section('title', $employee->name.' - Onboarding')

@php
    $allTasks = $employee->onboardingTasks;
    $doneCount = $allTasks->where('is_completed', true)->count();
    $percent = $allTasks->count() ? (int) round($doneCount / $allTasks->count() * 100) : 0;
    $overdueCount = $allTasks->filter(fn ($t) => ! $t->is_completed && $t->due_date?->isPast() && ! $t->due_date->isToday())->count();
    $canEdit = auth()->user()->can('update', $employee);
@endphp

@section('employee-content')
    {{-- Overall onboarding progress --}}
    <div class="section-hero tone-indigo">
        <span class="section-hero-icon"><i class="ti ti-rocket"></i></span>
        <div class="section-hero-text">
            <h4>Onboarding progress</h4>
            <p>
                @if ($allTasks->isEmpty())
                    No onboarding tasks yet - add them to each checklist below.
                @elseif ($percent === 100)
                    All {{ $allTasks->count() }} tasks complete. Welcome aboard!
                @else
                    {{ $doneCount }} of {{ $allTasks->count() }} tasks done{{ $overdueCount ? ' · '.$overdueCount.' overdue' : '' }}.
                @endif
            </p>
            <div class="category-bars">
                @foreach (\App\Models\OnboardingTask::CATEGORIES as $key => $label)
                    @php
                        $meta = \App\Support\Onboarding::meta($key);
                        $catTasks = $tasksByCategory->get($key, collect());
                        $catPercent = $catTasks->count() ? (int) round($catTasks->where('is_completed', true)->count() / $catTasks->count() * 100) : 0;
                    @endphp
                    <a href="#cat-{{ $key }}" class="category-bar tone-{{ $meta['tone'] }}" title="{{ $label }}: {{ $catTasks->where('is_completed', true)->count() }}/{{ $catTasks->count() }}">
                        <i class="ti {{ $meta['icon'] }}"></i>
                        <span class="category-bar-track"><span style="width: {{ $catPercent }}%"></span></span>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="progress-ring" style="--value: {{ $percent }}" role="img" aria-label="{{ $percent }}% complete">
            <span>{{ $percent }}<small>%</small></span>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach
        </div>
    @endif

    @foreach (\App\Models\OnboardingTask::CATEGORIES as $key => $label)
        @php
            $meta = \App\Support\Onboarding::meta($key);
            $tasks = $tasksByCategory->get($key, collect());
            $catDone = $tasks->where('is_completed', true)->count();
        @endphp
        <div class="card form-section tone-{{ $meta['tone'] }}" id="cat-{{ $key }}">
            <div class="form-section-head form-section-head-compact">
                <span class="form-section-icon form-section-icon-sm"><i class="ti {{ $meta['icon'] }}"></i></span>
                <h5 class="form-section-title flex-grow-1">{{ $label }}</h5>
                <span class="checklist-count">{{ $catDone }}/{{ $tasks->count() }}</span>
                @if ($canEdit)
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#add-{{ $key }}" aria-expanded="false" aria-controls="add-{{ $key }}">
                        <i class="ti ti-plus font-14 me-1"></i> Add task
                    </button>
                @endif
            </div>

            @if ($canEdit)
                <div class="collapse" id="add-{{ $key }}">
                    <form action="{{ route('admin.employees.onboarding.tasks.store', $employee) }}" method="POST" class="inline-add-form row g-2 align-items-end">
                        @csrf
                        <input type="hidden" name="category" value="{{ $key }}">
                        <div class="col-md-5">
                            <label class="form-label">Task</label>
                            <input type="text" name="title" class="form-control form-control-sm" placeholder="What needs to happen?" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Owner role</label>
                            <select name="assigned_role" class="form-select form-select-sm">
                                <option value="">Anyone</option>
                                @foreach (\App\Models\OnboardingTask::ROLES as $roleValue => $roleLabel)
                                    <option value="{{ $roleValue }}">{{ $roleLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Assigned to</label>
                            <select name="assigned_to_user_id" class="form-select form-select-sm">
                                <option value="">Unassigned</option>
                                @foreach ($users as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Due</label>
                            <input type="date" name="due_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-de-primary btn-sm text-light w-100" title="Add task"><i class="ti ti-plus"></i></button>
                        </div>
                    </form>
                </div>
            @endif

            @if ($tasks->isEmpty())
                <div class="checklist-empty"><i class="ti {{ $meta['icon'] }}"></i> No {{ strtolower($meta['short']) }} tasks yet</div>
            @else
                <div class="table-responsive">
                    <table class="table list-table check-table mb-0">
                        <tbody>
                            @foreach ($tasks as $task)
                                @include('admin.hrm.onboarding._task-row', ['task' => $task, 'showEmployee' => false, 'showAssignee' => true])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach

    {{-- Probation --}}
    <div class="card form-section tone-lime" id="probation">
        <div class="form-section-head form-section-head-compact">
            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-shield-check"></i></span>
            <h5 class="form-section-title flex-grow-1">Probation</h5>
            @if ($canEdit)
                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#add-probation" aria-expanded="false" aria-controls="add-probation">
                    <i class="ti ti-plus font-14 me-1"></i> Add record
                </button>
            @endif
        </div>

        @if ($canEdit)
            <div class="collapse" id="add-probation">
                <form action="{{ route('admin.employees.onboarding.probation.store', $employee) }}" method="POST" class="inline-add-form row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Start date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ optional($employee->joining_date)->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Review date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ optional($employee->joining_date)->copy()?->addMonths(6)->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            @foreach (\App\Models\ProbationRecord::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected($value === 'on_probation')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Remarks</label>
                        <input type="text" name="remarks" class="form-control form-control-sm" maxlength="1000">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-de-primary btn-sm text-light w-100" title="Add record"><i class="ti ti-plus"></i></button>
                    </div>
                </form>
            </div>
        @endif

        @if ($employee->probationRecords->isEmpty())
            <div class="checklist-empty"><i class="ti ti-shield-check"></i> No probation record yet</div>
        @else
            <div class="table-responsive">
                <table class="table list-table mb-0">
                    <tbody>
                        @foreach ($employee->probationRecords->sortByDesc('start_date') as $record)
                            @include('admin.hrm.onboarding.probation-records._row', ['record' => $record, 'showEmployee' => false])
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
