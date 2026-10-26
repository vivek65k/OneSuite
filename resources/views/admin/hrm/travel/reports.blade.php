@extends('layouts.admin')

@section('title', 'Travel Reports')

@php
    use App\Models\TravelRequest;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $query = array_filter([
        'year' => $state['year'], 'department_id' => $state['department_id'],
        'status' => $state['status'] === 'approved' ? null : $state['status'], 'sort' => $state['sort'] === 'date' ? null : $state['sort'],
    ], fn ($v) => $v !== null && $v !== '');
    $pageUrl = fn (array $overrides = []) => route('admin.travel-reports.index', array_filter(array_merge($query, $overrides), fn ($v) => $v !== null && $v !== ''));
    $statusMeta = ['pending' => ['amber', 'ti-clock'], 'approved' => ['success', 'ti-circle-check'], 'rejected' => ['rose', 'ti-x']];
    $sorts = ['date' => 'Newest', 'cost' => 'Cost', 'over' => 'Over estimate', 'name' => 'A–Z'];
    $statusOptions = ['approved' => 'Approved trips', 'all' => 'All requests', 'pending' => 'Pending', 'rejected' => 'Rejected'];
    $modeIcon = ['flight' => 'ti-plane', 'train' => 'ti-train', 'bus' => 'ti-bus', 'car' => 'ti-car', 'other' => 'ti-map-pin'];
    $has = $totals['trips'] > 0;
    $period = $state['year'] ? 'in '.$state['year'] : 'so far';
    $maxSpend = max(1, (float) $spend->max('amount'));
    $dates = fn ($t) => $t->start_date->isSameDay($t->end_date)
        ? $t->start_date->format('M j, Y')
        : $t->start_date->format('M j').' – '.$t->end_date->format($t->start_date->isSameMonth($t->end_date) ? 'j, Y' : 'M j, Y');
    $usedTone = fn ($pct) => $pct === null ? 'slate' : ($pct > 100 ? 'rose' : ($pct > 90 ? 'amber' : 'success'));
@endphp

