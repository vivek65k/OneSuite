@extends('layouts.admin')

@section('title', 'Loan Approval')

@php
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pageUrl = fn (array $overrides = []) => route('admin.loan-requests.index', array_filter(
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
    $oldEmployee = (string) old('employee_id', '');
    $canSeeLoans = auth()->user()->can('viewAny', \App\Models\Loan::class);
@endphp

@section('content')
    <x-page-title title="Loan Approval" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for decision" :value="$kpis['pending']" icon="ti-inbox" :tone="$kpis['pending'] ? 'amber' : 'success'"
                    :hint="$kpis['pending'] ? $money($kpis['pending_amount']).' asked for in total' : 'Nothing to decide'" :href="$pageUrl(['status' => 'pending'])" />
        <x-kpi-card label="Oldest waiting" :value="$kpis['oldest_days'] !== null ? ($kpis['oldest_days'] === 0 ? 'Today' : $kpis['oldest_days'].' '.Str::plural('day', $kpis['oldest_days'])) : '—'" icon="ti-clock"
                    :tone="($kpis['oldest_days'] ?? 0) > 7 ? 'rose' : 'slate'" :hint="$kpis['oldest_days'] === null ? 'No open requests' : (($kpis['oldest_days'] ?? 0) > 7 ? 'Waiting more than a week' : 'Since it was submitted')" />
        <x-kpi-card label="Approved in {{ now()->year }}" :value="$kpis['approved'] ? $money($kpis['approved_amount']) : '—'" icon="ti-circle-check" tone="success"
                    :hint="$kpis['approved'].' '.Str::plural('loan', $kpis['approved']).' given out'" :href="$pageUrl(['status' => 'approved'])" />
        <x-kpi-card label="Approval rate" :value="$kpis['rate'] !== null ? $kpis['rate'].'%' : '—'" icon="ti-percentage" tone="indigo"
                    :hint="$kpis['rate'] !== null ? $kpis['rejected'].' rejected this year' : 'No decisions yet this year'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.loan-requests.store') }}" class="card form-section tone-indigo mb-0 h-100" id="request-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-cash-banknote"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Request a loan</h5>
                            <p class="form-section-sub">File a request on an employee's behalf. Nothing is paid out until it is approved.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="req-employee">Employee</label>
                                <select name="employee_id" id="req-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formData as $id => $person)
                                        <option value="{{ $id }}" @selected($oldEmployee === (string) $id)>{{ $person['name'] }}{{ $person['meta'] ? ' — '.$person['meta'] : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="req-type">Loan type</label>
                                <input type="text" name="loan_type" id="req-type" value="{{ old('loan_type') }}" maxlength="120" class="form-control" placeholder="e.g. Personal loan" required>
                                <div class="quick-chips">
                                    @foreach (['Personal loan', 'Salary advance', 'Medical', 'Education', 'Festival advance'] as $type)
                                        <button type="button" class="quick-chip" data-type="{{ $type }}">{{ $type }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="req-principal">Loan amount</label>
                                <input type="number" step="0.01" min="0.01" name="principal_amount" id="req-principal" value="{{ old('principal_amount') }}" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="req-emi">Monthly instalment</label>
                                <input type="number" step="0.01" min="0.01" name="monthly_installment" id="req-emi" value="{{ old('monthly_installment') }}" class="form-control @error('monthly_installment') is-invalid @enderror" placeholder="0.00" required>
                                <div class="quick-chips">
                                    @foreach ([3, 6, 12, 24] as $months)
                                        <button type="button" class="quick-chip" data-months="{{ $months }}">{{ $months }} months</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="req-reason">Reason <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="reason" id="req-reason" value="{{ old('reason') }}" maxlength="1000" class="form-control" placeholder="What the loan is for">
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
                        <span class="side-list-icon"><i class="ti ti-scale"></i></span>
                        <span class="fw-semibold">Can they afford it?</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">Instalments</span>
                                <span class="rev-compare-value" id="req-count">—</span>
                                <span class="rev-compare-meta" id="req-each">Enter the amount and instalment</span>
                            </div>
                            <i class="ti ti-arrow-right"></i>
                            <div>
                                <span class="rev-compare-label">Share of salary</span>
                                <span class="rev-compare-value" id="req-share">—</span>
                                <span class="rev-compare-meta" id="req-share-meta">Pick an employee</span>
                            </div>
                        </div>
                        <div class="run-lines" id="req-person" hidden>
                            <div><span>Monthly salary</span><strong id="req-salary">—</strong></div>
                            <div><span>Other loan instalments</span><strong id="req-existing">—</strong></div>
                        </div>
                        <div class="run-note tone-amber mt-3" id="req-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.loan-requests.index') }}">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or loan type" aria-label="Search requests">
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
                        <th>Loan</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Instalment</th>
                        <th>Affordability</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $loanRequest)
                        @php
                            $principal = (float) $loanRequest->principal_amount;
                            $emi = (float) $loanRequest->monthly_installment;
                            $isPending = $loanRequest->status === 'pending';
                            $months = $emi > 0 ? (int) ceil($principal / $emi) : 0;
                            $salary = $salaries->get($loanRequest->employee_id);
                            $other = (float) $instalments->get($loanRequest->employee_id, 0);
                            $share = $salary ? (int) round(($other + $emi) / $salary * 100) : null;
                            $shareTone = $share === null ? 'slate' : ($share > 100 ? 'rose' : ($share > 50 ? 'amber' : 'success'));
                            $own = $myEmployeeId && $loanRequest->employee_id === $myEmployeeId && ! $canSelfApprove;
                            $status = ['pending' => ['Waiting for decision', 'amber', 'ti-clock'], 'approved' => ['Approved', 'success', 'ti-circle-check'], 'rejected' => ['Rejected', 'rose', 'ti-circle-x']][$loanRequest->status] ?? [$loanRequest->status, 'slate', 'ti-circle'];
                        @endphp
                        <tr class="{{ $isPending ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$loanRequest->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $loanRequest->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$loanRequest->employee?->employee_code, $loanRequest->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $loanRequest->loan_type }}</div>
                                <div class="payslip-parts">Asked {{ $loanRequest->created_at?->format('M j, Y') }}{{ $isPending && $loanRequest->created_at ? ' · '.$loanRequest->created_at->diffForHumans() : '' }}</div>
                                @if ($loanRequest->reason)
                                    <div class="payslip-parts text-wrap" title="{{ $loanRequest->reason }}">{{ Str::limit($loanRequest->reason, 70) }}</div>
                                @endif
                            </td>
                            <td class="text-end"><span class="salary-figure">{{ $money($principal) }}</span></td>
                            <td class="text-end text-nowrap">
                                {{ $money($emi) }}<span class="text-muted font-12"> /mo</span>
                                @if ($months)
                                    <div class="payslip-parts">over {{ $months }} {{ Str::plural('month', $months) }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($isPending)
                                    @if ($share === null)
                                        <span class="tag tone-rose" title="Payroll skips people without a salary structure, so nothing would be recovered">No salary structure</span>
                                    @else
                                        <span class="tag tone-{{ $shareTone }}">{{ $share }}% of salary</span>
                                        <div class="payslip-parts">{{ $other > 0 ? 'incl. '.$money($other).' on other loans' : 'No other loans' }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $status[1] }}"><i class="ti {{ $status[2] }}"></i>{{ $status[0] }}</span>
                                @unless ($isPending)
                                    <div class="payslip-parts">{{ collect([$loanRequest->reviewedBy ? 'by '.$loanRequest->reviewedBy->name : null, $loanRequest->reviewed_at?->format('M j, Y')])->filter()->implode(' · ') }}</div>
                                    @if ($loanRequest->remarks)
                                        <div class="payslip-parts text-wrap" title="{{ $loanRequest->remarks }}">{{ Str::limit($loanRequest->remarks, 70) }}</div>
                                    @endif
                                @endunless
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($isPending)
                                    @can('update', $loanRequest)
                                        @if ($own)
                                            <span class="font-12 text-muted" title="Someone else has to decide your own request">Your own request</span>
                                        @else
                                            <div class="list-row-actions">
                                                <button type="button" class="btn btn-sm review-btn is-approve" data-bs-toggle="modal" data-bs-target="#approve-modal"
                                                        data-action="{{ route('admin.loan-requests.approve', $loanRequest) }}" data-name="{{ $loanRequest->employee?->name ?? 'Removed employee' }}" data-type="{{ $loanRequest->loan_type }}"
                                                        data-principal="{{ number_format($principal, 2, '.', '') }}" data-emi="{{ number_format($emi, 2, '.', '') }}" data-salary="{{ $salary }}" data-other="{{ $other }}">
                                                    <i class="ti ti-check"></i> Approve
                                                </button>
                                                <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="modal" data-bs-target="#reject-modal"
                                                        data-action="{{ route('admin.loan-requests.reject', $loanRequest) }}" data-name="{{ $loanRequest->employee?->name ?? 'Removed employee' }}" data-type="{{ $loanRequest->loan_type }}" data-principal="{{ $money($principal) }}">
                                                    <i class="ti ti-x"></i> Reject
                                                </button>
                                            </div>
                                        @endif
                                    @endcan
                                @elseif ($loanRequest->status === 'approved' && $canSeeLoans)
                                    <a href="{{ route('admin.loans.index', ['employee_id' => $loanRequest->employee_id]) }}" class="btn-icon btn-icon-accent" title="See this person's loans"><i class="ti ti-arrow-right font-16"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-cash-banknote font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['status'] !== '')
                                    <div class="fw-semibold text-body mb-1">No requests match</div>
                                    <span class="font-13"><a href="{{ route('admin.loan-requests.index') }}">Show all requests</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No loan requests yet</div>
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
                    <h5 class="modal-title" id="approve-title">Approve loan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="font-13 text-muted mb-3">Approving opens the loan straight away. <strong id="approve-who" class="text-body"></strong></p>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" for="approve-principal">Amount to give</label>
                            <input type="number" step="0.01" min="0.01" name="principal_amount" id="approve-principal" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="approve-emi">Monthly instalment</label>
                            <input type="number" step="0.01" min="0.01" name="monthly_installment" id="approve-emi" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="approve-date">Paid out on</label>
                            <input type="date" name="disbursed_date" id="approve-date" value="{{ now()->toDateString() }}" min="{{ now()->subMonth()->toDateString() }}" max="{{ now()->addMonths(3)->toDateString() }}" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="approve-remarks">Note <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="remarks" id="approve-remarks" maxlength="500" class="form-control">
                        </div>
                    </div>
                    <div class="run-lines">
                        <div><span>Repaid over</span><strong id="approve-months">—</strong></div>
                        <div><span>Monthly salary</span><strong id="approve-salary">—</strong></div>
                        <div><span>All loan instalments</span><strong id="approve-share">—</strong></div>
                    </div>
                    <div class="run-note tone-amber mt-3" id="approve-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Approve and open loan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="reject-modal" tabindex="-1" aria-labelledby="reject-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="reject-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="reject-title">Reject loan request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="font-13 text-muted mb-3"><strong id="reject-who" class="text-body"></strong></p>
                    <label class="form-label" for="reject-remarks">Reason <span class="text-muted fw-normal">(optional, shown on the request)</span></label>
                    <input type="text" name="remarks" id="reject-remarks" maxlength="500" class="form-control" placeholder="Why it is being turned down">
                    <div class="quick-chips">
                        @foreach (['Existing loan still open', 'Instalment too high for salary', 'Still on probation', 'Budget not available'] as $reason)
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
            var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); };
            var el = function (id) { return document.getElementById(id); };
            var plural = function (n, word) { return n + ' ' + word + (n === 1 ? '' : 's'); };

            // Approve dialog
            var approve = el('approve-modal');
            if (approve) {
                var asked = 0, salary = null, other = 0, who = '';
                var principal = el('approve-principal'), emi = el('approve-emi');
                var check = function () {
                    var p = parseFloat(principal.value), e = parseFloat(emi.value), ok = p > 0 && e > 0, warn = '';
                    var all = other + (ok ? e : 0);
                    el('approve-months').textContent = ok ? plural(Math.ceil(p / e - 1e-9), 'month') : '—';
                    el('approve-salary').textContent = salary !== null ? fmt(salary) : 'No salary structure';
                    el('approve-share').textContent = ok ? fmt(all) + (salary ? ' · ' + Math.round(all / salary * 100) + '% of salary' : '') : '—';
                    if (p > asked + 0.005) warn = 'That is more than the ' + fmt(asked) + ' asked for.';
                    else if (ok && e > p) warn = 'The instalment is more than the loan itself.';
                    else if (salary === null) warn = who + ' has no salary structure. Payroll skips them, so nothing would be recovered.';
                    else if (ok && all > salary) warn = 'Instalments of ' + fmt(all) + ' are more than the monthly salary. This will be refused.';
                    else if (ok && all > salary / 2) warn = 'Instalments would take more than half of the salary.';
                    el('approve-warn').hidden = !warn;
                    el('approve-warn').querySelector('span').textContent = warn;
                };
                approve.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    asked = parseFloat(b.dataset.principal); other = parseFloat(b.dataset.other) || 0;
                    salary = b.dataset.salary === '' ? null : parseFloat(b.dataset.salary); who = b.dataset.name;
                    el('approve-form').action = b.dataset.action;
                    el('approve-who').textContent = b.dataset.name + ' · ' + b.dataset.type;
                    principal.value = b.dataset.principal; principal.max = b.dataset.principal; emi.value = b.dataset.emi; el('approve-remarks').value = '';
                    check();
                });
                [principal, emi].forEach(function (input) { input.addEventListener('input', check); });
            }

            // Reject dialog
            var reject = el('reject-modal');
            if (reject) {
                reject.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('reject-form').action = b.dataset.action;
                    el('reject-who').textContent = b.dataset.name + ' · ' + b.dataset.type + ' · ' + b.dataset.principal;
                    el('reject-remarks').value = '';
                });
                reject.querySelectorAll('[data-reject-reason]').forEach(function (chip) {
                    chip.addEventListener('click', function () { el('reject-remarks').value = chip.dataset.rejectReason; });
                });
            }

            // Request form
            var form = el('request-form');
            if (!form) return;
            var people = @json($formData);
            var employee = el('req-employee'), amount = el('req-principal'), each = el('req-emi');

            function render() {
                var p = people[employee.value];
                var a = parseFloat(amount.value), e = parseFloat(each.value), ok = a > 0 && e > 0, warn = '';
                el('req-count').textContent = ok ? Math.ceil(a / e - 1e-9) : '—';
                el('req-each').textContent = ok ? 'of ' + fmt(e) + ' a month' : 'Enter the amount and instalment';
                el('req-person').hidden = !p;
                el('req-share').textContent = '—';
                el('req-share-meta').textContent = p ? 'of monthly salary' : 'Pick an employee';
                if (p) {
                    var all = p.instalments + (ok ? e : 0);
                    el('req-salary').textContent = p.salary !== null ? fmt(p.salary) : 'No salary structure';
                    el('req-existing').textContent = p.instalments > 0 ? fmt(p.instalments) + ' a month' : 'None';
                    if (p.salary && ok) el('req-share').textContent = Math.round(all / p.salary * 100) + '%';
                    if (p.pending) warn = p.name + ' already has a request waiting. A second one will be refused.';
                    else if (p.salary === null) warn = p.name + ' has no salary structure. Payroll skips them, so nothing would be recovered.';
                    else if (ok && all > p.salary) warn = 'Instalments of ' + fmt(all) + ' are more than ' + p.name + ' earns in a month (' + fmt(p.salary) + '). This will be refused.';
                    else if (ok && all > p.salary / 2) warn = 'Instalments would take more than half of ' + p.name + '\'s salary.';
                }
                if (!warn && ok && e > a) warn = 'The instalment is more than the loan itself.';
                el('req-warn').hidden = !warn;
                el('req-warn').querySelector('span').textContent = warn;
            }

            [employee, amount, each].forEach(function (input) { input.addEventListener('input', render); input.addEventListener('change', render); });
            form.querySelectorAll('[data-months]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var a = parseFloat(amount.value);
                    if (!(a > 0)) { amount.focus(); return; }
                    each.value = (Math.ceil(a / parseInt(chip.dataset.months, 10) * 100) / 100).toFixed(2);
                    render();
                });
            });
            form.querySelectorAll('[data-type]').forEach(function (chip) {
                chip.addEventListener('click', function () { el('req-type').value = chip.dataset.type; });
            });
            render();
        })();
    </script>
@endpush
