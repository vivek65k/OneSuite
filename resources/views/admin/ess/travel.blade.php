@extends('layouts.admin')

@section('title', 'My Travel')

@php
    use App\Http\Controllers\Admin\Ess\MyTravelController as Travel;
    use App\Models\TravelBooking;
    use App\Models\TravelExpense;
    use App\Models\TravelRequest;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n, fmod((float) $n, 1) ? 2 : 0);
    $pageUrl = fn (array $overrides = []) => route('admin.my-travel.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All trips', 'tone' => 'indigo', 'count' => $counts['all']],
        'pending' => ['label' => 'Waiting for approval', 'tone' => 'amber', 'count' => $counts['pending']],
        'upcoming' => ['label' => 'Upcoming or under way', 'tone' => 'sky', 'count' => $counts['upcoming']],
        'past' => ['label' => 'Past', 'tone' => 'success', 'count' => $counts['past']],
        'rejected' => ['label' => 'Rejected', 'tone' => 'rose', 'count' => $counts['rejected']],
    ];
    $stageMeta = [
        'pending' => ['amber', 'ti-clock', 'Waiting for approval'],
        'upcoming' => ['sky', 'ti-calendar-event', 'Approved'],
        'now' => ['indigo', 'ti-plane', 'Under way'],
        'past' => ['success', 'ti-circle-check', 'Completed'],
        'rejected' => ['rose', 'ti-circle-x', 'Rejected'],
    ];
    $modeIcons = ['flight' => 'ti-plane', 'train' => 'ti-train', 'bus' => 'ti-bus', 'car' => 'ti-car', 'other' => 'ti-map-pin'];
    $expenseTones = ['pending' => 'amber', 'approved' => 'success', 'rejected' => 'rose'];
    $next = $kpis['next'];
    // A refused edit reopens its popup; a refused expense reopens that popup; a refused new request keeps what was typed.
    $tripFields = ['purpose', 'from_location', 'to_location', 'start_date', 'end_date', 'mode_of_travel', 'estimated_cost'];
    $expenseFields = ['category', 'amount', 'expense_date'];
    $editing = old('_method') === 'PUT' ? (int) old('_trip') : 0;
    $expensing = $errors->hasAny($expenseFields) ? (int) old('_trip') : 0;
    $newErrors = ! $editing && ! $expensing && $errors->hasAny($tripFields);
    $typed = collect(array_merge($tripFields, $expenseFields))->mapWithKeys(fn ($field) => [$field => is_scalar(old($field)) ? (string) old($field) : ''])->all();
    $fresh = fn (string $field) => $newErrors ? $typed[$field] : '';
@endphp

