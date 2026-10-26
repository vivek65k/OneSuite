{{--
    Shared list for Punch logs and Geo attendance.
    Expects: $records, $state, $statusCounts, $kpis, $employees, $departments, $geo (bool), $route.
--}}
@php
    use App\Support\Attendance;

    $filterLabels = ['search' => 'Search', 'status' => 'Status', 'employee_id' => 'Employee', 'department_id' => 'Department', 'source' => 'Source', 'from' => 'From', 'to' => 'To'];
    $activeFilters = collect($filterLabels)
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => match ($key) {
            'status' => Attendance::label($state[$key]),
            'employee_id' => $employees[$state[$key]] ?? $state[$key],
            'department_id' => $departments[$state[$key]] ?? $state[$key],
            'source' => \App\Models\AttendanceRecord::SOURCES[$state[$key]] ?? $state[$key],
            'from', 'to' => \Illuminate\Support\Carbon::parse($state[$key])->format('M j, Y'),
            default => $state[$key],
        }]);

    // Every link carries the full state - a query-less URL makes
    // PersistsTableState restore the previous session state instead.
    $listUrl = fn (array $overrides = []) => route($route, array_filter(
        array_merge($state, ['page' => null], $overrides) + ['per_page' => 25],
        fn ($value) => $value !== null && $value !== ''
    ));

    $currentStatus = (string) ($state['status'] ?? '');
    $tabs = collect(['' => ['label' => 'All', 'tone' => 'indigo']])
        ->merge(collect(Attendance::STATUS_META)->map(fn ($meta, $status) => ['label' => Attendance::label($status), 'tone' => $meta['tone']]))
        ->filter(fn ($tab, $key) => $key === '' || ($statusCounts[$key] ?? 0) > 0 || $currentStatus === (string) $key);

    $panelKeys = $geo ? ['employee_id', 'department_id', 'from', 'to'] : ['employee_id', 'department_id', 'source', 'from', 'to'];
    $panelFilterCount = collect($panelKeys)->filter(fn ($key) => filled($state[$key] ?? null))->count();
    $clearAllUrl = $listUrl(array_fill_keys(array_keys($filterLabels), null));
    $perPage = (int) ($state['per_page'] ?? 25);
    $avatarTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $mapLink = fn ($lat, $lng) => 'https://www.google.com/maps?q='.$lat.','.$lng;
    $avgHours = $kpis['avg_hours'] !== null ? round((float) $kpis['avg_hours'], 1) : null;
@endphp

<div class="kpi-strip">
    <x-kpi-card :label="$geo ? 'Geo check-ins' : 'Punch records'" :value="$kpis['records']" :icon="$geo ? 'ti-map-pin' : 'ti-clock'" tone="indigo" hint="Matching current filters" />
    <x-kpi-card label="Employees" :value="$kpis['employees']" icon="ti-users" tone="teal" />
    <x-kpi-card label="Checked out" :value="$kpis['records'] ? round($kpis['with_checkout'] / $kpis['records'] * 100).'%' : '—'" icon="ti-logout" tone="sky"
                :hint="$kpis['with_checkout'].' of '.$kpis['records'].' have a check-out'" />
    @if ($geo)
        <x-kpi-card label="Average hours" :value="$avgHours !== null ? $avgHours.' h' : '—'" icon="ti-clock" tone="violet" />
    @else
        <x-kpi-card label="Sources" :value="$kpis['by_source']->count()" icon="ti-fingerprint" tone="violet"
                    :hint="$kpis['by_source']->map(fn ($count, $source) => (\App\Models\AttendanceRecord::SOURCES[$source] ?? $source).' '.$count)->implode(' · ') ?: 'No records'" />
    @endif
</div>

