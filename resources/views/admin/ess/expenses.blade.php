@extends('layouts.admin')

@section('title', 'My Expenses')

@php
    use App\Http\Controllers\Admin\Ess\MyExpenseController as Expenses;
    use App\Models\ExpenseClaim;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n, fmod((float) $n, 1) ? 2 : 0);
    $pageUrl = fn (array $overrides = []) => route('admin.my-expenses.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All claims', 'tone' => 'indigo', 'count' => $counts['all']],
        'pending' => ['label' => 'Waiting for approval', 'tone' => 'amber', 'count' => $counts['pending']],
        'approved' => ['label' => 'Approved, to be paid', 'tone' => 'sky', 'count' => $counts['approved']],
        'reimbursed' => ['label' => 'Paid', 'tone' => 'success', 'count' => $counts['reimbursed']],
        'rejected' => ['label' => 'Rejected', 'tone' => 'rose', 'count' => $counts['rejected']],
    ];
    $statusMeta = [
        'pending' => ['amber', 'ti-clock', 'Waiting for approval'],
        'approved' => ['sky', 'ti-circle-check', 'Approved, to be paid'],
        'reimbursed' => ['success', 'ti-cash', 'Paid'],
        'rejected' => ['rose', 'ti-circle-x', 'Rejected'],
    ];
    $tones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky'];
    $categoryTone = fn (?string $name) => $tones[abs(crc32((string) $name)) % count($tones)];
    // A refused edit reopens its popup; a refused new claim keeps what was typed in the form.
    $editing = old('_method') === 'PUT' ? (int) old('_claim') : 0;
    $fields = ['expense_category_id', 'amount', 'expense_date', 'description'];
    $newErrors = ! $editing && $errors->hasAny($fields);
    $typed = collect($fields)->mapWithKeys(fn ($field) => [$field => is_scalar(old($field)) ? (string) old($field) : ''])->all();
    $waitingDays = $kpis['oldest_pending'] ? (int) \Illuminate\Support\Carbon::parse($kpis['oldest_pending'])->startOfDay()->diffInDays(today()) : null;
@endphp

