@extends('layouts.admin')

@section('title', 'Salary Components')

@php
    use App\Models\SalaryComponent;

    $pageUrl = fn (array $overrides = []) => route('admin.salary-components.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All', 'tone' => 'indigo'],
        'earning' => ['label' => 'Earnings', 'tone' => 'success'],
        'deduction' => ['label' => 'Deductions', 'tone' => 'rose'],
        'inactive' => ['label' => 'Inactive', 'tone' => 'slate'],
    ];
    $typeMeta = [
        'earning' => ['tone' => 'success', 'icon' => 'ti-trending-up', 'sign' => '+'],
        'deduction' => ['tone' => 'rose', 'icon' => 'ti-trending-down', 'sign' => '−'],
    ];
    $money = fn ($n) => number_format((float) $n, 2);
    $canCreate = auth()->user()->can('create', SalaryComponent::class);
    $formOpen = $errors->any() && old('_form') === 'create';
    $suggestions = [
        ['name' => 'Leave Travel Allowance', 'code' => 'LTA', 'type' => 'earning'],
        ['name' => 'Performance Bonus', 'code' => 'BONUS', 'type' => 'earning'],
        ['name' => 'Overtime Pay', 'code' => 'OT', 'type' => 'earning'],
        ['name' => 'Night Shift Allowance', 'code' => 'NIGHT', 'type' => 'earning'],
        ['name' => 'Provident Fund', 'code' => 'PF', 'type' => 'deduction'],
        ['name' => 'Professional Tax', 'code' => 'PT', 'type' => 'deduction'],
        ['name' => 'Income Tax (TDS)', 'code' => 'TDS', 'type' => 'deduction'],
        ['name' => 'ESI Contribution', 'code' => 'ESI', 'type' => 'deduction'],
    ];
@endphp

