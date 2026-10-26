@extends('layouts.admin')

@section('title', $title)

@php
    $isCategory = $mode === 'category';
    $meta = $isCategory
        ? \App\Support\Onboarding::meta($category)
        : ['short' => $title, 'tone' => $role === 'hr' ? 'rose' : 'sky', 'icon' => $role === 'hr' ? 'ti-users' : 'ti-user-check'];

    // Category pages persist state in the session (PersistsTableState), so
    // their links always carry the full state; my-task pages don't persist.
    $baseRoute = $isCategory ? route('admin.onboarding.category.index', $category) : url()->current();
    $listUrl = function (array $overrides = []) use ($state, $baseRoute, $isCategory) {
        $query = array_filter(array_merge($state, ['page' => null], $overrides), fn ($v) => $v !== null && $v !== '');
        if ($isCategory && $query === []) {
            $query = ['per_page' => 25];
        }

        return $baseRoute.($query ? '?'.http_build_query($query) : '');
    };

    $currentStatus = (string) ($state['status'] ?? '');
    $statusTabs = [
        '' => ['label' => 'All', 'tone' => 'indigo'],
        'pending' => ['label' => 'Pending', 'tone' => 'sky'],
        'overdue' => ['label' => 'Overdue', 'tone' => 'danger'],
        'due_week' => ['label' => 'Due this week', 'tone' => 'amber'],
        'completed' => ['label' => 'Completed', 'tone' => 'success'],
    ];

    $filterLabels = $isCategory
        ? ['search' => 'Search', 'employee_id' => 'Employee', 'assignee' => 'Assignee']
        : ['search' => 'Search', 'category' => 'Category'];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => match ($key) {
            'employee_id' => $employees[$state[$key]] ?? $state[$key],
            'assignee' => $users[$state[$key]] ?? $state[$key],
            'category' => \App\Support\Onboarding::meta($state[$key])['short'],
            default => $state[$key],
        }]);
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));

    $panelKeys = $isCategory ? ['employee_id', 'assignee'] : ['category'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $perPage = (int) ($state['per_page'] ?? 25);
    $canCreate = $isCategory && auth()->user()->can('create', \App\Models\OnboardingTask::class);
    $colspan = $isCategory ? 6 : 6;
@endphp

@section('content')
    <x-page-title :title="$title" :breadcrumbs="['Onboarding' => route('admin.onboarding.overview')]" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    {{-- Hero: what this page is + overall progress --}}
    <div class="section-hero tone-{{ $meta['tone'] }}">
        <span class="section-hero-icon"><i class="ti {{ $meta['icon'] }}"></i></span>
        <div class="section-hero-text">
            <h4>{{ $title }}</h4>
            <p>
                @if ($isCategory)
                    {{ $progress['done'] }} of {{ $progress['total'] }} {{ Str::plural('task', $progress['total']) }} complete across all new hires.
                @else
                    Onboarding tasks assigned to you as {{ $role === 'hr' ? 'HR' : 'a manager' }} - {{ $progress['done'] }} of {{ $progress['total'] }} done.
                @endif
            </p>
        </div>
        <div class="section-hero-stats">
            <div><strong>{{ $statusCounts['pending'] }}</strong><span>Pending</span></div>
            <div class="{{ $statusCounts['overdue'] ? 'is-alert' : '' }}"><strong>{{ $statusCounts['overdue'] }}</strong><span>Overdue</span></div>
            <div><strong>{{ $statusCounts['due_week'] }}</strong><span>Due in 7 days</span></div>
        </div>
        <div class="progress-ring" style="--value: {{ $progress['percent'] }}" role="img" aria-label="{{ $progress['percent'] }}% complete">
            <span>{{ $progress['percent'] }}<small>%</small></span>
        </div>
    </div>

    @if ($canCreate)
        <div class="collapse {{ $errors->any() ? 'show' : '' }}" id="add-task-panel">
            <div class="card form-section tone-{{ $meta['tone'] }} mb-3">
                <div class="form-section-head form-section-head-compact">
                    <span class="form-section-icon form-section-icon-sm"><i class="ti ti-plus"></i></span>
                    <h5 class="form-section-title">Add a {{ strtolower($meta['short']) }} task</h5>
                </div>
                <div class="form-section-body">
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 font-13">
                            @foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach
                        </div>
                    @endif
                    <form method="POST" action="{{ route('admin.onboarding.category.store', $category) }}" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label" for="task-employee">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" id="task-employee" class="form-select" required>
                                <option value="">Select employee</option>
                                @foreach ($employees as $id => $name)
                                    <option value="{{ $id }}" @selected((string) old('employee_id') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="task-title">Task <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="task-title" class="form-control" value="{{ old('title') }}" placeholder="e.g. Share welcome kit and team introductions" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="task-description">Details</label>
                            <input type="text" name="description" id="task-description" class="form-control" value="{{ old('description') }}" maxlength="1000" placeholder="Optional notes for whoever picks this up">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="task-role">Owner role</label>
                            <select name="assigned_role" id="task-role" class="form-select">
                                <option value="">Anyone</option>
                                @foreach (\App\Models\OnboardingTask::ROLES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('assigned_role') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="task-user">Assigned to</label>
                            <select name="assigned_to_user_id" id="task-user" class="form-select">
                                <option value="">Unassigned</option>
                                @foreach ($users as $id => $name)
                                    <option value="{{ $id }}" @selected((string) old('assigned_to_user_id') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="task-due">Due date</label>
                            <input type="date" name="due_date" id="task-due" class="form-control" value="{{ old('due_date') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-de-primary text-light w-100"><i class="ti ti-plus font-14 me-1"></i> Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ $baseRoute }}">
            @foreach (['status' => $currentStatus, 'per_page' => $isCategory ? $perPage : null] as $name => $value)
                @if ((string) $value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Task status">
                    @foreach ($statusTabs as $value => $tab)
                        <a href="{{ $listUrl(['status' => $value === '' ? null : $value]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $currentStatus === (string) $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $statusCounts[$value === '' ? 'all' : $value] ?? 0 }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search task or employee" aria-label="Search tasks">
                    </div>
                    <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                            data-bs-toggle="collapse" data-bs-target="#tasks-filter-panel" aria-expanded="false" aria-controls="tasks-filter-panel">
                        <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                        @if ($panelFilterCount)
                            <span class="list-filter-count">{{ $panelFilterCount }}</span>
                        @endif
                    </button>
                    @if ($canCreate)
                        <button type="button" class="btn btn-de-primary btn-sm text-light px-3" data-bs-toggle="collapse" data-bs-target="#add-task-panel" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}" aria-controls="add-task-panel">
                            <i class="ti ti-plus font-14 me-1"></i> Add task
                        </button>
                    @endif
                </div>
            </div>

            <div class="collapse" id="tasks-filter-panel">
                <div class="list-filter-panel">
                    <div class="row g-3">
                        @if ($isCategory)
                            <div class="col-sm-6 col-lg-4">
                                <label class="form-label">Employee</label>
                                <select name="employee_id" class="form-select form-select-sm">
                                    <option value="">All employees</option>
                                    @foreach ($employees as $id => $name)
                                        <option value="{{ $id }}" @selected((string) ($state['employee_id'] ?? '') === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-4">
                                <label class="form-label">Assignee</label>
                                <select name="assignee" class="form-select form-select-sm">
                                    <option value="">Anyone</option>
                                    @foreach ($users as $id => $name)
                                        <option value="{{ $id }}" @selected((string) ($state['assignee'] ?? '') === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="col-sm-6 col-lg-4">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select form-select-sm">
                                    <option value="">All categories</option>
                                    @foreach (\App\Models\OnboardingTask::CATEGORIES as $key => $label)
                                        <option value="{{ $key }}" @selected(($state['category'] ?? '') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-sm-6 col-lg-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-de-primary btn-sm text-light flex-fill">Apply</button>
                            <a href="{{ $listUrl(array_fill_keys($panelKeys, null)) }}" class="btn btn-light btn-sm">Clear</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $listUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">
                            {{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong>
                            <i class="ti ti-x"></i>
                        </a>
                    @endforeach
                    <a href="{{ $clearAllUrl }}" class="list-clear-all">Clear all</a>
                </div>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover list-table check-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 52px"><span class="visually-hidden">Done</span></th>
                        <th>Task</th>
                        @if (! $isCategory)
                            <th>Category</th>
                        @endif
                        <th>Employee</th>
                        @if ($isCategory)
                            <th>Owner</th>
                        @endif
                        <th>Due</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tasks as $task)
                        @include('admin.hrm.onboarding._task-row', ['showCategory' => ! $isCategory, 'showAssignee' => $isCategory])
                    @empty
                        <tr>
                            <td colspan="{{ $colspan }}" class="text-center text-muted py-5">
                                <i class="ti {{ $meta['icon'] }} font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">
                                    @if ($currentStatus === 'overdue')
                                        Nothing overdue - nice work
                                    @elseif ($activeFilters->isNotEmpty() || $currentStatus !== '')
                                        No tasks match
                                    @elseif ($isCategory)
                                        No {{ strtolower($meta['short']) }} tasks yet
                                    @else
                                        No tasks assigned to you
                                    @endif
                                </div>
                                @if ($activeFilters->isNotEmpty())
                                    <span class="font-13"><a href="{{ $clearAllUrl }}">Clear filters</a></span>
                                @elseif ($canCreate && $currentStatus === '')
                                    <span class="font-13">Use <strong>Add task</strong> to build this checklist for a new hire.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($isCategory)
            <div class="list-footer">
                <div class="list-per-page dropdown">
                    <span class="text-muted font-13">Rows per page</span>
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">{{ $perPage }}</button>
                    <ul class="dropdown-menu">
                        @foreach ([10, 25, 50, 100] as $option)
                            <li><a class="dropdown-item {{ $perPage === $option ? 'active' : '' }}" href="{{ $listUrl(['per_page' => $option]) }}">{{ $option }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div class="ms-auto">{{ $tasks->links() }}</div>
            </div>
        @endif
    </div>
@endsection
