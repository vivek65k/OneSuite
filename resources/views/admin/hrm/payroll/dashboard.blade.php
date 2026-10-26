@extends('layouts.admin')

@section('title', 'Payroll Dashboard')

@php
    use App\Models\PayrollRun;
    use App\Support\Dashboard\DashboardKit as Kit;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $today = today();
    $current = $data['current'];
    $latest = $data['latest'];
    $breakdown = $data['breakdown'];
    $checklist = $data['checklist'];
    $structures = $data['structures'];
    $loans = $data['loans'];
    $state = ! $current ? 'none' : ($current->status === 'paid' ? 'paid' : 'processed');
    $stateMeta = [
        'none' => ['tone' => 'amber', 'icon' => 'ti-clock', 'label' => 'Not run yet'],
        'processed' => ['tone' => 'indigo', 'icon' => 'ti-loader', 'label' => 'Processed, not paid'],
        'paid' => ['tone' => 'success', 'icon' => 'ti-circle-check', 'label' => 'Paid'],
    ][$state];
    $money = fn ($n) => Kit::money((float) $n);
    $previous = $data['runs']->get(1);
    $change = $latest && $previous && (float) $previous->total_net > 0
        ? round(((float) $latest->total_net - (float) $previous->total_net) / (float) $previous->total_net * 100, 1) : null;
    $pendingInputs = $data['inputs']->sum('count');
    $canRun = auth()->user()->can('create', PayrollRun::class);
@endphp