@section('content')
    <x-page-title title="My Travel" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any() && ! $newErrors)
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Next trip" :value="$next ? ($next->stage === 'now' ? 'Now' : $next->start_date->format('M j')) : '—'" icon="ti-plane" tone="sky" :hint="$next ? Str::limit($next->from_location.' to '.$next->to_location, 30).($next->stage === 'now' ? ' · back '.$next->end_date->format('M j') : ' · in '.$next->days_to_go.' '.Str::plural('day', $next->days_to_go)) : 'No approved trip coming up'" />
        <x-kpi-card label="Waiting for approval" :value="$kpis['pending']" icon="ti-clock" :tone="$kpis['pending'] ? 'amber' : 'indigo'" :hint="$kpis['pending'] ? 'Not decided yet' : 'Nothing is waiting'" :href="$kpis['pending'] ? $pageUrl(['tab' => 'pending']) : null" />
        <x-kpi-card label="Expenses to be paid" :value="$money($kpis['owed'])" icon="ti-wallet" :tone="$kpis['to_claim'] ? 'amber' : 'teal'" :hint="collect([$kpis['owed_pending'] > 0 ? $money($kpis['owed_pending']).' more waiting for approval' : null, $kpis['to_claim'] ? $kpis['to_claim'].' finished '.Str::plural('trip', $kpis['to_claim']).' with nothing claimed' : null])->filter()->implode(' · ') ?: 'Approved, on trips not settled yet'" />
        <x-kpi-card label="Trips in {{ now()->year }}" :value="$kpis['trips_year']" icon="ti-map-pin" tone="indigo" :hint="$kpis['trips_year'] ? $kpis['days_year'].' '.Str::plural('day', $kpis['days_year']).' on the road' : 'No approved trips this year'" />
    </div>

    @if ($canRequest)
        <form method="POST" action="{{ route('admin.my-travel.store') }}" class="card form-section tone-indigo" id="trip-form">
            @csrf
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-plane"></i></span>
                <div class="flex-grow-1">
                    <h5 class="form-section-title">Request a trip</h5>
                    <p class="form-section-sub">Tell your manager where, when and why. Bookings are made and expenses claimed after it is approved.</p>
                </div>
            </div>
            <div class="form-section-body">
                @if ($newErrors)
                    <div class="alert alert-danger py-2 font-13">@foreach (collect($tripFields)->flatMap(fn ($field) => $errors->get($field)) as $message)<div>{{ $message }}</div>@endforeach</div>
                @endif
                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label" for="trip-purpose">What is the trip for? <span class="text-danger">*</span></label>
                        <input type="text" name="purpose" id="trip-purpose" class="form-control" value="{{ $fresh('purpose') }}" maxlength="255" placeholder="e.g. Quarterly review with the client" required>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="trip-from">From <span class="text-danger">*</span></label>
                        <input type="text" name="from_location" id="trip-from" class="form-control" value="{{ $fresh('from_location') }}" maxlength="255" placeholder="City" required>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="trip-to">To <span class="text-danger">*</span></label>
                        <input type="text" name="to_location" id="trip-to" class="form-control" value="{{ $fresh('to_location') }}" maxlength="255" placeholder="City" required>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="trip-start">Leaving <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="trip-start" class="form-control" value="{{ $fresh('start_date') }}" min="{{ $minDate }}" max="{{ $maxDate }}" required>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="trip-end">Returning <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="trip-end" class="form-control" value="{{ $fresh('end_date') }}" required>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="trip-cost">Estimated cost <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="number" step="0.01" min="0" name="estimated_cost" id="trip-cost" class="form-control" value="{{ $fresh('estimated_cost') }}" placeholder="0.00">
                    </div>
                    <div class="col-12">
                        <label class="form-label">How will you travel? <span class="text-danger">*</span></label>
                        <div class="segmented flex-wrap" id="trip-modes" role="group" aria-label="Mode of travel">
                            @foreach (TravelRequest::MODES as $value => $label)
                                <button type="button" class="segmented-item" data-mode="{{ $value }}"><i class="ti {{ $modeIcons[$value] ?? 'ti-map-pin' }}"></i> {{ $label }}</button>
                            @endforeach
                        </div>
                        <input type="hidden" name="mode_of_travel" id="trip-mode" value="{{ $fresh('mode_of_travel') ?: 'flight' }}">
                    </div>
                </div>
                <div class="run-note tone-indigo mt-3" id="trip-note"><i class="ti ti-info-circle"></i><span>Pick the dates to see how long you will be away.</span></div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4" id="trip-submit"><i class="ti ti-send font-14 me-1"></i> Send for approval</button>
                </div>
            </div>
        </form>
    @else
        <div class="alert alert-warning d-flex align-items-center gap-2"><i class="ti ti-info-circle font-18"></i> Your employee record is {{ $employeeStatus }}, so new trips can't be requested.</div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <nav class="list-tabs" aria-label="Trip status">
                @foreach ($tabs as $value => $tab)
                    @if ($value === '' || $tab['count'] || $state['tab'] === $value)
                        <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
        </div>
        @if ($trips->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="ti ti-plane font-24 d-block mb-2"></i>
                @if ($state['tab'] !== '')
                    <div class="fw-semibold text-body mb-1">No trips here</div>
                    <span class="font-13"><a href="{{ route('admin.my-travel.index') }}">Show all trips</a></span>
                @else
                    <div class="fw-semibold text-body mb-1">You haven't requested any trips yet</div>
                    <span class="font-13">Requests you send appear here with who they are waiting on.</span>
                @endif
            </div>
        @endif
    </div>

    @foreach ($trips as $trip)
        @php
            $stage = $stageMeta[$trip->stage];
            $step = $flow[$trip->id] ?? null;
            $canEdit = $trip->status === 'pending' && $step && $step['done'] === 0 && $canRequest;
            $first = $trip->start_date->copy()->subDays(Travel::SLACK_DAYS);
            $last = $trip->end_date->copy()->addDays(Travel::SLACK_DAYS);
        @endphp
        <div class="card list-view" id="trip-{{ $trip->id }}">
            <div class="list-toolbar">
                <div class="list-user">
                    <span class="list-avatar list-avatar-square tone-{{ $stage[0] }}"><i class="ti {{ $modeIcons[$trip->mode_of_travel] ?? 'ti-map-pin' }}"></i></span>
                    <div class="list-user-text">
                        <div class="list-user-name">{{ $trip->from_location }} <i class="ti ti-arrow-right font-12 text-muted"></i> {{ $trip->to_location }}</div>
                        <div class="list-user-meta">{{ $trip->purpose }}</div>
                    </div>
                </div>
                <div class="list-toolbar-actions">
                    <span class="font-13 text-nowrap">{{ $trip->start_date->format('D, M j') }}{{ $trip->days > 1 ? ' – '.$trip->end_date->format('D, M j, Y') : ', '.$trip->start_date->format('Y') }} <span class="text-muted">· {{ $trip->days }} {{ Str::plural('day', $trip->days) }}</span></span>
                    <span class="tag tone-{{ $stage[0] }}"><i class="ti {{ $stage[1] }}"></i>{{ $stage[2] }}{{ $trip->stage === 'upcoming' ? ' · in '.$trip->days_to_go.' '.Str::plural('day', $trip->days_to_go) : '' }}</span>
                    @if ($trip->settled)
                        <span class="tag tone-slate"><i class="ti ti-lock"></i>Settled</span>
                    @endif
                    @if ($canEdit)
                        <button type="button" class="btn-icon" title="Correct this request" data-bs-toggle="modal" data-bs-target="#edit-modal" data-id="{{ $trip->id }}" data-action="{{ route('admin.my-travel.update', $trip) }}"
                                data-purpose="{{ $trip->purpose }}" data-from="{{ $trip->from_location }}" data-to="{{ $trip->to_location }}" data-start="{{ $trip->start_date->toDateString() }}" data-end="{{ $trip->end_date->toDateString() }}" data-mode="{{ $trip->mode_of_travel }}" data-cost="{{ $trip->estimated_cost }}"><i class="ti ti-edit font-16"></i></button>
                    @endif
                    @if ($trip->status === 'pending')
                        <form action="{{ route('admin.my-travel.destroy', $trip) }}" method="POST" class="d-inline" onsubmit="return confirm(@js('Withdraw your request to travel to '.$trip->to_location.'? It is removed and nobody will be asked to approve it.'))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm review-btn is-reject"><i class="ti ti-x"></i> Withdraw</button>
                        </form>
                    @endif
                    @if ($trip->can_expense)
                        <button type="button" class="btn btn-sm review-btn is-approve" data-bs-toggle="modal" data-bs-target="#expense-modal" data-id="{{ $trip->id }}" data-action="{{ route('admin.my-travel.expenses.store', $trip) }}"
                                data-label="{{ $trip->from_location }} to {{ $trip->to_location }}" data-min="{{ $first->toDateString() }}" data-max="{{ min($last->toDateString(), today()->toDateString()) }}" data-range="{{ $first->format('M j') }} and {{ $last->format('M j, Y') }}"><i class="ti ti-receipt"></i> Add expense</button>
                    @endif
                </div>
            </div>
            <div class="list-subbar">
                <div class="d-flex flex-wrap gap-3 font-13">
                    @if ($trip->status === 'pending' && $step)
                        <span class="text-muted"><i class="ti ti-user-check"></i> {{ $step['name'] ? 'Waiting on '.$step['name'] : 'Waiting on HR' }}{{ $step['required'] > 1 ? ' · step '.min($step['done'] + 1, $step['required']).' of '.$step['required'] : '' }}</span>
                    @elseif ($trip->reviewedBy || $trip->reviewed_at)
                        <span class="text-muted"><i class="ti ti-user-check"></i> {{ ucfirst($trip->status) }}{{ $trip->reviewedBy ? ' by '.$trip->reviewedBy->name : '' }}{{ $trip->reviewed_at ? ' on '.$trip->reviewed_at->format('M j') : '' }}</span>
                    @endif
                    @if ($trip->remarks)
                        <span class="{{ $trip->status === 'rejected' ? 'text-danger' : 'text-muted' }}" title="{{ $trip->remarks }}">“{{ Str::limit($trip->remarks, 90) }}”</span>
                    @endif
                    <span class="text-muted">{{ TravelRequest::MODES[$trip->mode_of_travel] ?? Str::headline((string) $trip->mode_of_travel) }}{{ $trip->estimated_cost !== null ? ' · estimated '.$money($trip->estimated_cost) : '' }}</span>
                    @if ($trip->status === 'approved')
                        <span class="text-muted">Booked for you: <strong class="text-body">{{ $money($trip->booked) }}</strong></span>
                        <span class="text-muted">You claimed: <strong class="text-body">{{ $money($trip->claimed) }}</strong></span>
                        @if ($trip->settled && $trip->settlement)
                            <span class="text-success"><i class="ti ti-cash"></i> Reimbursed {{ $money($trip->settlement->reimbursement_amount) }}{{ $trip->settlement->settled_date ? ' on '.$trip->settlement->settled_date->format('M j, Y') : '' }}</span>
                        @endif
                    @endif
                </div>
            </div>
            @if ($trip->bookings->isNotEmpty() || $trip->expenses->isNotEmpty())
                <div class="table-responsive">
                    <table class="table list-table mb-0">
                        <tbody>
                            @foreach ($trip->bookings->sortBy('travel_date') as $booking)
                                <tr>
                                    <td style="width: 26%"><span class="tag tone-indigo"><i class="ti ti-ticket"></i>{{ TravelBooking::TYPES[$booking->booking_type] ?? Str::headline((string) $booking->booking_type) }} booking</span></td>
                                    <td>
                                        {{ collect([$booking->provider, $booking->booking_reference ? 'ref '.$booking->booking_reference : null])->filter()->implode(' · ') ?: 'Booked by the travel desk' }}
                                        @if ($booking->details)
                                            <div class="payslip-parts" title="{{ $booking->details }}">{{ Str::limit($booking->details, 90) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $booking->travel_date?->format('D, M j') ?? '—' }}</td>
                                    <td class="text-end text-nowrap" style="width: 14%">{{ number_format((float) $booking->cost, 2) }}</td>
                                    <td style="width: 18%"><span class="font-12 text-muted">paid by the company</span></td>
                                </tr>
                            @endforeach
                            @foreach ($trip->expenses->sortBy('expense_date') as $expense)
                                <tr>
                                    <td style="width: 26%"><span class="tag tone-teal"><i class="ti ti-receipt"></i>{{ TravelExpense::CATEGORIES[$expense->category] ?? Str::headline((string) $expense->category) }}</span></td>
                                    <td>
                                        <span class="tag tone-{{ $expenseTones[$expense->status] ?? 'slate' }}">{{ TravelExpense::STATUSES[$expense->status] ?? Str::headline((string) $expense->status) }}</span>
                                        @if ($expense->remarks)
                                            <span class="font-12 {{ $expense->status === 'rejected' ? 'text-danger' : 'text-muted' }} ms-1" title="{{ $expense->remarks }}">“{{ Str::limit($expense->remarks, 70) }}”</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $expense->expense_date?->format('D, M j') }}</td>
                                    <td class="text-end text-nowrap" style="width: 14%"><span class="salary-figure">{{ number_format((float) $expense->amount, 2) }}</span></td>
                                    <td class="text-end" style="width: 18%">
                                        @if ($expense->status === 'pending' && ! $trip->settled)
                                            <form action="{{ route('admin.my-travel.expenses.destroy', [$trip, $expense]) }}" method="POST" class="d-inline" onsubmit="return confirm(@js('Remove this expense of '.number_format((float) $expense->amount, 2).'?'))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Remove this expense"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif ($trip->status === 'approved')
                <div class="font-13 text-muted px-3 py-3">{{ $trip->can_expense ? 'No bookings or expenses yet. Use "Add expense" for anything you paid for yourself.' : ($trip->settled ? 'Settled with no bookings or expenses.' : 'No bookings yet. You can add expenses from the day before you leave.') }}</div>
            @endif
        </div>
    @endforeach

    <div class="modal fade" id="edit-modal" tabindex="-1" aria-labelledby="edit-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="edit-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="_trip" id="edit-id">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit-modal-title">Correct this request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="edit-purpose">Purpose</label>
                    <input type="text" name="purpose" id="edit-purpose" class="form-control" maxlength="255" required>
                    <div class="row g-3 mt-0">
                        <div class="col-6"><label class="form-label" for="edit-from">From</label><input type="text" name="from_location" id="edit-from" class="form-control" maxlength="255" required></div>
                        <div class="col-6"><label class="form-label" for="edit-to">To</label><input type="text" name="to_location" id="edit-to" class="form-control" maxlength="255" required></div>
                        <div class="col-6"><label class="form-label" for="edit-start">Leaving</label><input type="date" name="start_date" id="edit-start" class="form-control" min="{{ $minDate }}" max="{{ $maxDate }}" required></div>
                        <div class="col-6"><label class="form-label" for="edit-end">Returning</label><input type="date" name="end_date" id="edit-end" class="form-control" required></div>
                        <div class="col-6">
                            <label class="form-label" for="edit-mode">Mode</label>
                            <select name="mode_of_travel" id="edit-mode" class="form-select" required>
                                @foreach (TravelRequest::MODES as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label" for="edit-cost">Estimated cost</label><input type="number" step="0.01" min="0" name="estimated_cost" id="edit-cost" class="form-control"></div>
                    </div>
                    <div class="font-12 text-muted mt-2"><i class="ti ti-info-circle"></i> A request can be corrected until someone has approved it.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="expense-modal" tabindex="-1" aria-labelledby="expense-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="expense-form">
                @csrf
                <input type="hidden" name="_trip" id="expense-trip">
                <div class="modal-header">
                    <h5 class="modal-title" id="expense-modal-title">Add a trip expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="font-13 text-muted mb-3" id="expense-intro"></div>
                    <label class="form-label">What was it? <span class="text-danger">*</span></label>
                    <div class="segmented flex-wrap" id="expense-categories" role="group" aria-label="Expense category">
                        @foreach (TravelExpense::CATEGORIES as $value => $label)
                            <button type="button" class="segmented-item" data-category="{{ $value }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    <input type="hidden" name="category" id="expense-category">
                    <div class="row g-3 mt-0">
                        <div class="col-6"><label class="form-label" for="expense-amount">Amount <span class="text-danger">*</span></label><input type="number" step="0.01" min="0.01" name="amount" id="expense-amount" class="form-control" placeholder="0.00" required></div>
                        <div class="col-6"><label class="form-label" for="expense-date">Spent on <span class="text-danger">*</span></label><input type="date" name="expense_date" id="expense-date" class="form-control" required></div>
                    </div>
                    <div class="font-12 text-muted mt-2"><i class="ti ti-info-circle"></i> Only what you paid for yourself. Tickets and hotels the company booked are already listed.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-plus font-14 me-1"></i> Add expense</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var $ = function (id) { return document.getElementById(id); };
            var booked = @json($booked), typed = @json($typed), maxDays = {{ (int) Travel::MAX_DAYS }};
            var at = function (v) { return new Date(v + 'T00:00:00'); };
            var nice = function (v) { return at(v).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }); };

            var form = $('trip-form');
            if (form) {
                var start = $('trip-start'), end = $('trip-end'), note = $('trip-note');
                function mode(value) {
                    $('trip-mode').value = value;
                    $('trip-modes').querySelectorAll('.segmented-item').forEach(function (b) { b.classList.toggle('active', b.dataset.mode === value); });
                }
                // The first reason the server would refuse these dates, so it is seen before sending.
                function check() {
                    var why = '', text = 'Pick the dates to see how long you will be away.';
                    if (start.value) end.min = start.value;
                    if (start.value && end.value) {
                        var days = Math.round((at(end.value) - at(start.value)) / 86400000) + 1;
                        var clash = booked.find(function (b) { return b.from <= end.value && b.to >= start.value; });
                        if (days < 1) why = 'The return date is before the departure date.';
                        else if (days > maxDays) why = 'A single trip can last at most ' + maxDays + ' days.';
                        else if (clash) why = 'This overlaps your trip ' + clash.label + ' (' + nice(clash.from) + ' – ' + nice(clash.to) + ').';
                        else text = 'Away for ' + days + (days === 1 ? ' day' : ' days') + (days > 1 ? ', ' + (days - 1) + (days === 2 ? ' night' : ' nights') : '') + '.';
                    }
                    var from = $('trip-from').value.trim().toLowerCase(), to = $('trip-to').value.trim().toLowerCase();
                    if (!why && from && from === to) why = 'The destination is the same as the starting point.';
                    note.className = 'run-note mt-3 tone-' + (why ? 'rose' : 'indigo');
                    note.querySelector('span').textContent = why || text;
                    $('trip-submit').disabled = !!why;
                }
                $('trip-modes').addEventListener('click', function (event) { var b = event.target.closest('[data-mode]'); if (b) mode(b.dataset.mode); });
                start.addEventListener('change', function () { if (!end.value || end.value < start.value) end.value = start.value; check(); });
                [end, $('trip-from'), $('trip-to')].forEach(function (input) { input.addEventListener('input', check); });
                form.addEventListener('submit', function () { setTimeout(function () { $('trip-submit').disabled = true; }, 0); });
                mode($('trip-mode').value);
                check();
            }

            var editModal = $('edit-modal');
            function fillEdit(d) {
                $('edit-form').action = d.action;
                $('edit-id').value = d.id;
                $('edit-purpose').value = d.purpose || '';
                $('edit-from').value = d.from || '';
                $('edit-to').value = d.to || '';
                $('edit-start').value = d.start || '';
                $('edit-end').value = d.end || '';
                $('edit-mode').value = d.mode || 'flight';
                $('edit-cost').value = d.cost || '';
            }
            editModal.addEventListener('show.bs.modal', function (event) { if (event.relatedTarget) fillEdit(event.relatedTarget.dataset); });

            var expenseModal = $('expense-modal');
            function category(value) {
                $('expense-category').value = value;
                $('expense-categories').querySelectorAll('.segmented-item').forEach(function (b) { b.classList.toggle('active', b.dataset.category === value); });
            }
            function fillExpense(d) {
                $('expense-form').action = d.action;
                $('expense-trip').value = d.id;
                $('expense-intro').textContent = 'For your trip ' + d.label + '. The date must be between ' + d.range + '.';
                $('expense-date').min = d.min; $('expense-date').max = d.max;
                $('expense-date').value = ''; $('expense-amount').value = '';
                category('');
            }
            expenseModal.addEventListener('show.bs.modal', function (event) { if (event.relatedTarget) fillExpense(event.relatedTarget.dataset); });
            $('expense-categories').addEventListener('click', function (event) { var b = event.target.closest('[data-category]'); if (b) category(b.dataset.category); });
            $('expense-form').addEventListener('submit', function (event) { if (!$('expense-category').value) { event.preventDefault(); alert('Choose what kind of expense it was.'); } });

            // A refused save: reopen its popup with what was typed.
            var editing = {{ $editing }}, expensing = {{ $expensing }};
            var button = editing ? document.querySelector('[data-bs-target="#edit-modal"][data-id="' + editing + '"]') : null;
            if (button) {
                fillEdit(Object.assign({}, button.dataset, { purpose: typed.purpose, from: typed.from_location, to: typed.to_location, start: typed.start_date, end: typed.end_date, mode: typed.mode_of_travel, cost: typed.estimated_cost }));
                bootstrap.Modal.getOrCreateInstance(editModal).show();
            }
            button = expensing ? document.querySelector('[data-bs-target="#expense-modal"][data-id="' + expensing + '"]') : null;
            if (button) {
                fillExpense(button.dataset);
                category(typed.category); $('expense-amount').value = typed.amount; $('expense-date').value = typed.expense_date;
                bootstrap.Modal.getOrCreateInstance(expenseModal).show();
            }
        })();
    </script>
@endpush
