@extends('layouts.admin')

@section('title', 'Shift Master')

@php
    use App\Support\ShiftTime;

    $listUrl = fn (array $overrides = []) => route('admin.shifts.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $currentStatus = (string) ($state['status'] ?? '');
    $tabs = [
        '' => ['label' => 'All shifts', 'tone' => 'indigo'],
        'active' => ['label' => 'Active', 'tone' => 'success'],
        'inactive' => ['label' => 'Inactive', 'tone' => 'slate'],
        'night' => ['label' => 'Night', 'tone' => 'violet'],
        'flexible' => ['label' => 'Flexible', 'tone' => 'teal'],
    ];
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $canCreate = auth()->user()->can('create', \App\Models\Shift::class);
    $hours = [0, 3, 6, 9, 12, 15, 18, 21, 24];
@endphp

@section('content')
    <x-page-title title="Shift Master" />

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @error('shift')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="kpi-strip">
        <x-kpi-card label="Active shifts" :value="$counts['active']" icon="ti-clock" tone="indigo" :hint="$counts['inactive'] ? $counts['inactive'].' inactive' : 'All shifts active'" :href="$listUrl(['status' => 'active'])" />
        <x-kpi-card label="Rostered this week" :value="$rosteredThisWeek" icon="ti-users" tone="teal" hint="Employees with a shift" :href="route('admin.shift-roster.weekly')" />
        <x-kpi-card label="Night shifts" :value="$counts['night']" icon="ti-moon" tone="violet" hint="Run past midnight" :href="$listUrl(['status' => 'night'])" />
        <x-kpi-card label="Flexible shifts" :value="$counts['flexible']" icon="ti-arrows-right-left" tone="amber" hint="With a start window" :href="$listUrl(['status' => 'flexible'])" />
    </div>

    {{-- 24h coverage of all active shifts --}}
    @if ($coverage->isNotEmpty())
        <div class="card side-list tone-indigo mb-3">
            <div class="side-list-head">
                <span class="side-list-icon"><i class="ti ti-chart-bar"></i></span>
                <span class="fw-semibold">Day coverage</span>
                <span class="list-subtext ms-auto">Active shifts across 24 hours</span>
            </div>
            <div class="card-body">
                <div class="coverage">
                    <div class="coverage-axis">
                        <span></span>
                        <div class="coverage-scale">
                            @foreach ($hours as $h)
                                <span style="left: {{ $h / 24 * 100 }}%">{{ $h === 24 ? '' : date('ga', mktime($h, 0)) }}</span>
                            @endforeach
                        </div>
                    </div>
                    @foreach ($coverage as $shift)
                        @php $style = ShiftTime::style($shift); @endphp
                        <div class="coverage-row">
                            <span class="coverage-name text-truncate" title="{{ $shift->name }}">{{ $shift->code }}</span>
                            <div class="shift-track">
                                @foreach ($hours as $h)
                                    <span class="shift-track-tick" style="left: {{ $h / 24 * 100 }}%"></span>
                                @endforeach
                                @foreach (ShiftTime::segments($shift->start_time, $shift->end_time) as $seg)
                                    <span class="shift-track-bar tone-{{ $style['tone'] }}" style="left: {{ $seg['left'] }}%; width: {{ $seg['width'] }}%"
                                          title="{{ $shift->name }} · {{ ShiftTime::format($shift->start_time) }} – {{ ShiftTime::format($shift->end_time) }}"></span>
                                @endforeach
                                <span class="shift-track-now" style="left: {{ round((now()->hour * 60 + now()->minute) / 1440 * 100, 2) }}%" title="Now"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view mb-3">
        <form method="GET" action="{{ route('admin.shifts.index') }}">
            @if ($currentStatus !== '')
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Shift filter">
                    @foreach ($tabs as $value => $tab)
                        <a href="{{ $listUrl(['status' => $value === '' ? null : $value]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $currentStatus === (string) $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $counts[$value === '' ? 'all' : $value] }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search name or code" aria-label="Search shifts">
                    </div>
                    @if ($canCreate)
                        <a href="{{ route('admin.shifts.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add shift</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    @if ($shifts->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-indigo"><i class="ti ti-clock"></i></span>
                <h5>{{ $counts['all'] ? 'No shifts match' : 'No shifts yet' }}</h5>
                <p>
                    @if ($counts['all'])
                        <a href="{{ route('admin.shifts.index') }}">Clear the filters</a> to see every shift.
                    @else
                        Define your working hours - General, Morning, Night and so on - then assign them on the roster.
                    @endif
                </p>
                @if ($canCreate && ! $counts['all'])
                    <a href="{{ route('admin.shifts.create') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-plus font-14 me-1"></i> Add shift</a>
                @endif
            </div>
        </div>
    @else
        <div class="policy-grid">
            @foreach ($shifts as $shift)
                @php
                    $style = ShiftTime::style($shift);
                    $net = ShiftTime::netHours($shift);
                    $span = ShiftTime::spanMinutes($shift->start_time, $shift->end_time);
                    $startPct = ShiftTime::toMinutes($shift->start_time) / 1440 * 100;
                    $inUse = (int) ($totalRosters[$shift->id] ?? 0);
                @endphp
                <article class="policy-card tone-{{ $style['tone'] }} {{ $shift->is_active ? '' : 'is-inactive' }}">
                    <div class="policy-card-head">
                        <span class="policy-card-icon"><i class="ti {{ $style['icon'] }}"></i></span>
                        <div class="min-w-0 flex-grow-1">
                            <h5 class="policy-card-title">
                                @can('update', $shift)
                                    <a href="{{ route('admin.shifts.edit', $shift) }}">{{ $shift->name }}</a>
                                @else
                                    {{ $shift->name }}
                                @endcan
                            </h5>
                            <div class="policy-card-sub">
                                <span class="list-code">{{ $shift->code }}</span>
                                <span class="tag tone-{{ $style['tone'] }}">{{ $style['label'] }}</span>
                                @if ($branchNames->isNotEmpty() && $shift->branch_id)
                                    <span class="list-icon-text"><i class="ti ti-building-store"></i>{{ $branchNames[$shift->branch_id] ?? 'Branch' }}</span>
                                @endif
                            </div>
                        </div>
                        @can('update', $shift)
                            <form method="POST" action="{{ route('admin.shifts.toggle', $shift) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="switch-btn {{ $shift->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $shift->is_active ? 'true' : 'false' }}"
                                        title="{{ $shift->is_active ? 'Active - click to deactivate' : 'Inactive - click to activate' }}">
                                    <span class="visually-hidden">{{ $shift->is_active ? 'Deactivate' : 'Activate' }} {{ $shift->name }}</span>
                                </button>
                            </form>
                        @endcan
                    </div>

                    <div class="shift-times">
                        <div>
                            <span class="shift-time-label">Starts</span>
                            <span class="shift-time">{{ ShiftTime::format($shift->start_time) }}</span>
                        </div>
                        <i class="ti ti-arrow-right text-muted"></i>
                        <div>
                            <span class="shift-time-label">Ends{{ ShiftTime::crossesMidnight($shift->start_time, $shift->end_time) ? ' (next day)' : '' }}</span>
                            <span class="shift-time">{{ ShiftTime::format($shift->end_time) }}</span>
                        </div>
                        <div class="ms-auto text-end">
                            <span class="shift-time-label">Working</span>
                            <span class="shift-time tone-text">{{ $fmt($net) }}<small>h</small></span>
                        </div>
                    </div>

                    {{-- 24h track: shift span, break (centred), grace after start --}}
                    <div class="shift-track shift-track-card">
                        @foreach (ShiftTime::segments($shift->start_time, $shift->end_time) as $seg)
                            <span class="shift-track-bar tone-{{ $style['tone'] }}" style="left: {{ $seg['left'] }}%; width: {{ $seg['width'] }}%"></span>
                        @endforeach
                        @if ($shift->grace_minutes)
                            <span class="shift-track-grace" style="left: {{ $startPct }}%; width: {{ min($span, $shift->grace_minutes) / 1440 * 100 }}%" title="{{ $shift->grace_minutes }} min grace"></span>
                        @endif
                        @if ($shift->break_minutes && $span + ShiftTime::toMinutes($shift->start_time) <= 1440)
                            <span class="shift-track-break" style="left: {{ (ShiftTime::toMinutes($shift->start_time) + ($span - $shift->break_minutes) / 2) / 1440 * 100 }}%; width: {{ $shift->break_minutes / 1440 * 100 }}%" title="{{ $shift->break_minutes }} min break"></span>
                        @endif
                    </div>
                    <div class="shift-track-scale"><span>12a</span><span>6a</span><span>12p</span><span>6p</span><span>12a</span></div>

                    <div class="policy-card-rules">
                        <span class="rule-chip is-yes tone-slate"><i class="ti ti-coffee"></i> {{ $shift->break_minutes }}m break</span>
                        <span class="rule-chip is-yes tone-slate"><i class="ti ti-clock"></i> {{ $shift->grace_minutes }}m grace</span>
                        @if ($shift->is_flexible)
                            <span class="rule-chip is-yes tone-teal"><i class="ti ti-arrows-right-left"></i> Flexible ±{{ $shift->flexible_window_minutes ?? 0 }}m</span>
                        @endif
                        @if ($shift->full_day_hours)
                            <span class="rule-chip is-yes tone-indigo" title="Hours needed for a full / half day">Full {{ $fmt($shift->full_day_hours) }}h · Half {{ $fmt($shift->half_day_hours ?? 0) }}h</span>
                        @endif
                    </div>

                    <div class="policy-card-stats">
                        <div><strong>{{ (int) ($thisWeek[$shift->id] ?? 0) }}</strong><span>People this week</span></div>
                        <div><strong>{{ (int) ($upcoming[$shift->id] ?? 0) }}</strong><span>Upcoming slots</span></div>
                        <div><strong>{{ $shift->overtime_rules_count }}</strong><span>Overtime rules</span></div>
                    </div>

                    <div class="policy-card-actions">
                        <a href="{{ route('admin.shift-roster.assign') }}" class="btn btn-light btn-sm"><i class="ti ti-user-plus font-14 me-1"></i> Assign</a>
                        <div class="ms-auto d-flex gap-1">
                            @can('update', $shift)
                                <a href="{{ route('admin.shifts.edit', $shift) }}" class="btn-icon btn-icon-accent" title="Edit"><i class="ti ti-pencil font-16"></i></a>
                            @endcan
                            @can('delete', $shift)
                                <form action="{{ route('admin.shifts.destroy', $shift) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm({{ $inUse ? json_encode('This shift has roster assignments and cannot be deleted - deactivate it instead. Try anyway?') : json_encode('Delete this shift?') }});">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon-danger" title="{{ $inUse ? 'In use - deactivate instead of deleting' : 'Delete' }}"><i class="ti ti-trash font-16"></i></button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
