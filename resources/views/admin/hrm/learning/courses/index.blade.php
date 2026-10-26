@extends('layouts.admin')

@section('title', 'Courses')

@php
    use App\Models\Course;
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $pageUrl = fn (array $overrides = []) => route('admin.courses.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All courses', 'tone' => 'indigo', 'count' => $counts['all']],
        'active' => ['label' => 'Active', 'tone' => 'success', 'count' => $counts['active']],
        'inactive' => ['label' => 'Switched off', 'tone' => 'slate', 'count' => $counts['inactive']],
    ];
    $modeIcon = ['online' => 'ti-device-laptop', 'offline' => 'ti-building', 'hybrid' => 'ti-switch-horizontal'];
    $modeTone = ['online' => 'sky', 'offline' => 'amber', 'hybrid' => 'violet'];
    $activeFilters = collect(['category' => 'Category', 'mode' => 'Delivery', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $key === 'mode' ? (Course::DELIVERY_MODES[$state[$key]] ?? $state[$key]) : $state[$key]]);
    // A steady colour per category, so the same category always looks the same.
    $categoryTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $categoryTone = fn (?string $category) => $category ? $categoryTones[crc32(mb_strtolower($category)) % count($categoryTones)] : 'slate';
    $canCreate = auth()->user()->can('create', Course::class);
    $canSchedule = auth()->user()->can('viewAny', \App\Models\TrainingSession::class);
@endphp

