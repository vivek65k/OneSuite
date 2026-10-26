@extends('layouts.admin')

@section('title', 'Asset History')

@php
    use App\Models\EmployeeAsset;
    use Illuminate\Support\Str;

    $query = fn (array $overrides = []) => array_filter(array_merge($state, $overrides), fn ($value) => $value !== null && $value !== '');
    $pageUrl = fn (array $overrides = []) => route('admin.asset-history.index', $query($overrides));
    $categoryMeta = [
        'laptop' => ['indigo', 'ti-device-laptop'], 'mobile' => ['teal', 'ti-device-mobile'], 'id_card' => ['amber', 'ti-id'],
        'sim_card' => ['success', 'ti-credit-card'], 'uniform' => ['rose', 'ti-shirt'], 'other' => ['slate', 'ti-box'],
    ];
    $conditionMeta = ['good' => ['Returned', 'slate', 'ti-arrow-back-up'], 'damaged' => ['Returned damaged', 'amber', 'ti-alert-triangle'], 'lost' => ['Lost', 'rose', 'ti-x']];
    $tabs = [
        '' => ['label' => 'All records', 'tone' => 'indigo', 'count' => $counts['all']],
        'assigned' => ['label' => 'Still out', 'tone' => 'teal', 'count' => $counts['assigned']],
        'returned' => ['label' => 'Returned', 'tone' => 'slate', 'count' => $counts['returned']],
        'problem' => ['label' => 'Damaged or lost', 'tone' => 'rose', 'count' => $counts['problem']],
    ];
    $filterValues = [
        'category' => EmployeeAsset::CATEGORIES[$state['category']] ?? null,
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'year' => $state['year'],
        'search' => $state['search'],
    ];
    $activeFilters = collect(['category' => 'Category', 'employee_id' => 'Employee', 'department_id' => 'Department', 'year' => 'Year', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $span = fn (int $d) => $d < 1 ? 'same day' : ($d < 60 ? $d.' '.Str::plural('day', $d) : ($d < 730 ? round($d / 30).' months' : round($d / 365, 1).' years'));
    $problems = $kpis['damaged'] + $kpis['lost'];
@endphp

@section('content')
    <x-page-title title="Asset History" />

    <div class="kpi-strip">
        <x-kpi-card label="Handovers on record" :value="$kpis['records']" icon="ti-history" tone="indigo" :hint="$kpis['records'] ? 'To '.$kpis['people'].' '.Str::plural('person', $kpis['people']) : 'Nothing has been assigned yet'" />
        <x-kpi-card label="Still out" :value="$kpis['out']" icon="ti-devices" tone="teal" :hint="$kpis['out'] ? 'With employees right now' : 'Nothing is out'" />
        <x-kpi-card label="Returned" :value="$kpis['returned']" icon="ti-arrow-back-up" tone="slate" :hint="$kpis['average_days'] !== null ? 'Kept for '.$span($kpis['average_days']).' on average' : 'Nothing has come back yet'" />
        <x-kpi-card label="Damaged or lost" :value="$problems" icon="ti-tool" :tone="$problems ? 'amber' : 'success'" :hint="$problems ? collect([$kpis['damaged'] ? $kpis['damaged'].' damaged' : null, $kpis['lost'] ? $kpis['lost'].' lost' : null])->filter()->implode(' · ') : 'Everything came back in good condition'" />
    </div>

    {{-- Following one item from holder to holder --}}
    @if ($trace)
        @php
            $traceMeta = $categoryMeta[$trace['category']] ?? ['slate', 'ti-box'];
        @endphp
        <div class="record-header">
            <span class="list-avatar list-avatar-square record-avatar tone-{{ $traceMeta[0] }}" aria-hidden="true"><i class="ti {{ $traceMeta[1] }} font-20"></i></span>
            <div class="record-header-text">
                <div class="record-header-title">{{ $trace['records'] ? ($trace['name'].' · '.Str::upper($trace['code'])) : 'Nothing found for "'.$trace['code'].'"' }}</div>
                <div class="record-header-meta">
                    @if ($trace['records'])
                        <span>{{ $trace['records'] }} {{ Str::plural('handover', $trace['records']) }} to {{ $trace['holders'] }} {{ Str::plural('person', $trace['holders']) }}{{ $trace['first'] ? ' since '.$trace['first']->format('M j, Y') : '' }}</span>
                        @if ($trace['with'])
                            <span class="status-dot status-dot-success">Now with {{ $trace['with'] }}</span>
                        @else
                            <span class="status-dot status-dot-{{ $trace['last_condition'] === 'lost' ? 'danger' : 'warning' }}">{{ $trace['last_condition'] === 'lost' ? 'Lost' : ($trace['last_condition'] === 'damaged' ? 'Came back damaged' : 'Not with anyone') }}</span>
                        @endif
                    @else
                        <span>No record has this tag or serial number.</span>
                    @endif
                </div>
            </div>
            <div class="record-header-actions">
                <a href="{{ $pageUrl(['trace' => null]) }}" class="btn btn-light btn-sm px-3"><i class="ti ti-arrow-left font-14 me-1"></i> All history</a>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.asset-history.index') }}">
            @foreach (['tab' => $state['tab'], 'trace' => $state['trace']] as $key => $value)
                @if ($value !== '')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Status">
                    @foreach ($tabs as $value => $tab)
                        @if ($value !== 'problem' || $tab['count'] || $state['tab'] === 'problem')
                            <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                                {{ $tab['label'] }}
                                <span class="list-tab-count">{{ $tab['count'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search asset, tag, serial, note or name" aria-label="Search history">
                    </div>
                    <select name="category" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Category">
                        <option value="">All categories</option>
                        @foreach (EmployeeAsset::CATEGORIES as $value => $label)
                            <option value="{{ $value }}" @selected($state['category'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($years->count() > 1 || $state['year'])
                        <select name="year" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Year">
                            <option value="">All years</option>
                            @foreach ($years->push($state['year'])->filter()->unique()->sortDesc() as $year)
                                <option value="{{ $year }}" @selected($state['year'] === (int) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if ($departments->count() > 1)
                        <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected($state['department_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if ($employees->isNotEmpty() || $state['employee_id'])
                        <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Employee">
                            <option value="">All employees</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected($state['employee_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if ($counts['all'])
                        <a href="{{ route('admin.asset-history.export', $query()) }}" class="btn btn-light btn-sm px-3" title="Download exactly this list as a spreadsheet"><i class="ti ti-download font-14 me-1"></i> Export</a>
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

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Asset</th>
                        <th>Tag / number</th>
                        <th>Employee</th>
                        <th>Period</th>
                        <th>Outcome</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        @php
                            $meta = $categoryMeta[$asset->category] ?? ['slate', 'ti-box'];
                            $out = $asset->status === 'assigned';
                            $transferred = ! $out && str_starts_with((string) $asset->remarks, 'Transferred to ');
                            $outcome = $out ? ['Still out', 'teal', 'ti-circle-check'] : ($transferred ? ['Transferred', 'indigo', 'ti-switch-horizontal'] : ($conditionMeta[$asset->condition_at_return] ?? $conditionMeta['good']));
                            // What this item can be followed by: its tag, or its serial number (a uniform's size doesn't count).
                            $code = $asset->asset_tag ?: ($asset->category !== 'uniform' ? $asset->identifier : null);
                            $days = $asset->assigned_date ? (int) $asset->assigned_date->diffInDays($asset->returned_date ?? today()) : null;
                        @endphp
                        <tr>
                            <td>
                                <div class="date-range-cell">
                                    <span class="list-avatar list-avatar-square tone-{{ $meta[0] }}" aria-hidden="true"><i class="ti {{ $meta[1] }} font-18"></i></span>
                                    <div>
                                        <div class="fw-semibold">{{ $asset->asset_name }}</div>
                                        <div class="payslip-parts">{{ collect([EmployeeAsset::CATEGORIES[$asset->category] ?? $asset->category, $asset->brand_model])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($code)
                                    <a href="{{ route('admin.asset-history.index', ['trace' => $code]) }}" class="fw-semibold" title="Show everyone who has had this item">{{ $code }}</a>
                                    @if ($asset->asset_tag && $asset->identifier)
                                        <div class="payslip-parts">{{ $asset->identifier }}</div>
                                    @endif
                                @elseif ($asset->identifier)
                                    {{ $asset->identifier }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$asset->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $asset->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$asset->employee?->employee_code, $asset->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                @if ($asset->assigned_date)
                                    {{ $asset->assigned_date->format('M j, Y') }} – {{ $out ? 'today' : ($asset->returned_date?->format('M j, Y') ?? '?') }}
                                    <div class="payslip-parts">{{ $out ? ($days < 1 ? 'since today' : 'for '.$span($days).' so far') :($asset->returned_date ? 'kept '.$span($days) : '') }}</div>
                                @else
                                    <span class="text-muted">{{ $asset->returned_date ? 'Returned '.$asset->returned_date->format('M j, Y') : '—' }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $outcome[1] }}"><i class="ti {{ $outcome[2] }}"></i>{{ $outcome[0] }}</span>
                                @if ($asset->remarks)
                                    <div class="payslip-parts text-wrap" title="{{ $asset->remarks }}">{{ Str::limit($asset->remarks, 70) }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-history font-24 d-block mb-2"></i>
                                @if ($kpis['records'])
                                    <div class="fw-semibold text-body mb-1">No records match</div>
                                    <span class="font-13"><a href="{{ route('admin.asset-history.index') }}">Show the whole history</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No asset history yet</div>
                                    <span class="font-13">Every asset handed to an employee is kept here, also after it comes back.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($assets->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $assets->links() }}</div></div>
        @endif
    </div>
@endsection
