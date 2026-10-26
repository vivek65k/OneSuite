@extends('layouts.admin')

@section('title', 'Salary Revision')

@php
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pageUrl = fn (array $overrides = []) => route('admin.salary-revisions.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All revisions', 'tone' => 'indigo', 'count' => $counts['all']],
        'current' => ['label' => 'In effect now', 'tone' => 'success', 'count' => $counts['current']],
        'scheduled' => ['label' => 'Scheduled', 'tone' => 'amber', 'count' => $counts['scheduled']],
        'past' => ['label' => 'Replaced', 'tone' => 'slate', 'count' => $counts['past']],
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
    $oldEmployee = (string) old('employee_id', $state['employee_id'] ?? '');
@endphp

@section('content')
    <x-page-title title="Salary Revision" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Revisions in {{ now()->year }}" :value="$kpis['this_year']" icon="ti-trending-up" tone="indigo"
                    :hint="$kpis['this_year'] ? $kpis['raises'].' '.Str::plural('raise', $kpis['raises']).' · '.$kpis['cuts'].' '.Str::plural('cut', $kpis['cuts']) : 'None in effect yet this year'" :href="$pageUrl(['year' => now()->year, 'tab' => null])" />
        <x-kpi-card label="Average raise" :value="$kpis['average_raise'] !== null ? '+'.$kpis['average_raise'].'%' : '—'" icon="ti-percentage" tone="success"
                    :hint="$kpis['average_raise'] !== null ? 'Biggest this year +'.$kpis['top_raise'].'%' : 'No raises this year'" />
        <x-kpi-card label="Scheduled" :value="$kpis['scheduled']" icon="ti-calendar-event" :tone="$kpis['scheduled'] ? 'amber' : 'slate'"
                    :hint="$kpis['next_scheduled'] ? 'Next takes effect '.$kpis['next_scheduled']->format('M j, Y') : 'No future-dated revisions'" :href="$pageUrl(['tab' => 'scheduled'])" />
        <x-kpi-card label="No salary on record" :value="$kpis['without']" icon="ti-user-exclamation" :tone="$kpis['without'] ? 'rose' : 'success'"
                    :hint="$kpis['without'] ? 'Of '.$kpis['people'].' active '.Str::plural('employee', $kpis['people']) : 'Everyone has a salary recorded'" />
    </div>

    @if ($canRevise)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.salary-revisions.store') }}" class="card form-section tone-indigo mb-0 h-100" id="revision-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-trending-up"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Record a revision</h5>
                            <p class="form-section-sub">Sets the employee's headline monthly salary from the date you choose.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label" for="rev-employee">Employee</label>
                                <select name="employee_id" id="rev-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formData as $id => $person)
                                        <option value="{{ $id }}" @selected($oldEmployee === (string) $id)>{{ $person['name'] }}{{ $person['meta'] ? ' — '.$person['meta'] : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="rev-amount">New monthly salary</label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="rev-amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" placeholder="0.00" required>
                                <div class="quick-chips" id="rev-raise-chips" hidden>
                                    @foreach ([5, 8, 10, 15, 20] as $pct)
                                        <button type="button" class="quick-chip" data-raise="{{ $pct }}">+{{ $pct }}%</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="rev-date">Effective from</label>
                                <input type="date" name="effective_date" id="rev-date" value="{{ old('effective_date', now()->toDateString()) }}" max="{{ now()->addYear()->toDateString() }}" class="form-control @error('effective_date') is-invalid @enderror" required>
                                <div class="quick-chips">
                                    <button type="button" class="quick-chip" data-date="{{ now()->toDateString() }}">Today</button>
                                    <button type="button" class="quick-chip" data-date="{{ now()->startOfMonth()->toDateString() }}">1st of this month</button>
                                    <button type="button" class="quick-chip" data-date="{{ now()->addMonthNoOverflow()->startOfMonth()->toDateString() }}">1st of next month</button>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="rev-notes">Reason <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="notes" id="rev-notes" value="{{ old('notes') }}" maxlength="1000" class="form-control" placeholder="e.g. Annual appraisal">
                                <div class="quick-chips">
                                    @foreach (['Annual appraisal', 'Promotion', 'Probation confirmed', 'Market correction', 'Role change'] as $reason)
                                        <button type="button" class="quick-chip" data-reason="{{ $reason }}">{{ $reason }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4" id="rev-submit"><i class="ti ti-check font-14 me-1"></i> Save revision</button>
                            <span class="font-12 text-muted" id="rev-submit-note"></span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">What this revision does</span>
                    </div>
                    <div class="card-body">
                        <div class="empty-state py-4" id="rev-preview-empty">
                            <div class="empty-state-icon"><i class="ti ti-user"></i></div>
                            <div class="fw-semibold">Pick an employee</div>
                            <div class="font-13 text-muted">Their current salary and the change will show here.</div>
                        </div>
                        <div id="rev-preview" hidden>
                            <div class="rev-compare">
                                <div>
                                    <span class="rev-compare-label">Now</span>
                                    <span class="rev-compare-value" id="rev-now">—</span>
                                    <span class="rev-compare-meta" id="rev-now-meta"></span>
                                </div>
                                <i class="ti ti-arrow-right"></i>
                                <div>
                                    <span class="rev-compare-label">After</span>
                                    <span class="rev-compare-value" id="rev-after">—</span>
                                    <span class="rev-compare-meta" id="rev-after-meta"></span>
                                </div>
                            </div>
                            <div class="run-lines">
                                <div><span>Change a month</span><strong id="rev-diff">—</strong></div>
                                <div><span>Change a year</span><strong id="rev-diff-year">—</strong></div>
                                <div><span>Salary structure pays</span><strong id="rev-structure">—</strong></div>
                            </div>
                            <div class="run-note tone-amber mt-3" id="rev-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                            <div class="run-note tone-indigo mt-2" id="rev-structure-note" hidden><i class="ti ti-info-circle"></i><span>Payroll pays from the salary structure, not this figure. <a href="#" id="rev-structure-link">Update the structure</a> so the payslip matches.</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.salary-revisions.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Revision status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or code" aria-label="Search revisions">
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
                        <th>Change</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($revisions as $revision)
                        @php
                            $change = $changes->get($revision->id);
                            $was = $change?->previous_amount;
                            $pct = $change?->change_percent;
                            $diff = $was !== null ? (float) $revision->amount - $was : null;
                            $future = $revision->effective_date->gt(today());
                            $status = $future ? ['Scheduled', 'amber', 'ti-calendar-event'] : ($revision->is_current ? ['In effect', 'success', 'ti-circle-check'] : ['Replaced', 'slate', 'ti-history']);
                        @endphp
                        <tr class="{{ $future ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$revision->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ $pageUrl(['employee_id' => $revision->employee_id, 'tab' => null]) }}" title="Show this person's salary history">{{ $revision->employee->name }}</a></div>
                                        <div class="list-user-meta">{{ collect([$revision->employee->employee_code, $revision->employee->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="fw-semibold">{{ $revision->effective_date->format('M j, Y') }}</div>
                                <div class="payslip-parts">{{ $future ? 'in '.today()->diffInDays($revision->effective_date).' '.Str::plural('day', today()->diffInDays($revision->effective_date)) : $revision->effective_date->diffForHumans() }}</div>
                            </td>
                            <td class="text-end text-muted">{{ $was !== null ? $money($was) : '—' }}</td>
                            <td class="text-end"><span class="salary-figure">{{ $money($revision->amount) }}</span></td>
                            <td class="text-nowrap">
                                @if ($was === null)
                                    <span class="tag tone-slate">First salary</span>
                                @elseif ($diff > 0)
                                    <span class="tag tone-success"><i class="ti ti-arrow-up-right"></i>{{ $pct !== null ? '+'.$pct.'%' : 'Raise' }}</span>
                                    <div class="payslip-parts">+{{ $money($diff) }} a month</div>
                                @elseif ($diff < 0)
                                    <span class="tag tone-rose"><i class="ti ti-arrow-down-right"></i>{{ $pct !== null ? $pct.'%' : 'Cut' }}</span>
                                    <div class="payslip-parts">−{{ $money(abs($diff)) }} a month</div>
                                @else
                                    <span class="tag tone-slate">No change</span>
                                @endif
                            </td>
                            <td><span class="tag tone-{{ $status[1] }}"><i class="ti {{ $status[2] }}"></i>{{ $status[0] }}</span></td>
                            <td class="rev-notes" title="{{ $revision->notes }}">{{ $revision->notes ? Str::limit($revision->notes, 60) : '—' }}</td>
                            <td class="text-end text-nowrap">
                                @can('update', $revision->employee)
                                    <form action="{{ route('admin.salary-revisions.destroy', $revision) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this revision for {{ addslashes($revision->employee->name) }}? Their current salary will be worked out again from what is left.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Remove revision"><i class="ti ti-trash font-16"></i></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="ti ti-trending-up font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No revisions match</div>
                                    <span class="font-13"><a href="{{ route('admin.salary-revisions.index') }}">Show all revisions</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No salary revisions yet</div>
                                    <span class="font-13">{{ $canRevise ? 'Record the first one with the form above.' : 'Revisions recorded by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($revisions->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $revisions->links() }}</div></div>
        @endif
    </div>
@endsection

@if ($canRevise)
    @push('scripts')
        <script>
            (function () {
                var people = @json($formData);
                var today = @json(now()->toDateString());
                var el = function (id) { return document.getElementById(id); };
                var employee = el('rev-employee'), amount = el('rev-amount'), date = el('rev-date'), notes = el('rev-notes');
                var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); };
                var signed = function (n) { return (n > 0 ? '+' : n < 0 ? '−' : '') + fmt(Math.abs(n)); };

                function render() {
                    var p = people[employee.value];
                    el('rev-preview').hidden = !p;
                    el('rev-preview-empty').hidden = !!p;
                    el('rev-raise-chips').hidden = !(p && p.current);
                    el('rev-submit-note').textContent = '';
                    if (!p) return;

                    var next = parseFloat(amount.value);
                    var has = !isNaN(next) && next > 0;
                    var warn = '';
                    el('rev-now').textContent = p.current !== null ? fmt(p.current) : 'Not set';
                    el('rev-now-meta').textContent = p.current !== null ? 'since ' + p.since : 'No salary on record';
                    el('rev-after').textContent = has ? fmt(next) : '—';
                    el('rev-after-meta').textContent = date.value ? (date.value > today ? 'scheduled, from ' + date.value : 'from ' + date.value) : '';

                    var diff = has && p.current !== null ? next - p.current : null;
                    var pct = diff !== null && p.current > 0 ? Math.round(diff / p.current * 1000) / 10 : null;
                    el('rev-diff').textContent = diff === null ? (has ? 'First salary' : '—') : signed(diff) + (pct !== null ? ' (' + (pct > 0 ? '+' : '') + pct + '%)' : '');
                    el('rev-diff').className = diff === null || diff === 0 ? '' : (diff > 0 ? 'tone-text-success' : 'tone-text-rose');
                    el('rev-diff-year').textContent = diff === null ? '—' : signed(diff * 12);
                    el('rev-structure').textContent = p.structure !== null ? fmt(p.structure) : 'No structure yet';

                    if (date.value && p.dates.indexOf(date.value) !== -1) warn = p.name + ' already has a revision effective that date.';
                    else if (date.value && p.joined && date.value < p.joined) warn = 'That is before ' + p.name + ' joined (' + p.joined + ').';
                    else if (diff !== null && diff < 0) warn = 'This is a pay cut of ' + fmt(Math.abs(diff)) + ' a month.';
                    else if (pct !== null && pct > 50) warn = 'A ' + pct + '% raise is unusually large. Check the amount.';
                    else if (diff === 0) warn = 'Same as the current salary. Nothing to revise.';
                    else if (p.scheduled.length) warn = 'A revision is already scheduled for ' + p.scheduled.join(', ') + '.';
                    el('rev-warn').hidden = !warn;
                    el('rev-warn').querySelector('span').textContent = warn;

                    var mismatch = has && (p.structure === null || Math.abs(p.structure - next) > 0.5);
                    el('rev-structure-note').hidden = !mismatch;
                    el('rev-structure-link').href = p.structureUrl;
                    if (date.value > today) el('rev-submit-note').textContent = 'Takes effect on ' + date.value + '. Until then the current salary stays.';
                }

                [employee, amount, date].forEach(function (input) { input.addEventListener('input', render); input.addEventListener('change', render); });
                document.querySelectorAll('#revision-form [data-raise]').forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        var p = people[employee.value];
                        if (!p || !p.current) return;
                        amount.value = (Math.round(p.current * (1 + parseFloat(chip.dataset.raise) / 100) * 100) / 100).toFixed(2);
                        render();
                    });
                });
                document.querySelectorAll('#revision-form [data-date]').forEach(function (chip) {
                    chip.addEventListener('click', function () { date.value = chip.dataset.date; render(); });
                });
                document.querySelectorAll('#revision-form [data-reason]').forEach(function (chip) {
                    chip.addEventListener('click', function () { notes.value = chip.dataset.reason; });
                });
                render();
            })();
        </script>
    @endpush
@endif
