@extends('layouts.admin')

@section('title', 'Expense Reports')

@php
    use App\Models\ExpenseClaim;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $query = array_filter([
        'year' => $state['year'], 'month' => $state['month'], 'department_id' => $state['department_id'],
        'status' => $state['status'] === 'spend' ? null : $state['status'], 'sort' => $state['sort'] === 'date' ? null : $state['sort'],
    ], fn ($v) => $v !== null && $v !== '');
    $pageUrl = fn (array $overrides = []) => route('admin.expense-reports.index', array_filter(array_merge($query, $overrides), fn ($v) => $v !== null && $v !== ''));
    $statusMeta = ['pending' => ['amber', 'ti-clock'], 'approved' => ['indigo', 'ti-circle-check'], 'rejected' => ['rose', 'ti-x'], 'reimbursed' => ['success', 'ti-cash']];
    $statusOptions = ['spend' => 'Accepted claims', 'all' => 'All claims'] + ExpenseClaim::STATUSES;
    $sorts = ['date' => 'Newest', 'amount' => 'Amount', 'name' => 'A–Z'];
    $monthName = $state['month'] ? \Illuminate\Support\Carbon::create(2000, $state['month'], 1)->format('F') : null;
    $period = $state['year'] ? 'in '.($monthName ? $monthName.' ' : '').$state['year'] : 'so far';
    $has = $totals['claims'] > 0;
    $maxCategory = max(1, (float) $byCategory->max('amount'));
    $maxMonth = max(1, (float) $byMonth->max('amount'));
    $paidPct = $totals['spend'] > 0 ? (int) round($totals['paid'] / $totals['spend'] * 100) : null;
    // The same colour for the same category everywhere.
    $tones = ['indigo', 'teal', 'amber', 'rose', 'success', 'slate'];
    $categoryTone = fn (?string $name) => $tones[abs(crc32((string) $name)) % count($tones)];
    $showsMoney = in_array($state['status'], ['spend', 'approved', 'reimbursed'], true);
@endphp

