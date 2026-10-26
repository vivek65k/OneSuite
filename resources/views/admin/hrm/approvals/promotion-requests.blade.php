@extends('layouts.admin')

@section('title', 'Promotion Approval')

@php
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.promotion-requests.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All requests', 'tone' => 'indigo', 'count' => $counts['all']],
        'pending' => ['label' => 'Waiting for decision', 'tone' => 'amber', 'count' => $counts['pending']],
        'approved' => ['label' => 'Approved', 'tone' => 'success', 'count' => $counts['approved']],
        'rejected' => ['label' => 'Rejected', 'tone' => 'rose', 'count' => $counts['rejected']],
    ];
    $filterValues = [
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $oldEmployee = is_scalar(old('employee_id')) ? (string) old('employee_id') : '';
    $tenure = function (?int $months) {
        if ($months === null) {
            return null;
        }
        [$y, $m] = [intdiv($months, 12), $months % 12];

        return trim(($y ? $y.' '.Str::plural('year', $y) : '').' '.($m || ! $y ? $m.' '.Str::plural('month', $m) : ''));
    };
    $today = now()->toDateString();
@endphp

@section('content')
    <x-page-title title="Promotion Approval" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for decision" :value="$kpis['pending']" icon="ti-inbox" :tone="$kpis['pending'] ? 'amber' : 'success'" :hint="$kpis['pending'] ? 'Promotion requests to decide' : 'Nothing to decide'" :href="$pageUrl(['status' => 'pending'])" />
        <x-kpi-card label="Oldest waiting" :value="$kpis['oldest_days'] !== null ? ($kpis['oldest_days'] === 0 ? 'Today' : $kpis['oldest_days'].' '.Str::plural('day', $kpis['oldest_days'])) : '—'" icon="ti-clock"
                    :tone="($kpis['oldest_days'] ?? 0) > 7 ? 'rose' : 'slate'" :hint="$kpis['oldest_days'] === null ? 'No open requests' : (($kpis['oldest_days'] ?? 0) > 7 ? 'Waiting more than a week' : 'Since it was submitted')" />
        <x-kpi-card label="Approved in {{ now()->year }}" :value="$kpis['approved']" icon="ti-arrow-up-circle" tone="success" :hint="$kpis['approved'] ? Str::plural('Promotion', $kpis['approved']).' applied' : 'None approved yet'" :href="$pageUrl(['status' => 'approved'])" />
        <x-kpi-card label="Approval rate" :value="$kpis['rate'] !== null ? $kpis['rate'].'%' : '—'" icon="ti-percentage" tone="indigo" :hint="$kpis['rate'] !== null ? $kpis['rejected'].' rejected this year' : 'No decisions yet this year'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.promotion-requests.store') }}" class="card form-section tone-indigo mb-0 h-100" id="req-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-arrow-up-circle"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Request a promotion</h5>
                            <p class="form-section-sub">Put someone forward for a new designation. Nothing changes until the request is approved.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="req-employee">Employee</label>
                                <select name="employee_id" id="req-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($payable as $person)
                                        <option value="{{ $person->id }}" @selected($oldEmployee === (string) $person->id)>{{ $person->name }}{{ filled($person->designation) ? ' — '.$person->designation : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="req-to">New designation</label>
                                <input type="text" name="to_designation" id="req-to" value="{{ is_scalar(old('to_designation')) ? old('to_designation') : '' }}" maxlength="255" class="form-control @error('to_designation') is-invalid @enderror" list="req-designations" placeholder="e.g. Senior Sales Executive" required>
                                <datalist id="req-designations">
                                    @foreach ($designations as $designation)
                                        <option value="{{ $designation }}">
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="req-date">Effective on</label>
                                <input type="date" name="promotion_date" id="req-date" value="{{ is_scalar(old('promotion_date')) ? old('promotion_date') : $today }}" max="{{ $maxDate }}" class="form-control @error('promotion_date') is-invalid @enderror" required>
                                <div class="quick-chips">
                                    <button type="button" class="quick-chip" data-date="{{ $today }}">Today</button>
                                    <button type="button" class="quick-chip" data-date="{{ now()->addMonthNoOverflow()->startOfMonth()->toDateString() }}">1st of next month</button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="req-notes">Why <span class="text-danger">*</span></label>
                                <input type="text" name="notes" id="req-notes" value="{{ is_scalar(old('notes')) ? old('notes') : '' }}" minlength="5" maxlength="1000" class="form-control @error('notes') is-invalid @enderror" placeholder="The case for promoting them" required>
                                <div class="quick-chips">
                                    @foreach (['Annual appraisal', 'Took on a bigger role', 'Filled a vacancy', 'Consistent performance'] as $reason)
                                        <button type="button" class="quick-chip" data-reason="{{ $reason }}">{{ $reason }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-send font-14 me-1"></i> Submit request</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">About this person</span>
                    </div>
                    <div class="card-body">
                        <div class="empty-state py-4" id="req-preview-empty">
                            <div class="empty-state-icon"><i class="ti ti-user"></i></div>
                            <div class="fw-semibold">Pick an employee</div>
                            <div class="font-13 text-muted">You'll see their current role, how long they have held it and their last rating.</div>
                        </div>
                        <div id="req-preview" hidden>
                            <div class="rev-compare">
                                <div>
                                    <span class="rev-compare-label">Now</span>
                                    <span class="rev-compare-value promo-title" id="req-now">—</span>
                                    <span class="rev-compare-meta" id="req-now-meta"></span>
                                </div>
                                <i class="ti ti-arrow-right"></i>
                                <div>
                                    <span class="rev-compare-label">Asking for</span>
                                    <span class="rev-compare-value promo-title" id="req-after">—</span>
                                    <span class="rev-compare-meta" id="req-after-meta"></span>
                                </div>
                            </div>
                            <div class="run-lines">
                                <div><span>Time in current role</span><strong id="req-tenure">—</strong></div>
                                <div><span>Last appraisal rating</span><strong id="req-rating">—</strong></div>
                            </div>
                            <div class="run-note tone-amber mt-3" id="req-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.promotion-requests.index') }}">
            @if ($state['status'] !== '')
                <input type="hidden" name="status" value="{{ $state['status'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Request status">
                    @foreach ($tabs as $value => $tab)
                        <a href="{{ $pageUrl(['status' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['status'] === $value ? 'active' : '' }}">
                            {{ $tab['label'] }}
                            <span class="list-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or designation" aria-label="Search requests">
                    </div>
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
                        <th>What to go on</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $promotionRequest)
                        @php
                            $employee = $promotionRequest->employee;
                            $info = $context[$promotionRequest->employee_id] ?? null;
                            $isPending = $promotionRequest->status === 'pending';
                            $future = $promotionRequest->promotion_date->gt(today());
                            // Their title has changed since the request was filed.
                            $stale = $isPending && $info && mb_strtolower($info['designation']) !== mb_strtolower(trim((string) $promotionRequest->from_designation));
                            $own = $myEmployeeId && $promotionRequest->employee_id === $myEmployeeId;
                            $status = ['pending' => ['Waiting for decision', 'amber', 'ti-clock'], 'approved' => ['Approved', 'success', 'ti-circle-check'], 'rejected' => ['Rejected', 'rose', 'ti-circle-x']][$promotionRequest->status] ?? [$promotionRequest->status, 'slate', 'ti-circle'];
                        @endphp
                        <tr class="{{ $isPending ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$employee?->employee_code, $employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="promo-move">
                                    <span class="tag tone-slate">{{ $promotionRequest->from_designation ?: 'Not recorded' }}</span>
                                    <i class="ti ti-arrow-right text-muted"></i>
                                    <span class="tag tone-{{ $promotionRequest->status === 'rejected' ? 'slate' : 'success' }}"><i class="ti ti-arrow-up-circle"></i>{{ $promotionRequest->to_designation }}</span>
                                </div>
                                @if ($stale)
                                    <div class="payslip-parts">Now {{ $info['designation'] ?: 'no designation' }}, changed since this was filed</div>
                                @endif
                                @if ($promotionRequest->notes)
                                    <div class="payslip-parts text-wrap" title="{{ $promotionRequest->notes }}">{{ Str::limit($promotionRequest->notes, 70) }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <div class="fw-semibold">{{ $promotionRequest->promotion_date->format('M j, Y') }}</div>
                                <div class="payslip-parts">{{ $isPending && $future ? 'Not until then' : 'Asked '.$promotionRequest->created_at?->format('M j') }}</div>
                            </td>
                            <td class="font-13 text-nowrap">
                                @if ($isPending && $info)
                                    <div>{{ $info['months'] !== null ? $tenure($info['months']).' in role' : 'Tenure unknown' }}</div>
                                    <div class="text-muted">{{ $info['rating'] ? 'Last rated '.$info['rating'].' of 5' : 'No completed appraisal' }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $status[1] }}"><i class="ti {{ $status[2] }}"></i>{{ $status[0] }}</span>
                                @unless ($isPending)
                                    <div class="payslip-parts">{{ collect([$promotionRequest->reviewedBy ? 'by '.$promotionRequest->reviewedBy->name : null, $promotionRequest->reviewed_at?->format('M j, Y')])->filter()->implode(' · ') }}</div>
                                    @if ($promotionRequest->remarks)
                                        <div class="payslip-parts text-wrap" title="{{ $promotionRequest->remarks }}">{{ Str::limit($promotionRequest->remarks, 70) }}</div>
                                    @endif
                                @endunless
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($isPending)
                                    @can('update', $promotionRequest)
                                        @if ($own)
                                            <span class="font-12 text-muted" title="Someone else has to decide your own request">Your own request</span>
                                        @else
                                            <div class="list-row-actions">
                                                <button type="button" class="btn btn-sm review-btn is-approve" data-bs-toggle="modal" data-bs-target="#approve-modal"
                                                        data-action="{{ route('admin.promotion-requests.approve', $promotionRequest) }}" data-name="{{ $employee?->name ?? 'Removed employee' }}"
                                                        data-from="{{ $info['designation'] ?? $promotionRequest->from_designation }}" data-to="{{ $promotionRequest->to_designation }}"
                                                        data-date="{{ $promotionRequest->promotion_date->toDateString() }}" data-last="{{ $info['lastDate'] ?? '' }}" data-joined="{{ $info['joined'] ?? '' }}">
                                                    <i class="ti ti-check"></i> Approve
                                                </button>
                                                <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="modal" data-bs-target="#reject-modal"
                                                        data-action="{{ route('admin.promotion-requests.reject', $promotionRequest) }}" data-name="{{ $employee?->name ?? 'Removed employee' }}" data-to="{{ $promotionRequest->to_designation }}">
                                                    <i class="ti ti-x"></i> Reject
                                                </button>
                                            </div>
                                        @endif
                                    @endcan
                                    @can('delete', $promotionRequest)
                                        <form action="{{ route('admin.promotion-requests.destroy', $promotionRequest) }}" method="POST" class="d-inline" onsubmit="return confirm(@js('Withdraw the promotion request for '.($employee?->name ?? 'this employee').'?'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-icon-danger" title="Withdraw this request"><i class="ti ti-trash font-16"></i></button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-arrow-up-circle font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['status'] !== '')
                                    <div class="fw-semibold text-body mb-1">No requests match</div>
                                    <span class="font-13"><a href="{{ route('admin.promotion-requests.index') }}">Show all requests</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No promotion requests yet</div>
                                    <span class="font-13">{{ $canCreate ? 'File one with the form above.' : 'Requests will appear here when they are filed.' }}</span>
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

    <div class="modal fade" id="approve-modal" tabindex="-1" aria-labelledby="approve-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="approve-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="approve-title">Approve promotion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="font-13 text-muted mb-3">Approving changes the designation straight away. <strong id="approve-who" class="text-body"></strong></p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="approve-to">New designation</label>
                            <input type="text" name="to_designation" id="approve-to" maxlength="255" class="form-control" list="req-designations" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="approve-date">Effective on</label>
                            <input type="date" name="promotion_date" id="approve-date" max="{{ $today }}" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="approve-remarks">Note <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="remarks" id="approve-remarks" maxlength="500" class="form-control">
                        </div>
                    </div>
                    <div class="run-note tone-indigo mt-3" id="approve-note" hidden><i class="ti ti-info-circle"></i><span></span></div>
                    <div class="run-note tone-amber mt-2" id="approve-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Approve and promote</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="reject-modal" tabindex="-1" aria-labelledby="reject-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="reject-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="reject-title">Reject promotion request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="font-13 text-muted mb-3"><strong id="reject-who" class="text-body"></strong></p>
                    <label class="form-label" for="reject-remarks">Reason <span class="text-danger">*</span> <span class="text-muted fw-normal">(shown on the request)</span></label>
                    <input type="text" name="remarks" id="reject-remarks" minlength="3" maxlength="500" class="form-control" placeholder="Why it is being turned down" required>
                    <div class="quick-chips">
                        @foreach (['Too soon in the current role', 'No vacancy at that level', 'Wait for the appraisal', 'Budget not available'] as $reason)
                            <button type="button" class="quick-chip" data-reject-reason="{{ $reason }}">{{ $reason }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="ti ti-x font-14 me-1"></i> Reject request</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var today = @json($today);

            function tenure(months) {
                if (months === null) return 'Unknown';
                var y = Math.floor(months / 12), m = months % 12;
                return (y ? y + (y === 1 ? ' year' : ' years') : '') + (y && m ? ' ' : '') + (m || !y ? m + (m === 1 ? ' month' : ' months') : '');
            }

            // Approve dialog
            var approve = el('approve-modal');
            if (approve) {
                var asked = {}, to = el('approve-to'), date = el('approve-date');
                var check = function () {
                    var warn = '', note = '';
                    if (asked.date > today) note = 'They asked for ' + asked.date + ', which has not arrived. The date is set to today; approve later instead if it should wait.';
                    if (to.value.trim().toLowerCase() === (asked.from || '').toLowerCase()) warn = asked.name + ' is already "' + to.value.trim() + '".';
                    else if (date.value && asked.last && date.value <= asked.last) warn = 'They were last promoted on ' + asked.last + '. Pick a later date.';
                    else if (date.value && asked.joined && date.value < asked.joined) warn = 'That is before they joined (' + asked.joined + ').';
                    else if (date.value > today) warn = 'A future date can\'t be approved yet.';
                    el('approve-note').hidden = !note; el('approve-note').querySelector('span').textContent = note;
                    el('approve-warn').hidden = !warn; el('approve-warn').querySelector('span').textContent = warn;
                };
                approve.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    asked = { name: b.dataset.name, from: b.dataset.from, date: b.dataset.date, last: b.dataset.last, joined: b.dataset.joined };
                    el('approve-form').action = b.dataset.action;
                    el('approve-who').textContent = b.dataset.name + (b.dataset.from ? ' · now ' + b.dataset.from : '');
                    to.value = b.dataset.to;
                    date.value = b.dataset.date > today ? today : b.dataset.date;
                    el('approve-remarks').value = '';
                    check();
                });
                [to, date].forEach(function (input) { input.addEventListener('input', check); input.addEventListener('change', check); });
            }

            // Reject dialog
            var reject = el('reject-modal');
            if (reject) {
                reject.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('reject-form').action = b.dataset.action;
                    el('reject-who').textContent = b.dataset.name + ' · to ' + b.dataset.to;
                    el('reject-remarks').value = '';
                });
                reject.querySelectorAll('[data-reject-reason]').forEach(function (chip) {
                    chip.addEventListener('click', function () { el('reject-remarks').value = chip.dataset.rejectReason; });
                });
            }

            // Request form
            var form = el('req-form');
            if (!form) return;
            var people = @json($context);
            var pending = @json($pendingFor);
            var employee = el('req-employee'), title = el('req-to'), when = el('req-date');

            function render() {
                var p = people[employee.value];
                el('req-preview').hidden = !p;
                el('req-preview-empty').hidden = !!p;
                if (!p) return;
                var next = title.value.trim(), warn = '';
                el('req-now').textContent = p.designation || 'No designation set';
                el('req-now-meta').textContent = p.since ? p.sinceKind + ' ' + p.since : '';
                el('req-after').textContent = next || '—';
                el('req-after-meta').textContent = when.value ? (when.value > today ? 'from ' + when.value + ', if approved by then' : 'from ' + when.value) : '';
                el('req-tenure').textContent = tenure(p.months);
                el('req-rating').textContent = p.rating ? p.rating + ' of 5' : 'No completed appraisal';

                if (pending[employee.value] !== undefined) warn = p.name + ' already has a request waiting. A second one will be refused.';
                else if (next && next.toLowerCase() === (p.designation || '').toLowerCase()) warn = p.name + ' is already "' + next + '".';
                else if (when.value && p.lastDate && when.value <= p.lastDate) warn = 'They were last promoted on ' + p.lastDate + '. The new date must be after that.';
                else if (when.value && p.joined && when.value < p.joined) warn = 'That is before ' + p.name + ' joined (' + p.joined + ').';
                else if (p.months !== null && p.months < 6) warn = p.name + ' has been in this role for only ' + tenure(p.months) + '.';
                el('req-warn').hidden = !warn;
                el('req-warn').querySelector('span').textContent = warn;
            }

            employee.addEventListener('change', function () { title.value = ''; render(); });
            [title, when].forEach(function (input) { input.addEventListener('input', render); input.addEventListener('change', render); });
            form.querySelectorAll('[data-date]').forEach(function (chip) {
                chip.addEventListener('click', function () { when.value = chip.dataset.date; render(); });
            });
            form.querySelectorAll('[data-reason]').forEach(function (chip) {
                chip.addEventListener('click', function () { el('req-notes').value = chip.dataset.reason; });
            });
            render();
        })();
    </script>
@endpush
