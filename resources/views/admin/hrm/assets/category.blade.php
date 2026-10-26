@extends('layouts.admin')

@section('title', $categoryLabel)

@php
    use App\Models\EmployeeAsset;
    use Illuminate\Support\Str;

    $meta = [
        'laptop' => ['icon' => 'ti-device-laptop', 'tone' => 'indigo', 'route' => 'admin.assets.laptops', 'plural' => 'Laptops', 'one' => 'a laptop', 'identifier_label' => 'Serial number', 'tag_label' => 'Asset tag', 'tag_hint' => 'e.g. LAP-0042', 'placeholder' => 'e.g. Dell Latitude 5420'],
        'mobile' => ['icon' => 'ti-device-mobile', 'tone' => 'teal', 'route' => 'admin.assets.mobiles', 'plural' => 'Mobiles', 'one' => 'a mobile', 'identifier_label' => 'IMEI', 'tag_label' => 'Asset tag', 'tag_hint' => 'e.g. MOB-0042', 'placeholder' => 'e.g. iPhone 13'],
        'id_card' => ['icon' => 'ti-id', 'tone' => 'amber', 'route' => 'admin.assets.id-cards', 'plural' => 'ID cards', 'one' => 'an ID card', 'identifier_label' => 'Card number', 'tag_label' => 'Asset tag', 'tag_hint' => '', 'placeholder' => 'e.g. Employee ID Card'],
        'sim_card' => ['icon' => 'ti-credit-card', 'tone' => 'success', 'route' => 'admin.assets.sim-cards', 'plural' => 'SIM cards', 'one' => 'a SIM card', 'identifier_label' => 'SIM number', 'tag_label' => 'Mobile number', 'tag_hint' => '', 'placeholder' => 'e.g. Company SIM'],
        'uniform' => ['icon' => 'ti-shirt', 'tone' => 'rose', 'route' => 'admin.assets.uniforms', 'plural' => 'Uniforms', 'one' => 'a uniform', 'identifier_label' => 'Size', 'tag_label' => 'Set / batch no.', 'tag_hint' => '', 'placeholder' => 'e.g. Summer Uniform Set'],
    ][$category];
    // A size is not unique to one uniform, so it doesn't identify the item.
    $identifies = $category !== 'uniform';
    $pageUrl = fn (array $overrides = []) => route($meta['route'], array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All', 'tone' => 'indigo', 'count' => $counts['all']],
        'assigned' => ['label' => 'Assigned', 'tone' => 'teal', 'count' => $counts['assigned']],
        'returned' => ['label' => 'Returned', 'tone' => 'slate', 'count' => $counts['returned']],
        'problem' => ['label' => 'Damaged or lost', 'tone' => 'rose', 'count' => $counts['problem']],
    ];
    $filterValues = [
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $conditionMeta = ['good' => ['Returned', 'slate', 'ti-arrow-back-up'], 'damaged' => ['Returned damaged', 'amber', 'ti-alert-triangle'], 'lost' => ['Lost', 'rose', 'ti-x']];
    $oldEmployee = (string) old('employee_id', $state['employee_id'] ?? '');
    $canUpdate = auth()->user()->can('update', new EmployeeAsset);
    $attention = $kpis['leavers'] + $kpis['untagged'];
    $span = fn ($from, $to) => ($d = (int) $from->diffInDays($to)) < 1 ? 'same day' : ($d < 60 ? $d.' '.Str::plural('day', $d) : ($d < 730 ? round($d / 30).' months' : round($d / 365, 1).' years'));
@endphp

@section('content')
    <x-page-title :title="$categoryLabel" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card :label="$meta['plural'].' out'" :value="$kpis['out']" :icon="$meta['icon']" :tone="$meta['tone']" :hint="$kpis['out'] ? 'With staff right now' : 'None assigned right now'" />
        <x-kpi-card label="Staff who have one" :value="$kpis['staff'] ? $kpis['holders'].' / '.$kpis['staff'] : '—'" icon="ti-users" tone="teal" :hint="$kpis['staff'] ? ($without->count() ? $without->count().' current '.Str::plural('employee', $without->count()).' without '.$meta['one'] : 'Every current employee has '.$meta['one']) : 'No active employees'" />
        <x-kpi-card label="Returned" :value="$kpis['returned']" icon="ti-arrow-back-up" :tone="($kpis['damaged'] + $kpis['lost']) ? 'amber' : 'slate'" :hint="$kpis['returned'] ? (($kpis['damaged'] + $kpis['lost']) ? collect([$kpis['damaged'] ? $kpis['damaged'].' damaged' : null, $kpis['lost'] ? $kpis['lost'].' lost' : null])->filter()->implode(' · ') : 'All came back in good condition') : 'Nothing has come back yet'" />
        <x-kpi-card label="Needs attention" :value="$attention" icon="ti-alert-triangle" :tone="$attention ? 'rose' : 'success'"
                    :hint="$attention ? collect([$kpis['leavers'] ? $kpis['leavers'].' with people who left' : null, $kpis['untagged'] ? $kpis['untagged'].' without a tag or number' : null])->filter()->implode(' · ') : 'Everything out is tagged and with current staff'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.assets.store') }}" class="card form-section tone-{{ $meta['tone'] }} mb-0 h-100" id="asset-form">
                    @csrf
                    <input type="hidden" name="category" value="{{ $category }}">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti {{ $meta['icon'] }}"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Assign {{ $meta['one'] }}</h5>
                            <p class="form-section-sub">Record which one goes to whom. {{ $identifies ? 'The '.strtolower($meta['identifier_label']).' lets you trace it when it comes back.' : 'Note the size so a replacement is easy.' }}</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="asset-employee">Employee</label>
                                <select name="employee_id" id="asset-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formPeople as $person)
                                        <option value="{{ $person->id }}" data-joined="{{ $person->joining_date?->toDateString() }}" data-has="{{ $holderIds->has($person->id) ? 1 : 0 }}" @selected($oldEmployee === (string) $person->id)>{{ $person->name }}{{ $person->employee_code ? ' ('.$person->employee_code.')' : '' }}{{ $holderIds->has($person->id) ? ' — already has one' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="asset-name">{{ $categoryLabel }}</label>
                                <input type="text" name="asset_name" id="asset-name" value="{{ old('asset_name') }}" maxlength="255" list="asset-names" class="form-control @error('asset_name') is-invalid @enderror" placeholder="{{ $meta['placeholder'] }}" required>
                                <datalist id="asset-names">@foreach ($names as $name)<option value="{{ $name }}"></option>@endforeach</datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="asset-identifier">{{ $meta['identifier_label'] }} <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="identifier" id="asset-identifier" value="{{ old('identifier') }}" maxlength="100" class="form-control @error('identifier') is-invalid @enderror">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="asset-tag">{{ $meta['tag_label'] }} <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="asset_tag" id="asset-tag" value="{{ old('asset_tag') }}" maxlength="100" class="form-control @error('asset_tag') is-invalid @enderror" placeholder="{{ $meta['tag_hint'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="asset-brand">Brand / model <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="brand_model" id="asset-brand" value="{{ old('brand_model') }}" maxlength="255" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="asset-date">Handed over on</label>
                                <input type="date" name="assigned_date" id="asset-date" value="{{ old('assigned_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control @error('assigned_date') is-invalid @enderror" required>
                            </div>
                        </div>
                        <div class="run-note tone-amber mt-3" id="asset-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Assign {{ strtolower($categoryLabel) }}</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-{{ $without->isEmpty() ? 'success' : 'amber' }} mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-user-check"></i></span>
                        <span class="fw-semibold">Staff without {{ $meta['one'] }}</span>
                        <span class="side-list-count">{{ $without->count() }}</span>
                    </div>
                    @forelse ($without->take(6) as $person)
                        <a href="#asset-form" class="side-list-item" data-pick-employee="{{ $person->id }}" title="Put {{ $person->name }} in the form">
                            <x-avatar :person="$person" />
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold">{{ $person->name }}</div>
                                <div class="font-12 text-muted">{{ collect([$person->employee_code, $person->department?->name])->filter()->implode(' · ') }}</div>
                            </div>
                            <i class="ti ti-plus text-muted"></i>
                        </a>
                    @empty
                        <div class="card-body font-13 text-muted">{{ $formPeople->isEmpty() ? 'There are no active employees.' : 'Every current employee has '.$meta['one'].'.' }}</div>
                    @endforelse
                    @if ($without->count() > 6)
                        <div class="card-body py-2 font-12 text-muted">and {{ $without->count() - 6 }} more. They are the ones not marked "already has one" in the employee list.</div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route($meta['route']) }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name, {{ strtolower($meta['identifier_label']) }} or person" aria-label="Search {{ strtolower($meta['plural']) }}">
                    </div>
                    @if ($departments->count() > 1)
                        <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected($state['department_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Employee">
                        <option value="">All employees</option>
                        @foreach ($employees as $id => $name)
                            <option value="{{ $id }}" @selected($state['employee_id'] === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
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
                        <th>{{ $categoryLabel }}</th>
                        <th>{{ $meta['identifier_label'] }}</th>
                        <th>{{ $meta['tag_label'] }}</th>
                        <th>Held by</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        @php
                            $out = $asset->status === 'assigned';
                            $left = $out && $leaverIds->has($asset->employee_id);
                            $status = $out ? ['Assigned', 'teal', 'ti-circle-check'] : ($conditionMeta[$asset->condition_at_return] ?? $conditionMeta['good']);
                        @endphp
                        <tr class="{{ $left ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="fw-semibold">{{ $asset->asset_name }}</div>
                                @if ($asset->brand_model)
                                    <div class="payslip-parts">{{ $asset->brand_model }}</div>
                                @endif
                            </td>
                            <td>{{ $asset->identifier ?? '—' }}</td>
                            <td>
                                @if ($asset->asset_tag)
                                    {{ $asset->asset_tag }}
                                @elseif ($out && ! $asset->identifier)
                                    <span class="tag tone-amber" title="Without a tag or number this item can't be told apart from another one"><i class="ti ti-alert-triangle"></i>None</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$asset->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $asset->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">
                                            @if ($left)
                                                <span class="text-danger">Has left the company</span>
                                            @else
                                                {{ collect([$asset->employee?->employee_code, $asset->employee?->department?->name])->filter()->implode(' · ') }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                @if ($asset->assigned_date)
                                    {{ $asset->assigned_date->format('M j, Y') }}{{ ! $out && $asset->returned_date ? ' – '.$asset->returned_date->format('M j, Y') : '' }}
                                    <div class="payslip-parts">{{ $out ? ($asset->assigned_date->isToday() ? 'since today' : 'for '.$span($asset->assigned_date, today())) : ($asset->returned_date ? 'kept '.$span($asset->assigned_date, $asset->returned_date) : '') }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $status[1] }}"><i class="ti {{ $status[2] }}"></i>{{ $status[0] }}</span>
                                @if (! $out && $asset->remarks)
                                    <div class="payslip-parts text-wrap" title="{{ $asset->remarks }}">{{ Str::limit($asset->remarks, 50) }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($out)
                                        @can('update', $asset)
                                            <a href="{{ route('admin.asset-returns.index', ['employee_id' => $asset->employee_id, 'category' => $category]) }}" class="btn btn-sm review-btn {{ $left ? 'is-approve' : '' }}" title="Take it back on the Asset Return page"><i class="ti ti-arrow-back-up"></i> Return</a>
                                            @if ($canCreate)
                                                <button type="button" class="btn-icon" title="Transfer to someone else" data-bs-toggle="modal" data-bs-target="#transfer-modal"
                                                        data-action="{{ route('admin.assets.transfer', $asset) }}" data-name="{{ $asset->asset_name }}" data-holder="{{ $asset->employee?->name }}" data-holder-id="{{ $asset->employee_id }}" data-since="{{ $asset->assigned_date?->toDateString() }}"><i class="ti ti-switch-horizontal font-16"></i></button>
                                            @endif
                                            <button type="button" class="btn-icon btn-icon-accent" title="Change details" data-bs-toggle="modal" data-bs-target="#asset-modal"
                                                    data-action="{{ route('admin.assets.update', $asset) }}" data-holder="{{ $asset->employee?->name }}" data-category="{{ $asset->category }}" data-name="{{ $asset->asset_name }}" data-brand="{{ $asset->brand_model }}"
                                                    data-tag="{{ $asset->asset_tag }}" data-identifier="{{ $asset->identifier }}" data-date="{{ $asset->assigned_date?->toDateString() }}"><i class="ti ti-pencil font-16"></i></button>
                                        @endcan
                                    @endif
                                    @can('delete', $asset)
                                        <form action="{{ route('admin.assets.destroy', $asset) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $out ? 'Delete this record completely? Use Return instead if the item really was handed over and has come back; that keeps the history.' : 'Delete this returned record? It will disappear from the asset history.' }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete record"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti {{ $meta['icon'] }} font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No {{ strtolower($meta['plural']) }} match</div>
                                    <span class="font-13"><a href="{{ route($meta['route']) }}">Show all {{ strtolower($meta['plural']) }}</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No {{ strtolower($meta['plural']) }} on record yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Assign the first one with the form above.' : 'Those handed out by HR will appear here.' }}</span>
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

    @include('admin.hrm.assets._modals')
@endsection

@if ($canCreate)
    @push('scripts')
        <script>
            (function () {
                var employee = document.getElementById('asset-employee');
                if (!employee) return;
                var date = document.getElementById('asset-date'), warn = document.getElementById('asset-warn');
                var one = @json($meta['one']);

                function render() {
                    var opt = employee.options[employee.selectedIndex];
                    var who = employee.value ? opt.textContent.trim().replace(/\s*\(.*$/, '').replace(/\s—.*$/, '') : '';
                    var joined = employee.value ? opt.dataset.joined : '';
                    var text = joined && date.value && date.value < joined ? who + ' joined on ' + joined + '. Nothing can be assigned before that; saving will be refused.'
                        : (employee.value && opt.dataset.has === '1' ? who + ' already has ' + one + '. You can still assign another.' : '');
                    warn.hidden = !text;
                    warn.querySelector('span').textContent = text;
                }

                [employee, date].forEach(function (input) { input.addEventListener('change', render); });
                document.querySelectorAll('[data-pick-employee]').forEach(function (link) {
                    link.addEventListener('click', function () {
                        employee.value = link.dataset.pickEmployee;
                        render();
                        document.getElementById('asset-name').focus();
                    });
                });
                render();
            })();
        </script>
    @endpush
@endif