@section('content')
    <x-page-title title="Salary Components" :breadcrumbs="['Salary Structure' => route('admin.salary-structures.index')]" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any() && ! $formOpen)
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Earnings" :value="$kpis['earnings']" icon="ti-trending-up" tone="success" hint="Active pay components" :href="$pageUrl(['tab' => 'earning'])" />
        <x-kpi-card label="Deductions" :value="$kpis['deductions']" icon="ti-trending-down" tone="rose" hint="Active deductions" :href="$pageUrl(['tab' => 'deduction'])" />
        <x-kpi-card label="In use" :value="$kpis['in_use'].' / '.$counts['all']" icon="ti-link" tone="indigo" :hint="$kpis['employees'].' '.\Illuminate\Support\Str::plural('employee', $kpis['employees']).' with a structure'" :href="route('admin.salary-structures.index')" />
        <x-kpi-card label="Monthly earnings" :value="$money($kpis['monthly'])" icon="ti-cash" tone="amber" hint="Across current salary structures" />
    </div>

    @if ($canCreate)
        <div class="collapse {{ $formOpen ? 'show' : '' }}" id="component-form-panel">
            <form method="POST" action="{{ route('admin.salary-components.store') }}" id="component-form" class="card form-section tone-indigo mb-3">
                @csrf
                <input type="hidden" name="_form" value="create">
                <div class="form-section-head">
                    <span class="form-section-icon"><i class="ti ti-plus"></i></span>
                    <div class="flex-grow-1">
                        <h5 class="form-section-title">New salary component</h5>
                        <p class="form-section-sub">An earning adds to pay; a deduction comes off it.</p>
                    </div>
                    <button type="button" class="btn-icon" data-bs-toggle="collapse" data-bs-target="#component-form-panel" title="Close"><i class="ti ti-x font-18"></i></button>
                </div>
                <div class="form-section-body">
                    @if ($formOpen)
                        <div class="alert alert-danger py-2 font-13">@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
                    @endif
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <div class="toggle-cards comp-types">
                                @foreach (SalaryComponent::TYPES as $value => $label)
                                    <label class="toggle-card tone-{{ $typeMeta[$value]['tone'] }}">
                                        <input type="radio" name="type" value="{{ $value }}" @checked(old('type', 'earning') === $value)>
                                        <span class="toggle-card-icon"><i class="ti {{ $typeMeta[$value]['icon'] }}"></i></span>
                                        <span class="toggle-card-body">
                                            <span class="toggle-card-title">{{ $label }}</span>
                                            <span class="toggle-card-hint">{{ $value === 'earning' ? 'Adds to gross pay' : 'Taken off net pay' }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label" for="comp-name">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="comp-name" class="form-control" value="{{ old('name') }}" maxlength="255" placeholder="e.g. Leave Travel Allowance" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="comp-code">Code <span class="text-danger">*</span></label>
                                    <input type="text" name="code" id="comp-code" class="form-control text-uppercase list-code-input" value="{{ old('code') }}" maxlength="50" placeholder="LTA" required>
                                </div>
                                <div class="col-12">
                                    <div class="quick-chips mt-0">
                                        @foreach ($suggestions as $s)
                                            <button type="button" class="quick-chip tone-{{ $typeMeta[$s['type']]['tone'] }}" data-suggest='@json($s)'>{{ $typeMeta[$s['type']]['sign'] }} {{ $s['name'] }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-light" data-bs-toggle="collapse" data-bs-target="#component-form-panel">Cancel</button>
                                    <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Add component</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <nav class="list-tabs" aria-label="Component type">
                @foreach ($tabs as $value => $tab)
                    <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                        {{ $tab['label'] }}
                        <span class="list-tab-count">{{ $counts[$value ?: 'all'] }}</span>
                    </a>
                @endforeach
            </nav>
            <div class="list-toolbar-actions">
                <form method="GET" action="{{ route('admin.salary-components.index') }}" class="list-search">
                    <i class="ti ti-search"></i>
                    @if ($state['tab'] !== '')
                        <input type="hidden" name="tab" value="{{ $state['tab'] }}">
                    @endif
                    <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or code" aria-label="Search components">
                </form>
                @if ($canCreate)
                    <button type="button" class="btn btn-de-primary btn-sm text-light px-3" data-bs-toggle="collapse" data-bs-target="#component-form-panel" aria-controls="component-form-panel">
                        <i class="ti ti-plus font-14 me-1"></i> New component
                    </button>
                @endif
            </div>
        </div>

        @if ($state['search'] !== '')
            <div class="list-subbar">
                <div class="list-chips">
                    <a href="{{ $pageUrl(['search' => null]) }}" class="filter-chip" title="Remove filter">Search: <strong>{{ $state['search'] }}</strong> <i class="ti ti-x"></i></a>
                </div>
            </div>
        @endif

        <div class="list-segment-bar">
            <span class="list-segment-hint"><i class="ti ti-info-circle"></i> Salary structures are built from active earnings. Components used in a structure can be deactivated but not deleted.</span>
        </div>

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Component</th>
                        <th>Type</th>
                        <th>Used by</th>
                        <th class="comp-share-col">Monthly total</th>
                        <th>Active</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($components as $component)
                        @php
                            $meta = $typeMeta[$component->type] ?? ['tone' => 'slate', 'icon' => 'ti-point', 'sign' => ''];
                            $use = $usage[$component->id] ?? null;
                            $total = (float) ($use->total ?? 0);
                            $used = $everUsed->has($component->id);
                        @endphp
                        <tr class="{{ $component->is_active ? '' : 'is-past-row' }}">
                            <td>
                                <div class="list-user">
                                    <span class="comp-code tone-{{ $meta['tone'] }}">{{ $component->code }}</span>
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $component->name }}</div>
                                        <div class="list-user-meta">Added {{ $component->created_at?->format('M j, Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="tag tone-{{ $meta['tone'] }}"><i class="ti {{ $meta['icon'] }}"></i>{{ SalaryComponent::TYPES[$component->type] ?? $component->type }}</span></td>
                            <td>
                                @if ($use)
                                    <span class="fw-semibold">{{ $use->employees }}</span> <span class="text-muted font-13">{{ \Illuminate\Support\Str::plural('employee', $use->employees) }}</span>
                                @else
                                    <span class="text-muted font-13">{{ $used ? 'Past structures only' : 'Not used yet' }}</span>
                                @endif
                            </td>
                            <td class="comp-share-col">
                                @if ($total > 0)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="salary-figure">{{ $money($total) }}</span>
                                        @if ($component->type === 'earning' && $earningTotal > 0)
                                            <span class="list-subtext">{{ round($total / $earningTotal * 100) }}%</span>
                                        @endif
                                    </div>
                                    @if ($component->type === 'earning' && $earningTotal > 0)
                                        <span class="week-hours-bar comp-share"><span style="width: {{ $total / $earningTotal * 100 }}%"></span></span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @can('update', $component)
                                    <form method="POST" action="{{ route('admin.salary-components.toggle', $component) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="switch-btn {{ $component->is_active ? 'is-on' : '' }}" role="switch" aria-checked="{{ $component->is_active ? 'true' : 'false' }}"
                                                title="{{ $component->is_active ? 'Active - click to deactivate' : 'Inactive - click to activate' }}">
                                            <span class="visually-hidden">{{ $component->is_active ? 'Deactivate' : 'Activate' }} {{ $component->name }}</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="status-dot status-dot-{{ $component->is_active ? 'success' : 'muted' }}">{{ $component->is_active ? 'Active' : 'Inactive' }}</span>
                                @endcan
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $component)
                                        <div class="dropdown d-inline-block">
                                            <button type="button" class="btn-icon btn-icon-accent" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Edit"><i class="ti ti-pencil font-16"></i></button>
                                            <div class="dropdown-menu dropdown-menu-end p-3 comp-edit-menu">
                                                <form method="POST" action="{{ route('admin.salary-components.update', $component) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <label class="form-label font-12 fw-semibold">Name</label>
                                                    <input type="text" name="name" class="form-control form-control-sm mb-2" value="{{ $component->name }}" maxlength="255" required>
                                                    <div class="row g-2 mb-2">
                                                        <div class="col-5">
                                                            <label class="form-label font-12 fw-semibold">Code</label>
                                                            <input type="text" name="code" class="form-control form-control-sm text-uppercase" value="{{ $component->code }}" maxlength="50" required>
                                                        </div>
                                                        <div class="col-7">
                                                            <label class="form-label font-12 fw-semibold">Type</label>
                                                            <select name="type" class="form-select form-select-sm" @if ($used) title="Used in structures - type is locked" @endif>
                                                                @foreach (SalaryComponent::TYPES as $value => $label)
                                                                    <option value="{{ $value }}" @selected($component->type === $value) @disabled($used && $component->type !== $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    @if ($used)
                                                        <div class="form-hint mt-0 mb-2"><i class="ti ti-lock"></i> Type is locked - used in salary structures.</div>
                                                    @endif
                                                    <button type="submit" class="btn btn-sm btn-de-primary text-light w-100">Save</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endcan
                                    @can('delete', $component)
                                        @if ($used)
                                            <span class="btn-icon disabled" title="Used in salary structures - deactivate instead"><i class="ti ti-trash-off font-16 text-muted"></i></span>
                                        @else
                                            <form action="{{ route('admin.salary-components.destroy', $component) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete “{{ addslashes($component->name) }}”?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Delete"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-cash font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No components match</div>
                                <span class="font-13"><a href="{{ route('admin.salary-components.index') }}">Show all components</a></span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@if ($canCreate)
    @push('scripts')
        <script>
            (function () {
                var name = document.getElementById('comp-name'), code = document.getElementById('comp-code');
                var touched = !!code.value;
                code.addEventListener('input', function () { touched = !!code.value; code.value = code.value.toUpperCase().replace(/[^A-Z0-9_\-]/g, ''); });
                // Suggest a code from the name's initials until one is typed.
                name.addEventListener('input', function () {
                    if (touched) return;
                    var words = name.value.trim().split(/\s+/).filter(Boolean);
                    code.value = (words.length > 1 ? words.map(function (w) { return w[0]; }).join('') : (words[0] || '').slice(0, 6)).toUpperCase().replace(/[^A-Z0-9]/g, '');
                });
                document.querySelectorAll('[data-suggest]').forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        var s = JSON.parse(chip.dataset.suggest);
                        name.value = s.name; code.value = s.code; touched = true;
                        document.querySelector('#component-form input[name="type"][value="' + s.type + '"]').checked = true;
                    });
                });
            })();
        </script>
    @endpush
@endif
