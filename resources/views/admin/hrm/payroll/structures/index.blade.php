@extends('layouts.admin')

@section('title', 'Salary Structure')

@php
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.salary-structures.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== '' && $value !== 'name'
    ));
    $tabs = [
        '' => ['label' => 'Everyone', 'tone' => 'indigo', 'count' => $counts['all']],
        'set' => ['label' => 'Structure set', 'tone' => 'success', 'count' => $counts['set']],
        'missing' => ['label' => 'Missing', 'tone' => 'rose', 'count' => $counts['missing']],
    ];
    $money = fn ($n) => number_format((float) $n);
    $mixTones = ['indigo', 'teal', 'amber', 'pink', 'violet', 'sky', 'lime', 'slate'];
    $toneOf = fn ($componentId) => $mixTones[$componentId % count($mixTones)];
    $mixTotal = max(1, (float) $mix->sum('amount'));
    $activeFilters = collect(['search' => 'Search', 'department_id' => 'Department'])
        ->filter(fn ($label, $key) => filled($state[$key] ?? null))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $key === 'department_id' ? ($departments[$state[$key]] ?? $state[$key]) : $state[$key]]);
@endphp

@section('content')
    <x-page-title title="Salary Structure" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Structures set" :value="$counts['set'].' / '.$counts['all']" icon="ti-file-invoice" :tone="$counts['missing'] ? 'amber' : 'success'"
                    :hint="$counts['missing'] ? $counts['missing'].' '.Str::plural('person', $counts['missing']).' can\'t be paid yet' : 'Everyone is ready for payroll'" :href="$pageUrl(['tab' => $counts['missing'] ? 'missing' : null])" />
        <x-kpi-card label="Monthly payroll" :value="$counts['set'] ? $money($kpis['monthly']) : '—'" icon="ti-cash" tone="success" :hint="$counts['set'] ? $money($kpis['monthly'] * 12).' a year' : 'No structures yet'" />
        <x-kpi-card label="Average salary" :value="$kpis['average'] !== null ? $money($kpis['average']) : '—'" icon="ti-chart-bar" tone="indigo" :hint="$kpis['median'] !== null ? 'Median '.$money($kpis['median']).' a month' : 'Per month'" />
        <x-kpi-card label="Salary range" :value="$kpis['highest'] !== null ? $money($kpis['lowest']).' – '.$money($kpis['highest']) : '—'" icon="ti-arrows-up-down" tone="violet" hint="Lowest to highest, monthly" :href="$pageUrl(['sort' => 'amount'])" />
    </div>

    @if ($mix->isNotEmpty())
        <div class="card side-list tone-teal mb-3">
            <div class="side-list-head">
                <span class="side-list-icon"><i class="ti ti-chart-donut"></i></span>
                <span class="fw-semibold">Where the monthly payroll goes</span>
                <a href="{{ route('admin.salary-components.index') }}" class="ms-auto font-12 text-decoration-none">Salary components <i class="ti ti-arrow-right"></i></a>
            </div>
            <div class="card-body">
                <div class="stack-bar stack-bar-lg">
                    @foreach ($mix as $i => $part)
                        <span class="tone-{{ $mixTones[$i % count($mixTones)] }}" style="flex: {{ $part['amount'] }}" title="{{ $part['name'] }}: {{ $money($part['amount']) }}"></span>
                    @endforeach
                </div>
                <div class="stack-legend">
                    @foreach ($mix as $i => $part)
                        <span><i class="legend-dot tone-{{ $mixTones[$i % count($mixTones)] }}"></i>{{ $part['name'] }} <strong>{{ $money($part['amount']) }}</strong> <span class="text-muted">· {{ round($part['amount'] / $mixTotal * 100) }}%</span></span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.salary-structures.index') }}">
            @foreach (['tab' => $state['tab'], 'sort' => $state['sort'] === 'name' ? '' : $state['sort']] as $name => $value)
                @if ($value !== '')
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Structure status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search employee" aria-label="Search employees">
                    </div>
                    @if ($departments->count() > 1)
                        <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected($state['department_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <div class="segmented" role="group" aria-label="Sort">
                        <a href="{{ $pageUrl(['sort' => null]) }}" class="segmented-item {{ $state['sort'] === 'name' ? 'active' : '' }}">A–Z</a>
                        <a href="{{ $pageUrl(['sort' => 'amount']) }}" class="segmented-item {{ $state['sort'] === 'amount' ? 'active' : '' }}">Highest</a>
                        <a href="{{ $pageUrl(['sort' => 'recent']) }}" class="segmented-item {{ $state['sort'] === 'recent' ? 'active' : '' }}">Recent</a>
                    </div>
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
                        <th>Employee</th>
                        <th class="struct-amount-col">Monthly salary</th>
                        <th class="struct-amount-col">Breakdown</th>
                        <th>Effective from</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php
                            $structure = $employee->current_structure;
                            $total = $employee->monthly_total;
                        @endphp
                        <tr class="{{ $structure ? '' : 'is-pending-row' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ route('admin.salary-structures.edit', $employee) }}">{{ $employee->name }}</a></div>
                                        <div class="list-user-meta">{{ collect([$employee->designation, $employee->department?->name])->filter()->implode(' · ') ?: $employee->employee_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="struct-amount-col">
                                @if ($structure)
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="salary-figure">{{ $money($total) }}</span>
                                        <span class="list-subtext">{{ $money($total * 12) }} / yr</span>
                                    </div>
                                    <span class="week-hours-bar struct-bar"><span style="width: {{ round($total / $maxTotal * 100) }}%"></span></span>
                                @else
                                    <span class="tag tone-rose"><i class="ti ti-alert-triangle"></i>No salary structure</span>
                                @endif
                            </td>
                            <td>
                                @if ($structure)
                                    <div class="stack-bar struct-mix" role="img" aria-label="Component breakdown">
                                        @foreach ($structure->items->sortByDesc('amount') as $item)
                                            <span class="tone-{{ $toneOf($item->salary_component_id) }}" style="flex: {{ (float) $item->amount }}" title="{{ $item->component?->name }}: {{ $money($item->amount) }}"></span>
                                        @endforeach
                                    </div>
                                    <div class="list-subtext mt-1 text-truncate struct-mix-text">{{ $structure->items->sortByDesc('amount')->take(3)->map(fn ($i) => ($i->component?->code ?? '?').' '.$money($i->amount))->implode(' · ') }}{{ $structure->items->count() > 3 ? ' +'.($structure->items->count() - 3) : '' }}</div>
                                @else
                                    <span class="list-subtext">Can't be included in payroll until a structure is set.</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($structure)
                                    <div class="font-13 fw-semibold">{{ $structure->effective_date?->format('M j, Y') }}</div>
                                    <div class="list-subtext">
                                        @if ($structure->effective_date?->isFuture())
                                            <span class="text-warning">future-dated</span>
                                        @else
                                            {{ $structure->effective_date?->diffForHumans() }}
                                        @endif
                                        @if ($employee->salary_structures_count > 1) · {{ $employee->salary_structures_count - 1 }} earlier @endif
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($structure)
                                    <a href="{{ route('admin.salary-structures.edit', $employee) }}" class="btn-icon btn-icon-accent" title="{{ $canEdit ? 'Revise structure' : 'View structure' }}"><i class="ti {{ $canEdit ? 'ti-pencil' : 'ti-eye' }} font-16"></i></a>
                                @elseif ($canEdit)
                                    <a href="{{ route('admin.salary-structures.edit', $employee) }}" class="btn btn-sm btn-de-primary text-light"><i class="ti ti-plus font-14"></i> Set up</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-file-invoice font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">{{ $counts['all'] ? 'No one matches' : 'No active employees yet' }}</div>
                                @if ($counts['all'])
                                    <span class="font-13"><a href="{{ route('admin.salary-structures.index') }}">Show everyone</a></span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