@section('content')
    <x-page-title title="Payroll Dashboard" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif

    {{-- This month --}}
    <div class="section-hero hrm-hero tone-{{ $stateMeta['tone'] }}">
        <span class="section-hero-icon"><i class="ti ti-report-money"></i></span>
        <div class="section-hero-text">
            <h4>Payroll · {{ $today->format('F Y') }}</h4>
            <p>
                @if ($branchName){{ $branchName }} · @endif
                @if ($state === 'none')
                    {{ $data['days_left'] }} {{ Str::plural('day', $data['days_left']) }} left this month
                @elseif ($state === 'processed')
                    Processed {{ $current->run_date ? Carbon::parse($current->run_date)->format('M j') : '' }} · {{ $current->employee_count }} {{ Str::plural('payslip', $current->employee_count) }} waiting to be paid
                @else
                    {{ $current->employee_count }} {{ Str::plural('person', $current->employee_count) }} paid · {{ $money($current->total_net) }} net
                @endif
            </p>
            <div class="hero-chips">
                <span class="highlight-chip tone-{{ $stateMeta['tone'] }}"><i class="ti {{ $stateMeta['icon'] }}"></i> {{ $stateMeta['label'] }}</span>
                @if ($state === 'none' && $checklist['blocked'])
                    <a href="#ready" class="highlight-chip tone-danger"><i class="ti ti-alert-triangle"></i> Not ready to run · fix the checklist</a>
                @elseif ($state === 'none' && $checklist['steps'])
                    <a href="#ready" class="highlight-chip tone-success"><i class="ti ti-circle-check"></i> Ready to run</a>
                @endif
                @if ($pendingInputs)
                    <a href="#inputs" class="highlight-chip tone-amber"><i class="ti ti-adjustments"></i> {{ $pendingInputs }} pay {{ Str::plural('adjustment', $pendingInputs) }} pending</a>
                @endif
            </div>
        </div>
        <div class="d-flex flex-column gap-2 align-items-stretch">
            @if ($current)
                <a href="{{ route('admin.payroll-runs.show', $current) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-report-money font-14 me-1"></i> Open {{ $today->format('F') }} run</a>
                <a href="{{ route('admin.payslips.index') }}" class="btn btn-light btn-sm px-3"><i class="ti ti-file-invoice font-14 me-1"></i> Payslips</a>
            @else
                <a href="{{ route('admin.payroll-runs.index') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-player-play font-14 me-1"></i> {{ $canRun ? 'Run payroll' : 'Payroll runs' }}</a>
                <a href="{{ route('admin.salary-structures.index') }}" class="btn btn-light btn-sm px-3"><i class="ti ti-list-check font-14 me-1"></i> Salary structures</a>
            @endif
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card :label="$latest ? 'Net pay · '.$latest->monthLabel() : 'Net pay'" :value="$latest ? $money($latest->total_net) : '—'" icon="ti-cash" tone="success"
                    :hint="$latest ? ($change !== null ? ($change >= 0 ? '+' : '').$change.'% vs '.$previous->monthLabel() : $latest->employee_count.' payslips') : 'No payroll run yet'"
                    :href="$latest ? route('admin.payroll-runs.show', $latest) : route('admin.payroll-runs.index')" />
        <x-kpi-card label="Employer cost" :value="$breakdown ? $money($breakdown['employer_cost']) : '—'" icon="ti-building-bank" tone="indigo"
                    :hint="$breakdown ? 'Gross + '.$money($breakdown['employer_pf'] + $breakdown['employer_esi']).' PF/ESI' : 'Gross plus employer PF and ESI'" />
        <x-kpi-card label="Paid in {{ $today->year }}" :value="$data['ytd']['runs'] ? $money($data['ytd']['net']) : '—'" icon="ti-calendar-stats" tone="teal"
                    :hint="$data['ytd']['runs'] ? $data['ytd']['runs'].' '.Str::plural('run', $data['ytd']['runs']).' · gross '.$money($data['ytd']['gross']) : 'Nothing paid this year yet'"
                    :href="route('admin.payroll-reports.index')" />
        @if ($loans)
            <x-kpi-card label="Loans outstanding" :value="$loans['count'] ? $money($loans['outstanding']) : '—'" icon="ti-credit-card" tone="amber"
                        :hint="$loans['count'] ? $loans['count'].' active · '.$money($loans['emi']).' recovered monthly' : 'No active loans'" :href="route('admin.loans.index')" />
        @else
            <x-kpi-card label="People on payroll" :value="$data['headcount']" icon="ti-users" tone="amber" hint="Active employees" />
        @endif
    </div>

    <div class="row g-3">
        {{-- Ready to run --}}
        <div class="col-xl-5">
            <div class="card side-list tone-{{ $checklist['blocked'] ? 'rose' : 'success' }} h-100" id="ready">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-list-check"></i></span>
                    <span class="fw-semibold">{{ $state === 'none' ? 'Ready to run '.$today->format('F').'?' : 'Payroll checklist' }}</span>
                    @if ($checklist['steps'])
                        <span class="side-list-count">{{ $checklist['done'] }}/{{ count($checklist['steps']) }}</span>
                    @endif
                </div>
                @forelse ($checklist['steps'] as $step)
                    <a href="{{ route($step['route']) }}" class="side-list-item health-row">
                        <span class="status-dot status-dot-{{ $step['done'] ? 'success' : ($step['blocking'] ? 'danger' : 'warning') }}"></span>
                        <span class="flex-grow-1 font-13 {{ $step['done'] ? '' : 'fw-semibold' }} {{ ! $step['done'] && $step['blocking'] ? 'text-danger' : '' }}">{{ $step['label'] }}</span>
                        <i class="ti ti-arrow-right text-muted"></i>
                    </a>
                @empty
                    <div class="side-list-empty is-neutral"><i class="ti ti-list-check"></i> Nothing to check.</div>
                @endforelse

                @if ($structures && $structures['missing'])
                    <div class="side-list-subhead">Set up a salary structure for</div>
                    @foreach ($structures['people'] as $person)
                        <a href="{{ route('admin.salary-structures.edit', $person) }}" class="side-list-item py-2">
                            <x-avatar :person="$person" />
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold font-13 text-truncate">{{ $person->name }}</div>
                                <div class="list-subtext text-truncate">{{ $person->designation ?: $person->employee_code }}</div>
                            </div>
                            <span class="tag tone-rose">Set up <i class="ti ti-arrow-right"></i></span>
                        </a>
                    @endforeach
                    @if ($structures['missing'] > $structures['people']->count())
                        <a href="{{ route('admin.salary-structures.index') }}" class="side-list-foot">+{{ $structures['missing'] - $structures['people']->count() }} more without a structure</a>
                    @endif
                @endif
            </div>
        </div>

        {{-- Cost trend --}}
        <div class="col-xl-7">
            <div class="card side-list tone-indigo h-100">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-chart-bar"></i></span>
                    <span class="fw-semibold">Payroll cost · last {{ max(1, count($data['trend']['labels'])) }} {{ Str::plural('run', max(1, count($data['trend']['labels']))) }}</span>
                    <a href="{{ route('admin.payroll-reports.index') }}" class="ms-auto font-12 text-decoration-none">Reports <i class="ti ti-arrow-right"></i></a>
                </div>
                @if ($data['trend']['empty'])
                    <div class="empty-state tone-indigo">
                        <span class="empty-state-icon"><i class="ti ti-chart-bar"></i></span>
                        <h5>No payroll has been run yet</h5>
                        <p>Once you run payroll, gross, deductions and net pay for each month show up here.</p>
                        <a href="{{ route('admin.payroll-runs.index') }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-player-play font-14 me-1"></i> {{ $canRun ? 'Run the first payroll' : 'Payroll runs' }}</a>
                    </div>
                @else
                    <div class="card-body pt-2"><div class="chart-box" id="payrollTrend"></div></div>
                @endif
            </div>
        </div>
    </div>

    @if ($breakdown)
        <div class="dash-section-title"><i class="ti ti-cash"></i> {{ $latest->monthLabel() }} at a glance</div>
        <div class="row g-3">
            <div class="col-xl-7">
                <div class="card side-list tone-success h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-chart-donut"></i></span>
                        <span class="fw-semibold">Where the gross pay went</span>
                        <span class="tag tone-{{ $latest->status === 'paid' ? 'success' : 'amber' }} ms-auto">{{ $breakdown['paid'] }}/{{ $breakdown['slips'] }} payslips paid</span>
                    </div>
                    <div class="card-body">
                        <div class="policy-card-stats mb-3">
                            <div><strong>{{ $money($breakdown['gross']) }}</strong><span>Gross</span></div>
                            <div><strong>{{ $money($breakdown['net']) }}</strong><span>Net pay</span></div>
                            <div><strong>{{ $money($breakdown['average_net']) }}</strong><span>Average net</span></div>
                        </div>
                        @if ($breakdown['segments']->isNotEmpty())
                            <div class="stack-bar stack-bar-lg">
                                @foreach ($breakdown['segments'] as $s)
                                    <span class="tone-{{ $s['tone'] }}" style="flex: {{ $s['amount'] }}" title="{{ $s['label'] }}: {{ $money($s['amount']) }}"></span>
                                @endforeach
                            </div>
                            <div class="stack-legend">
                                @foreach ($breakdown['segments'] as $s)
                                    <span><i class="legend-dot tone-{{ $s['tone'] }}"></i>{{ $s['label'] }} <strong>{{ $money($s['amount']) }}</strong>
                                        @if ($breakdown['gross'] > 0)<span class="text-muted">· {{ round($s['amount'] / $breakdown['gross'] * 100) }}%</span>@endif</span>
                                @endforeach
                            </div>
                        @endif
                        <div class="list-subtext mt-2"><i class="ti ti-building-bank"></i> Employer also contributes {{ $money($breakdown['employer_pf']) }} PF and {{ $money($breakdown['employer_esi']) }} ESI on top of gross.</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-sitemap"></i></span>
                        <span class="fw-semibold">Cost by department</span>
                    </div>
                    <div class="ot-leaders dept-bars">
                        @php $maxDept = max(1, (float) $data['departments']->max('gross')); @endphp
                        @foreach ($data['departments'] as $dept)
                            <div class="ot-leader">
                                <span class="ot-leader-body">
                                    <span class="ot-leader-name">{{ $dept['name'] }} <span class="list-subtext">· {{ $dept['people'] }}</span></span>
                                    <span class="week-hours-bar"><span style="width: {{ round($dept['gross'] / $maxDept * 100) }}%"></span></span>
                                </span>
                                <span class="hours-figure">{{ $money($dept['gross']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="dash-section-title"><i class="ti ti-adjustments"></i> Inputs for the next run</div>
    <div class="row g-3">
        {{-- Pending adjustments --}}
        <div class="col-xl-4">
            <div class="card side-list tone-amber h-100" id="inputs">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-adjustments"></i></span>
                    <span class="fw-semibold">Pay adjustments pending</span>
                    @if ($pendingInputs)<span class="side-list-count">{{ $pendingInputs }}</span>@endif
                </div>
                @forelse ($data['inputs'] as $input)
                    <a href="{{ route($input['route']) }}" class="side-list-item attention-row {{ $input['older'] ? 'is-danger' : ($input['count'] ? 'is-warning' : '') }}">
                        <span class="attention-icon tone-{{ $input['tone'] }}"><i class="ti {{ $input['icon'] }}"></i></span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold font-13">{{ $input['label'] }}</div>
                            <div class="attention-row-meta">
                                @if ($input['count'])
                                    {{ $money($input['amount']) }} · {{ $input['this_period'] }} for {{ $today->format('F') }}{{ $input['older'] ? ' · '.$input['older'].' from earlier months' : '' }}
                                @else
                                    Nothing pending
                                @endif
                            </div>
                        </div>
                        <span class="count-pill tone-{{ $input['count'] ? $input['tone'] : 'slate' }}">{{ $input['count'] }}</span>
                    </a>
                @empty
                    <div class="side-list-empty is-neutral"><i class="ti ti-adjustments"></i> You don't have access to pay adjustments.</div>
                @endforelse
                @if ($data['revisions'])
                    <a href="{{ route('admin.salary-revisions.index') }}" class="side-list-foot">
                        <i class="ti ti-trending-up"></i> {{ $data['revisions']['count'] }} salary {{ Str::plural('revision', $data['revisions']['count']) }} in the last 90 days{{ $data['revisions']['upcoming'] ? ' · '.$data['revisions']['upcoming'].' upcoming' : '' }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Loans --}}
        @if ($loans)
            <div class="col-xl-4">
                <div class="card side-list tone-violet h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-credit-card"></i></span>
                        <span class="fw-semibold">Active loans</span>
                        <a href="{{ route('admin.loans.index') }}" class="ms-auto font-12 text-decoration-none">All loans <i class="ti ti-arrow-right"></i></a>
                    </div>
                    @forelse ($loans['rows'] as $loan)
                        @php $repaid = (float) $loan->principal_amount > 0 ? max(0, min(100, round((1 - (float) $loan->outstanding_amount / (float) $loan->principal_amount) * 100))) : 0; @endphp
                        <div class="side-list-item">
                            <x-avatar :person="$loan->employee" />
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold font-13 text-truncate">{{ $loan->employee?->name }} <span class="list-subtext">· {{ $loan->loan_type }}</span></div>
                                <div class="mini-progress">
                                    <span class="mini-progress-bar is-toned tone-violet"><span style="width: {{ $repaid }}%"></span></span>
                                    <span class="mini-progress-label">{{ $repaid }}% repaid</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold font-13">{{ $money($loan->outstanding_amount) }}</div>
                                <div class="list-subtext">{{ $money($loan->monthly_installment) }}/mo</div>
                            </div>
                        </div>
                    @empty
                        <div class="side-list-empty is-neutral"><i class="ti ti-credit-card"></i> No active loans.</div>
                    @endforelse
                    @if ($loans['count'] > $loans['rows']->count())
                        <a href="{{ route('admin.loans.index') }}" class="side-list-foot">+{{ $loans['count'] - $loans['rows']->count() }} more loans</a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Statutory --}}
        @if ($data['statutory'])
            <div class="col-xl-4">
                <div class="card side-list tone-sky h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-shield-check"></i></span>
                        <span class="fw-semibold">Statutory & tax</span>
                    </div>
                    @foreach (['pf' => ['Provident Fund', 'admin.pf.index', 'ti-building-bank'], 'esi' => ['ESI', 'admin.esi.index', 'ti-heart']] as $key => [$label, $route, $icon])
                        @if (array_key_exists($key, $data['statutory']))
                            @php $setting = $data['statutory'][$key]; @endphp
                            <a href="{{ route($route) }}" class="side-list-item">
                                <span class="attention-icon tone-{{ $setting?->is_enabled ? 'success' : 'slate' }}"><i class="ti {{ $icon }}"></i></span>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold font-13">{{ $label }}</div>
                                    <div class="attention-row-meta">
                                        @if ($setting)
                                            Employee {{ Kit::days((float) $setting->employee_contribution_percent) }}% · employer {{ Kit::days((float) $setting->employer_contribution_percent) }}%{{ $setting->wage_ceiling ? ' · ceiling '.$money($setting->wage_ceiling) : '' }}
                                        @else
                                            Not set up for this branch
                                        @endif
                                    </div>
                                </div>
                                <span class="tag {{ $setting?->is_enabled ? 'tone-success' : 'tag-outline tone-slate' }}">{{ $setting ? ($setting->is_enabled ? 'On' : 'Off') : 'Set up' }}</span>
                            </a>
                        @endif
                    @endforeach
                    @isset($data['statutory']['tax'])
                        @php $tax = $data['statutory']['tax']; @endphp
                        <a href="{{ route('admin.tax-declarations.index') }}" class="side-list-item">
                            <span class="attention-icon tone-rose"><i class="ti ti-receipt-tax"></i></span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold font-13">Tax declarations · FY {{ $tax['fy'] }}</div>
                                <div class="mini-progress">
                                    <span class="mini-progress-bar is-toned tone-rose"><span style="width: {{ $tax['total'] ? round($tax['declared'] / $tax['total'] * 100) : 0 }}%"></span></span>
                                    <span class="mini-progress-label">{{ $tax['declared'] }} / {{ $tax['total'] }} declared</span>
                                </div>
                            </div>
                        </a>
                    @endisset
                </div>
            </div>
        @endif
    </div>

    {{-- Recent runs --}}
    <div class="dash-section-title"><i class="ti ti-history"></i> Recent payroll runs</div>
    <div class="card list-view">
        <div class="table-responsive">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Period</th>
                        <th class="text-center">Payslips</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Deductions</th>
                        <th class="text-end">Net pay</th>
                        <th>Change</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Open</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['runs']->take(6) as $i => $run)
                        @php
                            $prev = $data['runs']->get($i + 1);
                            $delta = $prev && (float) $prev->total_net > 0 ? round(((float) $run->total_net - (float) $prev->total_net) / (float) $prev->total_net * 100, 1) : null;
                        @endphp
                        <tr>
                            <td>
                                <div class="date-range-cell">
                                    <span class="when-block tone-{{ $run->status === 'paid' ? 'success' : 'amber' }}"><span class="when-block-month">{{ $run->year }}</span><span class="when-block-day">{{ Str::limit(PayrollRun::MONTHS[$run->month] ?? $run->month, 3, '') }}</span></span>
                                    <a href="{{ route('admin.payroll-runs.show', $run) }}" class="fw-semibold">{{ $run->monthLabel() }}</a>
                                </div>
                            </td>
                            <td class="text-center">{{ $run->employee_count }}</td>
                            <td class="text-end">{{ $money($run->total_gross) }}</td>
                            <td class="text-end text-muted">{{ $money($run->total_deductions) }}</td>
                            <td class="text-end"><span class="salary-figure">{{ $money($run->total_net) }}</span></td>
                            <td>
                                @if ($delta === null)
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="tag {{ $delta > 0 ? 'tone-amber' : ($delta < 0 ? 'tone-teal' : 'tag-outline tone-slate') }}"><i class="ti {{ $delta > 0 ? 'ti-trending-up' : ($delta < 0 ? 'ti-trending-down' : 'ti-minus') }}"></i>{{ $delta > 0 ? '+' : '' }}{{ $delta }}%</span>
                                @endif
                            </td>
                            <td><span class="tag tone-{{ $run->status === 'paid' ? 'success' : 'amber' }}"><i class="ti {{ $run->status === 'paid' ? 'ti-circle-check' : 'ti-clock' }}"></i>{{ PayrollRun::STATUSES[$run->status] ?? $run->status }}</span></td>
                            <td class="text-end"><a href="{{ route('admin.payroll-runs.show', $run) }}" class="btn-icon btn-icon-accent" title="Open run"><i class="ti ti-arrow-right font-16"></i></a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="ti ti-report-money font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No payroll runs yet</div>
                                <span class="font-13">Set up salary structures, then <a href="{{ route('admin.payroll-runs.index') }}">run your first payroll</a>.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($data['runs']->count() > 6)
            <div class="list-footer"><a href="{{ route('admin.payroll-runs.index') }}" class="font-13">View all payroll runs <i class="ti ti-arrow-right"></i></a></div>
        @endif
    </div>