<div class="card list-view">
    <form method="GET" action="{{ route($route) }}">
        @foreach (['per_page' => $perPage, 'status' => $currentStatus] as $name => $value)
            @if ((string) $value !== '')
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach

        <div class="list-toolbar">
            <nav class="list-tabs" aria-label="Attendance status">
                @foreach ($tabs as $value => $tab)
                    <a href="{{ $listUrl(['status' => $value === '' ? null : $value]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $currentStatus === (string) $value ? 'active' : '' }}">
                        {{ $tab['label'] }}
                        <span class="list-tab-count">{{ $statusCounts[$value === '' ? 'all' : $value] ?? 0 }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="list-toolbar-actions">
                <div class="list-search">
                    <i class="ti ti-search"></i>
                    <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Search name or code" aria-label="Search">
                </div>
                <button type="button" class="btn btn-light btn-sm list-filter-toggle {{ $panelFilterCount ? 'has-filters' : '' }}"
                        data-bs-toggle="collapse" data-bs-target="#log-filter-panel" aria-expanded="false" aria-controls="log-filter-panel">
                    <i class="ti ti-adjustments-horizontal font-15"></i> Filters
                    @if ($panelFilterCount)
                        <span class="list-filter-count">{{ $panelFilterCount }}</span>
                    @endif
                </button>
                @unless ($geo)
                    <x-export-csv-button route="admin.attendance.punch-logs.export" />
                @endunless
            </div>
        </div>

        {{-- Quick date ranges --}}
        <div class="list-segment-bar">
            <div class="segmented" role="group" aria-label="Date range">
                @php
                    $ranges = [
                        'Today' => [now()->toDateString(), now()->toDateString()],
                        'Yesterday' => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
                        'This week' => [now()->startOfWeek()->toDateString(), now()->toDateString()],
                        'This month' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
                        'All time' => [null, null],
                    ];
                @endphp
                @foreach ($ranges as $label => [$from, $to])
                    <a href="{{ $listUrl(['from' => $from, 'to' => $to]) }}" class="segmented-item {{ ($state['from'] ?? null) === $from && ($state['to'] ?? null) === $to ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="collapse" id="log-filter-panel">
            <div class="list-filter-panel">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select form-select-sm">
                            <option value="">All employees</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected((string) ($state['employee_id'] ?? '') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected((string) ($state['department_id'] ?? '') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @unless ($geo)
                        <div class="col-sm-6 col-lg-2">
                            <label class="form-label">Source</label>
                            <select name="source" class="form-select form-select-sm">
                                <option value="">Any source</option>
                                @foreach (\App\Models\AttendanceRecord::SOURCES as $value => $label)
                                    <option value="{{ $value }}" @selected(($state['source'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endunless
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label">Between</label>
                        <div class="list-date-range">
                            <input type="date" name="from" class="form-control form-control-sm" value="{{ $state['from'] ?? '' }}" aria-label="From">
                            <span>to</span>
                            <input type="date" name="to" class="form-control form-control-sm" value="{{ $state['to'] ?? '' }}" aria-label="To">
                        </div>
                    </div>
                    <div class="col-lg-2 d-flex align-items-end gap-2">
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

    <div class="table-responsive table-responsive-sticky">
        <table class="table table-hover list-table mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Check in</th>
                    <th>Check out</th>
                    <th>Hours</th>
                    <th>Status</th>
                    @unless ($geo)
                        <th>Source</th>
                    @endunless
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    @php
                        $meta = Attendance::meta($record->status);
                        $employee = $record->employee;
                        $hours = $record->work_hours ?? Attendance::hoursBetween($record->check_in_time, $record->check_out_time);
                        $initials = collect(preg_split('/\s+/', trim((string) $employee?->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                    @endphp
                    <tr>
                        <td class="text-nowrap">
                            <div class="when-cell">
                                <span class="when-block tone-{{ $meta['tone'] }}">
                                    <span class="when-block-month">{{ $record->date->format('M') }}</span>
                                    <span class="when-block-day">{{ $record->date->format('j') }}</span>
                                </span>
                                <span class="list-subtext">{{ $record->date->format('D') }}</span>
                            </div>
                        </td>
                        <td>
                            @if ($employee)
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-{{ $avatarTones[crc32($employee->employee_code.$employee->name) % count($avatarTones)] }}" aria-hidden="true">{{ $initials }}</span>
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></div>
                                        <div class="list-user-meta">{{ $employee->employee_code }}@if ($employee->department) · {{ $employee->department->name }}@endif</div>
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @if ($record->check_in_time)
                                <span class="punch-time is-in"><i class="ti ti-login"></i> {{ Attendance::formatTime($record->check_in_time) }}</span>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                            @if ($geo && $record->check_in_lat && $record->check_in_lng)
                                <a href="{{ $mapLink($record->check_in_lat, $record->check_in_lng) }}" target="_blank" rel="noopener" class="map-chip" title="Open check-in location in Google Maps">
                                    <i class="ti ti-map-pin"></i> {{ number_format($record->check_in_lat, 4) }}, {{ number_format($record->check_in_lng, 4) }}
                                </a>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @if ($record->check_out_time)
                                <span class="punch-time is-out"><i class="ti ti-logout"></i> {{ Attendance::formatTime($record->check_out_time) }}</span>
                            @elseif ($record->check_in_time && $record->date->isToday())
                                <span class="countdown tone-sky">Still in</span>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                            @if ($geo && $record->check_out_lat && $record->check_out_lng)
                                <a href="{{ $mapLink($record->check_out_lat, $record->check_out_lng) }}" target="_blank" rel="noopener" class="map-chip" title="Open check-out location in Google Maps">
                                    <i class="ti ti-map-pin"></i> {{ number_format($record->check_out_lat, 4) }}, {{ number_format($record->check_out_lng, 4) }}
                                </a>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @if ($hours)
                                <span class="hours-figure">{{ rtrim(rtrim(number_format((float) $hours, 2), '0'), '.') }}<small>h</small></span>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td><span class="tag tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i>{{ Attendance::label($record->status) }}</span></td>
                        @unless ($geo)
                            <td>
                                @php $sourceMeta = Attendance::SOURCE_META[$record->source] ?? ['tone' => 'slate', 'icon' => 'ti-point']; @endphp
                                <span class="tag tag-outline tone-{{ $sourceMeta['tone'] }}"><i class="ti {{ $sourceMeta['icon'] }}"></i>{{ \App\Models\AttendanceRecord::SOURCES[$record->source] ?? $record->source }}</span>
                            </td>
                        @endunless
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $geo ? 6 : 7 }}" class="text-center text-muted py-5">
                            <i class="ti {{ $geo ? 'ti-map-pin' : 'ti-clock' }} font-24 d-block mb-2"></i>
                            <div class="fw-semibold text-body mb-1">{{ $geo ? 'No geo check-ins found' : 'No punch records found' }}</div>
                            @if ($activeFilters->isNotEmpty())
                                <span class="font-13"><a href="{{ $clearAllUrl }}">Clear filters</a></span>
                            @elseif ($geo)
                                <span class="font-13">Check-ins made from the mobile app with location enabled appear here.</span>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

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
        <div class="ms-auto">{{ $records->links() }}</div>
    </div>
</div>