@section('content')
    <x-page-title title="Courses" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Active courses" :value="$kpis['active']" icon="ti-book" tone="indigo" :hint="$kpis['inactive'] ? $kpis['inactive'].' switched off' : ($kpis['active'] ? 'Ready to be scheduled' : 'Add your first course')" :href="$pageUrl(['tab' => 'active'])" />
        <x-kpi-card label="Learning hours on offer" :value="$kpis['hours'] > 0 ? $num($kpis['hours']) : '—'" icon="ti-clock" tone="teal" :hint="$kpis['hours'] > 0 ? 'Across the active courses' : 'No durations set'" />
        <x-kpi-card label="Sessions coming up" :value="$kpis['upcoming']" icon="ti-calendar-event" :tone="$kpis['upcoming'] ? 'success' : 'slate'"
                    :hint="$kpis['never_run'] ? $kpis['never_run'].' active '.Str::plural('course', $kpis['never_run']).' never scheduled' : ($kpis['upcoming'] ? 'On the Training Calendar' : 'Nothing scheduled')" :href="$canSchedule ? route('admin.training-sessions.index') : null" />
        <x-kpi-card label="People enrolled" :value="$kpis['learners']" icon="ti-users" tone="amber" :hint="$kpis['learners'] ? $kpis['completed'].' completed their course' : 'No one enrolled yet'" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.courses.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Course status">
                    @foreach ($tabs as $value => $tab)
                        <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search courses" aria-label="Search courses">
                    </div>
                    @if ($categories->count() > 1)
                        <select name="category" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Category">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected($state['category'] === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    @elseif ($state['category'] !== '')
                        <input type="hidden" name="category" value="{{ $state['category'] }}">
                    @endif
                    <select name="mode" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Delivery mode">
                        <option value="">Any delivery</option>
                        @foreach (Course::DELIVERY_MODES as $value => $label)
                            <option value="{{ $value }}" @selected($state['mode'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($canCreate)
                        <a href="{{ route('admin.courses.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add course</a>
                    @endif
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $pageUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card-body">
            @if ($courses->isEmpty())
                <div class="empty-state py-5">
                    <div class="empty-state-icon"><i class="ti ti-book"></i></div>
                    @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                        <div class="fw-semibold">No courses match</div>
                        <div class="font-13 text-muted"><a href="{{ route('admin.courses.index') }}">Show all courses</a></div>
                    @else
                        <div class="fw-semibold">No courses yet</div>
                        <div class="font-13 text-muted mb-3">A course is something people can be trained on, like "Fire safety" or "Excel basics". Add one, then schedule sessions for it.</div>
                        @if ($canCreate)
                            <a href="{{ route('admin.courses.create') }}" class="btn btn-de-primary text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add the first course</a>
                        @endif
                    @endif
                </div>
            @else
                <div class="course-grid">
                    @foreach ($courses as $course)
                        @php
                            $stat = $stats[$course->id] ?? ['sessions' => 0, 'upcoming' => 0, 'next' => null, 'enrolled' => 0, 'completed' => 0];
                            $tone = $categoryTone($course->category);
                            $used = $stat['sessions'] > 0 || $stat['enrolled'] > 0;
                        @endphp
                        <div class="course-card tone-{{ $tone }} {{ $course->is_active ? '' : 'is-off' }}">
                            <div class="course-card-head">
                                <span class="course-card-icon"><i class="ti ti-book"></i></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="course-card-title" title="{{ $course->title }}">{{ $course->title }}</div>
                                    <div class="course-card-tags">
                                        @if ($course->category)
                                            <a href="{{ $pageUrl(['category' => $course->category]) }}" class="tag tone-{{ $tone }}" title="Show this category">{{ $course->category }}</a>
                                        @endif
                                        <span class="tag tone-{{ $modeTone[$course->delivery_mode] ?? 'slate' }}"><i class="ti {{ $modeIcon[$course->delivery_mode] ?? 'ti-book' }}"></i>{{ Course::DELIVERY_MODES[$course->delivery_mode] ?? $course->delivery_mode }}</span>
                                        @if ($course->duration_hours !== null)
                                            <span class="tag tone-slate"><i class="ti ti-clock"></i>{{ $num($course->duration_hours) }} {{ (float) $course->duration_hours === 1.0 ? 'hour' : 'hours' }}</span>
                                        @endif
                                        @unless ($course->is_active)
                                            <span class="tag tone-slate">Switched off</span>
                                        @endunless
                                    </div>
                                </div>
                            </div>
                            <p class="course-card-text">{{ $course->description ? Str::limit($course->description, 140) : 'No description yet.' }}</p>
                            <div class="course-card-stats">
                                <div><strong>{{ $stat['sessions'] }}</strong><span>{{ Str::plural('session', $stat['sessions']) }}</span></div>
                                <div><strong>{{ $stat['enrolled'] }}</strong><span>enrolled</span></div>
                                <div><strong>{{ $stat['completed'] }}</strong><span>completed</span></div>
                            </div>
                            <div class="course-card-foot">
                                <span class="font-12 {{ $stat['next'] ? 'text-success fw-semibold' : 'text-muted' }}">
                                    @if ($stat['next'])
                                        <i class="ti ti-calendar-event"></i> Next: {{ $stat['next']->format('M j, Y') }}
                                    @elseif ($course->is_active)
                                        {{ $stat['sessions'] ? 'Nothing scheduled' : 'Never scheduled' }}
                                    @else
                                        Not offered
                                    @endif
                                </span>
                                <div class="list-row-actions ms-auto">
                                    @if ($canSchedule && $course->is_active)
                                        <a href="{{ route('admin.training-sessions.index') }}" class="btn-icon btn-icon-success" title="Schedule a session on the Training Calendar"><i class="ti ti-calendar-plus font-16"></i></a>
                                    @endif
                                    @can('update', $course)
                                        <form action="{{ route('admin.courses.toggle', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-icon {{ $course->is_active ? 'btn-icon-success' : '' }}" title="{{ $course->is_active ? 'Switch off (keeps its records)' : 'Switch on' }}"><i class="ti {{ $course->is_active ? 'ti-toggle-right' : 'ti-toggle-left' }} font-18"></i></button>
                                        </form>
                                        <a href="{{ route('admin.courses.edit', $course) }}" class="btn-icon btn-icon-accent" title="Edit course"><i class="ti ti-pencil font-16"></i></a>
                                    @endcan
                                    @unless ($used)
                                        @can('delete', $course)
                                            <form action="{{ route('admin.courses.destroy', $course) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this course? It has no sessions yet.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Delete course"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    @endunless
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @if ($courses->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $courses->links() }}</div></div>
        @endif
    </div>
@endsection
