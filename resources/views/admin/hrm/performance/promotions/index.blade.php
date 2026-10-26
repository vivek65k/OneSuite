@extends('layouts.admin')

@section('title', 'Promotion')

@php
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.promotions.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
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
    $canSeeIncrements = auth()->user()->can('viewAny', \App\Models\Increment::class);
@endphp

@section('content')
    <x-page-title title="Promotion" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Promotions in {{ now()->year }}" :value="$kpis['this_year']" icon="ti-arrow-up-circle" tone="indigo"
                    :hint="$kpis['this_year'] ? $kpis['people_this_year'].' '.Str::plural('employee', $kpis['people_this_year']) : 'None yet this year'" :href="$pageUrl(['year' => now()->year])" />
        <x-kpi-card label="Promoted in the last year" :value="$kpis['rate'] !== null ? $kpis['rate'].'%' : '—'" icon="ti-trending-up" tone="success" :hint="$kpis['active'] ? 'Of '.$kpis['active'].' current '.Str::plural('employee', $kpis['active']) : null" />
        <x-kpi-card label="Never promoted" :value="$kpis['never']" icon="ti-user" :tone="$kpis['never'] ? 'amber' : 'success'" :hint="$kpis['never'] ? 'Still in the role they joined in' : ($kpis['active'] ? 'Everyone has moved up at least once' : null)" />
        <x-kpi-card label="Requests waiting" :value="$kpis['requests']" icon="ti-inbox" :tone="$kpis['requests'] ? 'amber' : 'slate'" :hint="$kpis['requests'] ? 'Promotion requests to approve' : 'No promotion request is waiting'"
                    :href="$kpis['requests'] ? route('admin.promotion-requests.index') : null" />
    </div>

    @if ($canPromote)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.promotions.store') }}" class="card form-section tone-indigo mb-0 h-100" id="promo-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-arrow-up-circle"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Promote someone</h5>
                            <p class="form-section-sub">Records the move and changes the employee's designation straight away.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label" for="promo-employee">Employee</label>
                                <select name="employee_id" id="promo-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formData as $id => $person)
                                        <option value="{{ $id }}" @selected($oldEmployee === (string) $id)>{{ $person['name'] }}{{ $person['designation'] !== '' ? ' — '.$person['designation'] : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="promo-from">From <span class="text-muted fw-normal" id="promo-from-note">(their current designation)</span></label>
                                <input type="text" name="from_designation" id="promo-from" value="{{ old('from_designation') }}" maxlength="255" class="form-control @error('from_designation') is-invalid @enderror" list="promo-designations" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="promo-to">New designation</label>
                                <input type="text" name="to_designation" id="promo-to" value="{{ old('to_designation') }}" maxlength="255" class="form-control @error('to_designation') is-invalid @enderror" list="promo-designations" placeholder="e.g. Senior Sales Executive" required>
                                <datalist id="promo-designations">
                                    @foreach ($designations as $designation)
                                        <option value="{{ $designation }}">
                                    @endforeach
                                </datalist>
                                <div class="quick-chips" id="promo-suggest" hidden></div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="promo-date">Effective on</label>
                                <input type="date" name="promotion_date" id="promo-date" value="{{ old('promotion_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="form-control @error('promotion_date') is-invalid @enderror" required>
                                <div class="quick-chips">
                                    <button type="button" class="quick-chip" data-date="{{ now()->toDateString() }}">Today</button>
                                    <button type="button" class="quick-chip" data-date="{{ now()->startOfMonth()->toDateString() }}">1st of this month</button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="promo-notes">Reason <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="notes" id="promo-notes" value="{{ old('notes') }}" maxlength="1000" class="form-control" placeholder="Why they are being promoted">
                                <div class="quick-chips">
                                    @foreach (['Annual appraisal', 'Took on a bigger role', 'Filled a vacancy', 'Consistent performance'] as $reason)
                                        <button type="button" class="quick-chip" data-reason="{{ $reason }}">{{ $reason }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> <span id="promo-submit">Promote</span></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">What this promotion does</span>
                    </div>
                    <div class="card-body">
                        <div class="empty-state py-4" id="promo-preview-empty">
                            <div class="empty-state-icon"><i class="ti ti-user"></i></div>
                            <div class="fw-semibold">Pick an employee</div>
                            <div class="font-13 text-muted">You'll see their current role and how long they have held it.</div>
                        </div>
                        <div id="promo-preview" hidden>
                            <div class="rev-compare">
                                <div>
                                    <span class="rev-compare-label">Now</span>
                                    <span class="rev-compare-value promo-title" id="promo-now">—</span>
                                    <span class="rev-compare-meta" id="promo-now-meta"></span>
                                </div>
                                <i class="ti ti-arrow-right"></i>
                                <div>
                                    <span class="rev-compare-label">After</span>
                                    <span class="rev-compare-value promo-title" id="promo-after">—</span>
                                    <span class="rev-compare-meta" id="promo-after-meta"></span>
                                </div>
                            </div>
                            <div class="run-lines">
                                <div><span>Time in current role</span><strong id="promo-tenure">—</strong></div>
                                <div><span>Last appraisal rating</span><strong id="promo-rating">—</strong></div>
                            </div>
                            <div class="run-note tone-amber mt-3" id="promo-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                            <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>A promotion changes the designation only. @if ($canSeeIncrements)Give the pay rise that goes with it under <a href="{{ route('admin.increments.index') }}">Increment</a>.@else Any pay rise is recorded separately.@endif</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.promotions.index') }}">
            <div class="list-toolbar">
                <div class="list-toolbar-title"><i class="ti ti-arrow-up-circle"></i> Promotion history <span class="list-tab-count">{{ $promotions->total() }}</span></div>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or designation" aria-label="Search promotions">
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
                        <th>Move</th>
                        <th>Effective on</th>
                        <th>Reason</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($promotions as $promotion)
                        @php
                            $employee = $promotion->employee;
                            $isLatest = $latestIds->has($promotion->id);
                            // Still the title they hold today?
                            $current = $isLatest && $employee && mb_strtolower(trim((string) $employee->designation)) === mb_strtolower(trim($promotion->to_designation));
                        @endphp
                        <tr>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name"><a href="{{ $pageUrl(['employee_id' => $promotion->employee_id]) }}" title="Show this person's promotions">{{ $employee?->name ?? 'Removed employee' }}</a></div>
                                        <div class="list-user-meta">{{ collect([$employee?->employee_code, $employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="promo-move">
                                    <span class="tag tone-slate">{{ $promotion->from_designation ?: 'Not recorded' }}</span>
                                    <i class="ti ti-arrow-right text-muted"></i>
                                    <span class="tag tone-success"><i class="ti ti-arrow-up-circle"></i>{{ $promotion->to_designation }}</span>
                                </div>
                                @if ($current)
                                    <div class="payslip-parts">Current role</div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <div class="fw-semibold">{{ $promotion->promotion_date->format('M j, Y') }}</div>
                                <div class="payslip-parts">{{ $promotion->promotion_date->isToday() ? 'Today' : $promotion->promotion_date->diffForHumans() }}</div>
                            </td>
                            <td class="rev-notes" title="{{ $promotion->notes }}">{{ $promotion->notes ? Str::limit($promotion->notes, 70) : '—' }}</td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($canSeeIncrements && $current)
                                        <a href="{{ route('admin.increments.index', ['employee_id' => $promotion->employee_id]) }}" class="btn-icon btn-icon-accent" title="Increments for this person"><i class="ti ti-trending-up font-16"></i></a>
                                    @endif
                                    @if ($isLatest && $employee)
                                        @can('update', $employee)
                                            <form action="{{ route('admin.promotions.destroy', $promotion) }}" method="POST" class="d-inline" onsubmit="return confirm('Undo this promotion?{{ $current && $promotion->from_designation ? ' The designation goes back to '.addslashes($promotion->from_designation).'.' : '' }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Undo promotion"><i class="ti ti-arrow-back-up font-16"></i></button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-arrow-up-circle font-24 d-block mb-2"></i>
                                @if ($total || $activeFilters->isNotEmpty())
                                    <div class="fw-semibold text-body mb-1">No promotions match</div>
                                    <span class="font-13"><a href="{{ route('admin.promotions.index') }}">Show all promotions</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No promotions yet</div>
                                    <span class="font-13">{{ $canPromote ? 'Record the first one with the form above.' : 'Promotions recorded by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($promotions->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $promotions->links() }}</div></div>
        @endif
    </div>
@endsection

@if ($canPromote)
    @push('scripts')
        <script>
            (function () {
                var form = document.getElementById('promo-form');
                if (!form) return;
                var people = @json($formData);
                var el = function (id) { return document.getElementById(id); };
                var employee = el('promo-employee'), from = el('promo-from'), to = el('promo-to'), date = el('promo-date');

                function tenure(months) {
                    if (months === null) return 'Unknown';
                    var y = Math.floor(months / 12), m = months % 12;
                    return (y ? y + (y === 1 ? ' year' : ' years') : '') + (y && m ? ' ' : '') + (m || !y ? m + (m === 1 ? ' month' : ' months') : '');
                }

                // "Senior <title>" and "Lead <title>" are the usual next steps, offered as one-click picks.
                function suggestions(title) {
                    if (!title) return [];
                    var out = [];
                    if (!/^(senior|sr\.?|lead|principal|head)\b/i.test(title)) out.push('Senior ' + title);
                    if (/^(senior|sr\.?)\s+/i.test(title)) out.push('Lead ' + title.replace(/^(senior|sr\.?)\s+/i, ''));
                    if (/executive$/i.test(title)) out.push(title.replace(/executive$/i, 'Manager'));
                    if (/engineer$/i.test(title) && !/^lead/i.test(title)) out.push('Lead ' + title.replace(/^(senior|sr\.?)\s+/i, ''));
                    return out.filter(function (v, i, a) { return a.indexOf(v) === i; }).slice(0, 3);
                }

                function render(reset) {
                    var p = people[employee.value];
                    el('promo-preview').hidden = !p;
                    el('promo-preview-empty').hidden = !!p;
                    if (!p) { from.value = ''; return; }

                    // Dated before their last promotion = a history entry; the "from" title must then be typed in.
                    var history = !!(p.lastDate && date.value && date.value <= p.lastDate);
                    from.readOnly = !history;
                    if (!history) from.value = p.designation;
                    else if (reset) from.value = '';
                    el('promo-from-note').textContent = history ? '(what they held before this one)' : '(their current designation)';
                    el('promo-submit').textContent = history ? 'Add to history' : 'Promote';

                    var next = to.value.trim(), warn = '';
                    el('promo-now').textContent = p.designation || 'No designation set';
                    el('promo-now-meta').textContent = p.since ? p.sinceKind + ' ' + p.since : '';
                    el('promo-after').textContent = history ? (p.designation || '—') : (next || '—');
                    el('promo-after-meta').textContent = history ? 'unchanged: this is a history entry' : (date.value ? 'from ' + date.value : '');
                    el('promo-tenure').textContent = tenure(p.months);
                    el('promo-rating').textContent = p.rating || 'No completed appraisal';

                    var picks = history ? [] : suggestions(p.designation);
                    el('promo-suggest').hidden = !picks.length;
                    el('promo-suggest').innerHTML = picks.map(function (t) { return '<button type="button" class="quick-chip" data-title="' + t.replace(/"/g, '&quot;') + '">' + t.replace(/</g, '&lt;') + '</button>'; }).join('');

                    if (date.value && p.lastDate === date.value) warn = p.name + ' already has a promotion on that date.';
                    else if (date.value && p.joined && date.value < p.joined) warn = 'That is before ' + p.name + ' joined (' + p.joined + ').';
                    else if (history) warn = 'This is dated before their latest promotion (' + p.lastDate + '), so it is saved as history and their current designation stays as it is.';
                    else if (next && next.toLowerCase() === (p.designation || '').toLowerCase()) warn = p.name + ' is already "' + next + '".';
                    else if (p.months !== null && p.months < 6) warn = p.name + ' has been in this role for only ' + tenure(p.months) + '.';
                    el('promo-warn').hidden = !warn;
                    el('promo-warn').querySelector('span').textContent = warn;
                }

                // A title typed for one person makes no sense for the next one picked.
                employee.addEventListener('change', function () { to.value = ''; render(true); });
                date.addEventListener('change', function () { render(true); });
                to.addEventListener('input', function () { render(false); });
                el('promo-suggest').addEventListener('click', function (event) {
                    var chip = event.target.closest('[data-title]');
                    if (chip) { to.value = chip.dataset.title; render(false); }
                });
                form.querySelectorAll('[data-date]').forEach(function (chip) {
                    chip.addEventListener('click', function () { date.value = chip.dataset.date; render(true); });
                });
                form.querySelectorAll('[data-reason]').forEach(function (chip) {
                    chip.addEventListener('click', function () { el('promo-notes').value = chip.dataset.reason; });
                });
                render(false);
            })();
        </script>
    @endpush
@endif
