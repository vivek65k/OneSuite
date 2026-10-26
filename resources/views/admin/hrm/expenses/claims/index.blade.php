@extends('layouts.admin')

@section('title', 'Expense Claims')

@php
    use App\Models\ExpenseClaim;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $pageUrl = fn (array $overrides = []) => route('admin.expense-claims.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $statusMeta = ['pending' => ['amber', 'ti-clock'], 'approved' => ['indigo', 'ti-circle-check'], 'rejected' => ['rose', 'ti-x'], 'reimbursed' => ['success', 'ti-cash']];
    $tabs = ['' => ['label' => 'All claims', 'tone' => 'indigo', 'count' => $counts['all']]];
    foreach (ExpenseClaim::STATUSES as $value => $label) {
        $tabs[$value] = ['label' => $label, 'tone' => $statusMeta[$value][0], 'count' => $counts[$value] ?? 0];
    }
    $filterValues = [
        'category' => $state['category'],
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['category' => 'Category', 'employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $oldEmployee = (string) old('employee_id', $state['employee_id'] ?? '');
    $oldestDays = $kpis['oldest'] ? (int) $kpis['oldest']->copy()->startOfDay()->diffInDays(today()) : null;
    $canSeeReimbursements = \Illuminate\Support\Facades\Route::has('admin.expense-reimbursements.index');
    // The same colour for the same category everywhere on the page.
    $tones = ['indigo', 'teal', 'amber', 'rose', 'success', 'slate'];
    $categoryTone = fn (?string $name) => $tones[abs(crc32((string) $name)) % count($tones)];
@endphp

@section('content')
    <x-page-title title="Expense Claims" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for approval" :value="$kpis['pending']" icon="ti-clock" :tone="$kpis['pending'] ? ($oldestDays > 7 ? 'rose' : 'amber') : 'success'" :hint="$kpis['pending'] ? $money($kpis['pending_amount']).' claimed · oldest '.($oldestDays === 0 ? 'today' : $oldestDays.' '.Str::plural('day', $oldestDays).' ago') : 'Nothing to approve'" />
        <x-kpi-card label="Approved, to be paid" :value="$kpis['to_pay_count'] ? $money($kpis['to_pay']) : '—'" icon="ti-wallet" tone="indigo" :hint="$kpis['to_pay_count'] ? $kpis['to_pay_count'].' '.Str::plural('claim', $kpis['to_pay_count']).' waiting for reimbursement' : 'Nothing is waiting to be paid'" />
        <x-kpi-card :label="'Paid in '.today()->format('F')" :value="$kpis['paid_month_count'] ? $money($kpis['paid_month']) : '—'" icon="ti-cash" tone="teal" :hint="$kpis['paid_month_count'] ? $kpis['paid_month_count'].' '.Str::plural('claim', $kpis['paid_month_count']).' reimbursed this month' : 'Nothing reimbursed this month'" />
        <x-kpi-card :label="'Spent in '.today()->format('F')" :value="$kpis['month_count'] ? $money($kpis['month']) : '—'" icon="ti-receipt" tone="amber" :hint="$kpis['month_count'] ? $kpis['month_count'].' '.Str::plural('claim', $kpis['month_count']).' · mostly '.$kpis['top'] : 'No expenses dated this month'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.expense-claims.store') }}" class="card form-section tone-indigo mb-0 h-100" id="claim-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-receipt"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Submit a claim</h5>
                            <p class="form-section-sub">Money an employee spent for work and wants back. It goes for approval, then for reimbursement.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="claim-employee">Employee</label>
                                <select name="employee_id" id="claim-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formPeople as $person)
                                        <option value="{{ $person->id }}" data-branch="{{ $person->branch_id }}" @selected($oldEmployee === (string) $person->id)>{{ $person->name }}{{ $person->employee_code ? ' ('.$person->employee_code.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="claim-category">Spent on</label>
                                <select name="expense_category_id" id="claim-category" class="form-select @error('expense_category_id') is-invalid @enderror" data-old="{{ old('expense_category_id') }}" required>
                                    <option value="">Pick an employee first</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="claim-amount">Amount</label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="claim-amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" placeholder="0.00" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="claim-date">Spent on date</label>
                                <input type="date" name="expense_date" id="claim-date" value="{{ old('expense_date', today()->toDateString()) }}" min="{{ $limits['min'] }}" max="{{ $limits['max'] }}" class="form-control @error('expense_date') is-invalid @enderror" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="claim-description">What it was for <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="description" id="claim-description" value="{{ old('description') }}" maxlength="1000" class="form-control" placeholder="e.g. Lunch with the Acme team">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Submit claim</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold" id="sum-title">This person's claims this month</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">Claimed so far</span>
                                <span class="rev-compare-value" id="sum-now">—</span>
                                <span class="rev-compare-meta" id="sum-now-meta">Pick an employee</span>
                            </div>
                            <i class="ti ti-arrow-right"></i>
                            <div>
                                <span class="rev-compare-label">With this claim</span>
                                <span class="rev-compare-value" id="sum-after">—</span>
                                <span class="rev-compare-meta" id="sum-after-meta"></span>
                            </div>
                        </div>
                        <div class="run-note tone-amber mt-3" id="sum-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>The same expense (same person, category, date and amount) can't be claimed twice. A rejected claim can be submitted again once corrected.</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.expense-claims.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Claim status">
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
                    @if ($categoryNames->count() > 1 || $state['category'] !== '')
                        <select name="category" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Category">
                            <option value="">All categories</option>
                            @foreach ($categoryNames->push($state['category'])->filter()->unique()->sort() as $name)
                                <option value="{{ $name }}" @selected($state['category'] === $name)>{{ $name }}</option>
                            @endforeach
                        </select>
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
                        <th>Expense</th>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($claims as $claim)
                        @php
                            $meta = $statusMeta[$claim->status] ?? ['slate', 'ti-circle'];
                            $stage = $stages[$claim->id] ?? null;
                            $pending = $claim->status === 'pending';
                            $waited = $pending && $claim->created_at ? (int) $claim->created_at->copy()->startOfDay()->diffInDays(today()) : 0;
                        @endphp
                        <tr class="{{ $pending && $waited > 7 ? 'is-pending-row' : '' }}">
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
                                    <div class="payslip-parts text-wrap" title="{{ $claim->description }}">{{ Str::limit($claim->description, 70) }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                {{ $claim->expense_date->format('M j, Y') }}
                                @if ($pending)
                                    <div class="payslip-parts">{{ $waited === 0 ? 'claimed today' : 'waiting '.$waited.' '.Str::plural('day', $waited) }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap fw-semibold">{{ $money($claim->amount) }}</td>
                            <td>
                                <span class="tag tone-{{ $meta[0] }}"><i class="ti {{ $meta[1] }}"></i>{{ ExpenseClaim::STATUSES[$claim->status] ?? $claim->status }}</span>
                                <div class="payslip-parts text-wrap">
                                    @if ($pending && $stage)
                                        @if ($stage['required'] > 1)
                                            Level {{ min($stage['done'] + 1, $stage['required']) }} of {{ $stage['required'] }}{{ $stage['waiting'] ? ' · waiting for '.$stage['waiting'] : '' }}
                                        @elseif ($stage['waiting'])
                                            Waiting for {{ $stage['waiting'] }}
                                        @endif
                                    @elseif ($claim->status === 'rejected')
                                        {{ $claim->remarks ? Str::limit($claim->remarks, 60) : ($claim->reviewedBy ? 'by '.$claim->reviewedBy->name : '') }}
                                    @elseif ($claim->status === 'reimbursed')
                                        {{ $claim->reimbursed_at ? 'paid '.$claim->reimbursed_at->format('M j, Y') : '' }}{{ $claim->reimbursedBy ? ' · by '.$claim->reimbursedBy->name : '' }}
                                    @elseif ($claim->reviewedBy)
                                        by {{ $claim->reviewedBy->name }}{{ $claim->reviewed_at ? ' · '.$claim->reviewed_at->format('M j') : '' }}
                                    @endif
                                </div>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($pending && $stage)
                                        @if ($stage['can_act'])
                                            <form method="POST" action="{{ route('admin.expense-approval.approve', $claim) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-check"></i> Approve</button>
                                            </form>
                                            <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="modal" data-bs-target="#reject-modal"
                                                    data-action="{{ route('admin.expense-approval.reject', $claim) }}" data-what="{{ $claim->employee?->name }}: {{ $claim->category?->name }}, {{ $money($claim->amount) }} on {{ $claim->expense_date->format('M j') }}"><i class="ti ti-x"></i> Reject</button>
                                        @elseif ($stage['own'])
                                            <span class="font-12 text-muted" title="Someone else has to review your own claim">Your own claim</span>
                                        @endif
                                        @can('update', $claim)
                                            <button type="button" class="btn-icon btn-icon-accent" title="Change claim" data-bs-toggle="modal" data-bs-target="#claim-modal"
                                                    data-action="{{ route('admin.expense-claims.update', $claim) }}" data-who="{{ $claim->employee?->name }}" data-branch="{{ $claim->employee?->branch_id }}" data-category="{{ $claim->expense_category_id }}" data-category-name="{{ $claim->category?->name }}"
                                                    data-amount="{{ $claim->amount }}" data-date="{{ $claim->expense_date->toDateString() }}" data-description="{{ $claim->description }}"><i class="ti ti-pencil font-16"></i></button>
                                        @endcan
                                    @elseif ($claim->status === 'approved' && $canSeeReimbursements)
                                        @can('update', $claim)
                                            <a href="{{ route('admin.expense-reimbursements.index') }}" class="btn btn-sm review-btn" title="Pay it back on the Reimbursement page"><i class="ti ti-cash"></i> Reimburse</a>
                                        @endcan
                                    @endif
                                    @if ($claim->status !== 'reimbursed')
                                        @can('delete', $claim)
                                            <form action="{{ route('admin.expense-claims.destroy', $claim) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $claim->status === 'approved' ? 'Delete this approved claim? It will not be paid.' : 'Delete this claim?' }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Delete claim"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-receipt font-24 d-block mb-2"></i>
                                @if ($counts['all'] || $activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No claims match</div>
                                    <span class="font-13"><a href="{{ route('admin.expense-claims.index') }}">Show all claims</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No expense claims yet</div>
                                    <span class="font-13">{{ $canCreate ? 'Submit the first one with the form above.' : 'Claims submitted by employees will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($claims->hasPages())
            <div class="list-footer"><div class="ms-auto">{{ $claims->links() }}</div></div>
        @endif
    </div>

    {{-- Reject with a reason --}}
    <div class="modal fade" id="reject-modal" tabindex="-1" aria-labelledby="reject-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="reject-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="reject-modal-title">Reject expense claim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="font-13 text-muted mb-3" id="reject-intro"></div>
                    <label class="form-label" for="reject-remarks">Reason</label>
                    <textarea name="remarks" id="reject-remarks" rows="3" maxlength="1000" class="form-control" placeholder="The employee is told this" required></textarea>
                    <div class="quick-chips">
                        @foreach (['No receipt', 'Not a business expense', 'Above the allowed limit', 'Already claimed'] as $preset)
                            <button type="button" class="quick-chip" data-reason="{{ $preset }}">{{ $preset }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="ti ti-x font-14 me-1"></i> Reject claim</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Change a pending claim --}}
    <div class="modal fade" id="claim-modal" tabindex="-1" aria-labelledby="claim-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="claim-edit">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="claim-modal-title">Change expense claim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="font-13 text-muted mb-3" id="edit-who"></div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="edit-category">Spent on</label>
                            <select name="expense_category_id" id="edit-category" class="form-select" required></select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-amount">Amount</label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="edit-amount" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-date">Spent on date</label>
                            <input type="date" name="expense_date" id="edit-date" min="{{ $limits['min'] }}" max="{{ $limits['max'] }}" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit-description">What it was for</label>
                            <input type="text" name="description" id="edit-description" maxlength="1000" class="form-control">
                        </div>
                    </div>
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
            var categories = @json($categoriesByBranch);
            var month = @json($monthByEmployee);
            var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); };
            // Fill a category list with one branch's categories, keeping the chosen one selected.
            var fill = function (select, branch, chosen, keepName) {
                var list = (branch && categories[branch]) || [];
                select.textContent = '';
                var first = document.createElement('option');
                first.value = ''; first.textContent = branch ? (list.length ? 'Select category' : 'No categories set up for this branch') : 'Pick an employee first';
                select.appendChild(first);
                list.forEach(function (c) { var o = document.createElement('option'); o.value = c.id; o.textContent = c.name; select.appendChild(o); });
                // A claim may keep a category that has since been switched off.
                if (chosen && !list.some(function (c) { return String(c.id) === String(chosen); }) && keepName) {
                    var o = document.createElement('option'); o.value = chosen; o.textContent = keepName + ' (switched off)'; select.appendChild(o);
                }
                select.value = chosen && [].some.call(select.options, function (o) { return o.value === String(chosen); }) ? String(chosen) : '';
            };

            var reject = el('reject-modal');
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

            el('claim-modal').addEventListener('show.bs.modal', function (event) {
                var b = event.relatedTarget;
                if (!b) return;
                el('claim-edit').action = b.dataset.action;
                el('edit-who').textContent = b.dataset.who || '';
                fill(el('edit-category'), b.dataset.branch, b.dataset.category, b.dataset.categoryName);
                el('edit-amount').value = b.dataset.amount; el('edit-date').value = b.dataset.date; el('edit-description').value = b.dataset.description || '';
            });

            var form = el('claim-form');
            if (!form) return;
            var employee = el('claim-employee'), category = el('claim-category'), amount = el('claim-amount');

            function render(changedEmployee) {
                var opt = employee.options[employee.selectedIndex];
                var who = employee.value ? opt.textContent.trim().replace(/\s*\(.*\)$/, '') : '';
                if (changedEmployee) fill(category, employee.value ? opt.dataset.branch : '', category.dataset.old || category.value, null);
                category.dataset.old = '';
                var mine = employee.value ? (month[employee.value] || { count: 0, amount: 0, pending: 0 }) : null;
                var value = parseFloat(amount.value);
                el('sum-title').textContent = who ? who + ' this month' : 'This person\'s claims this month';
                el('sum-now').textContent = mine ? fmt(mine.amount) : '—';
                el('sum-now-meta').textContent = mine ? (mine.count ? mine.count + (mine.count === 1 ? ' claim' : ' claims') + (mine.pending ? ' · ' + mine.pending + ' still pending' : '') : 'No claims yet this month') : 'Pick an employee';
                el('sum-after').textContent = mine && value > 0 ? fmt(mine.amount + value) : '—';
                el('sum-after-meta').textContent = mine && value > 0 ? (mine.count + 1) + (mine.count + 1 === 1 ? ' claim' : ' claims') + ' this month' : '';
                var warn = employee.value && category.options.length <= 1 ? 'No expense categories are set up for this employee\'s branch. Add them under Expense Categories first.' : '';
                el('sum-warn').hidden = !warn;
                el('sum-warn').querySelector('span').textContent = warn;
            }

            employee.addEventListener('change', function () { render(true); });
            amount.addEventListener('input', function () { render(false); });
            render(true);
        })();
    </script>
@endpush
