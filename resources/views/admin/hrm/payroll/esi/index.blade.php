@extends('layouts.admin')

@section('title', 'ESI')

@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.').'%';
    $key = $period->format('Y-m');
    $pageUrl = fn (string $p) => route('admin.esi.index', ['period' => $p]);
    $choices = $periods->contains($key) ? $periods : $periods->concat([$key])->sortDesc()->values();
    $isLatest = $period->gte(now()->startOfMonth());
    $total = $totals['employee'] + $totals['employer'];
    $has = $payslips->isNotEmpty();
    $canEdit = auth()->user()->can('update', $setting);
    $enabled = (bool) old('is_enabled', $setting->is_enabled);
@endphp

@section('content')
    <x-page-title title="ESI" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-{{ $setting->is_enabled ? 'indigo' : 'slate' }}" aria-hidden="true"><i class="ti ti-stethoscope font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">ESI for {{ $period->format('F Y') }}</div>
            <div class="record-header-meta">
                <span class="status-dot status-dot-{{ $setting->is_enabled ? 'success' : 'warning' }}">{{ $setting->is_enabled ? 'ESI is on' : 'ESI is switched off' }}</span>
                <span>{{ $pct($setting->employee_contribution_percent) }} employee · {{ $pct($setting->employer_contribution_percent) }} employer</span>
                <span>{{ (float) $setting->wage_ceiling > 0 ? 'For gross pay up to '.$money($setting->wage_ceiling) : 'No wage ceiling: nobody is covered' }}</span>
            </div>
        </div>
        <div class="record-header-actions">
            <form method="GET" action="{{ route('admin.esi.index') }}" class="d-flex align-items-center gap-2">
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
                <a href="{{ route('admin.esi.export', ['period' => $key]) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-download font-14 me-1"></i> Export CSV</a>
            @endif
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="To deposit" :value="$has ? $money($total) : '—'" icon="ti-building-bank" tone="indigo" :hint="$has ? 'Employee + employer for '.$period->format('F') : ($run ? 'No ESI in this payroll' : 'Payroll not run for this month')" />
        <x-kpi-card label="Employee share" :value="$has ? $money($totals['employee']) : '—'" icon="ti-user" tone="teal" hint="Deducted from salaries" />
        <x-kpi-card label="Employer share" :value="$has ? $money($totals['employer']) : '—'" icon="ti-building" tone="violet" hint="Paid by the company on top" />
        <x-kpi-card label="Members covered" :value="$totals['slips'] ? $payslips->count().' / '.$totals['slips'] : '—'" icon="ti-users" :tone="$notCovered->isNotEmpty() ? 'amber' : 'success'"
                    :hint="$totals['slips'] ? ($notCovered->isNotEmpty() ? $notCovered->count().' paid without ESI' : 'Everyone paid has ESI') : null" />
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <form method="POST" action="{{ route('admin.esi.update') }}" class="card form-section tone-indigo mb-0 h-100" id="esi-form">
                @csrf
                @method('PUT')
                <div class="form-section-head">
                    <span class="form-section-icon"><i class="ti ti-settings"></i></span>
                    <div class="flex-grow-1">
                        <h5 class="form-section-title">ESI settings</h5>
                        <p class="form-section-sub">Used by every payroll run from now on. Payslips already generated keep their figures.</p>
                    </div>
                </div>
                <fieldset class="form-section-body" @disabled(! $canEdit)>
                    <div class="toggle-cards mb-3">
                        <label class="toggle-card tone-success">
                            <input type="checkbox" name="is_enabled" value="1" id="esi-enabled" @checked($enabled)>
                            <span class="toggle-card-icon"><i class="ti ti-stethoscope"></i></span>
                            <span class="toggle-card-body">
                                <span class="toggle-card-title">Deduct ESI in payroll</span>
                                <span class="toggle-card-hint">Off = no ESI for anyone, employee or employer.</span>
                            </span>
                            <span class="switch-btn" aria-hidden="true"></span>
                        </label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="esi-employee">Employee share %</label>
                            <input type="number" step="0.01" min="0" max="100" name="employee_contribution_percent" id="esi-employee" class="form-control @error('employee_contribution_percent') is-invalid @enderror" value="{{ old('employee_contribution_percent', (float) $setting->employee_contribution_percent) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="esi-employer">Employer share %</label>
                            <input type="number" step="0.01" min="0" max="100" name="employer_contribution_percent" id="esi-employer" class="form-control @error('employer_contribution_percent') is-invalid @enderror" value="{{ old('employer_contribution_percent', (float) $setting->employer_contribution_percent) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="esi-ceiling">Wage ceiling <span class="text-muted fw-normal">(gross pay)</span></label>
                            <input type="number" step="0.01" min="0" name="wage_ceiling" id="esi-ceiling" class="form-control @error('wage_ceiling') is-invalid @enderror" value="{{ old('wage_ceiling', $setting->wage_ceiling !== null ? (float) $setting->wage_ceiling : '') }}" placeholder="21000" required>
                        </div>
                    </div>
                    @if ($canEdit)
                        <div class="quick-chips">
                            <button type="button" class="quick-chip" data-preset="0.75,3.25,21000">Statutory: 0.75% + 3.25%, ceiling 21,000</button>
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
                            <span class="rev-compare-value" id="esi-est-employee">—</span>
                            <span class="rev-compare-meta" id="esi-est-people"></span>
                        </div>
                        <i class="ti ti-plus"></i>
                        <div>
                            <span class="rev-compare-label">Company pays</span>
                            <span class="rev-compare-value" id="esi-est-employer">—</span>
                            <span class="rev-compare-meta" id="esi-est-total"></span>
                        </div>
                    </div>
                    <div class="run-lines">
                        <div><span>On gross pay of 15,000</span><strong id="esi-ex-low">—</strong></div>
                        <div><span>On gross pay of 25,000</span><strong id="esi-ex-high">—</strong></div>
                        <div><span>{{ $yearToDate['label'] }} so far ({{ $yearToDate['months'] }} {{ Str::plural('payroll', $yearToDate['months']) }})</span><strong>{{ $money($yearToDate['employee'] + $yearToDate['employer']) }}</strong></div>
                    </div>
                    <div class="run-note tone-amber mt-3" id="esi-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                    <div class="run-note tone-slate mt-2" id="esi-above" hidden><i class="ti ti-user-off"></i><span></span></div>
                    <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>ESI is charged on gross pay, and only for people whose gross pay for the month is at or below the wage ceiling. Above it, nothing is deducted.</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <div class="list-toolbar-title"><i class="ti ti-stethoscope"></i> ESI register · {{ $period->format('F Y') }} <span class="list-tab-count">{{ $payslips->count() }}</span></div>
            @if ($has)
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" id="esi-search" class="form-control form-control-sm" placeholder="Search employee" aria-label="Search the register">
                    </div>
                </div>
            @endif
        </div>

        @if ($notCovered->isNotEmpty())
            <div class="list-subbar">
                <span class="font-13 text-muted"><i class="ti ti-info-circle"></i> Paid this month without ESI: {{ $notCovered->take(5)->map(fn ($p) => $p->employee?->name ?? 'Removed employee')->implode(', ') }}{{ $notCovered->count() > 5 ? ' and '.($notCovered->count() - 5).' more' : '' }}.</span>
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0" id="esi-table">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th class="text-end">Gross pay</th>
                        <th class="text-end">Employee share</th>
                        <th class="text-end">Employer share</th>
                        <th class="text-end">Total ESI</th>
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
                            <td class="text-end">{{ $money($payslip->esi_employee_amount) }}</td>
                            <td class="text-end">{{ $money($payslip->esi_employer_amount) }}</td>
                            <td class="text-end"><span class="salary-figure">{{ $money((float) $payslip->esi_employee_amount + (float) $payslip->esi_employer_amount) }}</span></td>
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
                                <i class="ti ti-stethoscope font-24 d-block mb-2"></i>
                                <div class="fw-semibold text-body mb-1">No ESI for {{ $period->format('F Y') }}</div>
                                <span class="font-13">
                                    @if (! $run && $periods->isNotEmpty())
                                        Payroll was not run for this month. Latest payroll is <a href="{{ $pageUrl($periods->first()) }}">{{ Carbon::createFromFormat('!Y-m', $periods->first())->format('F Y') }}</a>.
                                    @elseif (! $run)
                                        ESI appears here once payroll is run.
                                    @else
                                        Payroll ran, but nobody had ESI deducted. Check that ESI is on. People earning above the wage ceiling are not covered.
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
            var search = document.getElementById('esi-search');
            if (search) {
                var rows = document.querySelectorAll('#esi-table tbody tr[data-name]');
                search.addEventListener('input', function () {
                    var q = search.value.trim().toLowerCase();
                    rows.forEach(function (row) { row.hidden = q && row.dataset.name.indexOf(q) === -1; });
                });
            }

            var people = @json($estimate['people']);
            var el = function (id) { return document.getElementById(id); };
            var enabled = el('esi-enabled'), employee = el('esi-employee'), employer = el('esi-employer'), ceiling = el('esi-ceiling');
            var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 0 }); };
            var round2 = function (n) { return Math.round(n * 100) / 100; };

            function render() {
                var on = enabled.checked;
                var ee = Math.max(0, parseFloat(employee.value) || 0), er = Math.max(0, parseFloat(employer.value) || 0);
                var cap = parseFloat(ceiling.value) > 0 ? parseFloat(ceiling.value) : 0;
                // Covered only when the month's gross pay is at or below the ceiling.
                var covered = people.filter(function (p) { return p.gross <= cap; });
                var above = people.filter(function (p) { return p.gross > cap; });
                var sumEe = 0, sumEr = 0;
                covered.forEach(function (p) { sumEe += round2(p.gross * ee / 100); sumEr += round2(p.gross * er / 100); });
                var example = function (gross) { return !on ? 'Nothing' : (gross <= cap ? fmt(gross * ee / 100) + ' + ' + fmt(gross * er / 100) : 'Not covered'); };
                var warn = '';

                el('esi-est-employee').textContent = on ? fmt(sumEe) : 'Off';
                el('esi-est-employer').textContent = on ? fmt(sumEr) : 'Off';
                el('esi-est-people').textContent = covered.length + ' of ' + people.length + ' covered';
                el('esi-est-total').textContent = on ? fmt(sumEe + sumEr) + ' to deposit' : 'ESI is switched off';
                el('esi-ex-low').textContent = example(15000);
                el('esi-ex-high').textContent = example(25000);
                el('esi-above').hidden = !on || !above.length;
                el('esi-above').querySelector('span').textContent = above.length
                    ? 'Above the ceiling, so not covered: ' + above.slice(0, 4).map(function (p) { return p.name; }).join(', ') + (above.length > 4 ? ' and ' + (above.length - 4) + ' more' : '') + '.'
                    : '';

                if (!on) warn = 'With ESI off, the next payroll deducts nothing and the company contributes nothing.';
                else if (cap <= 0) warn = 'Without a wage ceiling nobody is covered. The statutory ceiling is 21,000.';
                else if (ee === 0 && er === 0) warn = 'Both shares are 0%, so ESI comes to nothing. Switch ESI off instead if that is intended.';
                else if (ee > 5 || er > 10) warn = 'These shares are unusually high. The statutory rates are 0.75% and 3.25%.';
                else if (people.length && !covered.length) warn = 'Everyone on payroll earns more than ' + fmt(cap) + ', so nobody would be covered.';
                el('esi-warn').hidden = !warn;
                el('esi-warn').querySelector('span').textContent = warn;
            }
            [enabled, employee, employer, ceiling].forEach(function (input) { input.addEventListener('input', render); input.addEventListener('change', render); });
            document.querySelectorAll('#esi-form [data-preset]').forEach(function (chip) {
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
