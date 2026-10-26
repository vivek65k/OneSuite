@extends('layouts.admin')

@section('title', 'Deductions')

@php
    use App\Models\PayrollRun;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pageUrl = fn (array $overrides = []) => route('admin.deductions.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All deductions', 'tone' => 'indigo', 'count' => $counts['all']],
        'pending' => ['label' => 'Waiting for payroll', 'tone' => 'amber', 'count' => $counts['pending']],
        'paid' => ['label' => 'Deducted', 'tone' => 'success', 'count' => $counts['paid']],
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
    <x-page-title title="Deductions" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for payroll" :value="$kpis['waiting']" icon="ti-clock" :tone="$kpis['waiting'] ? 'amber' : 'slate'"
                    :hint="$kpis['waiting'] ? $money($kpis['waiting_amount']).' to be taken from salary' : 'Nothing queued'" :href="$pageUrl(['tab' => 'pending'])" />
        <x-kpi-card label="{{ now()->format('F') }} deductions" :value="$kpis['month_people'] ? $money($kpis['month_amount']) : '—'" icon="ti-receipt" tone="indigo"
                    :hint="$kpis['month_people'] ? $kpis['month_people'].' '.Str::plural('person', $kpis['month_people']) : 'None for this month'" />
        <x-kpi-card label="Deducted in {{ now()->year }}" :value="$kpis['paid_year_count'] ? $money($kpis['paid_year']) : '—'" icon="ti-cash" tone="success"
                    :hint="$kpis['paid_year_count'].' '.Str::plural('deduction', $kpis['paid_year_count']).' taken through payroll'" :href="$pageUrl(['tab' => 'paid', 'year' => now()->year])" />
        <x-kpi-card label="Missed payroll" :value="$kpis['missed']" icon="ti-alert-triangle" :tone="$kpis['missed'] ? 'rose' : 'success'"
                    :hint="$kpis['missed'] ? $money($kpis['missed_amount']).' will not be recovered unless moved' : 'Every deduction has a payroll to go with'" :href="$pageUrl(['tab' => 'missed'])" />
    </div>

    @if ($kpis['missed'] && $state['tab'] !== 'missed')
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="ti ti-alert-triangle font-18"></i>
            <div><strong>{{ $kpis['missed'] }} {{ Str::plural('deduction', $kpis['missed']) }} missed payroll.</strong> Their month was already run, so nothing will take them. <a href="{{ $pageUrl(['tab' => 'missed']) }}">Review and move them</a> to the next payroll.</div>
        </div>
    @endif

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.deductions.store') }}" class="card form-section tone-rose mb-0 h-100" id="deduction-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-trending-down"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">New deduction</h5>
                            <p class="form-section-sub">Take one deduction from one person or many. It comes off their pay when that month's payroll runs.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="deduction-title">What for</label>
                                <input type="text" name="title" id="deduction-title" value="{{ old('title') }}" maxlength="120" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. Uniform cost" required>
                                <div class="quick-chips">
                                    @foreach (['Uniform cost', 'Salary advance recovery', 'Late coming fine', 'Asset damage', 'Canteen charges'] as $name)
                                        <button type="button" class="quick-chip" data-title="{{ $name }}">{{ $name }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="deduction-amount">Amount each</label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="deduction-amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" placeholder="0.00" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="deduction-period">Take from</label>
                                <select name="period" id="deduction-period" class="form-select @error('period') is-invalid @enderror" required>
                                    @foreach ($form['periods'] as $p)
                                        <option value="{{ $p['value'] }}" @selected($oldPeriod === $p['value']) @disabled($p['ran'])>{{ $p['label'] }}{{ $p['ran'] ? ' — already run' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                    <label class="form-label mb-0">Who it is taken from</label>
                                    <span class="count-pill" id="deduction-picked">0 selected</span>
                                    <div class="quick-chips mt-0 ms-auto">
                                        <button type="button" class="quick-chip" data-pick="all">Everyone</button>
                                        @foreach ($departments as $id => $name)
                                            <button type="button" class="quick-chip" data-pick="{{ $id }}">{{ $name }}</button>
                                        @endforeach
                                        <button type="button" class="quick-chip" data-pick="none">Clear</button>
                                    </div>
                                </div>
                                <div class="pick-list @error('employee_ids') is-invalid @enderror" id="deduction-people">
                                    @forelse ($form['people'] as $person)
                                        <label class="pick-item">
                                            <input type="checkbox" class="form-check-input" name="employee_ids[]" value="{{ $person['id'] }}" data-department="{{ $person['department_id'] }}" data-structure="{{ $person['structure'] ? 1 : 0 }}" data-salary="{{ $person['salary'] }}" data-name="{{ $person['name'] }}" @checked($oldIds->contains((string) $person['id']))>
                                            <x-avatar :person="$person['model']" />
                                            <span class="pick-item-text">
                                                <span class="pick-item-name">{{ $person['name'] }}</span>
                                                <span class="pick-item-meta">{{ $person['meta'] }}</span>
                                            </span>
                                            @unless ($person['structure'])
                                                <span class="tag tone-rose" title="Payroll skips people without a salary structure, so this deduction would not be taken">No salary structure</span>
                                            @endunless
                                        </label>
                                    @empty
                                        <div class="text-muted font-13 p-3">No employees on payroll yet.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="deduction-remarks">Note <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="remarks" id="deduction-remarks" value="{{ old('remarks') }}" maxlength="1000" class="form-control" placeholder="What it is for">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> <span id="deduction-submit-label">Add deduction</span></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">What this takes</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">People</span>
                                <span class="rev-compare-value" id="deduction-count">0</span>
                                <span class="rev-compare-meta" id="deduction-each">Pick who it is taken from</span>
                            </div>
                            <i class="ti ti-equal"></i>
                            <div>
                                <span class="rev-compare-label">Total recovered</span>
                                <span class="rev-compare-value" id="deduction-total">—</span>
                                <span class="rev-compare-meta" id="deduction-when"></span>
                            </div>
                        </div>
                        <div class="run-note tone-rose mt-3" id="deduction-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-amber mt-2" id="deduction-heavy" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>The deduction is taken from net pay on the payslip when you run payroll for the chosen month. Until then you can remove it.</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.deductions.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Deduction status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or deduction" aria-label="Search deductions">
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
                        <th>Deduction</th>
                        <th>Take from</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deductions as $deduction)
                        @php
                            $missed = $missedIds->has($deduction->id);
                            $paid = $deduction->status === 'paid';
                            $status = $paid ? ['Deducted', 'success', 'ti-circle-check'] : ($missed ? ['Missed payroll', 'rose', 'ti-alert-triangle'] : ['Waiting for payroll', 'amber', 'ti-clock']);
                        @endphp
                        <tr class="{{ $missed ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$deduction->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $deduction->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$deduction->employee?->employee_code, $deduction->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $deduction->title }}</div>
                                @if ($deduction->remarks)
                                    <div class="payslip-parts text-wrap" title="{{ $deduction->remarks }}">{{ Str::limit($deduction->remarks, 70) }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($paid && $deduction->payroll_run_id && $canSeeRuns && $runIds->has($deduction->payroll_run_id))
                                    <a href="{{ route('admin.payroll-runs.show', $deduction->payroll_run_id) }}" class="text-body" title="Open the payroll run that took it">{{ PayrollRun::MONTHS[$deduction->period_month] ?? $deduction->period_month }} {{ $deduction->period_year }}</a>
                                @else
                                    {{ PayrollRun::MONTHS[$deduction->period_month] ?? $deduction->period_month }} {{ $deduction->period_year }}
                                @endif
                            </td>
                            <td class="text-end"><span class="salary-figure">{{ $money($deduction->amount) }}</span></td>
                            <td>
                                <span class="tag tone-{{ $status[1] }}"><i class="ti {{ $status[2] }}"></i>{{ $status[0] }}</span>
                                @if ($missed)
                                    <div class="payslip-parts">That month's payroll already ran</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($missed)
                                        @can('update', $deduction)
                                            <form action="{{ route('admin.deductions.move', $deduction) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-arrow-forward-up"></i> Move to next payroll</button>
                                            </form>
                                        @endcan
                                    @endif
                                    @unless ($paid)
                                        @can('delete', $deduction)
                                            <form action="{{ route('admin.deductions.destroy', $deduction) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this deduction? It has not been taken yet.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Remove deduction"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-trending-down font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No deductions match</div>
                                    <span class="font-13"><a href="{{ route('admin.deductions.index') }}">Show all deductions</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No deductions yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Add the first one with the form above.' : 'Deductions added by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($totals['count'] > 0)
                    <tfoot>
                        <tr class="report-total">
                            <td class="fw-semibold" colspan="3">Total · {{ $totals['count'] }} {{ Str::plural('deduction', $totals['count']) }}</td>
                            <td class="text-end fw-bold">{{ $money($totals['amount']) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @if ($deductions->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $deductions->links() }}</div></div>
        @endif
    </div>
@endsection

@if ($canCreate)
    @push('scripts')
        <script>
            (function () {
                var form = document.getElementById('deduction-form');
                if (!form) return;
                var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name="employee_ids[]"]'));
                var amount = document.getElementById('deduction-amount'), period = document.getElementById('deduction-period'), title = document.getElementById('deduction-title');
                var el = function (id) { return document.getElementById(id); };
                var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); };

                function render() {
                    var picked = boxes.filter(function (b) { return b.checked; });
                    var each = parseFloat(amount.value);
                    var has = !isNaN(each) && each > 0;
                    var noStructure = picked.filter(function (b) { return b.dataset.structure === '0'; }).map(function (b) { return b.dataset.name; });
                    var month = period.options[period.selectedIndex] ? period.options[period.selectedIndex].text : '';

                    el('deduction-picked').textContent = picked.length + ' selected';
                    el('deduction-count').textContent = picked.length;
                    el('deduction-each').textContent = picked.length ? (has ? fmt(each) + ' each' : 'Enter the amount') : 'Pick who it is taken from';
                    el('deduction-total').textContent = picked.length && has ? fmt(each * picked.length) : '—';
                    el('deduction-when').textContent = month ? 'from ' + month + ' pay' : '';
                    el('deduction-submit-label').textContent = picked.length > 1 ? 'Add ' + picked.length + ' deductions' : 'Add deduction';
                    var heavy = has ? picked.filter(function (b) { return b.dataset.salary !== '' && each > parseFloat(b.dataset.salary) / 2; }) : [];
                    var over = heavy.filter(function (b) { return each > parseFloat(b.dataset.salary); });
                    el('deduction-heavy').hidden = !heavy.length;
                    el('deduction-heavy').querySelector('span').textContent = over.length
                        ? fmt(each) + ' is more than ' + over[0].dataset.name + ' earns in a month (' + fmt(over[0].dataset.salary) + '). Payroll would refuse to run, so this will be rejected.'
                        : (heavy.length ? fmt(each) + ' is more than half of ' + heavy[0].dataset.name + '\'s monthly salary' + (heavy.length > 1 ? ' (and ' + (heavy.length - 1) + ' more)' : '') + '. Consider splitting it over several months.' : '');
                    el('deduction-warn').hidden = !noStructure.length;
                    el('deduction-warn').querySelector('span').textContent = noStructure.length
                        ? noStructure.slice(0, 3).join(', ') + (noStructure.length > 3 ? ' and ' + (noStructure.length - 3) + ' more' : '') + (noStructure.length === 1 ? ' has' : ' have') + ' no salary structure. Payroll skips them, so their deduction would not be taken.'
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
                        el('deduction-people').classList.add('is-invalid');
                        el('deduction-picked').textContent = 'Pick at least one person';
                    }
                });
                render();
            })();
        </script>
    @endpush
@endif
