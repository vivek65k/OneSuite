@extends('layouts.admin')

@section('title', 'Travel Bookings')

@php
    use App\Models\TravelBooking;
    use App\Models\TravelRequest;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $pageUrl = fn (array $overrides = []) => route('admin.travel-bookings.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $typeMeta = [
        'flight' => ['indigo', 'ti-plane', 'Airline', 'PNR'],
        'train' => ['teal', 'ti-train', 'Train', 'PNR'],
        'bus' => ['amber', 'ti-bus', 'Operator', 'Ticket no.'],
        'hotel' => ['rose', 'ti-building', 'Hotel', 'Confirmation no.'],
        'cab' => ['success', 'ti-car', 'Cab company', 'Booking ID'],
        'other' => ['slate', 'ti-ticket', 'Provider', 'Reference'],
    ];
    $tabs = [
        '' => ['label' => 'Trips ahead', 'tone' => 'indigo', 'count' => $counts['']],
        'unbooked' => ['label' => 'Nothing booked', 'tone' => 'rose', 'count' => $counts['unbooked']],
        'over' => ['label' => 'Over estimate', 'tone' => 'amber', 'count' => $counts['over']],
        'past' => ['label' => 'Finished trips', 'tone' => 'slate', 'count' => $counts['past']],
    ];
    $filterValues = ['type' => TravelBooking::TYPES[$state['type']] ?? null, 'employee_id' => $employees[$state['employee_id']] ?? null, 'search' => $state['search']];
    $activeFilters = collect(['type' => 'Has a booking for', 'employee_id' => 'Employee', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $dates = fn ($t) => $t->start_date->isSameDay($t->end_date)
        ? $t->start_date->format('M j, Y')
        : $t->start_date->format('M j').' – '.$t->end_date->format($t->start_date->isSameMonth($t->end_date) ? 'j, Y' : 'M j, Y');
    $modeIcon = ['flight' => 'ti-plane', 'train' => 'ti-train', 'bus' => 'ti-bus', 'car' => 'ti-car', 'other' => 'ti-map-pin'];
    $canUpdate = auth()->user()->can('update', new TravelBooking);
    $bookedPct = $kpis['estimate'] > 0 ? (int) round($kpis['booked'] / $kpis['estimate'] * 100) : null;
@endphp

@section('content')
    <x-page-title title="Travel Bookings" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Trips ahead" :value="$kpis['open']" icon="ti-plane" tone="indigo" :hint="$kpis['open'] ? $kpis['bookings'].' '.Str::plural('booking', $kpis['bookings']).' made for them' : 'No approved trip ahead'" />
        <x-kpi-card label="Nothing booked yet" :value="$kpis['unbooked']" icon="ti-alert-triangle" :tone="$kpis['soon'] ? 'rose' : ($kpis['unbooked'] ? 'amber' : 'success')" :hint="$kpis['unbooked'] ? ($kpis['soon'] ? $kpis['soon'].' leaving within a week' : 'Tickets and stay still to arrange') : 'Every trip ahead has a booking'" />
        <x-kpi-card label="Booked so far" :value="$kpis['booked'] > 0 ? $money($kpis['booked']) : '—'" icon="ti-ticket" tone="teal" :hint="$bookedPct !== null ? $bookedPct.'% of the '.$money($kpis['estimate']).' estimated' : ($kpis['booked'] > 0 ? 'For the trips ahead' : 'Nothing booked for the trips ahead')" />
        <x-kpi-card label="Over estimate" :value="$kpis['over']" icon="ti-trending-up" :tone="$kpis['over'] ? 'amber' : 'success'" :hint="$kpis['over'] ? $money($kpis['over_by']).' more than was approved' : 'Bookings are within the estimates'" />
    </div>

    <div class="card list-view mb-3">
        <form method="GET" action="{{ route('admin.travel-bookings.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Trips">
                    @foreach ($tabs as $value => $tab)
                        @if (! in_array($value, ['unbooked', 'over'], true) || $tab['count'] || $state['tab'] === $value)
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name, place, provider or reference" aria-label="Search trips">
                    </div>
                    <select name="type" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Booking type">
                        <option value="">Any booking</option>
                        @foreach (TravelBooking::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected($state['type'] === $value)>{{ $label }}</option>
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
    </div>

    @forelse ($trips as $trip)
        @php
            $booked = (float) $trip->bookings->sum('cost');
            $estimate = $trip->estimated_cost !== null ? (float) $trip->estimated_cost : null;
            $overBy = $estimate !== null && $booked > $estimate ? $booked - $estimate : 0;
            $settled = $trip->settlement && $trip->settlement->status === 'settled';
            $startsIn = (int) today()->diffInDays($trip->start_date, false);
            $ended = $trip->end_date->lt(today());
            $days = (int) $trip->start_date->diffInDays($trip->end_date) + 1;
            $urgent = ! $ended && $trip->bookings->isEmpty() && $startsIn <= 7;
        @endphp
        <div class="card list-view mb-3" id="trip-{{ $trip->id }}">
            <div class="list-toolbar">
                <div class="list-user">
                <x-avatar :person="$trip->employee" />
                <div class="list-user-text">
                    <div class="list-user-name">{{ $trip->employee?->name ?? 'Removed employee' }} <span class="text-muted fw-normal">·</span> <i class="ti {{ $modeIcon[$trip->mode_of_travel] ?? 'ti-map-pin' }} text-muted"></i> {{ $trip->from_location }} <i class="ti ti-arrow-right text-muted font-14"></i> {{ $trip->to_location }}</div>
                    <div class="list-user-meta d-flex flex-wrap align-items-center gap-2">
                        <span>{{ $dates($trip) }} · {{ $days }} {{ Str::plural('day', $days) }}</span>
                        @if ($settled)
                            <span class="status-dot status-dot-success">Settled</span>
                        @elseif ($ended)
                            <span class="status-dot status-dot-warning">Trip over</span>
                        @elseif ($startsIn <= 0)
                            <span class="status-dot status-dot-success">Away now</span>
                        @else
                            <span class="status-dot status-dot-{{ $urgent ? 'danger' : 'warning' }}">Leaves in {{ $startsIn }} {{ Str::plural('day', $startsIn) }}</span>
                        @endif
                        <span title="{{ $trip->purpose }}">{{ Str::limit($trip->purpose, 50) }}</span>
                    </div>
                </div>
                </div>
                <div class="list-toolbar-actions">
                    <div class="text-end">
                        <div class="fw-semibold">{{ $money($booked) }}{{ $estimate !== null ? ' / '.$money($estimate) : '' }}</div>
                        <div class="font-12 {{ $overBy ? 'text-danger' : 'text-muted' }}">{{ $overBy ? $money($overBy).' over the estimate' : ($estimate !== null ? 'booked of the estimate' : 'booked · no estimate given') }}</div>
                    </div>
                    @if ($canCreate && ! $settled)
                        <button type="button" class="btn btn-de-primary btn-sm text-light px-3" data-bs-toggle="modal" data-bs-target="#booking-modal" data-mode="add"
                                data-action="{{ route('admin.travel-bookings.store') }}" data-trip="{{ $trip->id }}" data-who="{{ $trip->employee?->name }}" data-route="{{ $trip->from_location }} to {{ $trip->to_location }}"
                                data-start="{{ $trip->start_date->toDateString() }}" data-end="{{ $trip->end_date->toDateString() }}" data-transport="{{ $trip->mode_of_travel }}"><i class="ti ti-plus font-14 me-1"></i> Add booking</button>
                    @endif
                </div>
            </div>
            @if ($trip->bookings->isEmpty())
                <div class="list-subbar"><span class="font-13 {{ $urgent ? 'text-danger' : 'text-muted' }}"><i class="ti {{ $urgent ? 'ti-alert-triangle' : 'ti-info-circle' }}"></i> {{ $ended ? 'No booking was recorded for this trip.' : 'Nothing is booked for this trip yet.'.($urgent ? ' It leaves '.($startsIn <= 0 ? 'now' : 'in '.$startsIn.' '.Str::plural('day', $startsIn)).'.' : '') }}</span></div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <tbody>
                            @foreach ($trip->bookings as $booking)
                                @php
                                    $meta = $typeMeta[$booking->booking_type] ?? $typeMeta['other'];
                                @endphp
                                <tr>
                                    <td style="width: 38%">
                                        <div class="date-range-cell">
                                            <span class="list-avatar list-avatar-square tone-{{ $meta[0] }}" aria-hidden="true"><i class="ti {{ $meta[1] }} font-18"></i></span>
                                            <div>
                                                <div class="fw-semibold">{{ TravelBooking::TYPES[$booking->booking_type] ?? $booking->booking_type }}{{ $booking->provider ? ' · '.$booking->provider : '' }}</div>
                                                <div class="payslip-parts text-wrap">{{ $booking->booking_reference ? $meta[3].' '.$booking->booking_reference : 'No reference' }}{{ $booking->details ? ' · '.Str::limit($booking->details, 70) : '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">{{ $booking->travel_date?->format('D, M j, Y') }}</td>
                                    <td class="text-end text-nowrap fw-semibold">{{ $money($booking->cost) }}</td>
                                    <td class="text-end text-nowrap" style="width: 96px">
                                        @unless ($settled)
                                            <div class="list-row-actions">
                                                @can('update', $booking)
                                                    <button type="button" class="btn-icon btn-icon-accent" title="Change booking" data-bs-toggle="modal" data-bs-target="#booking-modal" data-mode="edit"
                                                            data-action="{{ route('admin.travel-bookings.update', $booking) }}" data-trip="{{ $trip->id }}" data-who="{{ $trip->employee?->name }}" data-route="{{ $trip->from_location }} to {{ $trip->to_location }}"
                                                            data-start="{{ $trip->start_date->toDateString() }}" data-end="{{ $trip->end_date->toDateString() }}"
                                                            data-type="{{ $booking->booking_type }}" data-provider="{{ $booking->provider }}" data-reference="{{ $booking->booking_reference }}" data-cost="{{ $booking->cost }}" data-date="{{ $booking->travel_date?->toDateString() }}" data-details="{{ $booking->details }}"><i class="ti ti-pencil font-16"></i></button>
                                                @endcan
                                                @can('delete', $booking)
                                                    <form action="{{ route('admin.travel-bookings.destroy', $booking) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this booking from the trip?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-icon btn-icon-danger" title="Remove booking"><i class="ti ti-trash font-16"></i></button>
                                                    </form>
                                                @endcan
                                            </div>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @empty
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-indigo"><i class="ti ti-ticket"></i></span>
                @if ($activeFilters->isNotEmpty() || $state['tab'] !== '')
                    <h5>No trips match</h5>
                    <p><a href="{{ route('admin.travel-bookings.index') }}">Show the trips ahead</a></p>
                @elseif ($counts['past'])
                    <h5>No trips ahead</h5>
                    <p>Bookings are added to approved trips. <a href="{{ $pageUrl(['tab' => 'past']) }}">See finished trips</a></p>
                @else
                    <h5>No approved trips yet</h5>
                    <p>Once a travel request is approved, its tickets and hotel are recorded here.</p>
                @endif
            </div>
        </div>
    @endforelse

    @if ($trips->hasPages())
        <div class="list-footer job-grid-footer"><div class="ms-auto">{{ $trips->links() }}</div></div>
    @endif

    @if ($canCreate || $canUpdate)
        <div class="modal fade" id="booking-modal" tabindex="-1" aria-labelledby="booking-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="booking-form">
                    @csrf
                    <input type="hidden" name="_method" id="booking-method" value="POST">
                    <input type="hidden" name="travel_request_id" id="booking-trip">
                    <div class="modal-header">
                        <h5 class="modal-title" id="booking-modal-title">Add booking</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="booking-intro"></div>
                        <label class="form-label d-block">What was booked</label>
                        <div class="segmented mb-3 flex-wrap" id="booking-types" role="group" aria-label="Booking type">
                            @foreach (TravelBooking::TYPES as $value => $label)
                                <button type="button" class="segmented-item" data-type="{{ $value }}" data-provider-label="{{ $typeMeta[$value][2] }}" data-reference-label="{{ $typeMeta[$value][3] }}"><i class="ti {{ $typeMeta[$value][1] }}"></i> {{ $label }}</button>
                            @endforeach
                        </div>
                        <input type="hidden" name="booking_type" id="booking-type" value="flight">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="booking-provider"><span id="booking-provider-label">Airline</span> <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="provider" id="booking-provider" maxlength="255" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="booking-reference"><span id="booking-reference-label">PNR</span> <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="booking_reference" id="booking-reference" maxlength="255" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="booking-date" id="booking-date-label">Travel date</label>
                                <input type="date" name="travel_date" id="booking-date" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="booking-cost">Cost</label>
                                <input type="number" step="0.01" min="0" name="cost" id="booking-cost" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="booking-details">Details <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="details" id="booking-details" maxlength="1000" class="form-control" placeholder="Flight number, room type, pick-up time…">
                            </div>
                        </div>
                        <div class="font-12 text-muted mt-2" id="booking-note"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> <span id="booking-submit">Add booking</span></button>
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
            var modal = el('booking-modal');
            if (!modal) return;
            var slack = {{ (int) $slack }};
            var chips = Array.prototype.slice.call(modal.querySelectorAll('[data-type]'));
            var addDays = function (iso, n) { var d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + n); return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); };
            var nice = function (s) { return new Date(s + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); };
            var trip = { start: '', end: '' };

            function pick(type) {
                var chip = chips.filter(function (c) { return c.dataset.type === type; })[0] || chips[0];
                chips.forEach(function (c) { c.classList.toggle('active', c === chip); c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
                el('booking-type').value = chip.dataset.type;
                el('booking-provider-label').textContent = chip.dataset.providerLabel;
                el('booking-reference-label').textContent = chip.dataset.referenceLabel;
                el('booking-date-label').textContent = chip.dataset.type === 'hotel' ? 'Check-in date' : 'Travel date';
            }
            function note() {
                var date = el('booking-date').value;
                el('booking-note').textContent = date && (date < el('booking-date').min || date > el('booking-date').max)
                    ? 'The trip runs ' + nice(trip.start) + ' to ' + nice(trip.end) + '. The date must be within a day of it; saving will be refused.' : '';
            }

            modal.addEventListener('show.bs.modal', function (event) {
                var b = event.relatedTarget;
                if (!b) return;
                var edit = b.dataset.mode === 'edit';
                trip = { start: b.dataset.start, end: b.dataset.end };
                el('booking-form').action = b.dataset.action;
                el('booking-method').value = edit ? 'PUT' : 'POST';
                el('booking-trip').value = b.dataset.trip;
                el('booking-modal-title').textContent = edit ? 'Change booking' : 'Add booking';
                el('booking-submit').textContent = edit ? 'Save' : 'Add booking';
                el('booking-intro').textContent = (b.dataset.who || 'Trip') + ': ' + b.dataset.route + ', ' + nice(trip.start) + (trip.end !== trip.start ? ' to ' + nice(trip.end) : '') + '.';
                el('booking-date').min = addDays(trip.start, -slack); el('booking-date').max = addDays(trip.end, slack);
                // A new booking starts as the trip's own transport on its first day.
                var transport = { flight: 'flight', train: 'train', bus: 'bus', car: 'cab' }[b.dataset.transport] || 'other';
                pick(edit ? b.dataset.type : transport);
                el('booking-provider').value = edit ? b.dataset.provider || '' : '';
                el('booking-reference').value = edit ? b.dataset.reference || '' : '';
                el('booking-cost').value = edit ? b.dataset.cost || '' : '';
                el('booking-date').value = edit ? b.dataset.date : trip.start;
                el('booking-details').value = edit ? b.dataset.details || '' : '';
                note();
            });
            chips.forEach(function (chip) { chip.addEventListener('click', function () { pick(chip.dataset.type); }); });
            el('booking-date').addEventListener('input', note);
        })();
    </script>
@endpush
