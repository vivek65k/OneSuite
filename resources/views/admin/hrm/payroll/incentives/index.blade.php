@extends('layouts.admin')

@section('title', 'Incentives')

@php
    use App\Models\PayrollRun;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pageUrl = fn (array $overrides = []) => route('admin.incentives.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All incentives', 'tone' => 'indigo', 'count' => $counts['all']],
        'pending' => ['label' => 'Waiting for payroll', 'tone' => 'amber', 'count' => $counts['pending']],
        'paid' => ['label' => 'Paid', 'tone' => 'success', 'count' => $counts['paid']],
        'missed' => ['label' => 'Missed payroll', 'tone' => 'rose', 'count' => $counts['missed']],
    ];
    $filterValues = [
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'year' => $state['year'],
        'search' => $state['search'],
    ];
    $activeFilters = collect(['employee_id' => 'Employee', 'department_id' => 'Department', 'year' => 'Year', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $oldIds = collect((array) old('employee_ids', []))->map(fn ($v) => (string) $v);
    $oldPeriod = old('period', $form['default'] ?? null);
    $canSeeRuns = auth()->user()->can('viewAny', PayrollRun::class);
@endphp

@section('content')
    <x-page-title title="Incentives" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for payroll" :value="$kpis['waiting']" icon="ti-clock" :tone="$kpis['waiting'] ? 'amber' : 'slate'"
                    :hint="$kpis['waiting'] ? $money($kpis['waiting_amount']).' to be paid with salary' : 'Nothing queued'" :href="$pageUrl(['tab' => 'pending'])" />
        <x-kpi-card label="{{ now()->format('F') }} incentives" :value="$kpis['month_people'] ? $money($kpis['month_amount']) : '—'" icon="ti-target" tone="indigo"
                    :hint="$kpis['month_people'] ? $kpis['month_people'].' '.Str::plural('person', $kpis['month_people']) : 'None for this month'" />
        <x-kpi-card label="Paid in {{ now()->year }}" :value="$kpis['paid_year_count'] ? $money($kpis['paid_year']) : '—'" icon="ti-cash" tone="success"
                    :hint="$kpis['paid_year_count'].' '.Str::plural('incentive', $kpis['paid_year_count']).' paid through payroll'" :href="$pageUrl(['tab' => 'paid', 'year' => now()->year])" />
        <x-kpi-card label="Missed payroll" :value="$kpis['missed']" icon="ti-alert-triangle" :tone="$kpis['missed'] ? 'rose' : 'success'"
                    :hint="$kpis['missed'] ? $money($kpis['missed_amount']).' will not be paid unless moved' : 'Every incentive has a payroll to go with'" :href="$pageUrl(['tab' => 'missed'])" />
    </div>

    @if ($kpis['missed'] && $state['tab'] !== 'missed')
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="ti ti-alert-triangle font-18"></i>
            <div><strong>{{ $kpis['missed'] }} {{ Str::plural('incentive', $kpis['missed']) }} missed payroll.</strong> Their month was already run, so nothing will pay them. <a href="{{ $pageUrl(['tab' => 'missed']) }}">Review and move them</a> to the next payroll.</div>
        </div>
    @endif

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.incentives.store') }}" class="card form-section tone-indigo mb-0 h-100" id="incentive-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-target"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">New incentive</h5>
                            <p class="form-section-sub">Give one incentive to one person or many. It is added to their payslip when that month's payroll runs.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="incentive-title">Incentive name</label>
                                <input type="text" name="title" id="incentive-title" value="{{ old('title') }}" maxlength="120" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. Sales incentive" required>
                                <div class="quick-chips">
                                    @foreach (['Sales incentive', 'Target achieved', 'Attendance incentive', 'Project completion', 'Customer rating'] as $name)
                                        <button type="button" class="quick-chip" data-title="{{ $name }}">{{ $name }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="incentive-amount">Amount each</label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="incentive-amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" placeholder="0.00" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="incentive-period">Pay with</label>
                                <select name="period" id="incentive-period" class="form-select @error('period') is-invalid @enderror" required>
                                    @foreach ($form['periods'] as $p)
                                        <option value="{{ $p['value'] }}" @selected($oldPeriod === $p['value']) @disabled($p['ran'])>{{ $p['label'] }}{{ $p['ran'] ? ' — already run' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                    <label class="form-label mb-0">Who gets it</label>
                                    <span class="count-pill" id="incentive-picked">0 selected</span>
                                    <div class="quick-chips mt-0 ms-auto">
                                        <button type="button" class="quick-chip" data-pick="all">Everyone</button>
                                        @foreach ($departments as $id => $name)
                                            <button type="button" class="quick-chip" data-pick="{{ $id }}">{{ $name }}</button>
                                        @endforeach
                                        <button type="button" class="quick-chip" data-pick="none">Clear</button>
                                    </div>
                                </div>
                                <div class="pick-list @error('employee_ids') is-invalid @enderror" id="incentive-people">
                                    @forelse ($form['people'] as $person)
                                        <label class="pick-item">
                                            <input type="checkbox" class="form-check-input" name="employee_ids[]" value="{{ $person['id'] }}" data-department="{{ $person['department_id'] }}" data-structure="{{ $person['structure'] ? 1 : 0 }}" data-name="{{ $person['name'] }}" @checked($oldIds->contains((string) $person['id']))>
                                            <x-avatar :person="$person['model']" />
                                            <span class="pick-item-text">
                                                <span class="pick-item-name">{{ $person['name'] }}</span>
                                                <span class="pick-item-meta">{{ $person['meta'] }}</span>
                                            </span>
                                            @unless ($person['structure'])
                                                <span class="tag tone-rose" title="Payroll skips people without a salary structure, so this incentive would not be paid">No salary structure</span>
                                            @endunless
                                        </label>
                                    @empty
                                        <div class="text-muted font-13 p-3">No employees on payroll yet.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="incentive-remarks">Note <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="remarks" id="incentive-remarks" value="{{ old('remarks') }}" maxlength="1000" class="form-control" placeholder="What it was earned for, e.g. 120% of Q3 target">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> <span id="incentive-submit-label">Add incentive</span></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">What this adds</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">People</span>
                                <span class="rev-compare-value" id="incentive-count">0</span>
                                <span class="rev-compare-meta" id="incentive-each">Pick who gets it</span>
                            </div>
                            <i class="ti ti-equal"></i>
                            <div>
                                <span class="rev-compare-label">Total cost</span>
                                <span class="rev-compare-value" id="incentive-total">—</span>
                                <span class="rev-compare-meta" id="incentive-when"></span>
                            </div>
                        </div>
                        <div class="run-note tone-rose mt-3" id="incentive-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>The incentive is added to gross pay on the payslip when you run payroll for the chosen month. Until then you can remove it.</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.incentives.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Incentive status">
                    @foreach ($tabs as $value => $tab)
                        @if ($value !== 'missed' || $tab['count'] || $state['tab'] === 'missed')
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or incentive" aria-label="Search incentives">
                    </div>
                    @if ($years->count() > 1)
                        <select name="year" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Year">
                            <option value="">All years</option>
                            @foreach ($years as $year)
                                <option value="{{ $year }}" @selected($state['year'] === (int) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    @elseif ($state['year'])
                        <input type="hidden" name="year" value="{{ $state['year'] }}">
                    @endif
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
                        <th>Employee</th>
                        <th>Incentive</th>
                        <th>Pay with</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incentives as $incentive)
                        @php
                            $missed = $missedIds->has($incentive->id);
                            $paid = $incentive->status === 'paid';
                            $status = $paid ? ['Paid', 'success', 'ti-circle-check'] : ($missed ? ['Missed payroll', 'rose', 'ti-alert-triangle'] : ['Waiting for payroll', 'amber', 'ti-clock']);
                        @endphp
                        <tr class="{{ $missed ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$incentive->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $incentive->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$incentive->employee?->employee_code, $incentive->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $incentive->title }}</div>
                                @if ($incentive->remarks)
                                    <div class="payslip-parts text-wrap" title="{{ $incentive->remarks }}">{{ Str::limit($incentive->remarks, 70) }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($paid && $incentive->payroll_run_id && $canSeeRuns && $runIds->has($incentive->payroll_run_id))
                                    <a href="{{ route('admin.payroll-runs.show', $incentive->payroll_run_id) }}" class="text-body" title="Open the payroll run that paid it">{{ PayrollRun::MONTHS[$incentive->period_month] ?? $incentive->period_month }} {{ $incentive->period_year }}</a>
                                @else
                                    {{ PayrollRun::MONTHS[$incentive->period_month] ?? $incentive->period_month }} {{ $incentive->period_year }}
                                @endif
                            </td>
                            <td class="text-end"><span class="salary-figure">{{ $money($incentive->amount) }}</span></td>
                            <td>
                                <span class="tag tone-{{ $status[1] }}"><i class="ti {{ $status[2] }}"></i>{{ $status[0] }}</span>
                                @if ($missed)
                                    <div class="payslip-parts">That month's payroll already ran</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($missed)
                                        @can('update', $incentive)
                                            <form action="{{ route('admin.incentives.move', $incentive) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-arrow-forward-up"></i> Move to next payroll</button>
                                            </form>
                                        @endcan
                                    @endif
                                    @unless ($paid)
                                        @can('delete', $incentive)
                                            <form action="{{ route('admin.incentives.destroy', $incentive) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this incentive? It has not been paid yet.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Remove incentive"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-target font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No incentives match</div>
                                    <span class="font-13"><a href="{{ route('admin.incentives.index') }}">Show all incentives</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No incentives yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Add the first one with the form above.' : 'Incentives added by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($totals['count'] > 0)
                    <tfoot>
                        <tr class="report-total">
                            <td class="fw-semibold" colspan="3">Total · {{ $totals['count'] }} {{ Str::plural('incentive', $totals['count']) }}</td>
                            <td class="text-end fw-bold">{{ $money($totals['amount']) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @if ($incentives->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $incentives->links() }}</div></div>
        @endif
    </div>
@endsection

@if ($canCreate)
    @push('scripts')
        <script>
            (function () {
                var form = document.getElementById('incentive-form');
                if (!form) return;
                var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name="employee_ids[]"]'));
                var amount = document.getElementById('incentive-amount'), period = document.getElementById('incentive-period'), title = document.getElementById('incentive-title');
                var el = function (id) { return document.getElementById(id); };
                var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); };

                function render() {
                    var picked = boxes.filter(function (b) { return b.checked; });
                    var each = parseFloat(amount.value);
                    var has = !isNaN(each) && each > 0;
                    var noStructure = picked.filter(function (b) { return b.dataset.structure === '0'; }).map(function (b) { return b.dataset.name; });
                    var month = period.options[period.selectedIndex] ? period.options[period.selectedIndex].text : '';

                    el('incentive-picked').textContent = picked.length + ' selected';
                    el('incentive-count').textContent = picked.length;
                    el('incentive-each').textContent = picked.length ? (has ? fmt(each) + ' each' : 'Enter the amount') : 'Pick who gets it';
                    el('incentive-total').textContent = picked.length && has ? fmt(each * picked.length) : '—';
                    el('incentive-when').textContent = month ? 'with ' + month + ' payroll' : '';
                    el('incentive-submit-label').textContent = picked.length > 1 ? 'Add ' + picked.length + ' incentives' : 'Add incentive';
                    el('incentive-warn').hidden = !noStructure.length;
                    el('incentive-warn').querySelector('span').textContent = noStructure.length
                        ? noStructure.slice(0, 3).join(', ') + (noStructure.length > 3 ? ' and ' + (noStructure.length - 3) + ' more' : '') + (noStructure.length === 1 ? ' has' : ' have') + ' no salary structure. Payroll skips them, so their incentive would not be paid.'
                        : '';
                }

                boxes.forEach(function (b) { b.addEventListener('change', render); });
                [amount, period].forEach(function (input) { input.addEventListener('input', render); input.addEventListener('change', render); });
                form.querySelectorAll('[data-pick]').forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        var pick = chip.dataset.pick;
                        boxes.forEach(function (b) { b.checked = pick === 'all' || (pick !== 'none' && b.dataset.department === pick); });
                        render();
                    });
                });
                form.querySelectorAll('[data-title]').forEach(function (chip) {
                    chip.addEventListener('click', function () { title.value = chip.dataset.title; });
                });
                form.addEventListener('submit', function (event) {
                    if (!boxes.some(function (b) { return b.checked; })) {
                        event.preventDefault();
                        el('incentive-people').classList.add('is-invalid');
                        el('incentive-picked').textContent = 'Pick at least one person';
                    }
                });
                render();
            })();
        </script>
    @endpush
@endif
