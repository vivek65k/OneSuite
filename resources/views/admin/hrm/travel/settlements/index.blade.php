@extends('layouts.admin')

@section('title', 'Travel Settlements')

@php
    use App\Models\TravelBooking;
    use App\Models\TravelExpense;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $pageUrl = fn (array $overrides = []) => route('admin.travel-settlements.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'Ready to settle', 'tone' => 'indigo', 'count' => $counts['']],
        'waiting' => ['label' => 'Not ready yet', 'tone' => 'amber', 'count' => $counts['waiting']],
        'settled' => ['label' => 'Settled', 'tone' => 'success', 'count' => $counts['settled']],
    ];
    $filterValues = ['employee_id' => $employees[$state['employee_id']] ?? null, 'search' => $state['search']];
    $activeFilters = collect(['employee_id' => 'Employee', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $dates = fn ($t) => $t->start_date->isSameDay($t->end_date)
        ? $t->start_date->format('M j, Y')
        : $t->start_date->format('M j').' – '.$t->end_date->format($t->start_date->isSameMonth($t->end_date) ? 'j, Y' : 'M j, Y');
    $canSeeBookings = auth()->user()->can('viewAny', TravelBooking::class);
    $canSeeExpenses = auth()->user()->can('viewAny', TravelExpense::class);
@endphp

@section('content')
    <x-page-title title="Travel Settlements" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Ready to settle" :value="$kpis['ready']" icon="ti-wallet" :tone="$kpis['ready'] ? 'indigo' : 'success'" :hint="$kpis['ready'] ? $money($kpis['ready_amount']).' to pay back to employees' : 'No finished trip is waiting'" />
        <x-kpi-card label="Not ready yet" :value="$kpis['waiting']" icon="ti-clock" :tone="$kpis['blocked'] ? 'amber' : 'slate'" :hint="$kpis['blocked'] ? $kpis['blocked'].' held up by expenses waiting for approval' : ($kpis['waiting'] ? 'Trips still ahead or under way' : 'Nothing is under way')" />
        <x-kpi-card :label="'Paid back in '.today()->format('F')" :value="$kpis['month_count'] ? $money($kpis['month_paid']) : '—'" icon="ti-cash" tone="teal" :hint="$kpis['month_count'] ? $kpis['month_count'].' '.Str::plural('trip', $kpis['month_count']).' settled this month' : 'Nothing settled this month'" />
        <x-kpi-card :label="'Travel cost in '.today()->year" :value="$kpis['year_count'] ? $money($kpis['year_cost']) : '—'" icon="ti-receipt" tone="amber" :hint="$kpis['year_count'] ? 'Bookings and expenses of '.$kpis['year_count'].' settled '.Str::plural('trip', $kpis['year_count']) : 'No trip settled this year'" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.travel-settlements.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Settlement status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name, place or purpose" aria-label="Search trips">
                    </div>
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

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Trip</th>
                        <th class="text-end" title="Tickets and stay paid by the company">Booked</th>
                        <th class="text-end" title="Approved expenses the employee paid">Expenses</th>
                        <th class="text-end">Trip cost</th>
                        <th>{{ $state['tab'] === 'settled' ? 'Paid back' : 'Status' }}</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trips as $trip)
                        @php
                            $settlement = $trip->settlement;
                            $settled = $settlement && $settlement->status === 'settled';
                            // A settled trip shows the figures it was settled with; an open one shows them live.
                            $booked = $settled ? (float) $settlement->total_booking_cost : (float) $trip->booked_total;
                            $approved = $settled ? (float) $settlement->total_expense_amount : (float) $trip->approved_total;
                            $cost = $booked + $approved;
                            $estimate = $trip->estimated_cost !== null ? (float) $trip->estimated_cost : null;
                            $over = $estimate !== null && $cost > $estimate ? $cost - $estimate : 0;
                            $ended = $trip->end_date->lt(today());
                            $pending = (int) $trip->pending_count;
                            $ready = ! $settled && $ended && $pending === 0;
                            $own = $ownEmployeeId !== null && (int) $trip->employee_id === (int) $ownEmployeeId;
                            $waitedDays = $ended ? (int) $trip->end_date->diffInDays(today()) : 0;
                        @endphp
                        <tr class="{{ $ready && $waitedDays > 14 ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$trip->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $trip->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$trip->employee?->employee_code, $trip->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $trip->from_location }} <i class="ti ti-arrow-right text-muted font-12"></i> {{ $trip->to_location }}</div>
                                <div class="payslip-parts text-wrap" title="{{ $trip->purpose }}">{{ $dates($trip) }} · {{ Str::limit($trip->purpose, 40) }}</div>
                            </td>
                            <td class="text-end text-nowrap">
                                {{ $booked > 0 ? $money($booked) : '—' }}
                                @if ($trip->bookings_count)
                                    <div class="payslip-parts">@if ($canSeeBookings)<a href="{{ route('admin.travel-bookings.index', ['search' => $trip->employee?->name, 'tab' => $ended ? 'past' : null]) }}" class="text-reset">{{ $trip->bookings_count }} {{ Str::plural('booking', $trip->bookings_count) }}</a>@else{{ $trip->bookings_count }} {{ Str::plural('booking', $trip->bookings_count) }}@endif</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                {{ $approved > 0 ? $money($approved) : '—' }}
                                @if ($trip->approved_count || $pending)
                                    <div class="payslip-parts">@if ($canSeeExpenses)<a href="{{ route('admin.travel-expenses.index', ['trip_id' => $trip->id]) }}" class="text-reset">{{ $trip->approved_count }} approved{{ $pending ? ' · '.$pending.' pending' : '' }}</a>@else{{ $trip->approved_count }} approved{{ $pending ? ' · '.$pending.' pending' : '' }}@endif</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <span class="fw-semibold">{{ $money($cost) }}</span>
                                <div class="payslip-parts {{ $over ? 'text-danger' : '' }}">{{ $estimate !== null ? ($over ? $money($over).' over the '.$money($estimate).' estimate' : 'of '.$money($estimate).' estimated') : 'no estimate' }}</div>
                            </td>
                            <td>
                                @if ($settled)
                                    <span class="fw-semibold">{{ $money($settlement->reimbursement_amount) }}</span>
                                    <div class="payslip-parts text-wrap">{{ $settlement->settled_date?->format('M j, Y') }}{{ $settlement->settledBy ? ' · by '.$settlement->settledBy->name : '' }}{{ $settlement->remarks ? ' · '.Str::limit($settlement->remarks, 50) : '' }}</div>
                                @elseif ($ready)
                                    <span class="tag tone-indigo"><i class="ti ti-wallet"></i>Ready</span>
                                    <div class="payslip-parts">{{ $waitedDays === 0 ? 'ended today' : 'ended '.$waitedDays.' '.Str::plural('day', $waitedDays).' ago' }}</div>
                                @elseif ($ended)
                                    <span class="tag tone-amber"><i class="ti ti-clock"></i>Expenses pending</span>
                                    <div class="payslip-parts">{{ $pending }} of {{ $money($trip->pending_total) }} to decide</div>
                                @elseif ($trip->start_date->lte(today()))
                                    <span class="tag tone-teal"><i class="ti ti-plane"></i>Away now</span>
                                    <div class="payslip-parts">back {{ $trip->end_date->format('M j') }}</div>
                                @else
                                    <span class="tag tone-slate"><i class="ti ti-calendar-event"></i>Not started</span>
                                    <div class="payslip-parts">leaves {{ $trip->start_date->format('M j') }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($canSettle)
                                        @if ($own)
                                            <span class="font-12 text-muted" title="Someone else has to settle your own trip">Your own trip</span>
                                        @elseif ($ready)
                                            <button type="button" class="btn btn-sm review-btn is-approve" data-bs-toggle="modal" data-bs-target="#settle-modal"
                                                    data-action="{{ route('admin.travel-settlements.settle', $trip) }}" data-who="{{ $trip->employee?->name }}" data-trip="{{ $trip->from_location }} to {{ $trip->to_location }}, {{ $dates($trip) }}"
                                                    data-booked="{{ $booked }}" data-approved="{{ $approved }}" data-estimate="{{ $estimate }}" data-remarks="{{ $settlement?->remarks }}" data-was="{{ $settlement ? (float) $settlement->reimbursement_amount : '' }}"><i class="ti ti-wallet"></i> Settle</button>
                                        @elseif ($settled)
                                            <form method="POST" action="{{ route('admin.travel-settlements.reopen', $trip) }}" class="d-inline" onsubmit="return confirm('Reopen this settlement? Its bookings and expenses can then be changed, and it has to be settled again.');">
                                                @csrf
                                                <button type="submit" class="btn-icon" title="Reopen to correct it"><i class="ti ti-arrow-back-up font-16"></i></button>
                                            </form>
                                        @elseif ($ended && $pending && $canSeeExpenses)
                                            <a href="{{ route('admin.travel-expenses.index', ['trip_id' => $trip->id, 'tab' => 'pending']) }}" class="btn btn-sm review-btn"><i class="ti ti-receipt"></i> Review expenses</a>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-wallet font-24 d-block mb-2"></i>
                                @if ($activeFilters->isNotEmpty())
                                    <div class="fw-semibold text-body mb-1">No trips match</div>
                                    <span class="font-13"><a href="{{ $pageUrl(['employee_id' => null, 'search' => null]) }}">Clear the filters</a></span>
                                @elseif ($state['tab'] === 'settled')
                                    <div class="fw-semibold text-body mb-1">No trip has been settled yet</div>
                                    <span class="font-13">Settled trips are listed here with what was paid back.</span>
                                @elseif ($state['tab'] === 'waiting')
                                    <div class="fw-semibold text-body mb-1">Nothing is under way</div>
                                    <span class="font-13">Approved trips appear here until they are over and their expenses are decided.</span>
                                @else
                                    <div class="fw-semibold text-body mb-1">Nothing to settle</div>
                                    <span class="font-13">{{ $counts['waiting'] ? $counts['waiting'].' '.Str::plural('trip', $counts['waiting']).' will be ready once over and their expenses are decided.' : 'A trip can be settled once it is over and its expenses are approved or rejected.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($trips->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $trips->links() }}</div></div>
        @endif
    </div>

    @if ($canSettle)
        <div class="modal fade" id="settle-modal" tabindex="-1" aria-labelledby="settle-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="settle-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="settle-modal-title">Settle trip</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="settle-intro"></div>
                        <div class="rev-compare mb-3">
                            <div>
                                <span class="rev-compare-label">Paid by the company</span>
                                <span class="rev-compare-value" id="settle-booked">—</span>
                                <span class="rev-compare-meta">tickets and stay</span>
                            </div>
                            <i class="ti ti-plus"></i>
                            <div>
                                <span class="rev-compare-label">Paid by the employee</span>
                                <span class="rev-compare-value" id="settle-approved">—</span>
                                <span class="rev-compare-meta">approved expenses</span>
                            </div>
                        </div>
                        <div class="font-13 mb-3" id="settle-total"></div>
                        <div class="row g-3">
                            <div class="col-5">
                                <label class="form-label" for="settle-amount">Pay back</label>
                                <input type="number" step="0.01" min="0" name="reimbursement_amount" id="settle-amount" class="form-control" required>
                            </div>
                            <div class="col-7">
                                <label class="form-label" for="settle-remarks">Note <span class="text-muted fw-normal" id="settle-remarks-need">(optional)</span></label>
                                <input type="text" name="remarks" id="settle-remarks" maxlength="1000" class="form-control" placeholder="e.g. advance of 2,000 already paid">
                            </div>
                        </div>
                        <div class="font-12 text-muted mt-2" id="settle-note"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Settle trip</button>
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
            var modal = el('settle-modal');
            if (!modal) return;
            var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); };
            var approved = 0;

            // Paying back less than was approved needs a reason; more is not possible.
            function note() {
                var value = parseFloat(el('settle-amount').value);
                var less = value < approved, more = value > approved;
                el('settle-remarks').required = less;
                el('settle-remarks-need').textContent = less ? '(required)' : '(optional)';
                el('settle-note').textContent = more ? 'Only ' + fmt(approved) + ' was approved, so no more than that can be paid back. Saving will be refused.'
                    : (less ? fmt(approved - value) + ' less than the approved expenses. Say why in the note.'
                    : 'After settling, this trip\'s bookings and expenses can\'t be changed unless it is reopened.');
            }

            modal.addEventListener('show.bs.modal', function (event) {
                var b = event.relatedTarget;
                if (!b) return;
                var booked = parseFloat(b.dataset.booked) || 0, estimate = b.dataset.estimate === '' ? null : parseFloat(b.dataset.estimate);
                approved = parseFloat(b.dataset.approved) || 0;
                el('settle-form').action = b.dataset.action;
                el('settle-intro').textContent = (b.dataset.who || 'The employee') + ': ' + b.dataset.trip + '.';
                el('settle-booked').textContent = fmt(booked);
                el('settle-approved').textContent = fmt(approved);
                el('settle-total').textContent = 'Trip cost ' + fmt(booked + approved) + (estimate !== null ? (booked + approved > estimate ? ', ' + fmt(booked + approved - estimate) + ' over the ' + fmt(estimate) + ' estimate.' : ' of ' + fmt(estimate) + ' estimated.') : '.');
                el('settle-amount').value = approved.toFixed(2); el('settle-amount').max = approved.toFixed(2);
                el('settle-remarks').value = b.dataset.remarks || '';
                note();
            });
            el('settle-amount').addEventListener('input', note);
        })();
    </script>
@endpush
