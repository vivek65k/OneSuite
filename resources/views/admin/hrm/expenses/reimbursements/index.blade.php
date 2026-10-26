@extends('layouts.admin')

@section('title', 'Reimbursement')

@php
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $pageUrl = fn (array $overrides = []) => route('admin.expense-reimbursements.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'To pay', 'tone' => 'indigo', 'count' => $counts['']],
        'paid' => ['label' => 'Paid', 'tone' => 'success', 'count' => $counts['paid']],
    ];
    $filterValues = ['employee_id' => $employees[$state['employee_id']] ?? null, 'search' => $state['search']];
    $activeFilters = collect(['employee_id' => 'Employee', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $oldestDays = $kpis['oldest'] ? (int) $kpis['oldest']->copy()->startOfDay()->diffInDays(today()) : null;
    // The same colour for the same category everywhere.
    $tones = ['indigo', 'teal', 'amber', 'rose', 'success', 'slate'];
    $categoryTone = fn (?string $name) => $tones[abs(crc32((string) $name)) % count($tones)];
    // Claims this login may pay: not their own.
    $payable = fn ($claim) => $canPay && ($ownEmployeeId === null || (int) $claim->employee_id !== (int) $ownEmployeeId);
    $payableCount = $groups->sum(fn ($g) => $g['claims']->filter($payable)->count());
@endphp

@section('content')
    <x-page-title title="Reimbursement" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="To pay back" :value="$kpis['owed_count'] ? $money($kpis['owed']) : '—'" icon="ti-wallet" :tone="$kpis['owed_count'] ? 'indigo' : 'success'" :hint="$kpis['owed_count'] ? $kpis['owed_count'].' '.Str::plural('claim', $kpis['owed_count']).' owed to '.$kpis['owed_people'].' '.Str::plural('person', $kpis['owed_people']) : 'Nobody is owed anything'" />
        <x-kpi-card label="Owed for longest" :value="$oldestDays !== null ? ($oldestDays === 0 ? 'Today' : $oldestDays.' '.Str::plural('day', $oldestDays)) : '—'" icon="ti-history" :tone="$oldestDays !== null && $oldestDays > 14 ? 'rose' : ($oldestDays !== null && $oldestDays > 7 ? 'amber' : 'teal')" :hint="$oldestDays !== null ? 'Since the oldest unpaid claim was approved' : 'Nothing is waiting'" />
        <x-kpi-card :label="'Paid in '.today()->format('F')" :value="$kpis['month_count'] ? $money($kpis['month']) : '—'" icon="ti-cash" tone="teal" :hint="$kpis['month_count'] ? $kpis['month_count'].' '.Str::plural('claim', $kpis['month_count']).' paid this month' : 'Nothing paid this month'" />
        <x-kpi-card :label="'Paid in '.today()->year" :value="$kpis['year_count'] ? $money($kpis['year']) : '—'" icon="ti-receipt" tone="amber" :hint="$kpis['year_count'] ? ($kpis['average_wait'] !== null ? 'Paid '.($kpis['average_wait'] === 0 ? 'the same day' : $kpis['average_wait'].' '.Str::plural('day', $kpis['average_wait'])).' after approval on average' : $kpis['year_count'].' '.Str::plural('claim', $kpis['year_count'])) : 'Nothing paid this year'" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.expense-reimbursements.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Payment status">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name, category or description" aria-label="Search claims">
                    </div>
                    @if ($employees->count() > 1 || $state['employee_id'])
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

        @if ($state['tab'] === '')
            @if ($payableCount > 1)
                <div class="bulk-bar">
                    <label class="bulk-select">
                        <input type="checkbox" class="form-check-input" id="select-all" aria-label="Select every claim">
                        <span id="bulk-summary">Select claims to pay</span>
                    </label>
                    <div class="bulk-actions">
                        <button type="button" class="btn btn-sm review-btn is-approve" disabled data-bulk-action><i class="ti ti-cash"></i> Pay selected</button>
                    </div>
                </div>
            @endif
            @if ($truncated)
                <div class="list-subbar"><span class="font-13 text-muted"><i class="ti ti-info-circle"></i> Showing the 500 claims owed for longest. Pay these to see the rest, or narrow the list with the search.</span></div>
            @endif

            <div class="table-responsive table-responsive-sticky">
                <table class="table table-hover list-table mb-0">
                    <tbody>
                        @forelse ($groups as $group)
                            @php
                                $person = $group['employee'];
                                $mine = $group['claims']->filter($payable);
                                $waited = $group['since'] ? (int) $group['since']->copy()->startOfDay()->diffInDays(today()) : null;
                                $own = $ownEmployeeId !== null && $person && (int) $person->id === (int) $ownEmployeeId;
                            @endphp
                            <tr class="table-light">
                                <td colspan="4">
                                    <div class="list-user">
                                        <x-avatar :person="$person" />
                                        <div class="list-user-text">
                                            <div class="list-user-name">{{ $person?->name ?? 'Removed employee' }}</div>
                                            <div class="list-user-meta">{{ collect([$person?->employee_code, $person?->department?->name, $group['claims']->count().' '.Str::plural('claim', $group['claims']->count()), $waited !== null ? ($waited === 0 ? 'approved today' : 'owed for '.$waited.' '.Str::plural('day', $waited)) : null])->filter()->implode(' · ') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end text-nowrap"><span class="fw-bold font-15">{{ $money($group['total']) }}</span></td>
                                <td class="text-end text-nowrap">
                                    @if ($own)
                                        <span class="font-12 text-muted" title="Someone else has to pay your own claims">Your own claims</span>
                                    @elseif ($mine->isNotEmpty())
                                        <button type="button" class="btn btn-sm review-btn is-approve" data-pay="{{ $mine->pluck('id')->implode(',') }}" data-who="{{ $person?->name }}" data-total="{{ $mine->sum(fn ($c) => (float) $c->amount) }}" data-since="{{ $mine->max('reviewed_at')?->toDateString() }}"><i class="ti ti-cash"></i> {{ $mine->count() > 1 ? 'Pay all '.$mine->count() : 'Pay' }}</button>
                                    @endif
                                </td>
                            </tr>
                            @foreach ($group['claims'] as $claim)
                                <tr>
                                    <td style="width: 36px">
                                        @if ($payableCount > 1 && $payable($claim))
                                            <input type="checkbox" class="form-check-input" value="{{ $claim->id }}" data-row-select data-amount="{{ $claim->amount }}" data-since="{{ $claim->reviewed_at?->toDateString() }}" aria-label="Select this claim of {{ $person?->name }}">
                                        @endif
                                    </td>
                                    <td>
                                        <span class="tag tone-{{ $categoryTone($claim->category?->name) }}">{{ $claim->category?->name ?? 'Removed category' }}</span>
                                        @if ($claim->description)
                                            <div class="payslip-parts text-wrap" title="{{ $claim->description }}">{{ Str::limit($claim->description, 70) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        {{ $claim->expense_date->format('M j, Y') }}
                                        <div class="payslip-parts">spent</div>
                                    </td>
                                    <td class="text-nowrap">
                                        {{ $claim->reviewed_at?->format('M j, Y') ?? '—' }}
                                        <div class="payslip-parts">approved{{ $claim->reviewedBy ? ' by '.$claim->reviewedBy->name : '' }}</div>
                                    </td>
                                    <td class="text-end text-nowrap fw-semibold">{{ $money($claim->amount) }}</td>
                                    <td class="text-end text-nowrap">
                                        @if ($payable($claim) && $group['claims']->count() > 1)
                                            <button type="button" class="btn-icon btn-icon-accent" title="Pay only this claim" data-pay="{{ $claim->id }}" data-who="{{ $person?->name }}" data-total="{{ $claim->amount }}" data-since="{{ $claim->reviewed_at?->toDateString() }}"><i class="ti ti-cash font-16"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="ti ti-wallet font-24 d-block mb-2"></i>
                                    @if ($activeFilters->isNotEmpty())
                                        <div class="fw-semibold text-body mb-1">Nothing matches</div>
                                        <span class="font-13"><a href="{{ route('admin.expense-reimbursements.index') }}">Show everything to pay</a></span>
                                    @else
                                        <div class="fw-semibold text-body mb-1">Nothing to pay back</div>
                                        <span class="font-13">Approved expense claims appear here until they are paid.</span>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="table-responsive table-responsive-sticky">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Employee</th>
                            <th>Expense</th>
                            <th>Spent</th>
                            <th>Paid</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paid as $claim)
                            @php
                                $wait = $claim->reviewed_at && $claim->reimbursed_at ? max(0, (int) $claim->reviewed_at->copy()->startOfDay()->diffInDays($claim->reimbursed_at->copy()->startOfDay())) : null;
                            @endphp
                            <tr>
                                <td>
                                    <div class="list-user">
                                        <x-avatar :person="$claim->employee" />
                                        <div class="list-user-text">
                                            <div class="list-user-name">{{ $claim->employee?->name ?? 'Removed employee' }}</div>
                                            <div class="list-user-meta">{{ collect([$claim->employee?->employee_code, $claim->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="tag tone-{{ $categoryTone($claim->category?->name) }}">{{ $claim->category?->name ?? 'Removed category' }}</span>
                                    @if ($claim->description)
                                        <div class="payslip-parts text-wrap" title="{{ $claim->description }}">{{ Str::limit($claim->description, 60) }}</div>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $claim->expense_date->format('M j, Y') }}</td>
                                <td class="text-nowrap">
                                    {{ $claim->reimbursed_at?->format('M j, Y') ?? '—' }}
                                    <div class="payslip-parts">{{ collect([$claim->reimbursedBy ? 'by '.$claim->reimbursedBy->name : null, $wait !== null ? ($wait === 0 ? 'same day as approval' : $wait.' '.Str::plural('day', $wait).' after approval') : null])->filter()->implode(' · ') }}</div>
                                </td>
                                <td class="text-end text-nowrap fw-semibold">{{ $money($claim->amount) }}</td>
                                <td class="text-end text-nowrap">
                                    <div class="list-row-actions">
                                        @if ($payable($claim))
                                            <form method="POST" action="{{ route('admin.expense-reimbursements.undo', $claim) }}" class="d-inline" onsubmit="return confirm('Undo this payment? The claim will show as still owed.');">
                                                @csrf
                                                <button type="submit" class="btn-icon" title="Undo (recorded by mistake)"><i class="ti ti-arrow-back-up font-16"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="ti ti-cash font-24 d-block mb-2"></i>
                                    @if ($activeFilters->isNotEmpty())
                                        <div class="fw-semibold text-body mb-1">Nothing matches</div>
                                        <span class="font-13"><a href="{{ $pageUrl(['employee_id' => null, 'search' => null]) }}">Clear the filters</a></span>
                                    @else
                                        <div class="fw-semibold text-body mb-1">Nothing has been paid back yet</div>
                                        <span class="font-13">Claims are listed here once they are paid.</span>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($paid->hasPages())
                <div class="list-footer"><div class="ms-auto">{{ $paid->links() }}</div></div>
            @endif
        @endif
    </div>

    @if ($canPay && $state['tab'] === '')
        <div class="modal fade" id="pay-modal" tabindex="-1" aria-labelledby="pay-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.expense-reimbursements.bulk') }}" class="modal-content" id="pay-form">
                    @csrf
                    <div id="pay-ids"></div>
                    <div class="modal-header">
                        <h5 class="modal-title" id="pay-modal-title">Mark as paid</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="rev-compare mb-3">
                            <div>
                                <span class="rev-compare-label">Paying back</span>
                                <span class="rev-compare-value" id="pay-total">—</span>
                                <span class="rev-compare-meta" id="pay-count"></span>
                            </div>
                            <i class="ti ti-arrow-right"></i>
                            <div>
                                <span class="rev-compare-label">To</span>
                                <span class="rev-compare-value promo-title" id="pay-who">—</span>
                                <span class="rev-compare-meta">They are told it was paid</span>
                            </div>
                        </div>
                        <label class="form-label" for="pay-date">Paid on</label>
                        <input type="date" name="paid_on" id="pay-date" max="{{ today()->toDateString() }}" class="form-control" style="max-width: 200px" required>
                        <div class="font-12 text-muted mt-2">This records that the money was paid; it does not send a payment. Pay it first by bank transfer, cash or with salary.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Mark as paid</button>
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
            var modalEl = el('pay-modal');
            if (!modalEl) return;
            var today = @json(today()->toDateString());
            // Whole amounts without decimals, anything else with two (2,000.50 rather than 2,000.5).
            var fmt = function (n) { n = Number(n); return n.toLocaleString('en-US', { minimumFractionDigits: n % 1 === 0 ? 0 : 2, maximumFractionDigits: 2 }); };

            // Every "pay" button and the bulk bar go through the same dialog.
            function open(ids, who, total, since) {
                var holder = el('pay-ids');
                holder.textContent = '';
                ids.forEach(function (id) { var i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; holder.appendChild(i); });
                el('pay-total').textContent = fmt(total);
                el('pay-count').textContent = ids.length + (ids.length === 1 ? ' claim' : ' claims');
                el('pay-who').textContent = who;
                el('pay-date').value = today; el('pay-date').min = since || '';
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            document.querySelectorAll('[data-pay]').forEach(function (button) {
                button.addEventListener('click', function () {
                    open(button.dataset.pay.split(','), button.dataset.who || 'the employee', parseFloat(button.dataset.total) || 0, button.dataset.since);
                });
            });

            var all = el('select-all');
            if (!all) return;
            var rows = Array.prototype.slice.call(document.querySelectorAll('[data-row-select]'));
            var action = document.querySelector('[data-bulk-action]');
            var picked = function () { return rows.filter(function (box) { return box.checked; }); };
            var total = function (list) { return list.reduce(function (sum, box) { return sum + (parseFloat(box.dataset.amount) || 0); }, 0); };
            function refresh() {
                var list = picked();
                action.disabled = list.length === 0;
                el('bulk-summary').textContent = list.length ? list.length + ' selected · ' + fmt(total(list)) : 'Select claims to pay';
                all.checked = list.length > 0 && list.length === rows.length;
                all.indeterminate = list.length > 0 && list.length < rows.length;
                rows.forEach(function (box) { box.closest('tr').classList.toggle('table-active', box.checked); });
            }
            all.addEventListener('change', function () { rows.forEach(function (box) { box.checked = all.checked; }); refresh(); });
            rows.forEach(function (box) { box.addEventListener('change', refresh); });
            action.addEventListener('click', function () {
                var list = picked();
                // The earliest date all of them can be paid on is the latest approval among them.
                var since = list.map(function (box) { return box.dataset.since || ''; }).sort().pop();
                open(list.map(function (box) { return box.value; }), list.length + (list.length === 1 ? ' claim' : ' claims') + ' selected', total(list), since);
            });
            refresh();
        })();
    </script>
@endpush
