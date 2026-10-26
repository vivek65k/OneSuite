@extends('layouts.admin')

@section('title', 'Asset Return')

@php
    use App\Http\Controllers\Admin\Hrm\AssetReturnController;
    use App\Models\EmployeeAsset;
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.asset-returns.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $categoryMeta = [
        'laptop' => ['indigo', 'ti-device-laptop'], 'mobile' => ['teal', 'ti-device-mobile'], 'id_card' => ['amber', 'ti-id'],
        'sim_card' => ['success', 'ti-credit-card'], 'uniform' => ['rose', 'ti-shirt'], 'other' => ['slate', 'ti-box'],
    ];
    $conditionMeta = [
        'good' => ['Good', 'success', 'ti-circle-check', 'Came back in working order'],
        'damaged' => ['Damaged', 'amber', 'ti-alert-triangle', 'Came back, but needs repair'],
        'lost' => ['Lost', 'rose', 'ti-x', 'Did not come back'],
    ];
    $tabs = [
        '' => ['label' => 'To collect', 'tone' => 'indigo', 'count' => $counts['pending']],
        'due' => ['label' => 'Due now', 'tone' => 'rose', 'count' => $counts['due']],
        'returned' => ['label' => 'Returned', 'tone' => 'success', 'count' => $counts['returned']],
    ];
    $filterValues = [
        'category' => EmployeeAsset::CATEGORIES[$state['category']] ?? null,
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['category' => 'Category', 'employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $showReturned = $state['tab'] === 'returned';
    $canUpdate = auth()->user()->can('update', new EmployeeAsset);
    $span = fn ($from, $to) => ($d = (int) $from->diffInDays($to)) < 1 ? 'same day' : ($d < 60 ? $d.' '.Str::plural('day', $d) : ($d < 730 ? round($d / 30).' months' : round($d / 365, 1).' years'));
    $problems = $kpis['damaged'] + $kpis['lost'];
@endphp

@section('content')
    <x-page-title title="Asset Return" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Still out" :value="$kpis['out']" icon="ti-devices" tone="indigo" :hint="$kpis['out'] ? 'With '.$kpis['holders'].' '.Str::plural('person', $kpis['holders']) : 'Nothing is out'" />
        <x-kpi-card label="Due now" :value="$kpis['due']" icon="ti-alert-triangle" :tone="$kpis['due'] ? 'rose' : 'success'" :hint="$kpis['due'] ? 'With '.$kpis['due_people'].' '.Str::plural('person', $kpis['due_people']).' who left or are leaving' : 'Nobody leaving holds anything'" />
        <x-kpi-card label="Returned this month" :value="$kpis['month']" icon="ti-arrow-back-up" tone="teal" :hint="$kpis['returned'] ? $kpis['returned'].' returned in all' : 'Nothing has come back yet'" />
        <x-kpi-card label="Damaged or lost" :value="$problems" icon="ti-tool" :tone="$problems ? 'amber' : 'success'" :hint="$problems ? collect([$kpis['damaged'] ? $kpis['damaged'].' damaged' : null, $kpis['lost'] ? $kpis['lost'].' lost' : null])->filter()->implode(' · ') : 'Everything came back in good condition'" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.asset-returns.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Return status">
                    @foreach ($tabs as $value => $tab)
                        @if ($value !== 'due' || $tab['count'] || $state['tab'] === 'due')
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
                    <select name="category" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Category">
                        <option value="">All categories</option>
                        @foreach (EmployeeAsset::CATEGORIES as $value => $label)
                            <option value="{{ $value }}" @selected($state['category'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
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
                </div>
            </div>
        </form>

        @php
            // "Return all" covers exactly the rows the person has on this page.
            $bulkIds = $focus && ! $showReturned ? $assets->where('employee_id', $focus['employee']->id)->pluck('id') : collect();
        @endphp
        @if ($activeFilters->isNotEmpty() || $bulkIds->count() > 1)
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $pageUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                </div>
                @if ($canUpdate && $bulkIds->count() > 1)
                    <form method="POST" action="{{ route('admin.asset-returns.bulk') }}" class="ms-auto" onsubmit="return confirm('Mark all {{ $bulkIds->count() }} assets listed here as returned today in good condition?');">
                        @csrf
                        <input type="hidden" name="condition_at_return" value="good">
                        @foreach ($bulkIds as $assetId)
                            <input type="hidden" name="asset_ids[]" value="{{ $assetId }}">
                        @endforeach
                        <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-arrow-back-up"></i> Return all {{ $bulkIds->count() }} in good condition</button>
                    </form>
                @endif
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Asset</th>
                        <th>Tag / number</th>
                        <th>{{ $showReturned ? 'Returned by' : 'Held by' }}</th>
                        <th>{{ $showReturned ? 'Period' : 'Out since' }}</th>
                        @if ($showReturned)
                            <th>Condition</th>
                        @endif
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        @php
                            $meta = $categoryMeta[$asset->category] ?? ['slate', 'ti-box'];
                            $hasLeft = ! $showReturned && $leftIds->has($asset->employee_id);
                            $goes = ! $showReturned ? $leaving->get($asset->employee_id) : null;
                            $lastDay = $goes?->last_working_day;
                            $condition = $conditionMeta[$asset->condition_at_return] ?? $conditionMeta['good'];
                            $transferred = str_starts_with((string) $asset->remarks, 'Transferred to ');
                        @endphp
                        <tr class="{{ $hasLeft || ($lastDay && $lastDay->lte(today())) ? 'is-pending-row' : '' }}">
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
                                        <div class="payslip-parts">{{ $asset->identifier }}</div>
                                    @endif
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
                                            @if ($hasLeft)
                                                <span class="text-danger">Has left the company</span>
                                            @elseif ($goes)
                                                <span class="text-danger">{{ $lastDay ? ($lastDay->isPast() && ! $lastDay->isToday() ? 'Last day was '.$lastDay->format('M j') : ($lastDay->isToday() ? 'Last day is today' : 'Leaving on '.$lastDay->format('M j, Y'))) : 'Resignation accepted' }}</span>
                                            @else
                                                {{ collect([$asset->employee?->employee_code, $asset->employee?->department?->name])->filter()->implode(' · ') }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                @if ($asset->assigned_date)
                                    {{ $asset->assigned_date->format('M j, Y') }}{{ $showReturned && $asset->returned_date ? ' – '.$asset->returned_date->format('M j, Y') : '' }}
                                    <div class="payslip-parts">{{ $showReturned ? ($asset->returned_date ? 'kept '.$span($asset->assigned_date, $asset->returned_date) : '') : ($asset->assigned_date->isToday() ? 'since today' : 'for '.$span($asset->assigned_date, today())) }}</div>
                                @else
                                    <span class="text-muted">{{ $showReturned && $asset->returned_date ? 'Returned '.$asset->returned_date->format('M j, Y') : '—' }}</span>
                                @endif
                            </td>
                            @if ($showReturned)
                                <td>
                                    <span class="tag tone-{{ $transferred ? 'indigo' : $condition[1] }}"><i class="ti {{ $transferred ? 'ti-switch-horizontal' : $condition[2] }}"></i>{{ $transferred ? 'Transferred' : $condition[0] }}</span>
                                    @if ($asset->remarks)
                                        <div class="payslip-parts text-wrap" title="{{ $asset->remarks }}">{{ Str::limit($asset->remarks, 60) }}</div>
                                    @endif
                                </td>
                            @endif
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $asset)
                                        @if ($showReturned)
                                            @unless ($transferred)
                                                <form method="POST" action="{{ route('admin.asset-returns.undo', $asset) }}" class="d-inline" onsubmit="return confirm('Undo this return? The asset will show as still being with the employee.');">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn-icon" title="Undo return (recorded by mistake)"><i class="ti ti-arrow-back-up font-16"></i></button>
                                                </form>
                                            @endunless
                                        @else
                                            <button type="button" class="btn btn-sm review-btn is-approve" data-bs-toggle="modal" data-bs-target="#return-modal"
                                                    data-action="{{ route('admin.asset-returns.update', $asset) }}" data-name="{{ $asset->asset_name }}" data-holder="{{ $asset->employee?->name }}" data-tag="{{ $asset->asset_tag ?? $asset->identifier }}" data-since="{{ $asset->assigned_date?->toDateString() }}"><i class="ti ti-arrow-back-up"></i> Return</button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $showReturned ? 6 : 5 }}" class="text-center text-muted py-5">
                                <i class="ti ti-arrow-back-up font-24 d-block mb-2"></i>
                                @if ($activeFilters->isNotEmpty())
                                    <div class="fw-semibold text-body mb-1">Nothing matches</div>
                                    <span class="font-13"><a href="{{ route('admin.asset-returns.index', array_filter(['tab' => $state['tab']])) }}">Clear the filters</a></span>
                                @elseif ($showReturned)
                                    <div class="fw-semibold text-body mb-1">Nothing has been returned yet</div>
                                    <span class="font-13">Assets taken back will be listed here with their condition.</span>
                                @elseif ($state['tab'] === 'due')
                                    <div class="fw-semibold text-body mb-1">Nothing is due</div>
                                    <span class="font-13">Nobody who left or is leaving holds an asset.</span>
                                @else
                                    <div class="fw-semibold text-body mb-1">Nothing to collect</div>
                                    <span class="font-13">No assets are out with employees right now.</span>
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

    @if ($canUpdate)
        <div class="modal fade" id="return-modal" tabindex="-1" aria-labelledby="return-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="return-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="return-modal-title">Take back asset</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="return-intro"></div>
                        <label class="form-label d-block">Condition</label>
                        <div class="rel-pick">
                            @foreach (AssetReturnController::CONDITIONS as $value => $label)
                                <label class="rel-option tone-{{ $conditionMeta[$value][1] }}">
                                    <input type="radio" name="condition_at_return" value="{{ $value }}" @checked($value === 'good') required>
                                    <i class="ti {{ $conditionMeta[$value][2] }}"></i>
                                    <span class="rel-option-title">{{ $label }}</span>
                                    <span class="rel-option-hint">{{ $conditionMeta[$value][3] }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="row g-3 mt-0">
                            <div class="col-5">
                                <label class="form-label" for="return-date" id="return-date-label">Returned on</label>
                                <input type="date" name="returned_date" id="return-date" max="{{ today()->toDateString() }}" class="form-control" required>
                            </div>
                            <div class="col-7">
                                <label class="form-label" for="return-remarks">Note <span class="text-muted fw-normal" id="return-remarks-need">(optional)</span></label>
                                <input type="text" name="remarks" id="return-remarks" maxlength="1000" class="form-control" placeholder="e.g. charger missing">
                            </div>
                        </div>
                        <div class="font-12 text-muted mt-2" id="return-note"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> <span id="return-submit">Mark as returned</span></button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var modal = el('return-modal');
            if (!modal) return;
            var today = @json(today()->toDateString());
            var radios = Array.prototype.slice.call(modal.querySelectorAll('input[name="condition_at_return"]'));

            // Damaged and lost need an explanation; "lost" has a date it was reported rather than returned.
            function render() {
                var picked = (radios.filter(function (r) { return r.checked; })[0] || {}).value || 'good';
                var needs = picked !== 'good';
                el('return-remarks').required = needs;
                el('return-remarks-need').textContent = needs ? '(required)' : '(optional)';
                el('return-remarks').placeholder = picked === 'damaged' ? 'What is damaged' : (picked === 'lost' ? 'How it was lost' : 'e.g. charger missing');
                el('return-date-label').textContent = picked === 'lost' ? 'Reported lost on' : 'Returned on';
                el('return-submit').textContent = picked === 'lost' ? 'Mark as lost' : (picked === 'damaged' ? 'Mark as returned damaged' : 'Mark as returned');
                el('return-note').textContent = picked === 'good' ? '' : 'The cost is not deducted automatically. Add a deduction under Payroll if the employee has to pay for it.';
            }

            modal.addEventListener('show.bs.modal', function (event) {
                var b = event.relatedTarget;
                if (!b) return;
                el('return-form').action = b.dataset.action;
                el('return-intro').textContent = b.dataset.name + (b.dataset.tag ? ' (' + b.dataset.tag + ')' : '') + ', with ' + (b.dataset.holder || 'the employee') + '.';
                radios.forEach(function (r) { r.checked = r.value === 'good'; });
                el('return-date').value = today; el('return-date').min = b.dataset.since || '';
                el('return-remarks').value = '';
                render();
            });
            radios.forEach(function (r) { r.addEventListener('change', render); });
        })();
    </script>
@endpush