@section('content')
    <x-page-title title="Expense Reports" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-indigo" aria-hidden="true"><i class="ti ti-report-analytics font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $state['year'] ? 'Expenses in '.($monthName ? $monthName.' ' : '').$state['year'] : 'All expenses to date' }}{{ $state['department_id'] && isset($departments[$state['department_id']]) ? ' · '.$departments[$state['department_id']] : '' }}</div>
            <div class="record-header-meta">
                <span>{{ ($statusCounts['approved'] ?? 0) + ($statusCounts['reimbursed'] ?? 0) }} accepted · {{ $statusCounts['pending'] ?? 0 }} pending · {{ $statusCounts['rejected'] ?? 0 }} rejected</span>
                @if ($totals['owed_count'])
                    <span class="status-dot status-dot-warning">{{ $money($totals['owed']) }} approved but not paid back yet</span>
                @endif
            </div>
        </div>
        <div class="record-header-actions">
            <form method="GET" action="{{ route('admin.expense-reports.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
                @if ($years->isNotEmpty() || $state['year'])
                    <select name="year" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Year">
                        <option value="">All time</option>
                        @foreach ($years->push($state['year'])->filter()->unique()->sortDesc() as $year)
                            <option value="{{ $year }}" @selected($state['year'] === (int) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                @endif
                @if ($state['year'])
                    <select name="month" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Month">
                        <option value="">Whole year</option>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" @selected($state['month'] === $m)>{{ \Illuminate\Support\Carbon::create(2000, $m, 1)->format('F') }}</option>
                        @endforeach
                    </select>
                @endif
                @if ($departments->count() > 1 || $state['department_id'])
                    <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                        <option value="">All departments</option>
                        @foreach ($departments as $id => $name)
                            <option value="{{ $id }}" @selected($state['department_id'] === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="status" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Claims shown">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($state['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @if ($state['sort'] !== 'date')
                    <input type="hidden" name="sort" value="{{ $state['sort'] }}">
                @endif
            </form>
            @if ($rows->isNotEmpty())
                <a href="{{ route('admin.expense-reports.export', $query) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-download font-14 me-1"></i> Export CSV</a>
            @endif
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Expenses accepted" :value="$has ? $money($totals['spend']) : '—'" icon="ti-receipt" tone="indigo" :hint="$has ? $totals['claims'].' '.Str::plural('claim', $totals['claims']).' from '.$totals['people'].' '.Str::plural('person', $totals['people']) : 'No accepted claims '.$period" />
        <x-kpi-card label="Paid back" :value="$has ? $money($totals['paid']) : '—'" icon="ti-cash" :tone="$paidPct === 100 ? 'success' : 'teal'" :hint="$has ? ($totals['owed_count'] ? $paidPct.'% · '.$money($totals['owed']).' still owed on '.$totals['owed_count'].' '.Str::plural('claim', $totals['owed_count']) : 'Everything accepted has been paid') : 'Nothing to pay back'" />
        <x-kpi-card label="Average claim" :value="$totals['per_claim'] ? $money(round($totals['per_claim'])) : '—'" icon="ti-calculator" tone="amber" :hint="$totals['per_person'] ? $money(round($totals['per_person'])).' per person claiming' : 'Nothing to average yet'" />
        <x-kpi-card label="Rejected" :value="$totals['rejected_count']" icon="ti-x" :tone="$totals['rejected_count'] ? 'rose' : 'success'" :hint="$totals['rejected_count'] ? $money($totals['rejected']).' not paid · '.$totals['rejected_rate'].'% of decided claims' : ($totals['pending_count'] ? $totals['pending_count'].' still waiting for a decision' : 'No claim was turned down')" />
    </div>

    @if ($has)
        <div class="row g-3 mb-3">
            <div class="col-xl-4">
                <div class="card side-list tone-indigo mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-chart-bar"></i></span>
                        <span class="fw-semibold">What it was spent on</span>
                    </div>
                    <div class="card-body">
                        @foreach ($byCategory->take(7) as $item)
                            <div class="rating-row" title="{{ $item['claims'] }} {{ Str::plural('claim', $item['claims']) }} · {{ round($item['amount'] / $totals['spend'] * 100) }}% of the total">
                                <span class="rating-row-label text-truncate" style="flex: 0 0 130px; width: 130px; max-width: 130px">{{ $item['name'] }}</span>
                                <div class="mini-progress-bar is-toned tone-{{ $categoryTone($item['name']) }}"><span style="width: {{ round($item['amount'] / $maxCategory * 100) }}%"></span></div>
                                <span class="rating-row-count" style="min-width: 56px; text-align: right">{{ $money($item['amount']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-chart-line"></i></span>
                        <span class="fw-semibold">{{ $state['month'] ? 'Who claimed most' : 'Month by month' }}</span>
                    </div>
                    @if ($state['month'])
                        @foreach ($claimants as $i => $person)
                            <div class="side-list-item">
                                <span class="rank-badge rank-{{ $i + 1 }}">{{ $i + 1 }}</span>
                                <x-avatar :person="$person['employee']" />
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold">{{ $person['name'] }}</div>
                                    <div class="font-12 text-muted">{{ $person['claims'] }} {{ Str::plural('claim', $person['claims']) }}</div>
                                </div>
                                <span class="fw-semibold">{{ $money($person['amount']) }}</span>
                            </div>
                        @endforeach
                    @else
                        <div class="card-body">
                            @foreach ($byMonth as $m)
                                <div class="rating-row">
                                    <span class="rating-row-label" style="flex: 0 0 52px; width: 52px">{{ $m['label'] }}</span>
                                    <div class="mini-progress-bar is-toned tone-teal"><span style="width: {{ round($m['amount'] / $maxMonth * 100) }}%"></span></div>
                                    <span class="rating-row-count" style="min-width: 56px; text-align: right">{{ $m['amount'] > 0 ? $money($m['amount']) : '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card side-list tone-success mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-award"></i></span>
                        <span class="fw-semibold">{{ $state['month'] ? 'By department' : 'Who claimed most' }}</span>
                    </div>
                    @if ($state['month'])
                        @foreach ($byDepartment->take(5) as $dept)
                            <div class="side-list-item">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold">{{ $dept['name'] }}</div>
                                    <div class="font-12 text-muted">{{ $dept['claims'] }} {{ Str::plural('claim', $dept['claims']) }} · {{ $dept['people'] }} {{ Str::plural('person', $dept['people']) }}</div>
                                </div>
                                <span class="fw-semibold">{{ $money($dept['amount']) }}</span>
                            </div>
                        @endforeach
                    @else
                        @foreach ($claimants as $i => $person)
                            <div class="side-list-item">
                                <span class="rank-badge rank-{{ $i + 1 }}">{{ $i + 1 }}</span>
                                <x-avatar :person="$person['employee']" />
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold">{{ $person['name'] }}</div>
                                    <div class="font-12 text-muted">{{ $person['claims'] }} {{ Str::plural('claim', $person['claims']) }}</div>
                                </div>
                                <span class="fw-semibold">{{ $money($person['amount']) }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        @if (! $state['month'] && $byDepartment->count() > 1)
            <div class="card list-view mb-3">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-sitemap"></i> By department</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Department</th>
                                <th class="text-center">Claims</th>
                                <th class="text-center">People claiming</th>
                                <th class="text-end">Accepted</th>
                                <th style="min-width: 190px">Share of the total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($byDepartment as $dept)
                                @php
                                    $share = (int) round($dept['amount'] / $totals['spend'] * 100);
                                @endphp
                                <tr class="{{ $state['department_id'] && $dept['id'] === $state['department_id'] ? 'table-active' : '' }}">
                                    <td>
                                        @if ($dept['id'])
                                            <a href="{{ $pageUrl(['department_id' => $state['department_id'] === $dept['id'] ? null : $dept['id']]) }}" class="fw-semibold" title="Show only this department">{{ $dept['name'] }}</a>
                                        @else
                                            <span class="fw-semibold">{{ $dept['name'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $dept['claims'] }}</td>
                                    <td class="text-center">{{ $dept['people'] }}</td>
                                    <td class="text-end fw-semibold">{{ $money($dept['amount']) }}</td>
                                    <td><div class="mini-progress tone-indigo"><div class="mini-progress-bar is-toned"><span style="width: {{ $share }}%"></span></div><span class="mini-progress-label">{{ $share }}%</span></div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-receipt"></i> {{ $statusOptions[$state['status']] ?? 'Claims' }} <span class="list-tab-count">{{ $rows->count() }}</span></div>
            @if ($rows->isNotEmpty())
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" id="report-search" class="form-control form-control-sm" placeholder="Search name, category or description" aria-label="Search the report">
                    </div>
                    <div class="segmented" role="group" aria-label="Sort by">
                        @foreach ($sorts as $value => $label)
                            <a href="{{ $pageUrl(['sort' => $value === 'date' ? null : $value]) }}" class="segmented-item {{ $state['sort'] === $value ? 'active' : '' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0" id="report-table">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Expense</th>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $claim = $row['claim'];
                            $meta = $statusMeta[$row['status']] ?? ['slate', 'ti-circle'];
                        @endphp
                        <tr data-search="{{ mb_strtolower($row['name'].' '.$row['category'].' '.$claim->description) }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$claim->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $row['name'] }}</div>
                                        <div class="list-user-meta">{{ $row['department'] ?? $claim->employee?->employee_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="tag tone-{{ $categoryTone($row['category']) }}">{{ $row['category'] }}</span>
                                @if ($claim->description)
                                    <div class="payslip-parts text-wrap" title="{{ $claim->description }}">{{ Str::limit($claim->description, 70) }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $claim->expense_date->format('M j, Y') }}</td>
                            <td class="text-end text-nowrap fw-semibold">{{ $money($row['amount']) }}</td>
                            <td>
                                <span class="tag tone-{{ $meta[0] }}"><i class="ti {{ $meta[1] }}"></i>{{ ExpenseClaim::STATUSES[$row['status']] ?? $row['status'] }}</span>
                                <div class="payslip-parts text-wrap">
                                    @if ($row['status'] === 'reimbursed')
                                        {{ $claim->reimbursed_at ? 'paid '.$claim->reimbursed_at->format('M j, Y') : '' }}{{ $row['days_to_pay'] !== null ? ' · '.($row['days_to_pay'] === 0 ? 'same day as approval' : $row['days_to_pay'].' '.Str::plural('day', $row['days_to_pay']).' after approval') : '' }}
                                    @elseif ($row['status'] === 'rejected')
                                        {{ $claim->remarks ? Str::limit($claim->remarks, 60) : ($claim->reviewedBy ? 'by '.$claim->reviewedBy->name : '') }}
                                    @elseif ($row['status'] === 'approved')
                                        {{ collect([$claim->reviewedBy ? 'by '.$claim->reviewedBy->name : null, $claim->reviewed_at ? 'on '.$claim->reviewed_at->format('M j') : null])->filter()->implode(' ') }}
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-report-analytics font-24 d-block mb-2"></i>
                                @if ($state['department_id'] || $state['status'] !== 'spend' || $state['year'])
                                    <div class="fw-semibold text-body mb-1">Nothing matches</div>
                                    <span class="font-13"><a href="{{ route('admin.expense-reports.index') }}">Show all accepted claims</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No accepted claims yet</div>
                                    <span class="font-13">{{ $totals['pending_count'] ? $totals['pending_count'].' '.Str::plural('claim', $totals['pending_count']).' still waiting for approval.' : 'The report fills in once expense claims are approved.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    <tr id="report-none" hidden><td colspan="5" class="text-center text-muted py-4">Nothing matches your search.</td></tr>
                </tbody>
                @if ($rows->count() > 1)
                    <tfoot>
                        <tr class="report-total">
                            <td class="fw-semibold" colspan="3">Total · {{ $rows->count() }} claims{{ $showsMoney ? '' : ' (not all of this was or will be paid)' }}</td>
                            <td class="text-end fw-bold">{{ $money($totals['shown']) }}</td>
                            <td class="fw-semibold">{{ $state['status'] === 'spend' && $totals['owed_count'] ? $money($totals['paid']).' paid · '.$money($totals['owed']).' owed' : '' }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var input = document.getElementById('report-search');
            if (!input) return;
            var rows = Array.prototype.slice.call(document.querySelectorAll('#report-table tbody tr[data-search]'));
            input.addEventListener('input', function () {
                var needle = input.value.trim().toLowerCase(), shown = 0;
                rows.forEach(function (row) {
                    var match = row.dataset.search.indexOf(needle) !== -1;
                    row.hidden = !match;
                    shown += match ? 1 : 0;
                });
                document.getElementById('report-none').hidden = shown > 0;
            });
        })();
    </script>
@endpush
