@extends('layouts.admin')

@section('title', 'Travel Expenses')

@php
    use App\Models\TravelExpense;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $pageUrl = fn (array $overrides = []) => route('admin.travel-expenses.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $categoryMeta = [
        'food' => ['amber', 'ti-tools-kitchen-2'],
        'transport' => ['teal', 'ti-car'],
        'lodging' => ['rose', 'ti-building'],
        'misc' => ['indigo', 'ti-receipt'],
        'other' => ['slate', 'ti-receipt'],
    ];
    $statusMeta = ['pending' => ['amber', 'ti-clock'], 'approved' => ['success', 'ti-circle-check'], 'rejected' => ['rose', 'ti-x']];
    $tabs = ['' => ['label' => 'All expenses', 'tone' => 'indigo', 'count' => $counts['all']]];
    foreach (TravelExpense::STATUSES as $value => $label) {
        $tabs[$value] = ['label' => $label, 'tone' => $statusMeta[$value][0], 'count' => $counts[$value] ?? 0];
    }
    $filterValues = [
        'category' => TravelExpense::CATEGORIES[$state['category']] ?? null,
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'trip_id' => $focus ? ($focus->employee?->name.' to '.$focus->to_location) : null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['category' => 'Category', 'employee_id' => 'Employee', 'trip_id' => 'Trip', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $oldTrip = (string) old('travel_request_id', $state['trip_id'] ?? '');
    $oldCategory = (string) old('category', 'food');
    // Which rows on this page this login may approve: pending, not their own, trip not settled.
    $actable = $canReview ? $expenses->getCollection()->filter(fn ($e) => $e->status === 'pending' && ! $settledIds->has($e->travel_request_id) && ($ownEmployeeId === null || (int) $e->employee_id !== (int) $ownEmployeeId))->pluck('id')->flip() : collect();
@endphp

@section('content')
    <x-page-title title="Travel Expenses" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for approval" :value="$kpis['pending']" icon="ti-clock" :tone="$kpis['pending'] ? 'amber' : 'success'" :hint="$kpis['pending'] ? $money($kpis['pending_amount']).' claimed by '.$kpis['pending_people'].' '.Str::plural('person', $kpis['pending_people']) : 'Nothing to approve'" />
        <x-kpi-card label="Approved, not settled" :value="$kpis['owed'] > 0 ? $money($kpis['owed']) : '—'" icon="ti-cash" tone="teal" :hint="$kpis['owed'] > 0 ? 'Across '.$kpis['owed_trips'].' '.Str::plural('trip', $kpis['owed_trips']).' still to settle' : 'Nothing is waiting to be settled'" />
        <x-kpi-card :label="'Claimed in '.today()->format('F')" :value="$kpis['month'] > 0 ? $money($kpis['month']) : '—'" icon="ti-receipt" tone="indigo" :hint="$kpis['month_count'] ? $kpis['month_count'].' '.Str::plural('expense', $kpis['month_count']).' dated this month' : 'No expenses dated this month'" />
        <x-kpi-card label="Rejected" :value="$kpis['rejected']" icon="ti-x" :tone="$kpis['rejected'] ? 'rose' : 'slate'" :hint="$kpis['rejected'] ? $money($kpis['rejected_amount']).' not paid' : 'No claim has been turned down'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.travel-expenses.store') }}" class="card form-section tone-indigo mb-0 h-100" id="expense-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-receipt"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Add a travel expense</h5>
                            <p class="form-section-sub">Something the employee paid for on an approved trip. It goes for approval, then into the trip's settlement.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        @if ($trips->isEmpty())
                            <div class="run-note tone-amber"><i class="ti ti-alert-triangle"></i><span>There is no approved trip that has started and is not yet settled, so there is nothing to claim against.</span></div>
                        @else
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="expense-trip">Trip</label>
                                    <select name="travel_request_id" id="expense-trip" class="form-select @error('travel_request_id') is-invalid @enderror" required>
                                        <option value="">Select trip</option>
                                        @foreach ($trips as $trip)
                                            <option value="{{ $trip['id'] }}" @selected($oldTrip === (string) $trip['id'])>{{ $trip['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label d-block">Spent on</label>
                                    <div class="rel-pick">
                                        @foreach (TravelExpense::CATEGORIES as $value => $label)
                                            <label class="rel-option tone-{{ $categoryMeta[$value][0] }}">
                                                <input type="radio" name="category" value="{{ $value }}" @checked($oldCategory === $value) required>
                                                <i class="ti {{ $categoryMeta[$value][1] }}"></i>
                                                <span class="rel-option-title">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="expense-amount">Amount</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" id="expense-amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" placeholder="0.00" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="expense-date">Spent on date</label>
                                    <input type="date" name="expense_date" id="expense-date" value="{{ old('expense_date') }}" max="{{ today()->toDateString() }}" class="form-control @error('expense_date') is-invalid @enderror" required>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Add expense</button>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">Spending on this trip</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">Spent so far</span>
                                <span class="rev-compare-value" id="sum-spent">—</span>
                                <span class="rev-compare-meta" id="sum-spent-meta">Pick a trip</span>
                            </div>
                            <i class="ti ti-arrow-right"></i>
                            <div>
                                <span class="rev-compare-label">With this expense</span>
                                <span class="rev-compare-value" id="sum-after">—</span>
                                <span class="rev-compare-meta" id="sum-after-meta"></span>
                            </div>
                        </div>
                        <div class="run-note tone-amber mt-3" id="sum-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>Only approved expenses are paid back, when the trip is settled. After that the trip's expenses can't be changed.</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.travel-expenses.index') }}">
            @foreach (['tab' => $state['tab'], 'trip_id' => (string) ($state['trip_id'] ?? '')] as $key => $value)
                @if ($value !== '')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Expense status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name, place or purpose" aria-label="Search expenses">
                    </div>
                    <select name="category" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Category">
                        <option value="">All categories</option>
                        @foreach (TravelExpense::CATEGORIES as $value => $label)
                            <option value="{{ $value }}" @selected($state['category'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
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

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $pageUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($actable->count() > 1)
            <form method="POST" action="{{ route('admin.travel-expenses.bulk-approve') }}" id="bulk-approve-form" class="bulk-bar" onsubmit="return confirm('Approve all selected expenses?');">
                @csrf
                <label class="bulk-select">
                    <input type="checkbox" class="form-check-input" id="select-all" aria-label="Select all on this page">
                    <span id="bulk-summary">Select expenses</span>
                </label>
                <div class="bulk-actions">
                    <button type="submit" class="btn btn-sm review-btn is-approve" disabled data-bulk-action><i class="ti ti-checks"></i> Approve selected</button>
                </div>
            </form>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        @if ($actable->count() > 1)
                            <th style="width: 36px"><span class="visually-hidden">Select</span></th>
                        @endif
                        <th>Employee</th>
                        <th>Expense</th>
                        <th>Trip</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        @php
                            $cat = $categoryMeta[$expense->category] ?? $categoryMeta['other'];
                            $meta = $statusMeta[$expense->status] ?? ['slate', 'ti-circle'];
                            $trip = $expense->travelRequest;
                            $settled = $settledIds->has($expense->travel_request_id);
                            $pending = $expense->status === 'pending';
                            $own = $ownEmployeeId !== null && (int) $expense->employee_id === (int) $ownEmployeeId;
                        @endphp
                        <tr>
                            @if ($actable->count() > 1)
                                <td>
                                    @if ($actable->has($expense->id))
                                        <input type="checkbox" class="form-check-input" name="ids[]" value="{{ $expense->id }}" form="bulk-approve-form" data-row-select data-amount="{{ $expense->amount }}" aria-label="Select this expense of {{ $expense->employee?->name }}">
                                    @endif
                                </td>
                            @endif
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$expense->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $expense->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$expense->employee?->employee_code, $expense->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="date-range-cell">
                                    <span class="list-avatar list-avatar-square tone-{{ $cat[0] }}" aria-hidden="true"><i class="ti {{ $cat[1] }} font-18"></i></span>
                                    <div>
                                        <div class="fw-semibold">{{ TravelExpense::CATEGORIES[$expense->category] ?? $expense->category }}</div>
                                        <div class="payslip-parts">{{ $expense->expense_date->format('D, M j, Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($trip)
                                    <a href="{{ $pageUrl(['trip_id' => $trip->id, 'tab' => null]) }}" class="text-body fw-semibold" title="Show every expense of this trip">{{ $trip->from_location }} <i class="ti ti-arrow-right text-muted font-12"></i> {{ $trip->to_location }}</a>
                                    <div class="payslip-parts">{{ $trip->start_date->format('M j') }}{{ $trip->start_date->isSameDay($trip->end_date) ? '' : ' – '.$trip->end_date->format('M j') }}{{ $settled ? ' · settled' : '' }}</div>
                                @else
                                    <span class="text-muted">Removed trip</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap fw-semibold">{{ $money($expense->amount) }}</td>
                            <td>
                                <span class="tag tone-{{ $meta[0] }}"><i class="ti {{ $meta[1] }}"></i>{{ TravelExpense::STATUSES[$expense->status] ?? $expense->status }}</span>
                                <div class="payslip-parts text-wrap">
                                    @if ($expense->status === 'rejected' && $expense->remarks)
                                        {{ Str::limit($expense->remarks, 60) }}
                                    @elseif (! $pending && $expense->reviewedBy)
                                        by {{ $expense->reviewedBy->name }}
                                    @endif
                                </div>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($pending && ! $settled)
                                        @can('update', $expense)
                                            @if ($own)
                                                <span class="font-12 text-muted" title="Someone else has to review your own expense">Your own expense</span>
                                            @else
                                                <form method="POST" action="{{ route('admin.travel-expenses.approve', $expense) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-check"></i> Approve</button>
                                                </form>
                                                <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="modal" data-bs-target="#reject-modal"
                                                        data-action="{{ route('admin.travel-expenses.reject', $expense) }}" data-what="{{ $expense->employee?->name }}: {{ TravelExpense::CATEGORIES[$expense->category] ?? $expense->category }}, {{ $money($expense->amount) }} on {{ $expense->expense_date->format('M j') }}"><i class="ti ti-x"></i> Reject</button>
                                            @endif
                                        @endcan
                                    @endif
                                    @unless ($settled)
                                        @can('delete', $expense)
                                            <form action="{{ route('admin.travel-expenses.destroy', $expense) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $expense->status === 'approved' ? 'Delete this approved expense? It will not be paid back.' : 'Delete this expense?' }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Delete expense"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-receipt font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No expenses match</div>
                                    <span class="font-13"><a href="{{ route('admin.travel-expenses.index') }}">Show all expenses</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No travel expenses yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Add what was spent on a trip with the form above.' : 'Expenses claimed on trips will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenses->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $expenses->links() }}</div></div>
        @endif
    </div>

    @if ($canReview)
        <div class="modal fade" id="reject-modal" tabindex="-1" aria-labelledby="reject-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="reject-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="reject-modal-title">Reject expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="reject-intro"></div>
                        <label class="form-label" for="reject-remarks">Reason</label>
                        <textarea name="remarks" id="reject-remarks" rows="3" maxlength="1000" class="form-control" placeholder="The employee sees this" required></textarea>
                        <div class="quick-chips">
                            @foreach (['No receipt', 'Not covered by the travel policy', 'Above the allowed limit', 'Personal expense'] as $preset)
                                <button type="button" class="quick-chip" data-reason="{{ $preset }}">{{ $preset }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="ti ti-x font-14 me-1"></i> Reject expense</button>
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
            var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); };

            // Reject dialog
            var reject = el('reject-modal');
            if (reject) {
                reject.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('reject-form').action = b.dataset.action;
                    el('reject-intro').textContent = b.dataset.what + '.';
                    el('reject-remarks').value = '';
                });
                reject.addEventListener('shown.bs.modal', function () { el('reject-remarks').focus(); });
                reject.querySelectorAll('[data-reason]').forEach(function (chip) {
                    chip.addEventListener('click', function () { el('reject-remarks').value = chip.dataset.reason; });
                });
            }

            // Bulk approve
            var all = el('select-all');
            if (all) {
                var rows = Array.prototype.slice.call(document.querySelectorAll('[data-row-select]'));
                var action = document.querySelector('[data-bulk-action]');
                var refresh = function () {
                    var picked = rows.filter(function (box) { return box.checked; });
                    var total = picked.reduce(function (sum, box) { return sum + (parseFloat(box.dataset.amount) || 0); }, 0);
                    action.disabled = picked.length === 0;
                    el('bulk-summary').textContent = picked.length ? picked.length + ' selected · ' + fmt(total) : 'Select expenses';
                    all.checked = picked.length > 0 && picked.length === rows.length;
                    all.indeterminate = picked.length > 0 && picked.length < rows.length;
                    rows.forEach(function (box) { box.closest('tr').classList.toggle('table-active', box.checked); });
                };
                all.addEventListener('change', function () { rows.forEach(function (box) { box.checked = all.checked; }); refresh(); });
                rows.forEach(function (box) { box.addEventListener('change', refresh); });
                refresh();
            }

            // Add form
            var pick = el('expense-trip');
            if (!pick) return;
            var trips = {};
            @json($trips->values()).forEach(function (t) { trips[t.id] = t; });
            var slack = {{ (int) $slack }};
            var today = @json(today()->toDateString());
            var amount = el('expense-amount'), date = el('expense-date');
            var addDays = function (iso, n) { var d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + n); return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); };
            var nice = function (s) { return new Date(s + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); };

            function render(changedTrip) {
                var t = trips[pick.value], value = parseFloat(amount.value), warn = '';
                if (t) {
                    var first = addDays(t.start, -slack), last = addDays(t.end, slack);
                    date.min = first; date.max = last < today ? last : today;
                    // Picking a trip puts the date on its first day (or today, when it is still running).
                    if (changedTrip || !date.value) date.value = t.start > today ? today : t.start;
                    var spent = t.booked + t.claimed, after = spent + (value > 0 ? value : 0);
                    el('sum-spent').textContent = fmt(spent);
                    el('sum-spent-meta').textContent = fmt(t.booked) + ' booked · ' + fmt(t.claimed) + ' claimed';
                    el('sum-after').textContent = value > 0 ? fmt(after) : '—';
                    el('sum-after-meta').textContent = t.estimate !== null ? 'of ' + fmt(t.estimate) + ' estimated' : 'No estimate was given';
                    if (date.value && (date.value < first || date.value > last)) warn = 'The trip ran ' + nice(t.start) + ' to ' + nice(t.end) + '. The date must be within a day of it; saving will be refused.';
                    else if (date.value && date.value > today) warn = 'An expense can\'t be dated in the future.';
                    else if (t.estimate !== null && value > 0 && after > t.estimate) warn = 'This takes the trip ' + fmt(after - t.estimate) + ' over its estimate of ' + fmt(t.estimate) + '. It can still be added.';
                } else {
                    el('sum-spent').textContent = '—'; el('sum-spent-meta').textContent = 'Pick a trip';
                    el('sum-after').textContent = '—'; el('sum-after-meta').textContent = '';
                }
                el('sum-warn').hidden = !warn;
                el('sum-warn').querySelector('span').textContent = warn;
            }

            pick.addEventListener('change', function () { render(true); });
            [amount, date].forEach(function (input) { input.addEventListener('input', function () { render(false); }); });
            render(false);
        })();
    </script>
@endpush
