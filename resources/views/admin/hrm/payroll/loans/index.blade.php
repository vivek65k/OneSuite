@extends('layouts.admin')

@section('title', 'Loans')

@php
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pageUrl = fn (array $overrides = []) => route('admin.loans.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All loans', 'tone' => 'indigo', 'count' => $counts['all']],
        'active' => ['label' => 'Being repaid', 'tone' => 'amber', 'count' => $counts['active']],
        'closed' => ['label' => 'Fully repaid', 'tone' => 'success', 'count' => $counts['closed']],
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
    $recoveredPct = $kpis['lent'] > 0 ? round($kpis['recovered'] / $kpis['lent'] * 100) : 0;
@endphp

@section('content')
    <x-page-title title="Loans" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Being repaid" :value="$kpis['active']" icon="ti-credit-card" :tone="$kpis['active'] ? 'indigo' : 'slate'"
                    :hint="$kpis['active'] ? $kpis['borrowers'].' '.Str::plural('employee', $kpis['borrowers']).' with a loan' : 'No open loans'" :href="$pageUrl(['tab' => 'active'])" />
        <x-kpi-card label="Still owed" :value="$kpis['active'] ? $money($kpis['outstanding']) : '—'" icon="ti-cash" tone="amber"
                    :hint="$kpis['active'] ? 'Of '.$money($kpis['active_principal']).' lent on open loans' : null" />
        <x-kpi-card label="Next payroll recovers" :value="$kpis['monthly_count'] ? $money($kpis['monthly']) : '—'" icon="ti-calendar-event" tone="teal"
                    :hint="$kpis['monthly_count'] ? 'From '.$kpis['monthly_count'].' '.Str::plural('loan', $kpis['monthly_count']).' in '.$nextPayroll->format('F Y') : 'Nothing due'" />
        <x-kpi-card label="Recovered so far" :value="$kpis['lent'] > 0 ? $recoveredPct.'%' : '—'" icon="ti-trending-up" tone="success"
                    :hint="$kpis['lent'] > 0 ? $money($kpis['recovered']).' of '.$money($kpis['lent']).' · '.$kpis['closed'].' closed' : 'No loans issued yet'" :href="$pageUrl(['tab' => 'closed'])" />
    </div>

    @if ($kpis['requests'])
        <div class="alert alert-info d-flex align-items-center gap-2">
            <i class="ti ti-inbox font-18"></i>
            <div><strong>{{ $kpis['requests'] }} loan {{ Str::plural('request', $kpis['requests']) }}</strong> from employees {{ $kpis['requests'] === 1 ? 'is' : 'are' }} waiting for a decision. <a href="{{ route('admin.loan-requests.index') }}">Review requests</a></div>
        </div>
    @endif

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.loans.store') }}" class="card form-section tone-indigo mb-0 h-100" id="loan-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-credit-card"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Issue a loan</h5>
                            <p class="form-section-sub">The instalment is taken from salary every payroll until the loan is repaid.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="loan-employee">Employee</label>
                                <select name="employee_id" id="loan-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formData as $id => $person)
                                        <option value="{{ $id }}" @selected($oldEmployee === (string) $id)>{{ $person['name'] }}{{ $person['meta'] ? ' — '.$person['meta'] : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="loan-type">Loan type</label>
                                <input type="text" name="loan_type" id="loan-type" value="{{ old('loan_type') }}" maxlength="120" class="form-control @error('loan_type') is-invalid @enderror" placeholder="e.g. Personal loan" required>
                                <div class="quick-chips">
                                    @foreach (['Personal loan', 'Salary advance', 'Medical', 'Education', 'Festival advance'] as $type)
                                        <button type="button" class="quick-chip" data-type="{{ $type }}">{{ $type }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="loan-principal">Loan amount</label>
                                <input type="number" step="0.01" min="0.01" name="principal_amount" id="loan-principal" value="{{ old('principal_amount') }}" class="form-control @error('principal_amount') is-invalid @enderror" placeholder="0.00" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="loan-emi">Monthly instalment</label>
                                <input type="number" step="0.01" min="0.01" name="monthly_installment" id="loan-emi" value="{{ old('monthly_installment') }}" class="form-control @error('monthly_installment') is-invalid @enderror" placeholder="0.00" required>
                                <div class="quick-chips">
                                    @foreach ([3, 6, 12, 24] as $months)
                                        <button type="button" class="quick-chip" data-months="{{ $months }}">{{ $months }} months</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="loan-date">Given on</label>
                                <input type="date" name="disbursed_date" id="loan-date" value="{{ old('disbursed_date', now()->toDateString()) }}" min="{{ now()->subYears(5)->toDateString() }}" max="{{ now()->addYear()->toDateString() }}" class="form-control @error('disbursed_date') is-invalid @enderror" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="loan-remarks">Note <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="remarks" id="loan-remarks" value="{{ old('remarks') }}" maxlength="1000" class="form-control" placeholder="Purpose or approval reference">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Issue loan</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">Repayment plan</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">Instalments</span>
                                <span class="rev-compare-value" id="loan-count">—</span>
                                <span class="rev-compare-meta" id="loan-last">Enter the amount and instalment</span>
                            </div>
                            <i class="ti ti-arrow-right"></i>
                            <div>
                                <span class="rev-compare-label">Repaid by</span>
                                <span class="rev-compare-value" id="loan-ends">—</span>
                                <span class="rev-compare-meta" id="loan-starts"></span>
                            </div>
                        </div>
                        <div class="run-lines" id="loan-person" hidden>
                            <div><span>Monthly salary</span><strong id="loan-salary">—</strong></div>
                            <div><span>Other loan instalments</span><strong id="loan-existing">—</strong></div>
                            <div><span>All instalments as share of salary</span><strong id="loan-share">—</strong></div>
                        </div>
                        <div class="run-note tone-amber mt-3" id="loan-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>No interest is charged. The first instalment comes out of the first payroll run for the month the loan is given, or the next one not yet run.</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.loans.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Loan status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or loan type" aria-label="Search loans">
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
                        <th style="min-width: 190px">Repaid</th>
                        <th class="text-end">Still owed</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loans as $loan)
                        @php
                            $principal = (float) $loan->principal_amount;
                            $owed = (float) $loan->outstanding_amount;
                            $emi = (float) $loan->monthly_installment;
                            $closed = $loan->status === 'closed';
                            $pct = $principal > 0 ? max(0, min(100, round(($principal - $owed) / $principal * 100))) : 0;
                            $left = ! $closed && $emi > 0 ? (int) ceil($owed / $emi) : 0;
                            $later = ! $closed && $loan->disbursed_date->gt(today()->endOfMonth());
                            $first = $later ? $loan->disbursed_date->copy()->startOfMonth() : $nextPayroll->copy();
                            $ends = $left > 0 ? $first->copy()->addMonths($left - 1) : null;
                            $untouched = $owed >= $principal;
                            $status = $closed ? ['Fully repaid', 'success', 'ti-circle-check'] : ($later ? ['Starts '.$loan->disbursed_date->format('M Y'), 'slate', 'ti-calendar-event'] : ['Being repaid', 'amber', 'ti-clock']);
                        @endphp
                        <tr>
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$loan->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $loan->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$loan->employee?->employee_code, $loan->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $loan->loan_type }}</div>
                                <div class="payslip-parts">Given {{ $loan->disbursed_date->format('M j, Y') }}</div>
                                @if ($loan->remarks)
                                    <div class="payslip-parts text-wrap" title="{{ $loan->remarks }}">{{ Str::limit(Str::afterLast(trim($loan->remarks), "\n"), 70) }}</div>
                                @endif
                            </td>
                            <td class="text-end">{{ $money($principal) }}</td>
                            <td class="text-end text-nowrap">{{ $money($emi) }}<span class="text-muted font-12"> /mo</span></td>
                            <td>
                                <div class="mini-progress tone-{{ $closed ? 'success' : 'amber' }}">
                                    <div class="mini-progress-bar is-toned"><span style="width: {{ $pct }}%"></span></div>
                                    <span class="mini-progress-label">{{ $pct }}%</span>
                                </div>
                                @if ($left > 0)
                                    <div class="payslip-parts">{{ $left }} {{ Str::plural('instalment', $left) }} left · ends {{ $ends->format('M Y') }}</div>
                                @endif
                            </td>
                            <td class="text-end"><span class="salary-figure">{{ $closed ? '—' : $money($owed) }}</span></td>
                            <td><span class="tag tone-{{ $status[1] }}"><i class="ti {{ $status[2] }}"></i>{{ $status[0] }}</span></td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @unless ($closed)
                                        @can('update', $loan)
                                            <button type="button" class="btn btn-sm review-btn is-approve" data-bs-toggle="modal" data-bs-target="#repay-modal"
                                                    data-action="{{ route('admin.loans.repay', $loan) }}" data-name="{{ $loan->employee?->name ?? 'Removed employee' }}" data-type="{{ $loan->loan_type }}" data-owed="{{ number_format($owed, 2, '.', '') }}" data-emi="{{ number_format(min($emi, $owed), 2, '.', '') }}">
                                                <i class="ti ti-cash"></i> Record repayment
                                            </button>
                                        @endcan
                                    @endunless
                                    @if ($untouched && ! $closed)
                                        @can('delete', $loan)
                                            <form action="{{ route('admin.loans.destroy', $loan) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this loan? Nothing has been repaid on it yet.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Remove loan"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="ti ti-credit-card font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No loans match</div>
                                    <span class="font-13"><a href="{{ route('admin.loans.index') }}">Show all loans</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No loans yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Issue the first one with the form above.' : 'Loans issued by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($totals['count'] > 0)
                    <tfoot>
                        <tr class="report-total">
                            <td class="fw-semibold" colspan="2">Total · {{ $totals['count'] }} {{ Str::plural('loan', $totals['count']) }}</td>
                            <td class="text-end fw-semibold">{{ $money($totals['principal']) }}</td>
                            <td colspan="2"></td>
                            <td class="text-end fw-bold">{{ $money($totals['outstanding']) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @if ($loans->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $loans->links() }}</div></div>
        @endif
    </div>

    <div class="modal fade" id="repay-modal" tabindex="-1" aria-labelledby="repay-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="repay-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="repay-title">Record a repayment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="font-13 text-muted mb-3">For money paid back outside payroll, such as cash, a bank transfer or a final settlement. <strong id="repay-who" class="text-body"></strong></p>
                    <label class="form-label" for="repay-amount">Amount repaid</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="repay-amount" class="form-control" required>
                    <div class="quick-chips">
                        <button type="button" class="quick-chip" id="repay-one">One instalment</button>
                        <button type="button" class="quick-chip" id="repay-all">Everything owed</button>
                    </div>
                    <div class="font-12 text-muted mt-2" id="repay-after"></div>
                    <label class="form-label mt-3" for="repay-note">Note <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="note" id="repay-note" maxlength="200" class="form-control" placeholder="e.g. Cash, receipt 114">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Record repayment</button>
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

            // Repayment dialog
            var modal = el('repay-modal');
            if (modal) {
                var owed = 0, emi = 0, amount = el('repay-amount');
                var after = function () {
                    var v = parseFloat(amount.value);
                    el('repay-after').textContent = isNaN(v) || v <= 0 ? fmt(owed) + ' is still owed.'
                        : (v > owed ? 'That is more than the ' + fmt(owed) + ' still owed.' : (owed - v < 0.005 ? 'This closes the loan.' : fmt(owed - v) + ' will still be owed.'));
                };
                modal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    owed = parseFloat(b.dataset.owed); emi = parseFloat(b.dataset.emi);
                    el('repay-form').action = b.dataset.action;
                    el('repay-who').textContent = b.dataset.name + ' · ' + b.dataset.type;
                    amount.max = owed; amount.value = ''; el('repay-note').value = '';
                    after();
                });
                amount.addEventListener('input', after);
                el('repay-one').addEventListener('click', function () { amount.value = emi.toFixed(2); after(); });
                el('repay-all').addEventListener('click', function () { amount.value = owed.toFixed(2); after(); });
            }

            // Issue-a-loan preview
            var form = el('loan-form');
            if (!form) return;
            var people = @json($formData);
            var nextPayroll = @json($nextPayroll->format('Y-m'));
            var employee = el('loan-employee'), principal = el('loan-principal'), emiInput = el('loan-emi'), date = el('loan-date');
            var monthName = function (ym) { var p = ym.split('-'); return new Date(p[0], p[1] - 1, 1).toLocaleString('en-US', { month: 'short', year: 'numeric' }); };
            var addMonths = function (ym, n) { var p = ym.split('-'); var d = new Date(p[0], p[1] - 1 + n, 1); return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2); };

            function render() {
                var p = people[employee.value];
                var amount = parseFloat(principal.value), each = parseFloat(emiInput.value);
                var ok = amount > 0 && each > 0;
                var warn = '';
                var n = ok ? Math.ceil(amount / each - 1e-9) : 0;
                var first = date.value && date.value.slice(0, 7) > nextPayroll ? date.value.slice(0, 7) : nextPayroll;

                el('loan-count').textContent = ok ? n : '—';
                var last = ok ? Math.round((amount - each * (n - 1)) * 100) / 100 : 0;
                el('loan-last').textContent = ok ? (n > 1 && Math.abs(last - each) > 0.005 ? 'of ' + fmt(each) + ', last one ' + fmt(last) : 'of ' + fmt(each)) : 'Enter the amount and instalment';
                el('loan-ends').textContent = ok && n <= 600 ? monthName(addMonths(first, n - 1)) : '—';
                el('loan-starts').textContent = ok ? 'first taken in ' + monthName(first) + ' payroll' : '';

                el('loan-person').hidden = !p;
                if (p) {
                    var all = p.instalments + (ok ? each : 0);
                    el('loan-salary').textContent = p.salary !== null ? fmt(p.salary) : 'No salary structure';
                    el('loan-existing').textContent = p.loans ? fmt(p.instalments) + ' on ' + p.loans + (p.loans === 1 ? ' loan' : ' loans') + ' (' + fmt(p.owed) + ' owed)' : 'None';
                    el('loan-share').textContent = p.salary ? Math.round(all / p.salary * 100) + '%' : '—';
                    if (p.salary === null) warn = p.name + ' has no salary structure. Payroll skips them, so nothing would be recovered.';
                    else if (ok && all > p.salary) warn = 'Instalments of ' + fmt(all) + ' are more than ' + p.name + ' earns in a month (' + fmt(p.salary) + '). This will be rejected.';
                    else if (ok && all > p.salary / 2) warn = 'Instalments would take more than half of ' + p.name + '\'s salary. Consider a longer repayment.';
                }
                if (!warn && ok && each > amount) warn = 'The instalment is more than the loan itself.';
                if (!warn && ok && n > 120) warn = 'This would take more than 10 years to repay and will be rejected.';
                el('loan-warn').hidden = !warn;
                el('loan-warn').querySelector('span').textContent = warn;
            }

            [employee, principal, emiInput, date].forEach(function (input) { input.addEventListener('input', render); input.addEventListener('change', render); });
            form.querySelectorAll('[data-months]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var amount = parseFloat(principal.value);
                    if (!(amount > 0)) { principal.focus(); return; }
                    emiInput.value = (Math.ceil(amount / parseInt(chip.dataset.months, 10) * 100) / 100).toFixed(2);
                    render();
                });
            });
            form.querySelectorAll('[data-type]').forEach(function (chip) {
                chip.addEventListener('click', function () { el('loan-type').value = chip.dataset.type; });
            });
            render();
        })();
    </script>
@endpush
