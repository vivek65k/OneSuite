@extends('layouts.admin')

@section('title', 'Tax Declarations')

@php
    use App\Models\TaxDeclaration;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pageUrl = fn (array $overrides = []) => route('admin.tax-declarations.index', array_filter(
        array_merge(['financial_year' => $financialYear], $state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $fyLabel = fn (string $fy) => 'FY '.substr($fy, 0, 4).'–'.substr($fy, 7, 2);
    $tabs = [
        '' => ['label' => 'Declared', 'tone' => 'indigo', 'count' => $counts['all']],
        'new' => ['label' => 'New regime', 'tone' => 'teal', 'count' => $counts['new']],
        'old' => ['label' => 'Old regime', 'tone' => 'violet', 'count' => $counts['old']],
        'missing' => ['label' => 'Not declared', 'tone' => 'rose', 'count' => $counts['missing']],
    ];
    $isCurrentYear = $financialYear === TaxDeclaration::currentFinancialYear();
    $oldEmployee = (string) old('employee_id', '');
    $oldRegime = old('regime', 'new');
    $declaredPct = $counts['people'] > 0 ? round(($counts['people'] - $counts['missing']) / $counts['people'] * 100) : 0;
    $showMissing = $state['tab'] === 'missing';
@endphp

@section('content')
    <x-page-title title="Tax Declarations" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-indigo" aria-hidden="true"><i class="ti ti-receipt-tax font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $fyLabel($financialYear) }}</div>
            <div class="record-header-meta">
                <span>April {{ substr($financialYear, 0, 4) }} to March {{ substr($financialYear, 5, 4) }}</span>
                <span>{{ $monthsRun }} of 12 payrolls run</span>
                @if ($isCurrentYear)
                    <span class="status-dot status-dot-success">Current year</span>
                @endif
            </div>
        </div>
        <div class="record-header-actions">
            <div class="segmented" role="group" aria-label="Financial year">
                @foreach ($years as $year)
                    <a href="{{ route('admin.tax-declarations.index', ['financial_year' => $year]) }}" class="segmented-item {{ $year === $financialYear ? 'active' : '' }}">{{ $fyLabel($year) }}</a>
                @endforeach
            </div>
            @can('viewAny', \App\Models\TaxSlab::class)
                <a href="{{ route('admin.tax-slabs.index', ['financial_year' => $financialYear]) }}" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-percentage font-14 me-1"></i> Tax slabs</a>
            @endcan
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Declared" :value="($counts['people'] - $counts['missing']).' / '.$counts['people']" icon="ti-file-check" :tone="$counts['missing'] ? 'amber' : 'success'"
                    :hint="$counts['missing'] ? $counts['missing'].' '.Str::plural('employee', $counts['missing']).' still to declare' : 'Everyone has declared'" :href="$pageUrl(['tab' => $counts['missing'] ? 'missing' : null])" />
        <x-kpi-card label="Tax for the year" :value="$counts['all'] ? $money($kpis['annual_tax']) : '—'" icon="ti-receipt-tax" tone="rose"
                    :hint="$counts['all'] ? $money($kpis['monthly_tds']).' deducted every payroll' : 'No declarations yet'" />
        <x-kpi-card label="Deducted so far" :value="$kpis['deducted'] > 0 ? $money($kpis['deducted']) : '—'" icon="ti-cash" tone="teal"
                    :hint="$kpis['annual_tax'] > 0 ? round(min(100, $kpis['deducted'] / $kpis['annual_tax'] * 100)).'% of the year\'s tax' : 'Through payroll this year'" />
        <x-kpi-card label="Regime split" :value="$counts['new'].' new · '.$counts['old'].' old'" icon="ti-arrows-split" tone="indigo"
                    :hint="$counts['old'] ? $money($kpis['investments']).' investments declared' : 'No old-regime declarations'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3" id="declare">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.tax-declarations.store') }}" class="card form-section tone-indigo mb-0 h-100" id="tax-form">
                    @csrf
                    <input type="hidden" name="financial_year" value="{{ $financialYear }}">
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-receipt-tax"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Record a declaration <span class="text-muted fw-normal">· {{ $fyLabel($financialYear) }}</span></h5>
                            <p class="form-section-sub">One per employee per year. Saving again for the same person updates it.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label" for="tax-employee">Employee</label>
                                <select name="employee_id" id="tax-employee" class="form-select @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach ($formData as $id => $person)
                                        <option value="{{ $id }}" @selected($oldEmployee === (string) $id)>{{ $person['name'] }}{{ $person['meta'] ? ' — '.$person['meta'] : '' }}{{ $person['declared'] ? ' (declared)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Tax regime</label>
                                <div class="toggle-cards">
                                    <label class="toggle-card tone-teal">
                                        <input type="radio" name="regime" value="new" @checked($oldRegime !== 'old')>
                                        <span class="toggle-card-icon"><i class="ti ti-bolt"></i></span>
                                        <span class="toggle-card-body">
                                            <span class="toggle-card-title">New regime</span>
                                            <span class="toggle-card-hint">Lower rates, investments don't reduce tax.</span>
                                        </span>
                                    </label>
                                    <label class="toggle-card tone-violet">
                                        <input type="radio" name="regime" value="old" @checked($oldRegime === 'old')>
                                        <span class="toggle-card-icon"><i class="ti ti-building-bank"></i></span>
                                        <span class="toggle-card-body">
                                            <span class="toggle-card-title">Old regime</span>
                                            <span class="toggle-card-hint">Higher rates, declared investments are deducted first.</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tax-investments">Declared investments <span class="text-muted fw-normal">(old regime)</span></label>
                                <input type="number" step="0.01" min="0" name="declared_investments" id="tax-investments" value="{{ old('declared_investments') }}" class="form-control" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tax-annual">Tax for the year</label>
                                <input type="number" step="0.01" min="0" name="annual_tax" id="tax-annual" value="{{ old('annual_tax') }}" class="form-control @error('annual_tax') is-invalid @enderror" placeholder="0.00" required>
                                <div class="quick-chips">
                                    <button type="button" class="quick-chip" id="tax-use-estimate" hidden>Use estimate</button>
                                    <button type="button" class="quick-chip" id="tax-zero">No tax (0)</button>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="tax-remarks">Note <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="remarks" id="tax-remarks" value="{{ old('remarks') }}" maxlength="1000" class="form-control" placeholder="e.g. Proofs received, 80C and HRA">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> <span id="tax-submit-label">Save declaration</span></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-calculator"></i></span>
                        <span class="fw-semibold">What payroll will deduct</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">Tax for the year</span>
                                <span class="rev-compare-value" id="tax-year">—</span>
                                <span class="rev-compare-meta" id="tax-year-meta">Enter the yearly tax</span>
                            </div>
                            <i class="ti ti-arrow-right"></i>
                            <div>
                                <span class="rev-compare-label">Every payroll</span>
                                <span class="rev-compare-value" id="tax-month">—</span>
                                <span class="rev-compare-meta" id="tax-month-meta">one twelfth</span>
                            </div>
                        </div>
                        <div class="run-lines" id="tax-person" hidden>
                            <div><span>Yearly salary</span><strong id="tax-salary">—</strong></div>
                            <div><span>Less investments</span><strong id="tax-less">—</strong></div>
                            <div><span>Taxable income</span><strong id="tax-taxable">—</strong></div>
                            <div><span>Estimate from tax slabs</span><strong id="tax-estimate">—</strong></div>
                        </div>
                        <div class="run-note tone-amber mt-3" id="tax-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2" id="tax-existing" hidden><i class="ti ti-info-circle"></i><span></span></div>
                        @if (empty($slabs))
                            <div class="run-note tone-slate mt-2"><i class="ti ti-info-circle"></i><span>No tax slabs are set up for {{ $fyLabel($financialYear) }}, so there is no estimate. @can('viewAny', \App\Models\TaxSlab::class)<a href="{{ route('admin.tax-slabs.index', ['financial_year' => $financialYear]) }}">Set up tax slabs</a>@endcan</span></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.tax-declarations.index') }}">
            <input type="hidden" name="financial_year" value="{{ $financialYear }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Declarations">
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
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name or code" aria-label="Search">
                    </div>
                </div>
            </div>
        </form>

        @if ($state['search'] !== '')
            <div class="list-subbar">
                <div class="list-chips">
                    <a href="{{ $pageUrl(['search' => null]) }}" class="filter-chip" title="Remove filter">Search: <strong>{{ $state['search'] }}</strong> <i class="ti ti-x"></i></a>
                </div>
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            @if ($showMissing)
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Employee</th>
                            <th class="text-end">Yearly salary</th>
                            <th>Status</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($missing as $employee)
                            <tr>
                                <td>
                                    <div class="list-user">
                                        <x-avatar :person="$employee" />
                                        <div class="list-user-text">
                                            <div class="list-user-name">{{ $employee->name }}</div>
                                            <div class="list-user-meta">{{ collect([$employee->employee_code, $employee->department?->name])->filter()->implode(' · ') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">{{ $salaries->has($employee->id) ? $money($salaries[$employee->id] * 12) : '—' }}</td>
                                <td>
                                    <span class="tag tone-rose"><i class="ti ti-alert-triangle"></i>Not declared</span>
                                    <div class="payslip-parts">No tax is being deducted</div>
                                </td>
                                <td class="text-end">
                                    @if ($canCreate)
                                        <button type="button" class="btn btn-sm review-btn is-approve" data-declare="{{ $employee->id }}"><i class="ti ti-pencil"></i> Declare</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="ti ti-circle-check font-24 d-block mb-2"></i>
                                    <div class="fw-semibold text-body mb-1">{{ $state['search'] !== '' ? 'Nobody matches' : 'Everyone has declared for '.$fyLabel($financialYear) }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Employee</th>
                            <th>Regime</th>
                            <th class="text-end">Investments</th>
                            <th class="text-end">Tax for the year</th>
                            <th class="text-end">Every payroll</th>
                            <th style="min-width: 180px">Deducted so far</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($declarations as $declaration)
                            @php
                                $annual = (float) $declaration->annual_tax;
                                $taken = (float) ($deducted[$declaration->employee_id] ?? 0);
                                $pct = $annual > 0 ? max(0, min(100, round($taken / $annual * 100))) : 0;
                                $salary = $salaries->get($declaration->employee_id);
                            @endphp
                            <tr>
                                <td>
                                    <div class="list-user">
                                        <x-avatar :person="$declaration->employee" />
                                        <div class="list-user-text">
                                            <div class="list-user-name">{{ $declaration->employee?->name ?? 'Removed employee' }}</div>
                                            <div class="list-user-meta">{{ collect([$declaration->employee?->employee_code, $declaration->employee?->department?->name])->filter()->implode(' · ') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="tag tone-{{ $declaration->regime === 'old' ? 'violet' : 'teal' }}">{{ TaxDeclaration::REGIMES[$declaration->regime] ?? $declaration->regime }}</span>
                                    @if ($declaration->remarks)
                                        <div class="payslip-parts text-wrap" title="{{ $declaration->remarks }}">{{ Str::limit($declaration->remarks, 60) }}</div>
                                    @endif
                                </td>
                                <td class="text-end text-muted">{{ (float) $declaration->declared_investments > 0 ? $money($declaration->declared_investments) : '—' }}</td>
                                <td class="text-end">
                                    <span class="salary-figure">{{ $annual > 0 ? $money($annual) : 'Nil' }}</span>
                                    @if ($salary && $annual > 0)
                                        <div class="payslip-parts">{{ round($annual / ($salary * 12) * 100, 1) }}% of salary</div>
                                    @endif
                                </td>
                                <td class="text-end">{{ $annual > 0 ? $money($declaration->monthlyTds()) : '—' }}</td>
                                <td>
                                    @if ($annual > 0)
                                        <div class="mini-progress tone-teal">
                                            <div class="mini-progress-bar is-toned"><span style="width: {{ $pct }}%"></span></div>
                                            <span class="mini-progress-label">{{ $pct }}%</span>
                                        </div>
                                        <div class="payslip-parts">{{ $money($taken) }} of {{ $money($annual) }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="list-row-actions">
                                        @if ($canCreate && $declaration->employee && isset($formData[$declaration->employee_id]))
                                            <button type="button" class="btn-icon btn-icon-accent" title="Change declaration" data-declare="{{ $declaration->employee_id }}"><i class="ti ti-pencil font-16"></i></button>
                                        @endif
                                        @can('delete', $declaration)
                                            <form action="{{ route('admin.tax-declarations.destroy', $declaration) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this declaration? No tax will be deducted for this employee from the next payroll.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Remove declaration"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="ti ti-receipt-tax font-24 d-block mb-2"></i>
                                    @if ($counts['all'])
                                        <div class="fw-semibold text-body mb-1">No declarations match</div>
                                        <span class="font-13"><a href="{{ route('admin.tax-declarations.index', ['financial_year' => $financialYear]) }}">Show all</a></span>
                                    @else
                                        <div class="fw-semibold text-body mb-1">No declarations for {{ $fyLabel($financialYear) }} yet</div>
                                        <span class="font-13">Until someone declares, payroll deducts no tax for them. @if ($counts['missing'])<a href="{{ $pageUrl(['tab' => 'missing']) }}">See who is missing</a>@endif</span>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($declarations->isNotEmpty())
                        <tfoot>
                            <tr class="report-total">
                                <td class="fw-semibold" colspan="2">Total · {{ $declarations->count() }} {{ Str::plural('declaration', $declarations->count()) }}</td>
                                <td class="text-end">{{ $money($declarations->sum('declared_investments')) }}</td>
                                <td class="text-end fw-bold">{{ $money($declarations->sum('annual_tax')) }}</td>
                                <td class="text-end fw-semibold">{{ $money($declarations->sum(fn ($d) => $d->monthlyTds())) }}</td>
                                <td>{{ $money($declarations->sum(fn ($d) => (float) ($deducted[$d->employee_id] ?? 0))) }} deducted</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            @endif
        </div>
    </div>
@endsection

@if ($canCreate)
    @push('scripts')
        <script>
            (function () {
                var form = document.getElementById('tax-form');
                if (!form) return;
                var people = @json($formData);
                var slabs = @json((object) $slabs);
                var el = function (id) { return document.getElementById(id); };
                var employee = el('tax-employee'), investments = el('tax-investments'), annual = el('tax-annual'), remarks = el('tax-remarks');
                var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); };
                var regime = function () { return form.querySelector('input[name="regime"]:checked').value; };
                var estimate = null;

                // Progressive slabs: each band taxes only the income that falls inside it.
                function slabTax(income, bands) {
                    return bands.reduce(function (tax, b) {
                        var top = b.max === null ? income : Math.min(income, b.max);
                        return tax + (top > b.min ? (top - b.min) * b.rate / 100 : 0);
                    }, 0);
                }

                function render() {
                    var p = people[employee.value];
                    var old = regime() === 'old';
                    var tax = parseFloat(annual.value), hasTax = !isNaN(tax) && tax >= 0, warn = '';
                    investments.disabled = !old;
                    if (!old) investments.value = '';

                    el('tax-year').textContent = hasTax ? (tax === 0 ? 'Nil' : fmt(tax)) : '—';
                    el('tax-month').textContent = hasTax && tax > 0 ? fmt(Math.round(tax / 12 * 100) / 100) : '—';
                    el('tax-year-meta').textContent = hasTax ? (old ? 'old regime' : 'new regime') : 'Enter the yearly tax';
                    el('tax-person').hidden = !p;
                    el('tax-existing').hidden = !(p && p.declared);
                    el('tax-submit-label').textContent = p && p.declared ? 'Update declaration' : 'Save declaration';
                    estimate = null;

                    if (p) {
                        var yearly = p.salary !== null ? p.salary * 12 : null;
                        var less = old ? (parseFloat(investments.value) || 0) : 0;
                        var taxable = yearly !== null ? Math.max(0, yearly - less) : null;
                        var bands = slabs[regime()];
                        if (taxable !== null && bands && bands.length) estimate = Math.round(slabTax(taxable, bands) * 100) / 100;
                        el('tax-salary').textContent = yearly !== null ? fmt(yearly) : 'No salary structure';
                        el('tax-less').textContent = old ? (less > 0 ? '− ' + fmt(less) : 'None') : 'Not counted in the new regime';
                        el('tax-taxable').textContent = taxable !== null ? fmt(taxable) : '—';
                        el('tax-estimate').textContent = estimate !== null ? fmt(estimate) : 'No slabs set up';
                        el('tax-month-meta').textContent = hasTax && tax > 0 && p.salary ? Math.round(tax / 12 / p.salary * 1000) / 10 + '% of monthly salary' : 'one twelfth';
                        if (p.declared) el('tax-existing').querySelector('span').textContent = p.name + ' already declared ' + (p.declared.annual_tax > 0 ? fmt(p.declared.annual_tax) : 'nil tax') + ' under the ' + p.declared.regime + ' regime. Saving replaces it.';

                        if (p.salary === null) warn = p.name + ' has no salary structure. Payroll skips them, so no tax would be deducted.';
                        else if (hasTax && tax / 12 > p.salary) warn = 'That is ' + fmt(tax / 12) + ' a month, more than ' + p.name + ' earns (' + fmt(p.salary) + '). This will be rejected.';
                        else if (hasTax && tax / 12 > p.salary * 0.4) warn = 'Tax would take more than 40% of the monthly salary. Check the amount.';
                        else if (old && less > yearly) warn = 'Investments are more than the yearly salary.';
                        else if (estimate !== null && hasTax && estimate > 0 && Math.abs(tax - estimate) / estimate > 0.25) warn = 'This differs from the slab estimate (' + fmt(estimate) + ') by more than 25%.';
                    }
                    el('tax-use-estimate').hidden = estimate === null;
                    el('tax-warn').hidden = !warn;
                    el('tax-warn').querySelector('span').textContent = warn;
                }

                function load(id) {
                    employee.value = String(id);
                    var p = people[employee.value];
                    if (p && p.declared) {
                        form.querySelector('input[name="regime"][value="' + p.declared.regime + '"]').checked = true;
                        investments.value = p.declared.investments > 0 ? p.declared.investments : '';
                        annual.value = p.declared.annual_tax;
                        remarks.value = p.declared.remarks;
                    }
                    render();
                }

                employee.addEventListener('change', function () { load(employee.value); });
                [investments, annual].forEach(function (input) { input.addEventListener('input', render); });
                form.querySelectorAll('input[name="regime"]').forEach(function (r) { r.addEventListener('change', render); });
                el('tax-zero').addEventListener('click', function () { annual.value = '0'; render(); });
                el('tax-use-estimate').addEventListener('click', function () { if (estimate !== null) { annual.value = estimate.toFixed(2); render(); } });
                document.querySelectorAll('[data-declare]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        load(button.dataset.declare);
                        // Scroll the window itself (not scrollIntoView, which can shift the fixed sidebar and top bar).
                        window.scrollTo({ top: document.getElementById('declare').getBoundingClientRect().top + window.scrollY - 150, behavior: 'smooth' });
                        annual.focus({ preventScroll: true });
                    });
                });
                render();
            })();
        </script>
    @endpush
@endif
