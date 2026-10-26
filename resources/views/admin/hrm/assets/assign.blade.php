@extends('layouts.admin')

@section('title', 'Assign Assets')

@php
    use App\Models\EmployeeAsset;
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.assets.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $categoryMeta = [
        'laptop' => ['indigo', 'ti-device-laptop', 'Serial number'],
        'mobile' => ['teal', 'ti-device-mobile', 'IMEI'],
        'id_card' => ['amber', 'ti-id', 'Card number'],
        'sim_card' => ['success', 'ti-antenna-bars-5', 'SIM / phone number'],
        'uniform' => ['rose', 'ti-shirt', 'Size'],
        'other' => ['slate', 'ti-box', 'Serial / reference number'],
    ];
    $tabs = ['' => ['label' => 'All', 'tone' => 'indigo', 'count' => $counts['all']]];
    foreach (EmployeeAsset::CATEGORIES as $value => $label) {
        $tabs[$value] = ['label' => $label, 'tone' => $categoryMeta[$value][0] ?? 'slate', 'count' => $counts[$value] ?? 0];
    }
    $tabs['leavers'] = ['label' => 'With people who left', 'tone' => 'rose', 'count' => $counts['leavers']];
    $filterValues = [
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $oldEmployee = (string) old('employee_id', $state['employee_id'] ?? '');
    $oldCategory = (string) old('category', array_key_exists($state['tab'], EmployeeAsset::CATEGORIES) ? $state['tab'] : '');
    $canUpdate = auth()->user()->can('update', new EmployeeAsset);
    $canSeeReturns = \Illuminate\Support\Facades\Route::has('admin.asset-returns.index');
@endphp

@section('content')
    <x-page-title title="Assign Assets" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Assets out" :value="$kpis['out']" icon="ti-devices" tone="indigo" :hint="$kpis['out'] ? 'With '.$kpis['holders'].' of '.$kpis['staff'].' '.Str::plural('employee', $kpis['staff']) : 'Nothing is assigned yet'" />
        <x-kpi-card label="Assigned this month" :value="$kpis['month']" icon="ti-calendar-plus" tone="teal" :hint="$kpis['month'] ? 'Handed over in '.today()->format('F') : 'Nothing handed over in '.today()->format('F')" />
        <x-kpi-card label="Most issued" :value="$kpis['top'] ? (EmployeeAsset::CATEGORIES[$kpis['top']] ?? $kpis['top']) : '—'" :icon="$kpis['top'] ? ($categoryMeta[$kpis['top']][1] ?? 'ti-box') : 'ti-box'" tone="amber" :hint="$kpis['top'] ? $kpis['top_count'].' out right now' : 'No assets out'" />
        <x-kpi-card label="Needs attention" :value="$kpis['leavers'] + $kpis['untagged']" icon="ti-alert-triangle" :tone="($kpis['leavers'] + $kpis['untagged']) ? 'rose' : 'success'"
                    :hint="($kpis['leavers'] + $kpis['untagged']) ? collect([$kpis['leavers'] ? $kpis['leavers'].' with '.$kpis['leaver_people'].' '.Str::plural('person', $kpis['leaver_people']).' who left' : null, $kpis['untagged'] ? $kpis['untagged'].' without a tag or number' : null])->filter()->implode(' · ') : 'Everything is tagged and with current staff'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.assets.store') }}" class="card form-section tone-indigo mb-0 h-100" id="asset-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-devices"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Assign an asset</h5>
                            <p class="form-section-sub">Record what is handed to whom. A tag or serial number lets you trace it when it comes back.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="asset-employee">Employee</label>
                                <select name="employee_id" id="asset-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formPeople as $person)
                                        <option value="{{ $person->id }}" data-joined="{{ $person->joining_date?->toDateString() }}" @selected($oldEmployee === (string) $person->id)>{{ $person->name }}{{ $person->employee_code ? ' ('.$person->employee_code.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="asset-category">Category</label>
                                <select name="category" id="asset-category" class="form-select @error('category') is-invalid @enderror" required>
                                    <option value="">Select category</option>
                                    @foreach (EmployeeAsset::CATEGORIES as $value => $label)
                                        <option value="{{ $value }}" data-identifier="{{ $categoryMeta[$value][2] ?? 'Identifier' }}" @selected($oldCategory === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="asset-name">Asset</label>
                                <input type="text" name="asset_name" id="asset-name" value="{{ old('asset_name') }}" maxlength="255" list="asset-names" class="form-control @error('asset_name') is-invalid @enderror" placeholder="e.g. Dell Latitude 5420" required>
                                <datalist id="asset-names">@foreach ($names as $name)<option value="{{ $name }}"></option>@endforeach</datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="asset-brand">Brand / model <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="brand_model" id="asset-brand" value="{{ old('brand_model') }}" maxlength="255" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="asset-tag">Asset tag <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="asset_tag" id="asset-tag" value="{{ old('asset_tag') }}" maxlength="100" class="form-control @error('asset_tag') is-invalid @enderror" placeholder="e.g. LAP-0042">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="asset-identifier"><span id="asset-identifier-label">Identifier</span> <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="identifier" id="asset-identifier" value="{{ old('identifier') }}" maxlength="100" class="form-control @error('identifier') is-invalid @enderror">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="asset-date">Handed over on</label>
                                <input type="date" name="assigned_date" id="asset-date" value="{{ old('assigned_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control @error('assigned_date') is-invalid @enderror" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Assign asset</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-user-check"></i></span>
                        <span class="fw-semibold" id="held-title">What this person already has</span>
                        <span class="side-list-count" id="held-count" hidden></span>
                    </div>
                    <div id="held-list"></div>
                    <div class="card-body pt-2">
                        <div class="font-13 text-muted" id="held-empty">Pick an employee to see what they already hold.</div>
                        <div class="run-note tone-amber mt-2" id="held-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>The same tag or serial number can't be out with two people. To move an asset to someone else, use Transfer in the list below.</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.assets.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Asset category">
                    @foreach ($tabs as $value => $tab)
                        @if ($value !== 'leavers' || $tab['count'] || $state['tab'] === 'leavers')
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search asset, tag, serial or name" aria-label="Search assets">
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
                        <th>Asset</th>
                        <th>Tag / number</th>
                        <th>Held by</th>
                        <th>Since</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        @php
                            $meta = $categoryMeta[$asset->category] ?? ['slate', 'ti-box', 'Identifier'];
                            $left = $leaverIds->has($asset->employee_id);
                            $days = $asset->assigned_date ? (int) $asset->assigned_date->diffInDays(today()) : null;
                            $held = $days === null ? null : ($days < 1 ? 'today' : ($days < 60 ? $days.' '.Str::plural('day', $days) : ($days < 730 ? round($days / 30).' months' : round($days / 365, 1).' years')));
                        @endphp
                        <tr class="{{ $left ? 'is-pending-row' : '' }}">
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
                                @if ($asset->asset_tag || $asset->identifier)
                                    <div class="fw-semibold">{{ $asset->asset_tag ?? $asset->identifier }}</div>
                                    @if ($asset->asset_tag && $asset->identifier)
                                        <div class="payslip-parts">{{ $meta[2] }}: {{ $asset->identifier }}</div>
                                    @endif
                                @else
                                    <span class="tag tone-amber" title="Without a tag or serial number this item can't be told apart from another one"><i class="ti ti-alert-triangle"></i>No tag</span>
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
                                {{ $asset->assigned_date?->format('M j, Y') ?? '—' }}
                                @if ($held)
                                    <div class="payslip-parts">{{ $held === 'today' ? 'today' : 'for '.$held }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $asset)
                                        @if ($canSeeReturns)
                                            <a href="{{ route('admin.asset-returns.index', ['employee_id' => $asset->employee_id]) }}" class="btn btn-sm review-btn {{ $left ? 'is-approve' : '' }}" title="Take it back on the Asset Return page"><i class="ti ti-arrow-back-up"></i> Return</a>
                                        @endif
                                        @if ($canCreate)
                                            <button type="button" class="btn-icon" title="Transfer to someone else" data-bs-toggle="modal" data-bs-target="#transfer-modal"
                                                    data-action="{{ route('admin.assets.transfer', $asset) }}" data-name="{{ $asset->asset_name }}" data-holder="{{ $asset->employee?->name }}" data-holder-id="{{ $asset->employee_id }}" data-since="{{ $asset->assigned_date?->toDateString() }}"><i class="ti ti-switch-horizontal font-16"></i></button>
                                        @endif
                                        <button type="button" class="btn-icon btn-icon-accent" title="Change details" data-bs-toggle="modal" data-bs-target="#asset-modal"
                                                data-action="{{ route('admin.assets.update', $asset) }}" data-holder="{{ $asset->employee?->name }}" data-category="{{ $asset->category }}" data-name="{{ $asset->asset_name }}" data-brand="{{ $asset->brand_model }}"
                                                data-tag="{{ $asset->asset_tag }}" data-identifier="{{ $asset->identifier }}" data-date="{{ $asset->assigned_date?->toDateString() }}"><i class="ti ti-pencil font-16"></i></button>
                                    @endcan
                                    @can('delete', $asset)
                                        <form action="{{ route('admin.assets.destroy', $asset) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this record completely? Use Return instead if the item really was handed over and has come back; that keeps the history.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Delete (entered by mistake)"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-devices font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No assigned assets match</div>
                                    <span class="font-13"><a href="{{ route('admin.assets.index') }}">Show everything that is out</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No assets are assigned right now</div>
                                    <span class="font-13">{{ $canCreate ? 'Assign the first one with the form above.' : 'Assets handed out by HR will appear here.' }}</span>
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

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var holdings = @json($holdings);
            var categories = @json(EmployeeAsset::CATEGORIES);
            var today = @json(today()->toDateString());


            var form = el('asset-form');
            if (!form) return;
            var employee = el('asset-employee'), category = el('asset-category');

            function render() {
                var mine = employee.value && holdings[employee.value] ? holdings[employee.value] : [];
                var who = employee.value ? employee.options[employee.selectedIndex].textContent.trim().replace(/\s*\(.*\)$/, '') : '';
                var cat = category.options[category.selectedIndex];
                el('asset-identifier-label').textContent = category.value ? cat.dataset.identifier : 'Identifier';
                el('held-title').textContent = who ? 'What ' + who + ' already has' : 'What this person already has';
                el('held-count').hidden = !employee.value; el('held-count').textContent = mine.length;

                var list = el('held-list');
                list.textContent = '';
                mine.forEach(function (item) {
                    var row = document.createElement('div'), body = document.createElement('div'), name = document.createElement('div'), meta = document.createElement('div');
                    row.className = 'side-list-item'; body.className = 'flex-grow-1 min-w-0'; name.className = 'fw-semibold'; meta.className = 'font-12 text-muted';
                    name.textContent = item.name; meta.textContent = [categories[item.category] || item.category, item.tag].filter(Boolean).join(' · ');
                    body.appendChild(name); body.appendChild(meta); row.appendChild(body); list.appendChild(row);
                });
                el('held-empty').hidden = mine.length > 0;
                el('held-empty').textContent = employee.value ? who + ' holds nothing at the moment.' : 'Pick an employee to see what they already hold.';

                var same = category.value ? mine.filter(function (item) { return item.category === category.value; }) : [];
                var joined = employee.value ? employee.options[employee.selectedIndex].dataset.joined : '';
                var date = el('asset-date').value;
                var warn = joined && date && date < joined ? who + ' joined on ' + joined + '. Nothing can be assigned before that; saving will be refused.'
                    : (same.length ? who + ' already has ' + (same.length === 1 ? 'a ' + categories[category.value].toLowerCase() + ' (' + same[0].name + ')' : same.length + ' items in this category') + '. You can still assign another.' : '');
                el('held-warn').hidden = !warn;
                el('held-warn').querySelector('span').textContent = warn;
            }

            [employee, category, el('asset-date')].forEach(function (input) { input.addEventListener('change', render); });
            render();
        })();
    </script>
@endpush
