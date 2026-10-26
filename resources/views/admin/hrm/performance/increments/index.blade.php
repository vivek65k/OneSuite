@extends('layouts.admin')

@section('title', 'Increment')

@php
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.').'%';
    $pageUrl = fn (array $overrides = []) => route('admin.increments.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All increments', 'tone' => 'indigo', 'count' => $counts['all']],
        'effective' => ['label' => 'In effect', 'tone' => 'success', 'count' => $counts['effective']],
        'scheduled' => ['label' => 'Scheduled', 'tone' => 'amber', 'count' => $counts['scheduled']],
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
    $oldEmployee = (string) old('employee_id', '');
    $rating = fn ($appraisal) => $appraisal?->overall_rating !== null ? rtrim(rtrim(number_format((float) $appraisal->overall_rating, 2), '0'), '.') : null;
@endphp

@section('content')
    <x-page-title title="Increment" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Increments in {{ now()->year }}" :value="$kpis['this_year']" icon="ti-trending-up" tone="indigo"
                    :hint="$kpis['this_year'] ? $kpis['people'].' '.Str::plural('employee', $kpis['people']).($kpis['scheduled'] ? ' · '.$kpis['scheduled'].' scheduled' : '') : 'None yet this year'" :href="$pageUrl(['year' => now()->year, 'tab' => null])" />
        <x-kpi-card label="Average increment" :value="$kpis['average'] !== null ? $pct($kpis['average']) : '—'" icon="ti-percentage" tone="success"
                    :hint="$kpis['average'] !== null ? 'Highest this year '.$pct($kpis['highest']) : 'No increments this year'" />
        <x-kpi-card label="Added to monthly payroll" :value="$kpis['added'] > 0 ? $money($kpis['added']) : '—'" icon="ti-cash" tone="teal"
                    :hint="$kpis['added'] > 0 ? $money($kpis['added'] * 12).' a year' : 'From this year\'s increments'" />
        <x-kpi-card label="Appraisals waiting" :value="$kpis['waiting']" icon="ti-star" :tone="$kpis['waiting'] ? 'amber' : 'slate'"
                    :hint="$kpis['waiting'] ? 'Completed, no increment given yet' : 'No completed appraisal is waiting'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3" id="give">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.increments.store') }}" class="card form-section tone-indigo mb-0 h-100" id="increment-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-trending-up"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Give an increment</h5>
                            <p class="form-section-sub">Raises the employee's monthly salary by a percentage from the date you choose.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="inc-employee">Employee</label>
                                <select name="employee_id" id="inc-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formData as $id => $person)
                                        <option value="{{ $id }}" @selected($oldEmployee === (string) $id)>{{ $person['name'] }}{{ $person['meta'] ? ' — '.$person['meta'] : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="inc-appraisal">Linked appraisal <span class="text-muted fw-normal">(optional)</span></label>
                                <select name="appraisal_id" id="inc-appraisal" class="form-select @error('appraisal_id') is-invalid @enderror" data-old="{{ old('appraisal_id') }}">
                                    <option value="">Not linked to an appraisal</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="inc-percent">Increment %</label>
                                <input type="number" step="0.01" min="0.01" max="{{ $maxPercent }}" name="increment_percent" id="inc-percent" value="{{ old('increment_percent') }}" class="form-control @error('increment_percent') is-invalid @enderror" placeholder="0.00" required>
                                <div class="quick-chips">
                                    @foreach ([5, 8, 10, 12, 15] as $chip)
                                        <button type="button" class="quick-chip" data-percent="{{ $chip }}">{{ $chip }}%</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="inc-new">New monthly salary</label>
                                <input type="number" step="0.01" min="0" id="inc-new" class="form-control" placeholder="Works out the %">
                                <div class="form-text">Type either one; the other follows.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="inc-date">Effective from</label>
                                <input type="date" name="effective_date" id="inc-date" value="{{ old('effective_date', now()->toDateString()) }}" max="{{ now()->addYear()->toDateString() }}" class="form-control @error('effective_date') is-invalid @enderror" required>
                                <div class="quick-chips">
                                    <button type="button" class="quick-chip" data-date="{{ now()->startOfMonth()->toDateString() }}">1st of this month</button>
                                    <button type="button" class="quick-chip" data-date="{{ now()->addMonthNoOverflow()->startOfMonth()->toDateString() }}">1st of next month</button>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="inc-remarks">Note <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="remarks" id="inc-remarks" value="{{ old('remarks') }}" maxlength="1000" class="form-control" placeholder="e.g. Annual appraisal, exceeded targets">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Give increment</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-3">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">What this increment does</span>
                    </div>
                    <div class="card-body">
                        <div class="empty-state py-3" id="inc-preview-empty">
                            <div class="empty-state-icon"><i class="ti ti-user"></i></div>
                            <div class="fw-semibold">Pick an employee</div>
                            <div class="font-13 text-muted">Their salary and the raise will show here.</div>
                        </div>
                        <div id="inc-preview" hidden>
                            <div class="rev-compare">
                                <div>
                                    <span class="rev-compare-label">Before</span>
                                    <span class="rev-compare-value" id="inc-before">—</span>
                                    <span class="rev-compare-meta" id="inc-before-meta"></span>
                                </div>
                                <i class="ti ti-arrow-right"></i>
                                <div>
                                    <span class="rev-compare-label">After</span>
                                    <span class="rev-compare-value" id="inc-after">—</span>
                                    <span class="rev-compare-meta" id="inc-after-meta"></span>
                                </div>
                            </div>
                            <div class="run-lines">
                                <div><span>More a month</span><strong id="inc-diff">—</strong></div>
                                <div><span>More a year</span><strong id="inc-diff-year">—</strong></div>
                                <div><span>Last increment</span><strong id="inc-last">—</strong></div>
                            </div>
                            <div class="run-note tone-amber mt-3" id="inc-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                            <div class="run-note tone-indigo mt-2" id="inc-structure-note" hidden><i class="ti ti-info-circle"></i><span>Payroll pays from the salary structure, not this figure. <a href="#" id="inc-structure-link">Update the structure</a> so the payslip shows the raise.</span></div>
                        </div>
                    </div>
                </div>

                @if ($waiting->isNotEmpty())
                    <div class="card side-list tone-amber mb-0">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-star"></i></span>
                            <span class="fw-semibold">Appraisals waiting for an increment</span>
                            <span class="side-list-count">{{ $waiting->count() }}</span>
                        </div>
                        @foreach ($waiting->take(6) as $appraisal)
                            <button type="button" class="side-list-item w-100 text-start border-0 bg-transparent" data-give="{{ $appraisal->employee_id }}" data-appraisal="{{ $appraisal->id }}">
                                <x-avatar :person="$appraisal->employee" />
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $appraisal->employee?->name }}</div>
                                    <div class="font-12 text-muted">{{ $appraisal->appraisalCycle?->name ?? 'Appraisal' }}{{ $appraisal->completed_at ? ' · completed '.$appraisal->completed_at->format('M j') : '' }}</div>
                                </div>
                                @if ($rating($appraisal))
                                    <span class="tag tone-amber"><i class="ti ti-star"></i>{{ $rating($appraisal) }}</span>
                                @endif
                            </button>
                        @endforeach
                        @if ($waiting->count() > 6)
                            <div class="side-list-foot">+{{ $waiting->count() - 6 }} more</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.increments.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Increment status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or code" aria-label="Search increments">
                    </div>
                    @if ($years->count() > 1)
                        <select name="year" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Year">
                            <option value="">All years</option>
                            @foreach ($years as $year)
                                <option value="{{ $year }}" @selected($state['year'] === $year)>{{ $year }}</option>
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
                        <th>Effective from</th>
                        <th class="text-end">Was</th>
                        <th class="text-end">New salary</th>
                        <th>Increment</th>
                        <th>Appraisal</th>
                        <th>Note</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($increments as $increment)
                        @php
                            $future = $increment->effective_date->gt(today());
                            $diff = (float) $increment->new_amount - (float) $increment->previous_amount;
                        @endphp
                        <tr class="{{ $future ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$increment->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ $pageUrl(['employee_id' => $increment->employee_id, 'tab' => null]) }}" title="Show this person's increments">{{ $increment->employee?->name ?? 'Removed employee' }}</a></div>
                                        <div class="list-user-meta">{{ collect([$increment->employee?->employee_code, $increment->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="fw-semibold">{{ $increment->effective_date->format('M j, Y') }}</div>
                                @if ($future)
                                    <span class="tag tone-amber"><i class="ti ti-calendar-event"></i>Scheduled</span>
                                @else
                                    <div class="payslip-parts">{{ $increment->effective_date->diffForHumans() }}</div>
                                @endif
                            </td>
                            <td class="text-end text-muted">{{ $money($increment->previous_amount) }}</td>
                            <td class="text-end"><span class="salary-figure">{{ $money($increment->new_amount) }}</span></td>
                            <td class="text-nowrap">
                                <span class="tag tone-success"><i class="ti ti-arrow-up-right"></i>+{{ $pct($increment->increment_percent) }}</span>
                                <div class="payslip-parts">+{{ $money($diff) }} a month</div>
                            </td>
                            <td>
                                @if ($increment->appraisal)
                                    <div>{{ $increment->appraisal->appraisalCycle?->name ?? 'Appraisal' }}</div>
                                    @if ($rating($increment->appraisal))
                                        <div class="payslip-parts"><i class="ti ti-star"></i> rated {{ $rating($increment->appraisal) }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="rev-notes" title="{{ $increment->remarks }}">{{ $increment->remarks ? Str::limit($increment->remarks, 60) : '—' }}</td>
                            <td class="text-end text-nowrap">
                                @if ($undoable->has($increment->id))
                                    @can('delete', $increment)
                                        <form action="{{ route('admin.increments.destroy', $increment) }}" method="POST" class="d-inline" onsubmit="return confirm('Undo this {{ $pct($increment->increment_percent) }} increment? The salary it created is removed too.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Undo increment"><i class="ti ti-arrow-back-up font-16"></i></button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="ti ti-trending-up font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No increments match</div>
                                    <span class="font-13"><a href="{{ route('admin.increments.index') }}">Show all increments</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No increments yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Give the first one with the form above.' : 'Increments given by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($totals['count'] > 0)
                    <tfoot>
                        <tr class="report-total">
                            <td class="fw-semibold" colspan="4">Total · {{ $totals['count'] }} {{ Str::plural('increment', $totals['count']) }}</td>
                            <td class="fw-bold" colspan="4">+{{ $money($totals['added']) }} a month</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @if ($increments->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $increments->links() }}</div></div>
        @endif
    </div>
@endsection

@if ($canCreate)
    @push('scripts')
        <script>
            (function () {
                var form = document.getElementById('increment-form');
                if (!form) return;
                var people = @json($formData);
                var today = @json(now()->toDateString());
                var structureUrl = @json(route('admin.salary-structures.edit', '__ID__'));
                var el = function (id) { return document.getElementById(id); };
                var employee = el('inc-employee'), appraisal = el('inc-appraisal'), percent = el('inc-percent'), target = el('inc-new'), date = el('inc-date');
                var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); };

                // The salary the increment builds on: the last one that started before the effective date.
                function baseFor(p) {
                    var base = null;
                    p.history.forEach(function (h) { if (!date.value || h.date < date.value) base = h; });
                    return base;
                }

                function fillAppraisals(selected) {
                    var p = people[employee.value];
                    appraisal.innerHTML = '<option value="">Not linked to an appraisal</option>';
                    (p ? p.appraisals : []).forEach(function (a) {
                        var o = document.createElement('option');
                        o.value = a.id; o.textContent = a.label;
                        if (String(a.id) === String(selected)) o.selected = true;
                        appraisal.appendChild(o);
                    });
                    appraisal.disabled = !p || !p.appraisals.length;
                    if (p && !p.appraisals.length) appraisal.options[0].textContent = 'No completed appraisal waiting';
                }

                function render(source) {
                    var p = people[employee.value];
                    el('inc-preview').hidden = !p;
                    el('inc-preview-empty').hidden = !!p;
                    if (!p) return;

                    var base = baseFor(p), warn = '';
                    var pc = parseFloat(percent.value);
                    if (source === 'target' && base) {
                        var t = parseFloat(target.value);
                        pc = t > base.amount ? Math.round((t - base.amount) / base.amount * 10000) / 100 : NaN;
                        percent.value = isNaN(pc) ? '' : pc;
                    }
                    var has = base && !isNaN(pc) && pc > 0;
                    var next = has ? Math.round(base.amount * (1 + pc / 100) * 100) / 100 : null;
                    if (source !== 'target') target.value = next !== null ? next.toFixed(2) : '';

                    el('inc-before').textContent = base ? fmt(base.amount) : 'Not set';
                    el('inc-before-meta').textContent = base ? 'since ' + base.date : 'No salary on record';
                    el('inc-after').textContent = next !== null ? fmt(next) : '—';
                    el('inc-after-meta').textContent = date.value ? (date.value > today ? 'scheduled, from ' + date.value : 'from ' + date.value) : '';
                    el('inc-diff').textContent = next !== null ? '+' + fmt(next - base.amount) + ' (+' + pc + '%)' : '—';
                    el('inc-diff-year').textContent = next !== null ? '+' + fmt((next - base.amount) * 12) : '—';
                    el('inc-last').textContent = p.last || 'None on record';

                    if (!base) warn = p.name + ' has no salary on record' + (p.history.length ? ' before that date' : '') + '. Record it under Salary Revision first.';
                    else if (date.value && p.history.some(function (h) { return h.date === date.value; })) warn = p.name + ' already has a salary change effective that date.';
                    else if (date.value && p.joined && date.value < p.joined) warn = 'That is before ' + p.name + ' joined (' + p.joined + ').';
                    else if (source === 'target' && parseFloat(target.value) <= base.amount) warn = 'The new salary must be more than the current ' + fmt(base.amount) + '.';
                    else if (has && pc > 30) warn = 'A ' + pc + '% increment is unusually large. Check the figure.';
                    else if (date.value && p.history.some(function (h) { return h.date > date.value; })) warn = 'A later salary change already exists, so this one will not be the current salary.';
                    el('inc-warn').hidden = !warn;
                    el('inc-warn').querySelector('span').textContent = warn;

                    var mismatch = next !== null && (p.structure === null || Math.abs(p.structure - next) > 0.5);
                    el('inc-structure-note').hidden = !mismatch;
                    el('inc-structure-link').href = structureUrl.replace('__ID__', employee.value);
                }

                employee.addEventListener('change', function () { fillAppraisals(''); render(); });
                percent.addEventListener('input', function () { render('percent'); });
                target.addEventListener('input', function () { render('target'); });
                date.addEventListener('change', function () { render(); });
                form.querySelectorAll('[data-percent]').forEach(function (chip) {
                    chip.addEventListener('click', function () { percent.value = chip.dataset.percent; render('percent'); });
                });
                form.querySelectorAll('[data-date]').forEach(function (chip) {
                    chip.addEventListener('click', function () { date.value = chip.dataset.date; render(); });
                });
                document.querySelectorAll('[data-give]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        employee.value = button.dataset.give;
                        fillAppraisals(button.dataset.appraisal);
                        render();
                        window.scrollTo({ top: document.getElementById('give').getBoundingClientRect().top + window.scrollY - 90, behavior: 'smooth' });
                        percent.focus({ preventScroll: true });
                    });
                });
                fillAppraisals(appraisal.dataset.old);
                render();
            })();
        </script>
    @endpush
@endif
