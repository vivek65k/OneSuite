@extends('layouts.admin')

@section('title', 'Provident Fund')

@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.').'%';
    $key = $period->format('Y-m');
    $pageUrl = fn (string $p) => route('admin.pf.index', ['period' => $p]);
    $choices = $periods->contains($key) ? $periods : $periods->concat([$key])->sortDesc()->values();
    $isLatest = $period->gte(now()->startOfMonth());
    $total = $totals['employee'] + $totals['employer'];
    $has = $payslips->isNotEmpty();
    $canEdit = auth()->user()->can('update', $setting);
    $enabled = (bool) old('is_enabled', $setting->is_enabled);
@endphp

@section('content')
    <x-page-title title="Provident Fund" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $setting->is_enabled ? 'indigo' : 'slate' }}" aria-hidden="true"><i class="ti ti-shield-check font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">PF for {{ $period->format('F Y') }}</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-{{ $setting->is_enabled ? 'success' : 'warning' }}">{{ $setting->is_enabled ? 'PF is on' : 'PF is switched off' }}</span>
                <span>{{ $pct($setting->employee_contribution_percent) }} employee · {{ $pct($setting->employer_contribution_percent) }} employer</span>
                <span>{{ $setting->wage_ceiling ? 'Basic capped at '.$money($setting->wage_ceiling) : 'No wage ceiling' }}</span>
            </div>
        </div>
        <div class="record-header-actions">
            <form method="GET" action="{{ route('admin.pf.index') }}" class="d-flex align-items-center gap-2">
                <a href="{{ $pageUrl($period->copy()->subMonth()->format('Y-m')) }}" class="btn btn-light btn-sm px-2" title="Previous month"><i class="ti ti-chevron-left font-14"></i></a>
                <select name="period" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Month">
                    @foreach ($choices as $choice)
                        <option value="{{ $choice }}" @selected($choice === $key)>{{ Carbon::createFromFormat('!Y-m', $choice)->format('F Y') }}{{ $periods->contains($choice) ? '' : ' (not run)' }}</option>
                    @endforeach
                </select>
                @if ($isLatest)
                    <span class="btn btn-light btn-sm px-2 disabled" aria-disabled="true"><i class="ti ti-chevron-right font-14"></i></span>
                @else
                    <a href="{{ $pageUrl($period->copy()->addMonth()->format('Y-m')) }}" class="btn btn-light btn-sm px-2" title="Next month"><i class="ti ti-chevron-right font-14"></i></a>
                @endif
            </form>
            @if ($has)
                <a href="{{ route('admin.pf.export', ['period' => $key]) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-download font-14 me-1"></i> Export CSV</a>
            @endif
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="To deposit" :value="$has ? $money($total) : '—'" icon="ti-building-bank" tone="indigo" :hint="$has ? 'Employee + employer for '.$period->format('F') : ($run ? 'No PF in this payroll' : 'Payroll not run for this month')" />
        <x-kpi-card label="Employee share" :value="$has ? $money($totals['employee']) : '—'" icon="ti-user" tone="teal" hint="Deducted from salaries" />
        <x-kpi-card label="Employer share" :value="$has ? $money($totals['employer']) : '—'" icon="ti-building" tone="violet" hint="Paid by the company on top" />
        <x-kpi-card label="Members covered" :value="$totals['slips'] ? $payslips->count().' / '.$totals['slips'] : '—'" icon="ti-users" :tone="$notCovered->isNotEmpty() ? 'amber' : 'success'"
                    :hint="$totals['slips'] ? ($notCovered->isNotEmpty() ? $notCovered->count().' paid without PF' : 'Everyone paid has PF') : null" />
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <form method="POST" action="{{ route('admin.pf.update') }}" class="card form-section tone-indigo mb-0 h-100" id="pf-form">
                @csrf
                @method('PUT')
                <div class="form-section-head">
                    <span class="form-section-icon"><i class="ti ti-settings"></i></span>
                    <div class="flex-grow-1">
                        <h5 class="form-section-title">PF settings</h5>
                        <p class="form-section-sub">Used by every payroll run from now on. Payslips already generated keep their figures.</p>
                    </div>
                </div>
                <fieldset class="form-section-body" @disabled(! $canEdit)>
                    <div class="toggle-cards mb-3">
                        <label class="toggle-card tone-success">
                            <input type="checkbox" name="is_enabled" value="1" id="pf-enabled" @checked($enabled)>
                            <span class="toggle-card-icon"><i class="ti ti-shield-check"></i></span>
                            <span class="toggle-card-body">
                                <span class="toggle-card-title">Deduct PF in payroll</span>
                                <span class="toggle-card-hint">Off = no PF for anyone, employee or employer.</span>
                            </span>
                            <span class="switch-btn" aria-hidden="true"></span>
                        </label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="pf-employee">Employee share %</label>
                            <input type="number" step="0.01" min="0" max="100" name="employee_contribution_percent" id="pf-employee" class="form-control @error('employee_contribution_percent') is-invalid @enderror" value="{{ old('employee_contribution_percent', (float) $setting->employee_contribution_percent) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="pf-employer">Employer share %</label>
                            <input type="number" step="0.01" min="0" max="100" name="employer_contribution_percent" id="pf-employer" class="form-control @error('employer_contribution_percent') is-invalid @enderror" value="{{ old('employer_contribution_percent', (float) $setting->employer_contribution_percent) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="pf-ceiling">Wage ceiling <span class="text-muted fw-normal">(on Basic)</span></label>
                            <input type="number" step="0.01" min="0" name="wage_ceiling" id="pf-ceiling" class="form-control @error('wage_ceiling') is-invalid @enderror" value="{{ old('wage_ceiling', $setting->wage_ceiling !== null ? (float) $setting->wage_ceiling : '') }}" placeholder="No ceiling">
                        </div>
                    </div>
                    @if ($canEdit)
                        <div class="quick-chips">
                            <button type="button" class="quick-chip" data-preset="12,12,15000">Statutory: 12% + 12%, ceiling 15,000</button>
                            <button type="button" class="quick-chip" data-preset="12,12,">12% + 12% on full Basic</button>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Save settings</button>
                        </div>
                    @else
                        <div class="font-12 text-muted mt-3"><i class="ti ti-lock"></i> You can view these settings but not change them.</div>
                    @endif
                </fieldset>
            </form>
        </div>
        <div class="col-xl-5">
            <div class="card side-list tone-teal mb-0 h-100">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-calculator"></i></span>
                    <span class="fw-semibold">Next payroll with these settings</span>
                </div>
                <div class="card-body">
                    <div class="rev-compare">
                        <div>
                            <span class="rev-compare-label">Employees pay</span>
                            <span class="rev-compare-value" id="pf-est-employee">—</span>
                            <span class="rev-compare-meta" id="pf-est-people"></span>
                        </div>
                        <i class="ti ti-plus"></i>
                        <div>
                            <span class="rev-compare-label">Company pays</span>
                            <span class="rev-compare-value" id="pf-est-employer">—</span>
                            <span class="rev-compare-meta" id="pf-est-total"></span>
                        </div>
                    </div>
                    <div class="run-lines">
                        <div><span>On a Basic of 10,000</span><strong id="pf-ex-low">—</strong></div>
                        <div><span>On a Basic of 30,000</span><strong id="pf-ex-high">—</strong></div>
                        <div><span>{{ $yearToDate['label'] }} so far ({{ $yearToDate['months'] }} {{ Str::plural('payroll', $yearToDate['months']) }})</span><strong>{{ $money($yearToDate['employee'] + $yearToDate['employer']) }}</strong></div>
                    </div>
                    <div class="run-note tone-amber mt-3" id="pf-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                    @if ($estimate['noBasic'])
                        <div class="run-note tone-rose mt-2"><i class="ti ti-alert-triangle"></i><span>{{ collect($estimate['noBasic'])->take(3)->implode(', ') }}{{ count($estimate['noBasic']) > 3 ? ' and '.(count($estimate['noBasic']) - 3).' more' : '' }} {{ count($estimate['noBasic']) === 1 ? 'has' : 'have' }} no "Basic" component in the salary structure, so no PF is deducted for them.</span></div>
                    @endif
                    <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>PF is worked out on the "Basic" salary component only, capped at the wage ceiling when one is set.</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-shield-check"></i> PF register · {{ $period->format('F Y') }} <span class="list-tab-count">{{ $payslips->count() }}</span></div>
            @if ($has)
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" id="pf-search" class="form-control form-control-sm" placeholder="Search employee" aria-label="Search the register">
                    </div>
                </div>
            @endif
        </div>

        @if ($notCovered->isNotEmpty())
            <div class="list-subbar">
                <span class="font-13 text-muted"><i class="ti ti-info-circle"></i> Paid this month without PF: {{ $notCovered->take(5)->map(fn ($p) => $p->employee?->name ?? 'Removed employee')->implode(', ') }}{{ $notCovered->count() > 5 ? ' and '.($notCovered->count() - 5).' more' : '' }}.</span>
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0" id="pf-table">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th class="text-end">Gross pay</th>
                        <th class="text-end">Employee share</th>
                        <th class="text-end">Employer share</th>
                        <th class="text-end">Total PF</th>
                        <th>Payslip</th>
                        <th class="text-end"><span class="visually-hidden">Open</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payslips as $payslip)
                        <tr data-name="{{ mb_strtolower(($payslip->employee?->name ?? '').' '.($payslip->employee?->employee_code ?? '').' '.($payslip->employee?->department?->name ?? '')) }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$payslip->employee" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $payslip->employee?->name ?? 'Removed employee' }}</div>
                                        <div class="list-user-meta">{{ collect([$payslip->employee?->employee_code, $payslip->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end text-muted">{{ $money($payslip->gross_earnings) }}</td>
                            <td class="text-end">{{ $money($payslip->pf_employee_amount) }}</td>
                            <td class="text-end">{{ $money($payslip->pf_employer_amount) }}</td>
                            <td class="text-end"><span class="salary-figure">{{ $money((float) $payslip->pf_employee_amount + (float) $payslip->pf_employer_amount) }}</span></td>
                            <td><span class="tag tone-{{ $payslip->status === 'paid' ? 'success' : 'amber' }}">{{ $payslip->status === 'paid' ? 'Paid' : 'Not paid yet' }}</span></td>
                            <td class="text-end">
                                @can('viewAny', \App\Models\Payslip::class)
                                    <a href="{{ route('admin.payslips.show', $payslip) }}" class="btn-icon btn-icon-accent" title="View payslip"><i class="ti ti-eye font-16"></i></a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-shield-check font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No PF for {{ $period->format('F Y') }}</div>
                                <span class="font-13">
                                    @if (! $run && $periods->isNotEmpty())
                                        Payroll was not run for this month. Latest payroll is <a href="{{ $pageUrl($periods->first()) }}">{{ Carbon::createFromFormat('!Y-m', $periods->first())->format('F Y') }}</a>.
                                    @elseif (! $run)
                                        PF appears here once payroll is run.
                                    @else
                                        Payroll ran, but nobody had PF deducted. Check that PF is on and that salary structures have a "Basic" component.
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($has)
                    <tfoot>
                        <tr class="report-total">
                            <td class="fw-semibold">Total · {{ $payslips->count() }} {{ Str::plural('member', $payslips->count()) }}</td>
                            <td class="text-end">{{ $money($payslips->sum(fn ($p) => (float) $p->gross_earnings)) }}</td>
                            <td class="text-end fw-semibold">{{ $money($totals['employee']) }}</td>
                            <td class="text-end fw-semibold">{{ $money($totals['employer']) }}</td>
                            <td class="text-end fw-bold">{{ $money($total) }}</td>
                            <td colspan="2"></td>
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
            var search = document.getElementById('pf-search');
            if (search) {
                var rows = document.querySelectorAll('#pf-table tbody tr[data-name]');
                search.addEventListener('input', function () {
                    var q = search.value.trim().toLowerCase();
                    rows.forEach(function (row) { row.hidden = q && row.dataset.name.indexOf(q) === -1; });
                });
            }

            var basics = @json($estimate['basics']);
            var el = function (id) { return document.getElementById(id); };
            var enabled = el('pf-enabled'), employee = el('pf-employee'), employer = el('pf-employer'), ceiling = el('pf-ceiling');
            var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 0 }); };
            var round2 = function (n) { return Math.round(n * 100) / 100; };

            function render() {
                var on = enabled.checked;
                var ee = Math.max(0, parseFloat(employee.value) || 0), er = Math.max(0, parseFloat(employer.value) || 0);
                var cap = parseFloat(ceiling.value) > 0 ? parseFloat(ceiling.value) : null;
                var wage = function (basic) { return cap !== null ? Math.min(basic, cap) : basic; };
                var sumEe = 0, sumEr = 0;
                basics.forEach(function (b) { sumEe += round2(wage(b) * ee / 100); sumEr += round2(wage(b) * er / 100); });
                var example = function (basic) { return on ? fmt(wage(basic) * ee / 100) + ' + ' + fmt(wage(basic) * er / 100) : 'Nothing'; };
                var warn = '';

                el('pf-est-employee').textContent = on ? fmt(sumEe) : 'Off';
                el('pf-est-employer').textContent = on ? fmt(sumEr) : 'Off';
                el('pf-est-people').textContent = basics.length + (basics.length === 1 ? ' member' : ' members');
                el('pf-est-total').textContent = on ? fmt(sumEe + sumEr) + ' to deposit' : 'PF is switched off';
                el('pf-ex-low').textContent = example(10000);
                el('pf-ex-high').textContent = example(30000);

                if (!on) warn = 'With PF off, the next payroll deducts nothing and the company contributes nothing.';
                else if (ee === 0 && er === 0) warn = 'Both shares are 0%, so PF comes to nothing. Switch PF off instead if that is intended.';
                else if (ee > 20 || er > 20) warn = 'A share above 20% is unusual. The statutory rate is 12%.';
                else if (cap !== null && cap < 1000) warn = 'A ceiling of ' + fmt(cap) + ' caps PF at almost nothing. The statutory ceiling is 15,000.';
                else if (!basics.length) warn = 'Nobody on payroll has a "Basic" component yet, so PF would be zero.';
                el('pf-warn').hidden = !warn;
                el('pf-warn').querySelector('span').textContent = warn;
            }

            [enabled, employee, employer, ceiling].forEach(function (input) { input.addEventListener('input', render); input.addEventListener('change', render); });
            document.querySelectorAll('#pf-form [data-preset]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var p = chip.dataset.preset.split(',');
                    employee.value = p[0]; employer.value = p[1]; ceiling.value = p[2]; enabled.checked = true;
                    render();
                });
            });
            render();
        })();
    </script>
@endpush