@endsection

@unless ($data['trend']['empty'])
    @push('styles')
        <link href="{{ asset('vendor/metrica/assets/libs/apexcharts/apexcharts.css') }}" rel="stylesheet">
    @endpush
    @push('scripts')
        <script src="{{ asset('vendor/metrica/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
        <script>
            (function () {
                var el = document.getElementById('payrollTrend');
                if (!el || typeof ApexCharts === 'undefined') return;
                var css = getComputedStyle(document.documentElement);
                var muted = (css.getPropertyValue('--text-3') || '#8a94a6').trim();
                var grid = (css.getPropertyValue('--border-subtle') || '#eef1f8').trim();
                var short = function (v) { return v >= 1e7 ? (v / 1e7).toFixed(1) + 'Cr' : v >= 1e5 ? (v / 1e5).toFixed(1) + 'L' : v >= 1e3 ? Math.round(v / 1e3) + 'k' : Math.round(v); };
                new ApexCharts(el, {
                    chart: { type: 'line', height: 260, toolbar: { show: false }, fontFamily: 'inherit', foreColor: muted, stacked: false },
                    series: [
                        { name: 'Net pay', type: 'column', data: @json($data['trend']['net']) },
                        { name: 'Deductions', type: 'column', data: @json($data['trend']['deductions']) },
                        { name: 'Gross', type: 'line', data: @json($data['trend']['gross']) },
                    ],
                    colors: ['#0f9d70', '#f59e0b', '#5369f8'],
                    stroke: { width: [0, 0, 3], curve: 'smooth' },
                    plotOptions: { bar: { columnWidth: '50%', borderRadius: 4 } },
                    markers: { size: [0, 0, 4], strokeColors: '#fff' },
                    dataLabels: { enabled: false },
                    grid: { borderColor: grid, strokeDashArray: 4 },
                    xaxis: { categories: @json($data['trend']['labels']), axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: { min: 0, labels: { formatter: short } },
                    legend: { position: 'top', horizontalAlign: 'right' },
                    tooltip: { shared: true, intersect: false, y: { formatter: function (v) { return Math.round(v).toLocaleString(); } } },
                }).render();
            })();
        </script>
    @endpush
@endunless