@section('content')
    <x-page-title title="My Expenses" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any() && ! $newErrors)
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for approval" :value="$money($kpis['pending'])" icon="ti-clock" :tone="$kpis['pending_count'] ? 'amber' : 'indigo'" :hint="$kpis['pending_count'] ? $kpis['pending_count'].' '.Str::plural('claim', $kpis['pending_count']).($waitingDays ? ' · oldest sent '.$waitingDays.' '.Str::plural('day', $waitingDays).' ago' : '') : 'Nothing is waiting'" :href="$kpis['pending_count'] ? $pageUrl(['tab' => 'pending']) : null" />
        <x-kpi-card label="Approved, to be paid" :value="$money($kpis['approved'])" icon="ti-wallet" :tone="$kpis['approved_count'] ? 'sky' : 'teal'" :hint="$kpis['approved_count'] ? $kpis['approved_count'].' '.Str::plural('claim', $kpis['approved_count']).' with finance' : 'Nothing is owed to you'" :href="$kpis['approved_count'] ? $pageUrl(['tab' => 'approved']) : null" />
        <x-kpi-card label="Paid in {{ now()->year }}" :value="$money($kpis['paid_year'])" icon="ti-cash" tone="success" :hint="$kpis['paid_year_count'] ? $kpis['paid_year_count'].' '.Str::plural('claim', $kpis['paid_year_count']).' reimbursed' : 'Nothing reimbursed this year'" />
        <x-kpi-card label="Rejected" :value="$kpis['rejected_count']" icon="ti-circle-x" :tone="$kpis['rejected_count'] ? 'rose' : 'teal'" :hint="$kpis['rejected_count'] ? $money($kpis['rejected']).' in total · see the reason on each' : 'None of your claims were rejected'" :href="$kpis['rejected_count'] ? $pageUrl(['tab' => 'rejected']) : null" />
    </div>

    @if ($canClaim)
        <form method="POST" action="{{ route('admin.my-expenses.store') }}" class="card form-section tone-indigo" id="claim-form">
            @csrf
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-receipt"></i></span>
                <div class="flex-grow-1">
                    <h5 class="form-section-title">Claim an expense</h5>
                    <p class="form-section-sub">Money you spent for work in the last {{ Expenses::MONTHS_BACK }} months. It goes to your manager for approval, then to finance for payment.</p>
                </div>
            </div>
            <div class="form-section-body">
                @if ($newErrors)
                    <div class="alert alert-danger py-2 font-13">@foreach (collect($fields)->flatMap(fn ($field) => $errors->get($field)) as $message)<div>{{ $message }}</div>@endforeach</div>
                @endif
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">What was it for? <span class="text-danger">*</span></label>
                        <div class="segmented flex-wrap" id="claim-categories" role="group" aria-label="Expense category">
                            @foreach ($categories as $category)
                                <button type="button" class="segmented-item" data-id="{{ $category->id }}" data-name="{{ $category->name }}" @if ($category->description) title="{{ $category->description }}" @endif>{{ $category->name }}</button>
                            @endforeach
                        </div>
                        <input type="hidden" name="expense_category_id" id="claim-category" value="{{ $editing ? '' : $typed['expense_category_id'] }}">
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="claim-amount">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="claim-amount" class="form-control" value="{{ $editing ? '' : $typed['amount'] }}" placeholder="0.00" required>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="claim-date">Spent on <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" id="claim-date" class="form-control" value="{{ $editing ? '' : $typed['expense_date'] }}" min="{{ $minDate }}" max="{{ today()->toDateString() }}" required>
                        <div class="quick-chips">
                            <button type="button" class="quick-chip" data-days="0">Today</button>
                            <button type="button" class="quick-chip" data-days="1">Yesterday</button>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="claim-description">Details <span class="text-danger">*</span></label>
                        <input type="text" name="description" id="claim-description" class="form-control" value="{{ $editing ? '' : $typed['description'] }}" maxlength="1000" placeholder="e.g. Cab to the client's office and back" required>
                        <div class="form-hint"><i class="ti ti-info-circle"></i> <span>Say what it was and why; your approver has only this to go on.</span></div>
                    </div>
                </div>
                <div class="run-note tone-rose mt-3 d-none" id="claim-problem"><i class="ti ti-alert-circle"></i><span></span></div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-de-primary text-light px-4" id="claim-submit"><i class="ti ti-send font-14 me-1"></i> Send for approval</button>
                </div>
            </div>
        </form>
    @else
        <div class="alert alert-warning d-flex align-items-center gap-2"><i class="ti ti-info-circle font-18"></i>
            {{ $categories->isEmpty() ? 'No expense categories are set up for your branch yet, so claims can\'t be filed. Ask HR.' : 'Your employee record is '.$employeeStatus.', so new claims can\'t be filed.' }}
        </div>
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <nav class="list-tabs" aria-label="Claim status">
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
        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Spent on</th>
                        <th>Category</th>
                        <th style="width: 30%">Details</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($claims as $claim)
                        @php
                            $status = $statusMeta[$claim->status] ?? ['slate', 'ti-point', Str::headline($claim->status)];
                            $step = $flow[$claim->id] ?? null;
                            $canEdit = $claim->status === 'pending' && $step && $step['done'] === 0 && $canClaim;
                        @endphp
                        <tr class="{{ $claim->status === 'pending' ? 'is-pending-row' : '' }}">
                            <td class="text-nowrap">
                                {{ $claim->expense_date->format('M j, Y') }}
                                <div class="payslip-parts">claimed {{ $claim->created_at?->format('M j') }}</div>
                            </td>
                            <td><span class="tag tone-{{ $categoryTone($claim->category?->name) }}">{{ $claim->category?->name ?? 'Removed category' }}</span></td>
                            <td>
                                @if ($claim->description)
                                    <span title="{{ Str::length($claim->description) > 110 ? $claim->description : '' }}">{{ Str::limit($claim->description, 110) }}</span>
                                @else
                                    <span class="text-muted">No details given</span>
                                @endif
                            </td>
                            <td class="text-end"><span class="salary-figure">{{ number_format((float) $claim->amount, 2) }}</span></td>
                            <td>
                                <span class="tag tone-{{ $status[0] }}"><i class="ti {{ $status[1] }}"></i>{{ $status[2] }}</span>
                                @if ($claim->status === 'pending' && $step)
                                    <div class="payslip-parts">{{ $step['name'] ? 'waiting on '.$step['name'] : 'waiting on HR' }}{{ $step['required'] > 1 ? ' · step '.min($step['done'] + 1, $step['required']).' of '.$step['required'] : '' }}</div>
                                @elseif ($claim->status === 'reimbursed')
                                    <div class="payslip-parts">{{ $claim->reimbursed_at ? 'on '.$claim->reimbursed_at->format('M j, Y') : '' }}</div>
                                @elseif ($claim->reviewedBy || $claim->reviewed_at)
                                    <div class="payslip-parts">{{ collect([$claim->reviewedBy?->name ? 'by '.$claim->reviewedBy->name : null, $claim->reviewed_at?->format('M j')])->filter()->implode(' · ') }}</div>
                                @endif
                                @if ($claim->remarks)
                                    <div class="payslip-parts {{ $claim->status === 'rejected' ? 'text-danger' : '' }}" title="{{ $claim->remarks }}">“{{ Str::limit($claim->remarks, 80) }}”</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($canEdit)
                                        <button type="button" class="btn-icon" title="Correct this claim" data-bs-toggle="modal" data-bs-target="#edit-modal"
                                                data-id="{{ $claim->id }}" data-action="{{ route('admin.my-expenses.update', $claim) }}" data-category="{{ $claim->expense_category_id }}" data-category-name="{{ $claim->category?->name }}" data-amount="{{ $claim->amount }}" data-date="{{ $claim->expense_date->toDateString() }}" data-description="{{ $claim->description }}"><i class="ti ti-edit font-16"></i></button>
                                    @endif
                                    @if ($claim->status === 'pending')
                                        <form action="{{ route('admin.my-expenses.destroy', $claim) }}" method="POST" class="d-inline" onsubmit="return confirm(@js('Withdraw your claim for '.number_format((float) $claim->amount, 2).'? It is removed and nobody will be asked to approve it.'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm review-btn is-reject"><i class="ti ti-x"></i> Withdraw</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-receipt font-24 d-block mb-2"></i>
                                @if ($state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">No claims here</div>
                                    <span class="font-13"><a href="{{ route('admin.my-expenses.index') }}">Show all claims</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">You haven't claimed anything yet</div>
                                    <span class="font-13">Claims you send appear here with who they are waiting on.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($claims->count() > 1)
                    <tfoot>
                        <tr class="report-total">
                            <td colspan="3">{{ $state['tab'] === '' ? 'Total listed, including rejected' : 'Total shown' }}</td>
                            <td class="text-end"><span class="salary-figure">{{ number_format((float) $claims->sum('amount'), 2) }}</span></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="modal fade" id="edit-modal" tabindex="-1" aria-labelledby="edit-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="edit-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="_claim" id="edit-id">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit-modal-title">Correct this claim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="edit-category">Category</label>
                    <select name="expense_category_id" id="edit-category" class="form-select" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <div class="row g-3 mt-0">
                        <div class="col-6">
                            <label class="form-label" for="edit-amount">Amount</label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="edit-amount" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-date">Spent on</label>
                            <input type="date" name="expense_date" id="edit-date" class="form-control" min="{{ $minDate }}" max="{{ today()->toDateString() }}" required>
                        </div>
                    </div>
                    <label class="form-label mt-3" for="edit-description">Details</label>
                    <input type="text" name="description" id="edit-description" class="form-control" maxlength="1000" required>
                    <div class="font-12 text-muted mt-2"><i class="ti ti-info-circle"></i> A claim can be corrected until someone has approved it.</div>
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
            var $ = function (id) { return document.getElementById(id); };
            var existing = @json($existing);
            var pad = function (n) { return String(n).padStart(2, '0'); };
            var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };

            var form = $('claim-form');
            if (form) {
                var category = $('claim-category'), amount = $('claim-amount'), date = $('claim-date');
                function pick(id) {
                    var found = false;
                    $('claim-categories').querySelectorAll('.segmented-item').forEach(function (b) { var on = b.dataset.id === String(id); b.classList.toggle('active', on); found = found || on; });
                    category.value = found ? id : '';
                    check();
                }
                // The same category, day and amount as a claim already made would be paid twice.
                function check() {
                    var value = Math.round(parseFloat(amount.value) * 100) / 100;
                    var twin = category.value && date.value && value ? existing.find(function (c) { return String(c.category) === category.value && c.date === date.value && Math.abs(c.amount - value) < 0.005; }) : null;
                    $('claim-problem').classList.toggle('d-none', !twin);
                    $('claim-problem').querySelector('span').textContent = twin ? 'You already have a claim for this category, date and amount. Change one of them if this is a different expense.' : '';
                    $('claim-submit').disabled = !!twin;
                }
                $('claim-categories').addEventListener('click', function (event) { var b = event.target.closest('[data-id]'); if (b) pick(b.dataset.id); });
                form.querySelectorAll('[data-days]').forEach(function (chip) {
                    chip.addEventListener('click', function () { var d = new Date(); d.setDate(d.getDate() - parseInt(chip.dataset.days, 10)); date.value = iso(d); check(); });
                });
                [amount, date].forEach(function (input) { input.addEventListener('input', check); });
                form.addEventListener('submit', function (event) {
                    if (!category.value) { event.preventDefault(); alert('Choose what the money was spent on.'); return; }
                    setTimeout(function () { $('claim-submit').disabled = true; }, 0);
                });
                pick(category.value);
            }

            var modal = $('edit-modal');
            function fill(d) {
                $('edit-form').action = d.action;
                $('edit-id').value = d.id;
                var select = $('edit-category');
                // A claim filed under a category that has since been switched off keeps it.
                if (d.category && !select.querySelector('option[value="' + d.category + '"]')) { select.add(new Option(d.categoryName || 'Current category', d.category)); }
                select.value = d.category || '';
                $('edit-amount').value = d.amount || '';
                $('edit-date').value = d.date || '';
                $('edit-description').value = d.description || '';
            }
            modal.addEventListener('show.bs.modal', function (event) { if (event.relatedTarget) fill(event.relatedTarget.dataset); });
            // A refused edit: reopen the popup with what was typed.
            var reopen = {{ $editing }};
            var button = reopen ? document.querySelector('[data-bs-target="#edit-modal"][data-id="' + reopen + '"]') : null;
            if (button) {
                var typed = @json($typed);
                fill(Object.assign({}, button.dataset, { category: typed.expense_category_id, amount: typed.amount, date: typed.expense_date, description: typed.description }));
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        })();
    </script>
@endpush
