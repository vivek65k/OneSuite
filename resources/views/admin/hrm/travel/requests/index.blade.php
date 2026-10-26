@extends('layouts.admin')

@section('title', 'Travel Requests')

@php
    use App\Models\TravelBooking;
    use App\Models\TravelExpense;
    use App\Models\TravelRequest;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pageUrl = fn (array $overrides = []) => route('admin.travel-requests.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $modeIcon = ['flight' => 'ti-plane', 'train' => 'ti-train', 'bus' => 'ti-bus', 'car' => 'ti-car', 'other' => 'ti-map-pin'];
    $statusMeta = ['pending' => ['amber', 'ti-clock'], 'approved' => ['success', 'ti-circle-check'], 'rejected' => ['rose', 'ti-x']];
    $tabs = ['' => ['label' => 'All requests', 'tone' => 'indigo', 'count' => $counts['all']]];
    foreach (TravelRequest::STATUSES as $value => $label) {
        $tabs[$value] = ['label' => $label, 'tone' => $statusMeta[$value][0], 'count' => $counts[$value] ?? 0];
    }
    $filterValues = [
        'mode' => TravelRequest::MODES[$state['mode']] ?? null,
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['mode' => 'Travelling by', 'employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $dates = fn ($r) => $r->start_date->isSameDay($r->end_date)
        ? $r->start_date->format('M j, Y')
        : $r->start_date->format('M j').' – '.$r->end_date->format($r->start_date->isSameMonth($r->end_date) ? 'j, Y' : 'M j, Y');
    $oldEmployee = (string) old('employee_id', $state['employee_id'] ?? '');
    $canSeeBookings = auth()->user()->can('viewAny', TravelBooking::class);
    $canSeeExpenses = auth()->user()->can('viewAny', TravelExpense::class);
@endphp

@section('content')
    <x-page-title title="Travel Requests" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for approval" :value="$kpis['pending']" icon="ti-clock" :tone="$kpis['soon'] ? 'rose' : ($kpis['pending'] ? 'amber' : 'success')" :hint="$kpis['pending'] ? ($kpis['soon'] ? $kpis['soon'].' leaving within a week' : $money($kpis['pending_cost']).' estimated in total') : 'Nothing to approve'" />
        <x-kpi-card label="Travelling now" :value="$kpis['travelling']" icon="ti-plane" tone="indigo" :hint="$kpis['travelling'] ? 'On an approved trip today' : 'Nobody is away on a trip today'" />
        <x-kpi-card label="Upcoming trips" :value="$kpis['upcoming']" icon="ti-calendar-event" tone="teal" :hint="$kpis['next'] ? 'Next one leaves '.$kpis['next']->format('M j') : 'No approved trip ahead'" />
        <x-kpi-card :label="'Approved for '.today()->year" :value="$kpis['year_trips'] ? $money($kpis['year_cost']) : '—'" icon="ti-receipt" tone="amber" :hint="$kpis['year_trips'] ? 'Estimated cost of '.$kpis['year_trips'].' '.Str::plural('trip', $kpis['year_trips']) : 'No trip approved this year'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.travel-requests.store') }}" class="card form-section tone-indigo mb-0 h-100" id="travel-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-plane"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Request a trip</h5>
                            <p class="form-section-sub">Enter a business trip for an employee. It goes for approval before anything is booked.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="travel-employee">Employee</label>
                                <select name="employee_id" id="travel-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formPeople as $person)
                                        <option value="{{ $person->id }}" @selected($oldEmployee === (string) $person->id)>{{ $person->name }}{{ $person->employee_code ? ' ('.$person->employee_code.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="travel-purpose">Purpose</label>
                                <input type="text" name="purpose" id="travel-purpose" value="{{ old('purpose') }}" maxlength="255" class="form-control @error('purpose') is-invalid @enderror" placeholder="Why the trip is needed" required>
                                <div class="quick-chips">
                                    @foreach (['Client meeting', 'Training', 'Conference', 'Site visit'] as $purpose)
                                        <button type="button" class="quick-chip" data-purpose="{{ $purpose }}">{{ $purpose }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="travel-from">From</label>
                                <input type="text" name="from_location" id="travel-from" value="{{ old('from_location') }}" maxlength="255" class="form-control @error('from_location') is-invalid @enderror" placeholder="e.g. Delhi" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="travel-to">To</label>
                                <input type="text" name="to_location" id="travel-to" value="{{ old('to_location') }}" maxlength="255" class="form-control @error('to_location') is-invalid @enderror" placeholder="e.g. Mumbai" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="travel-start">Leaves on</label>
                                <input type="date" name="start_date" id="travel-start" value="{{ old('start_date') }}" min="{{ $limits['min'] }}" max="{{ $limits['max'] }}" class="form-control @error('start_date') is-invalid @enderror" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="travel-end">Returns on</label>
                                <input type="date" name="end_date" id="travel-end" value="{{ old('end_date') }}" class="form-control @error('end_date') is-invalid @enderror" required>
                                <div class="quick-chips">
                                    @foreach ([1 => 'Same day', 2 => '2 days', 3 => '3 days', 5 => '5 days', 7 => '1 week'] as $days => $label)
                                        <button type="button" class="quick-chip" data-days="{{ $days }}">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="travel-mode">Travelling by</label>
                                <select name="mode_of_travel" id="travel-mode" class="form-select" required>
                                    @foreach (TravelRequest::MODES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('mode_of_travel', 'flight') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="travel-cost">Estimated cost <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="number" step="0.01" min="0" name="estimated_cost" id="travel-cost" value="{{ old('estimated_cost') }}" class="form-control @error('estimated_cost') is-invalid @enderror" placeholder="Tickets, stay and local travel">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Submit request</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">The trip</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">From</span>
                                <span class="rev-compare-value promo-title" id="sum-from">—</span>
                                <span class="rev-compare-meta" id="sum-leave"></span>
                            </div>
                            <i class="ti ti-arrow-right"></i>
                            <div>
                                <span class="rev-compare-label">To</span>
                                <span class="rev-compare-value promo-title" id="sum-to">—</span>
                                <span class="rev-compare-meta" id="sum-length"></span>
                            </div>
                        </div>
                        <div class="font-13 text-muted mt-3" id="sum-cost"></div>
                        <div class="run-note tone-amber mt-2" id="sum-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>One person can't be on two trips on the same day, and a single trip can last at most {{ $limits['days'] }} days.</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.travel-requests.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Request status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name, place or purpose" aria-label="Search travel requests">
                    </div>
                    <select name="mode" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Travelling by">
                        <option value="">Any transport</option>
                        @foreach (TravelRequest::MODES as $value => $label)
                            <option value="{{ $value }}" @selected($state['mode'] === $value)>{{ $label }}</option>
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
                        <th>Trip</th>
                        <th>When</th>
                        <th class="text-end">Est. cost</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $r)
                        @php
                            $meta = $statusMeta[$r->status] ?? ['slate', 'ti-circle'];
                            $days = (int) $r->start_date->diffInDays($r->end_date) + 1;
                            $stage = $stages[$r->id] ?? null;
                            $pending = $r->status === 'pending';
                            $started = $r->start_date->lte(today());
                            $over = $r->end_date->lt(today());
                            // A pending request whose trip has already begun: someone is travelling without approval, or it was never dealt with.
                            $late = $pending && $started;
                            $hasRecords = $r->bookings_count || $r->expenses_count || $r->settlement;
                        @endphp
                        <tr class="{{ $late ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$r->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $r->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$r->employee?->employee_code, $r->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold"><i class="ti {{ $modeIcon[$r->mode_of_travel] ?? 'ti-map-pin' }} text-muted" title="{{ TravelRequest::MODES[$r->mode_of_travel] ?? $r->mode_of_travel }}"></i> {{ $r->from_location }} <i class="ti ti-arrow-right text-muted font-12"></i> {{ $r->to_location }}</div>
                                <div class="payslip-parts text-wrap" title="{{ $r->purpose }}">{{ Str::limit($r->purpose, 60) }}</div>
                            </td>
                            <td class="text-nowrap">
                                {{ $dates($r) }}
                                <div class="payslip-parts">{{ $days }} {{ Str::plural('day', $days) }}{{ $r->status === 'rejected' || $late ? '' : ($over ? ' · over' : ($started ? ' · away now' : ' · in '.(int) today()->diffInDays($r->start_date).' '.Str::plural('day', (int) today()->diffInDays($r->start_date)))) }}</div>
                            </td>
                            <td class="text-end text-nowrap">{{ $r->estimated_cost !== null ? $money($r->estimated_cost) : '—' }}</td>
                            <td>
                                <span class="tag tone-{{ $meta[0] }}"><i class="ti {{ $meta[1] }}"></i>{{ TravelRequest::STATUSES[$r->status] ?? $r->status }}</span>
                                <div class="payslip-parts text-wrap">
                                    @if ($pending)
                                        @if ($late)
                                            <span class="text-danger">{{ $over ? 'Trip is over, never approved' : 'Trip has started' }}</span>
                                        @endif
                                        @if ($stage && $stage['required'] > 1)
                                            <div>Level {{ min($stage['done'] + 1, $stage['required']) }} of {{ $stage['required'] }}{{ $stage['waiting'] ? ' · waiting for '.$stage['waiting'] : '' }}</div>
                                        @elseif ($stage && $stage['waiting'])
                                            <div>Waiting for {{ $stage['waiting'] }}</div>
                                        @endif
                                    @elseif ($r->status === 'rejected')
                                        {{ $r->remarks ? Str::limit($r->remarks, 60) : '' }}
                                    @else
                                        {{ collect([$r->bookings_count ? $r->bookings_count.' '.Str::plural('booking', $r->bookings_count) : null, $r->expenses_count ? $r->expenses_count.' '.Str::plural('expense', $r->expenses_count) : null, $r->settlement ? 'settled' : null])->filter()->implode(' · ') ?: ($r->reviewedBy ? 'by '.$r->reviewedBy->name : '') }}
                                    @endif
                                </div>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($pending && $stage)
                                        @if ($stage['can_act'])
                                            <form method="POST" action="{{ route('admin.travel-approval.approve', $r) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-check"></i> Approve</button>
                                            </form>
                                            <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="modal" data-bs-target="#reject-modal"
                                                    data-action="{{ route('admin.travel-approval.reject', $r) }}" data-who="{{ $r->employee?->name }}" data-trip="{{ $r->from_location }} to {{ $r->to_location }}, {{ $dates($r) }}"><i class="ti ti-x"></i> Reject</button>
                                        @elseif ($stage['own'])
                                            <span class="font-12 text-muted" title="Someone else has to review your own request">Your own request</span>
                                        @endif
                                        @can('update', $r)
                                            <button type="button" class="btn-icon btn-icon-accent" title="Change request" data-bs-toggle="modal" data-bs-target="#travel-modal"
                                                    data-action="{{ route('admin.travel-requests.update', $r) }}" data-id="{{ $r->id }}" data-employee="{{ $r->employee_id }}" data-who="{{ $r->employee?->name }}" data-purpose="{{ $r->purpose }}" data-from="{{ $r->from_location }}" data-to="{{ $r->to_location }}"
                                                    data-start="{{ $r->start_date->toDateString() }}" data-end="{{ $r->end_date->toDateString() }}" data-mode="{{ $r->mode_of_travel }}" data-cost="{{ $r->estimated_cost }}"><i class="ti ti-pencil font-16"></i></button>
                                        @endcan
                                    @elseif ($r->status === 'approved')
                                        @if ($canSeeBookings)
                                            <a href="{{ route('admin.travel-bookings.index') }}" class="btn-icon" title="Tickets and hotel bookings"><i class="ti ti-ticket font-16"></i></a>
                                        @endif
                                        @if ($canSeeExpenses)
                                            <a href="{{ route('admin.travel-expenses.index') }}" class="btn-icon" title="Travel expenses"><i class="ti ti-receipt font-16"></i></a>
                                        @endif
                                    @endif
                                    @unless ($hasRecords)
                                        @can('delete', $r)
                                            <form action="{{ route('admin.travel-requests.destroy', $r) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $r->status === 'approved' ? 'Delete this approved trip? Nothing has been booked or claimed against it yet.' : 'Delete this request?' }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Delete request"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-plane font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No travel requests match</div>
                                    <span class="font-13"><a href="{{ route('admin.travel-requests.index') }}">Show all requests</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No travel requests yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Enter the first trip with the form above.' : 'Trips requested by employees will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $requests->links() }}</div></div>
        @endif
    </div>

    {{-- Reject with a reason --}}
    <div class="modal fade" id="reject-modal" tabindex="-1" aria-labelledby="reject-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="reject-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="reject-modal-title">Reject travel request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="font-13 text-muted mb-3" id="reject-intro"></div>
                    <label class="form-label" for="reject-remarks">Reason</label>
                    <textarea name="remarks" id="reject-remarks" rows="3" maxlength="1000" class="form-control" placeholder="The employee sees this" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="ti ti-x font-14 me-1"></i> Reject request</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Change a pending request --}}
    <div class="modal fade" id="travel-modal" tabindex="-1" aria-labelledby="travel-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="travel-edit">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="travel-modal-title">Change travel request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="font-13 text-muted mb-3" id="edit-who"></div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="edit-purpose">Purpose</label>
                            <input type="text" name="purpose" id="edit-purpose" maxlength="255" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-from">From</label>
                            <input type="text" name="from_location" id="edit-from" maxlength="255" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-to">To</label>
                            <input type="text" name="to_location" id="edit-to" maxlength="255" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-start">Leaves on</label>
                            <input type="date" name="start_date" id="edit-start" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-end">Returns on</label>
                            <input type="date" name="end_date" id="edit-end" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-mode">Travelling by</label>
                            <select name="mode_of_travel" id="edit-mode" class="form-select" required>
                                @foreach (TravelRequest::MODES as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-cost">Estimated cost</label>
                            <input type="number" step="0.01" min="0" name="estimated_cost" id="edit-cost" class="form-control">
                        </div>
                    </div>
                    <div class="font-12 text-muted mt-2" id="edit-note"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var trips = @json($trips);
            var today = @json(today()->toDateString());
            var maxDays = {{ (int) $limits['days'] }};
            var addDays = function (iso, n) { var d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + n); return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); };
            var span = function (a, b) { return Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000) + 1; };
            var nice = function (s) { return new Date(s + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); };
            var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); };
            // Another open trip of this person on any of these days (ignoring the one being edited).
            var clash = function (employee, from, to, ignore) {
                if (!employee || !from || !to || !trips[employee]) return null;
                return trips[employee].filter(function (t) { return String(t.id) !== String(ignore) && t.from <= to && t.to >= from; })[0] || null;
            };
            // What is wrong with a set of trip details, in one sentence; empty when all is fine.
            var problem = function (employee, from, to, start, end, ignore) {
                var c = clash(employee, start, end, ignore);
                if (from && to && from.trim().toLowerCase() === to.trim().toLowerCase()) return 'The destination is the same as the starting point.';
                if (start && end && end < start) return 'The return date is before the departure date.';
                if (start && end && span(start, end) > maxDays) return 'That is ' + span(start, end) + ' days. A single trip can last at most ' + maxDays + '.';
                if (c) return 'This person already has a trip from ' + nice(c.from) + ' to ' + nice(c.to) + '. Saving will be refused.';
                if (start && start < today) return 'This trip has already started. It will still need approval.';
                return '';
            };

            var reject = el('reject-modal');
            reject.addEventListener('show.bs.modal', function (event) {
                var b = event.relatedTarget;
                if (!b) return;
                el('reject-form').action = b.dataset.action;
                el('reject-intro').textContent = (b.dataset.who || 'The employee') + ': ' + b.dataset.trip + '.';
                el('reject-remarks').value = '';
            });
            reject.addEventListener('shown.bs.modal', function () { el('reject-remarks').focus(); });

            var edit = el('travel-modal'), editing = null, editEmployee = null;
            var note = function () { el('edit-note').textContent = problem(editEmployee, el('edit-from').value, el('edit-to').value, el('edit-start').value, el('edit-end').value, editing); };
            edit.addEventListener('show.bs.modal', function (event) {
                var b = event.relatedTarget;
                if (!b) return;
                editing = b.dataset.id; editEmployee = b.dataset.employee;
                el('travel-edit').action = b.dataset.action;
                el('edit-who').textContent = b.dataset.who || '';
                el('edit-purpose').value = b.dataset.purpose; el('edit-from').value = b.dataset.from; el('edit-to').value = b.dataset.to;
                el('edit-start').value = b.dataset.start; el('edit-end').value = b.dataset.end;
                el('edit-mode').value = b.dataset.mode; el('edit-cost').value = b.dataset.cost || '';
                note();
            });
            ['edit-from', 'edit-to', 'edit-start', 'edit-end'].forEach(function (id) { el(id).addEventListener('input', note); el(id).addEventListener('change', note); });

            var form = el('travel-form');
            if (!form) return;
            var employee = el('travel-employee'), from = el('travel-from'), to = el('travel-to'), start = el('travel-start'), end = el('travel-end'), cost = el('travel-cost');

            function render() {
                var ok = start.value && end.value && end.value >= start.value;
                var days = ok ? span(start.value, end.value) : null;
                var amount = parseFloat(cost.value);
                var warn = problem(employee.value, from.value, to.value, start.value, end.value, null);
                el('sum-from').textContent = from.value.trim() || '—';
                el('sum-to').textContent = to.value.trim() || '—';
                el('sum-leave').textContent = start.value ? 'leaves ' + nice(start.value) : '';
                el('sum-length').textContent = ok ? (days === 1 ? 'back the same day' : days + ' days, back ' + nice(end.value)) : '';
                el('sum-cost').textContent = amount > 0 ? 'Estimated ' + fmt(amount) + (days > 1 ? ', about ' + fmt(amount / days) + ' a day.' : '.') : '';
                el('sum-warn').hidden = !warn;
                el('sum-warn').querySelector('span').textContent = warn;
            }

            start.addEventListener('change', function () { if (!end.value || end.value < start.value) end.value = start.value; render(); });
            [employee, from, to, end, cost].forEach(function (input) { input.addEventListener('input', render); input.addEventListener('change', render); });
            form.querySelectorAll('[data-days]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    if (!start.value) { start.focus(); return; }
                    end.value = addDays(start.value, parseInt(chip.dataset.days, 10) - 1);
                    render();
                });
            });
            form.querySelectorAll('[data-purpose]').forEach(function (chip) {
                chip.addEventListener('click', function () { el('travel-purpose').value = chip.dataset.purpose; });
            });
            render();
        })();
    </script>
@endpush
