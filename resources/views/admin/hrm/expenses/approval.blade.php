@extends('layouts.admin')

@section('title', 'Expense Approval')

@php
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $listUrl = fn (array $overrides = []) => route('admin.expense-approval.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $windows = ['' => ['All pending', $counts['all'], 'indigo'], 'week' => ['Waiting over a week', $counts['week'], 'rose'], 'large' => ['Largest claims', $counts['large'], 'amber']];
    $sorts = ['' => 'Waiting longest', 'amount' => 'Largest first', 'newest' => 'Newest first'];
    $filterValues = [
        'category' => $state['category'],
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['category' => 'Category', 'employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $actable = $claims->getCollection()->filter(fn ($c) => $stages[$c->id]['can_act'] ?? false)->count();
    $oldestDays = $kpis['oldest'] ? (int) $kpis['oldest']->copy()->startOfDay()->diffInDays(today()) : null;
    // The same colour for the same category everywhere.
    $tones = ['indigo', 'teal', 'amber', 'rose', 'success', 'slate'];
    $categoryTone = fn (?string $name) => $tones[abs(crc32((string) $name)) % count($tones)];
@endphp

@section('content')
    <x-page-title title="Expense Approval" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for approval" :value="$kpis['waiting']" icon="ti-clock" :tone="$kpis['waiting'] ? 'amber' : 'success'" :hint="$kpis['waiting'] ? 'From '.$kpis['people'].' '.Str::plural('person', $kpis['people']) : 'Nothing to approve'" />
        <x-kpi-card label="Amount waiting" :value="$kpis['amount'] > 0 ? $money($kpis['amount']) : '—'" icon="ti-receipt" tone="indigo" :hint="$kpis['largest'] ? 'Largest single claim '.$money($kpis['largest']) : 'No claims to decide'" />
        <x-kpi-card label="Waiting over a week" :value="$kpis['old']" icon="ti-alert-triangle" :tone="$kpis['old'] ? 'rose' : 'success'" :hint="$kpis['old'] ? 'People are out of pocket; decide these first' : 'Nothing has waited that long'" />
        <x-kpi-card label="Waiting longest" :value="$oldestDays !== null ? ($oldestDays === 0 ? 'Today' : $oldestDays.' '.Str::plural('day', $oldestDays)) : '—'" icon="ti-history" :tone="$oldestDays !== null && $oldestDays > 7 ? 'amber' : 'teal'" :hint="$oldestDays !== null ? 'Since the oldest claim was made' : 'No claim is waiting'" />
    </div>

    <div class="card list-view mb-3">
        <form method="GET" action="{{ route('admin.expense-approval.index') }}">
            @foreach (['window' => $state['window'], 'sort' => $state['sort']] as $key => $value)
                @if ($value !== '')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Claims shown">
                    @foreach ($windows as $value => [$label, $count, $tone])
                        @if ($value === '' || $count || $state['window'] === $value)
                            <a href="{{ $listUrl(['window' => $value ?: null]) }}" class="list-tab tone-{{ $tone }} {{ $state['window'] === $value ? 'active' : '' }}" @if ($value === 'large' && $largeFrom !== null) title="Claims of {{ $money($largeFrom) }} or more" @endif>
                                {{ $label }}
                                <span class="list-tab-count">{{ $count }}</span>
                            </a>
                        @endif
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
                    @if ($employees->count() > 1 || $state['employee_id'])
                        <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Employee">
                            <option value="">All employees</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected($state['employee_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-arrows-sort font-14 me-1"></i> {{ $sorts[$state['sort']] }}</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @foreach ($sorts as $value => $label)
                                <li><a class="dropdown-item {{ $state['sort'] === $value ? 'active' : '' }}" href="{{ $listUrl(['sort' => $value ?: null]) }}">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $listUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($canReview && $actable > 1)
            <form method="POST" action="{{ route('admin.expense-approval.bulk-approve') }}" id="bulk-approve-form" class="bulk-bar" onsubmit="return confirm('Approve all selected claims?');">
                @csrf
                <label class="bulk-select">
                    <input type="checkbox" class="form-check-input" id="select-all" aria-label="Select all on this page">
                    <span id="bulk-summary">Select claims</span>
                </label>
                <div class="bulk-actions">
                    <button type="submit" class="btn btn-sm review-btn is-approve" disabled data-bulk-action><i class="ti ti-checks"></i> Approve selected</button>
                </div>
            </form>
        @endif
    </div>

    @if ($claims->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-success"><i class="ti ti-circle-check"></i></span>
                <h5>{{ $activeFilters->isNotEmpty() || $state['window'] !== '' ? 'No pending claims match' : 'All caught up' }}</h5>
                <p>
                    @if ($activeFilters->isNotEmpty() || $state['window'] !== '')
                        <a href="{{ route('admin.expense-approval.index') }}">Show every pending claim</a>
                    @else
                        There are no expense claims waiting for approval.
                    @endif
                </p>
            </div>
        </div>
    @else
        <div class="approval-list">
            @foreach ($claims as $claim)
                @php
                    $employee = $claim->employee;
                    $stage = $stages[$claim->id];
                    $facts = $context[$claim->id];
                    $tone = $categoryTone($claim->category?->name);
                    $waited = $claim->created_at ? (int) $claim->created_at->copy()->startOfDay()->diffInDays(today()) : 0;
                    $spentAgo = (int) $claim->expense_date->diffInDays(today());
                    $isLarge = $largeFrom !== null && (float) $claim->amount >= $largeFrom;
                    // Well above what this person normally claims for this kind of thing.
                    $unusual = $facts['usual'] !== null && $facts['usual'] > 0 && (float) $claim->amount >= $facts['usual'] * 2;
                @endphp
                <article class="approval-card tone-{{ $tone }} {{ $waited > 7 ? 'is-urgent' : '' }}">
                    @if ($canReview && $actable > 1 && $stage['can_act'])
                        <input type="checkbox" class="form-check-input approval-select" name="ids[]" value="{{ $claim->id }}" form="bulk-approve-form" data-row-select data-amount="{{ $claim->amount }}" aria-label="Select the claim of {{ $employee?->name }}">
                    @endif

                    <div class="approval-who">
                        <x-avatar :person="$employee" />
                        <div class="min-w-0">
                            <div class="list-user-name">{{ $employee?->name ?? 'Removed employee' }}</div>
                            <div class="list-user-meta">{{ $employee?->department?->name ?? $employee?->employee_code }}</div>
                            <div class="list-subtext mt-1"><i class="ti ti-clock"></i> claimed {{ $claim->created_at?->diffForHumans() ?? 'earlier' }}</div>
                        </div>
                    </div>

                    <div class="approval-leave">
                        <span class="tag tone-{{ $tone }}"><i class="ti ti-receipt"></i>{{ $claim->category?->name ?? 'Removed category' }}</span>
                        <div class="approval-dates">
                            <span class="when-block tone-{{ $tone }}"><span class="when-block-month">{{ $claim->expense_date->format('M') }}</span><span class="when-block-day">{{ $claim->expense_date->format('j') }}</span></span>
                            <span class="approval-days">{{ $money($claim->amount) }}</span>
                        </div>
                        @if ($waited > 7)
                            <span class="countdown tone-danger"><i class="ti ti-alert-triangle"></i> Waiting {{ $waited }}d</span>
                        @elseif ($waited > 2)
                            <span class="countdown tone-amber"><i class="ti ti-clock"></i> Waiting {{ $waited }}d</span>
                        @else
                            <span class="countdown tone-slate">Spent {{ $spentAgo === 0 ? 'today' : $spentAgo.'d ago' }}</span>
                        @endif
                    </div>

                    <div class="approval-context">
                        @if ($claim->description)
                            <blockquote class="approval-reason" title="{{ $claim->description }}">“{{ Str::limit($claim->description, 140) }}”</blockquote>
                        @else
                            <blockquote class="approval-reason text-muted">No description given.</blockquote>
                        @endif
                        <div class="approval-facts">
                            @if ($facts['same_day']->isNotEmpty())
                                <span class="approval-fact is-bad" title="{{ $facts['same_day']->map(fn ($o) => $money($o->amount).' ('.$o->status.')')->implode(', ') }}">
                                    <i class="ti ti-copy"></i> {{ $facts['same_day']->count() }} other {{ $claim->category?->name }} {{ Str::plural('claim', $facts['same_day']->count()) }} on the same day
                                </span>
                            @endif
                            @if ($unusual)
                                <span class="approval-fact is-warn"><i class="ti ti-trending-up"></i> {{ round((float) $claim->amount / $facts['usual'], 1) }}× their usual {{ $money(round($facts['usual'])) }} for this</span>
                            @elseif ($isLarge)
                                <span class="approval-fact is-warn"><i class="ti ti-trending-up"></i> Among the largest claims waiting</span>
                            @endif
                            <span class="approval-fact" title="Other claims with an expense date in {{ $claim->expense_date->format('F Y') }}">
                                <i class="ti ti-calendar-event"></i> {{ $facts['month_count'] ? $facts['month_count'].' other '.Str::plural('claim', $facts['month_count']).' in '.$claim->expense_date->format('F').' · '.$money($facts['month_amount']) : 'Only claim in '.$claim->expense_date->format('F') }}
                            </span>
                            @if ($spentAgo > 60)
                                <span class="approval-fact is-warn"><i class="ti ti-history"></i> Spent {{ round($spentAgo / 30) }} months ago</span>
                            @endif
                            @if ($stage['required'] > 1)
                                <span class="approval-fact {{ $stage['can_act'] ? '' : 'is-good' }}"><i class="ti ti-sitemap"></i> Level {{ min($stage['done'] + 1, $stage['required']) }} of {{ $stage['required'] }}{{ $stage['waiting'] ? ' · waiting for '.$stage['waiting'] : '' }}</span>
                            @elseif ($stage['waiting'])
                                <span class="approval-fact"><i class="ti ti-sitemap"></i> Reports to {{ $stage['waiting'] }}</span>
                            @endif
                        </div>
                    </div>

                    @if ($canReview)
                        <div class="approval-actions">
                            @if ($stage['can_act'])
                                <form action="{{ route('admin.expense-approval.approve', $claim) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm review-btn is-approve w-100"><i class="ti ti-check"></i> Approve</button>
                                </form>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm review-btn is-reject w-100" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                        <i class="ti ti-x"></i> Reject
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                        <form action="{{ route('admin.expense-approval.reject', $claim) }}" method="POST">
                                            @csrf
                                            <label class="form-label font-12 fw-semibold">Reason for rejecting</label>
                                            <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="{{ $employee?->name }} is told this" required></textarea>
                                            <div class="quick-chips mt-0 mb-2">
                                                @foreach (['No receipt', 'Not a business expense', 'Above the allowed limit', 'Already claimed'] as $preset)
                                                    <button type="button" class="quick-chip" onclick="this.closest('form').querySelector('textarea').value = this.textContent">{{ $preset }}</button>
                                                @endforeach
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-danger w-100"><i class="ti ti-x font-14 me-1"></i> Reject claim</button>
                                        </form>
                                    </div>
                                </div>
                            @elseif ($stage['own'])
                                <span class="font-12 text-muted text-center">Your own claim. Someone else has to review it.</span>
                            @else
                                <span class="font-12 text-muted text-center">You have approved this. {{ $stage['waiting'] ? 'Waiting for '.$stage['waiting'].'.' : '' }}</span>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        @if ($claims->hasPages())
            <div class="list-footer job-grid-footer">
                <div class="ms-auto">{{ $claims->links() }}</div>
            </div>
        @endif
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var all = document.getElementById('select-all');
            if (!all) return;
            var rows = Array.prototype.slice.call(document.querySelectorAll('[data-row-select]'));
            var action = document.querySelector('[data-bulk-action]');
            var summary = document.getElementById('bulk-summary');
            var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); };
            function refresh() {
                var picked = rows.filter(function (box) { return box.checked; });
                var total = picked.reduce(function (sum, box) { return sum + (parseFloat(box.dataset.amount) || 0); }, 0);
                action.disabled = picked.length === 0;
                summary.textContent = picked.length ? picked.length + ' selected · ' + fmt(total) : 'Select claims';
                all.checked = picked.length > 0 && picked.length === rows.length;
                all.indeterminate = picked.length > 0 && picked.length < rows.length;
                rows.forEach(function (box) { box.closest('.approval-card').classList.toggle('is-selected', box.checked); });
            }
            all.addEventListener('change', function () { rows.forEach(function (box) { box.checked = all.checked; }); refresh(); });
            rows.forEach(function (box) { box.addEventListener('change', refresh); });
            refresh();
        })();
    </script>
@endpush