@section('content')
    <x-page-title title="Travel Reports" />

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-indigo" aria-hidden="true"><i class="ti ti-report-analytics font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $state['year'] ? 'Business travel in '.$state['year'] : 'All business travel to date' }}{{ $state['department_id'] && isset($departments[$state['department_id']]) ? ' · '.$departments[$state['department_id']] : '' }}</div>
            <div class="record-header-meta">
                <span>{{ $statusCounts['approved'] ?? 0 }} approved · {{ $statusCounts['pending'] ?? 0 }} pending · {{ $statusCounts['rejected'] ?? 0 }} rejected</span>
                @if ($totals['unsettled'])
                    <span class="status-dot status-dot-warning">{{ $totals['unsettled'] }} finished {{ Str::plural('trip', $totals['unsettled']) }} not settled yet</span>
                @endif
            </div>
        </div>
        <div class="record-header-actions">
            <form method="GET" action="{{ route('admin.travel-reports.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
                @if ($years->isNotEmpty() || $state['year'])
                    <select name="year" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Year">
                        <option value="">All time</option>
                        @foreach ($years->push($state['year'])->filter()->unique()->sortDesc() as $year)
                            <option value="{{ $year }}" @selected($state['year'] === (int) $year)>{{ $year }}</option>
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
                <select name="status" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Requests shown">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($state['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @if ($state['sort'] !== 'date')
                    <input type="hidden" name="sort" value="{{ $state['sort'] }}">
                @endif
            </form>
            @if ($rows->isNotEmpty())
                <a href="{{ route('admin.travel-reports.export', $query) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-download font-14 me-1"></i> Export CSV</a>
            @endif
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Trips taken" :value="$totals['trips']" icon="ti-plane" tone="indigo" :hint="$has ? $totals['days'].' travel '.Str::plural('day', $totals['days']).' by '.$totals['people'].' '.Str::plural('person', $totals['people']) : 'No approved trip '.$period" />
        <x-kpi-card label="Travel cost" :value="$totals['cost'] > 0 ? $money($totals['cost']) : '—'" icon="ti-receipt" tone="teal" :hint="$totals['cost'] > 0 ? $money($totals['booked']).' booked · '.$money($totals['expenses']).' in expenses' : 'Nothing booked or claimed '.$period" />
        <x-kpi-card label="Average per trip" :value="$totals['per_trip'] ? $money(round($totals['per_trip'])) : '—'" icon="ti-calculator" tone="amber" :hint="$totals['per_day'] ? $money(round($totals['per_day'])).' per travel day' : 'Nothing to average yet'" />
        <x-kpi-card label="Against estimates" :value="$totals['estimate_used'] !== null ? $totals['estimate_used'].'%' : '—'" icon="ti-target" :tone="$usedTone($totals['estimate_used'])" :hint="$totals['estimate_used'] !== null ? ($totals['over_trips'] ? $totals['over_trips'].' '.Str::plural('trip', $totals['over_trips']).' went over, by '.$money($totals['over_amount']) : 'of the '.$money($totals['estimated']).' estimated · none over') : 'No trip had an estimate'" />
    </div>

    @if ($has)
        <div class="row g-3 mb-3">
            <div class="col-xl-4">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-chart-bar"></i></span>
                        <span class="fw-semibold">Where the money went</span>
                    </div>
                    <div class="card-body">
                        @forelse ($spend->take(7) as $item)
                            <div class="rating-row" title="{{ $totals['cost'] > 0 ? round($item['amount'] / $totals['cost'] * 100) : 0 }}% of the travel cost">
                                <span class="rating-row-label text-truncate" style="flex: 0 0 130px; width: 130px; max-width: 130px">{{ $item['label'] }}</span>
                                <div class="mini-progress-bar is-toned tone-{{ $item['kind'] === 'booked' ? 'indigo' : 'teal' }}"><span style="width: {{ round($item['amount'] / $maxSpend * 100) }}%"></span></div>
                                <span class="rating-row-count" style="min-width: 56px; text-align: right">{{ $money($item['amount']) }}</span>
                            </div>
                        @empty
                            <div class="font-13 text-muted">Nothing has been booked or claimed on these trips.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card side-list tone-success mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-award"></i></span>
                        <span class="fw-semibold">Who travelled most</span>
                    </div>
                    @foreach ($travellers as $i => $person)
                        <div class="side-list-item">
                            <span class="rank-badge rank-{{ $i + 1 }}">{{ $i + 1 }}</span>
                            <x-avatar :person="$person['employee']" />
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold">{{ $person['name'] }}</div>
                                <div class="font-12 text-muted">{{ $person['trips'] }} {{ Str::plural('trip', $person['trips']) }} · {{ $person['days'] }} {{ Str::plural('day', $person['days']) }}</div>
                            </div>
                            <span class="fw-semibold" title="Travel cost">{{ $money($person['cost']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card side-list tone-indigo mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-map-pin"></i></span>
                        <span class="fw-semibold">Where people went</span>
                    </div>
                    @foreach ($destinations as $place)
                        <div class="side-list-item">
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold">{{ $place['name'] }}</div>
                                <div class="font-12 text-muted">{{ $place['trips'] }} {{ Str::plural('trip', $place['trips']) }}</div>
                            </div>
                            <span class="fw-semibold" title="Travel cost">{{ $place['cost'] > 0 ? $money($place['cost']) : '—' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($byDepartment->count() > 1)
            <div class="card list-view mb-3">
                <div class="list-toolbar">
                    <div class="list-toolbar-title"><i class="ti ti-sitemap"></i> By department</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover list-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Department</th>
                                <th class="text-center">Trips</th>
                                <th class="text-center">Travellers</th>
                                <th class="text-end">Travel cost</th>
                                <th style="min-width: 190px">Of the estimates</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($byDepartment as $dept)
                                @php
                                    $used = $dept['estimate'] > 0 ? (int) round($dept['cost'] / $dept['estimate'] * 100) : null;
                                @endphp
                                <tr class="{{ $state['department_id'] && $dept['id'] === $state['department_id'] ? 'table-active' : '' }}">
                                    <td>
                                        @if ($dept['id'])
                                            <a href="{{ $pageUrl(['department_id' => $state['department_id'] === $dept['id'] ? null : $dept['id']]) }}" class="fw-semibold" title="Show only this department">{{ $dept['name'] }}</a>
                                        @else
                                            <span class="fw-semibold">{{ $dept['name'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $dept['trips'] }}</td>
                                    <td class="text-center">{{ $dept['people'] }}</td>
                                    <td class="text-end fw-semibold">{{ $money($dept['cost']) }}</td>
                                    <td>
                                        @if ($used !== null)
                                            <div class="mini-progress tone-{{ $usedTone($used) }}"><div class="mini-progress-bar is-toned"><span style="width: {{ min(100, $used) }}%"></span></div><span class="mini-progress-label">{{ $used }}%</span></div>
                                        @else
                                            <span class="text-muted">No estimates</span>
                                        @endif
                                    </td>
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
            <div class="list-toolbar-title"><i class="ti ti-plane"></i> {{ $statusOptions[$state['status']] ?? 'Trips' }} <span class="list-tab-count">{{ $rows->count() }}</span></div>
            @if ($rows->isNotEmpty())
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" id="report-search" class="form-control form-control-sm" placeholder="Search name or place" aria-label="Search the report">
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
                        <th>Trip</th>
                        <th class="text-end">Estimated</th>
                        <th class="text-end" title="Tickets and stay paid by the company">Booked</th>
                        <th class="text-end" title="Approved expenses the employee paid">Expenses</th>
                        <th class="text-end">Trip cost</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $tr = $row['request'];
                            $approved = $row['status'] === 'approved';
                            $meta = $statusMeta[$row['status']] ?? ['slate', 'ti-circle'];
                        @endphp
                        <tr data-search="{{ mb_strtolower($row['name'].' '.$tr->from_location.' '.$tr->to_location.' '.$tr->purpose) }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$tr->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $row['name'] }}</div>
                                        <div class="list-user-meta">{{ $row['department'] ?? $tr->employee?->employee_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold"><i class="ti {{ $modeIcon[$tr->mode_of_travel] ?? 'ti-map-pin' }} text-muted" title="{{ TravelRequest::MODES[$tr->mode_of_travel] ?? $tr->mode_of_travel }}"></i> {{ $tr->from_location }} <i class="ti ti-arrow-right text-muted font-12"></i> {{ $tr->to_location }}</div>
                                <div class="payslip-parts text-wrap" title="{{ $tr->purpose }}">{{ $dates($tr) }} · {{ $row['days'] }} {{ Str::plural('day', $row['days']) }} · {{ Str::limit($tr->purpose, 36) }}</div>
                            </td>
                            <td class="text-end text-nowrap">{{ $row['estimate'] !== null ? $money($row['estimate']) : '—' }}</td>
                            <td class="text-end text-nowrap">{{ $approved && $row['booked'] > 0 ? $money($row['booked']) : '—' }}</td>
                            <td class="text-end text-nowrap">
                                {{ $approved && $row['expenses'] > 0 ? $money($row['expenses']) : '—' }}
                                @if ($approved && $row['pending_expenses'])
                                    <div class="payslip-parts">{{ $row['pending_expenses'] }} pending</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($approved)
                                    <span class="fw-semibold">{{ $money($row['cost']) }}</span>
                                    @if ($row['over'] !== null && $row['cost'] > 0)
                                        <div class="payslip-parts {{ $row['over'] > 0 ? 'text-danger' : '' }}">{{ $row['over'] > 0 ? $money($row['over']).' over' : ($row['over'] < 0 ? $money(abs($row['over'])).' under' : 'on estimate') }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($approved && $row['settled'])
                                    <span class="tag tone-success"><i class="ti ti-wallet"></i>Settled</span>
                                    <div class="payslip-parts">{{ $money($row['reimbursement']) }} paid back{{ $row['settled_on'] ? ' · '.$row['settled_on']->format('M j') : '' }}</div>
                                @elseif ($approved)
                                    <span class="tag tone-{{ $tr->end_date->lt(today()) ? 'amber' : 'indigo' }}"><i class="ti {{ $tr->end_date->lt(today()) ? 'ti-clock' : 'ti-plane' }}"></i>{{ $tr->end_date->lt(today()) ? 'Not settled' : ($tr->start_date->lte(today()) ? 'Away now' : 'Upcoming') }}</span>
                                @else
                                    <span class="tag tone-{{ $meta[0] }}"><i class="ti {{ $meta[1] }}"></i>{{ TravelRequest::STATUSES[$row['status']] ?? $row['status'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-report-analytics font-24 d-block mb-2"></i>
                                @if ($state['department_id'] || $state['status'] !== 'approved' || $state['year'])
                                    <div class="fw-semibold text-body mb-1">Nothing matches</div>
                                    <span class="font-13"><a href="{{ route('admin.travel-reports.index') }}">Show all approved trips</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No approved trips yet</div>
                                    <span class="font-13">{{ ($statusCounts['pending'] ?? 0) ? ($statusCounts['pending']).' '.Str::plural('request', $statusCounts['pending']).' still waiting for approval.' : 'The report fills in once travel requests are approved.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    <tr id="report-none" hidden><td colspan="7" class="text-center text-muted py-4">Nothing matches your search.</td></tr>
                </tbody>
                @if ($totals['trips'] > 1)
                    <tfoot>
                        <tr class="report-total">
                            <td class="fw-semibold" colspan="2">Total · {{ $totals['trips'] }} approved {{ Str::plural('trip', $totals['trips']) }} · {{ $totals['days'] }} days</td>
                            <td class="text-end fw-bold">{{ $totals['estimated'] > 0 ? $money($totals['estimated']) : '—' }}</td>
                            <td class="text-end fw-bold">{{ $money($totals['booked']) }}</td>
                            <td class="text-end fw-bold">{{ $money($totals['expenses']) }}</td>
                            <td class="text-end fw-bold">{{ $money($totals['cost']) }}</td>
                            <td class="fw-semibold">{{ $totals['settled'] ? $money($totals['reimbursed']).' paid back' : '' }}</td>
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
