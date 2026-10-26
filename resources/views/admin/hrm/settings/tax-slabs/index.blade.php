@extends('layouts.admin')

@section('title', 'Tax Rules')

@php
    use App\Models\TaxDeclaration;
    use App\Models\TaxSlab;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $rate = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.').'%';
    $fyLabel = fn (string $fy) => 'FY '.substr($fy, 0, 4).'–'.substr($fy, 7, 2);
    $range = fn ($slab) => $money($slab->min_income).($slab->max_income !== null ? ' – '.$money($slab->max_income) : ' and above');
    // Bands get darker as the rate climbs.
    $bandTones = ['slate', 'sky', 'teal', 'indigo', 'violet', 'amber', 'rose'];
    $regimeTone = ['new' => 'teal', 'old' => 'violet'];
    $regimeIcon = ['new' => 'ti-bolt', 'old' => 'ti-building-bank'];
    $isCurrentYear = $financialYear === TaxDeclaration::currentFinancialYear();
    $canCreate = auth()->user()->can('create', TaxSlab::class);
    $total = $regimes->sum(fn ($r) => $r['slabs']->count());
    $slabJson = $regimes->map(fn ($r) => $r['slabs']->map(fn ($s) => ['min' => (float) $s->min_income, 'max' => $s->max_income !== null ? (float) $s->max_income : null, 'rate' => (float) $s->tax_rate_percent])->values());
    $oldRegime = old('regime');
@endphp

@section('content')
    <x-page-title title="Tax Rules" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-indigo" aria-hidden="true"><i class="ti ti-receipt-tax font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">Income tax slabs · {{ $fyLabel($financialYear) }}</div>
            <div class="record-header-meta">
                <span>April {{ substr($financialYear, 0, 4) }} to March {{ substr($financialYear, 5, 4) }}</span>
                @if ($isCurrentYear)
                    <span class="status-dot status-dot-success">Current year</span>
                @endif
                @can('viewAny', TaxDeclaration::class)
                    <a href="{{ route('admin.tax-declarations.index', ['financial_year' => $financialYear]) }}">{{ $declarations }} tax {{ Str::plural('declaration', $declarations) }} this year</a>
                @endcan
            </div>
        </div>
        <div class="record-header-actions">
            <div class="segmented" role="group" aria-label="Financial year">
                @foreach ($years as $year)
                    <a href="{{ route('admin.tax-slabs.index', ['financial_year' => $year]) }}" class="segmented-item {{ $year === $financialYear ? 'active' : '' }}">{{ $fyLabel($year) }}</a>
                @endforeach
            </div>
        </div>
    </div>

    @if ($total === 0)
        <div class="card mb-3">
            <div class="card-body empty-state py-4">
                <div class="empty-state-icon"><i class="ti ti-receipt-tax"></i></div>
                <div class="fw-semibold">No tax slabs for {{ $fyLabel($financialYear) }} yet</div>
                <div class="font-13 text-muted mb-3">Slabs are used to estimate an employee's yearly tax when a declaration is recorded. Start from a ready-made set, then adjust.</div>
                @if ($canCreate)
                    <div class="d-flex justify-content-center flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.tax-slabs.fill') }}">
                            @csrf
                            <input type="hidden" name="financial_year" value="{{ $financialYear }}">
                            <input type="hidden" name="source" value="standard">
                            <button type="submit" class="btn btn-de-primary text-light px-3"><i class="ti ti-wand font-14 me-1"></i> Load standard Indian slabs</button>
                        </form>
                        @if ($previousCount)
                            <form method="POST" action="{{ route('admin.tax-slabs.fill') }}">
                                @csrf
                                <input type="hidden" name="financial_year" value="{{ $financialYear }}">
                                <input type="hidden" name="source" value="previous">
                                <button type="submit" class="btn btn-light px-3"><i class="ti ti-copy font-14 me-1"></i> Copy from {{ $fyLabel($previousYear) }}</button>
                            </form>
                        @endif
                    </div>
                    <div class="font-12 text-muted mt-2">The standard set uses FY 2025–26 rates. Check them against the current tax rules.</div>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach ($regimes->sortKeys() as $regime => $info)
            @php
                $slabs = $info['slabs'];
                $tone = $regimeTone[$regime] ?? 'indigo';
                $last = $slabs->last();
                // The add form starts where the last slab ends.
                $nextFrom = $last && $last->max_income !== null ? (float) $last->max_income : ($last ? null : 0);
                $capped = $slabs->filter(fn ($s) => $s->max_income !== null);
                $span = max(1, (float) $capped->max('max_income'));
            @endphp
            <div class="col-xl-6">
                <div class="card side-list tone-{{ $tone }} mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti {{ $regimeIcon[$regime] ?? 'ti-receipt-tax' }}"></i></span>
                        <span class="fw-semibold">{{ $info['label'] }}</span>
                        <span class="side-list-count">{{ $slabs->count() }} {{ Str::plural('slab', $slabs->count()) }}</span>
                    </div>
                    <div class="card-body">
                        @if ($slabs->isNotEmpty())
                            <div class="stack-bar stack-bar-lg slab-bar">
                                @foreach ($slabs as $i => $slab)
                                    <span class="tone-{{ $bandTones[min($i, count($bandTones) - 1)] }}" style="flex: {{ $slab->max_income !== null ? max(0.08, ((float) $slab->max_income - (float) $slab->min_income) / $span) : 0.25 }}" title="{{ $range($slab) }}: {{ $rate($slab->tax_rate_percent) }}">{{ $rate($slab->tax_rate_percent) }}</span>
                                @endforeach
                            </div>
                        @endif

                        @foreach ($info['issues'] as $issue)
                            <div class="run-note tone-amber mt-2"><i class="ti ti-alert-triangle"></i><span>{{ $issue }}</span></div>
                        @endforeach

                        @if ($slabs->isNotEmpty())
                            <table class="table table-sm report-mini slab-table mt-3 mb-0">
                                <thead>
                                    <tr><th>Yearly income</th><th class="text-end">Rate</th><th class="text-end">Tax on the full band</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($slabs as $i => $slab)
                                        <tr>
                                            <td><i class="legend-dot tone-{{ $bandTones[min($i, count($bandTones) - 1)] }}"></i> {{ $range($slab) }}</td>
                                            <td class="text-end fw-semibold">{{ (float) $slab->tax_rate_percent > 0 ? $rate($slab->tax_rate_percent) : 'Nil' }}</td>
                                            <td class="text-end text-muted">{{ $slab->max_income !== null ? $money(((float) $slab->max_income - (float) $slab->min_income) * (float) $slab->tax_rate_percent / 100) : '—' }}</td>
                                            <td class="text-end text-nowrap">
                                                @can('update', $slab)
                                                    <button type="button" class="btn-icon btn-icon-accent" title="Change slab" data-bs-toggle="modal" data-bs-target="#slab-modal"
                                                            data-action="{{ route('admin.tax-slabs.update', $slab) }}" data-label="{{ $info['label'] }}" data-min="{{ (float) $slab->min_income }}" data-max="{{ $slab->max_income !== null ? (float) $slab->max_income : '' }}" data-rate="{{ (float) $slab->tax_rate_percent }}"><i class="ti ti-pencil font-16"></i></button>
                                                @endcan
                                                @can('delete', $slab)
                                                    <form action="{{ route('admin.tax-slabs.destroy', $slab) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove the slab for {{ $range($slab) }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-icon btn-icon-danger" title="Remove slab"><i class="ti ti-trash font-16"></i></button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @elseif ($total > 0)
                            <div class="text-muted font-13 py-2">No slabs for this regime yet.</div>
                            @if ($canCreate)
                                <form method="POST" action="{{ route('admin.tax-slabs.fill') }}" class="mb-2">
                                    @csrf
                                    <input type="hidden" name="financial_year" value="{{ $financialYear }}">
                                    <input type="hidden" name="source" value="standard">
                                    <input type="hidden" name="regime" value="{{ $regime }}">
                                    <button type="submit" class="btn btn-light btn-sm"><i class="ti ti-wand font-14 me-1"></i> Load standard {{ strtolower($info['label']) }} slabs</button>
                                </form>
                            @endif
                        @endif

                        @if ($canCreate)
                            <form method="POST" action="{{ route('admin.tax-slabs.store') }}" class="slab-add">
                                @csrf
                                <input type="hidden" name="financial_year" value="{{ $financialYear }}">
                                <input type="hidden" name="regime" value="{{ $regime }}">
                                <div>
                                    <label class="form-label" for="slab-min-{{ $regime }}">From</label>
                                    <input type="number" step="0.01" min="0" name="min_income" id="slab-min-{{ $regime }}" value="{{ $oldRegime === $regime ? old('min_income') : $nextFrom }}" class="form-control form-control-sm" required>
                                </div>
                                <div>
                                    <label class="form-label" for="slab-max-{{ $regime }}">To <span class="text-muted fw-normal">(empty = above)</span></label>
                                    <input type="number" step="0.01" min="0" name="max_income" id="slab-max-{{ $regime }}" value="{{ $oldRegime === $regime ? old('max_income') : '' }}" class="form-control form-control-sm" placeholder="No limit">
                                </div>
                                <div>
                                    <label class="form-label" for="slab-rate-{{ $regime }}">Rate %</label>
                                    <input type="number" step="0.01" min="0" max="100" name="tax_rate_percent" id="slab-rate-{{ $regime }}" value="{{ $oldRegime === $regime ? old('tax_rate_percent') : '' }}" class="form-control form-control-sm" required>
                                </div>
                                <button type="submit" class="btn btn-de-primary btn-sm text-light"><i class="ti ti-plus font-14"></i> Add slab</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($total > 0)
        <div class="card form-section tone-indigo mb-0">
            <div class="form-section-head">
                <span class="form-section-icon"><i class="ti ti-calculator"></i></span>
                <div class="flex-grow-1">
                    <h5 class="form-section-title">Try the slabs</h5>
                    <p class="form-section-sub">See what these slabs charge on a yearly income. Nothing is saved.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="calc-income">Yearly income</label>
                        <input type="number" step="1000" min="0" id="calc-income" class="form-control" value="1200000">
                        <div class="quick-chips">
                            @foreach ([500000 => '5 lakh', 1000000 => '10 lakh', 1500000 => '15 lakh', 2500000 => '25 lakh'] as $value => $label)
                                <button type="button" class="quick-chip" data-income="{{ $value }}">{{ $label }}</button>
                            @endforeach
                        </div>
                        <label class="form-label mt-3" for="calc-investments">Investments <span class="text-muted fw-normal">(old regime only)</span></label>
                        <input type="number" step="1000" min="0" id="calc-investments" class="form-control" value="150000">
                    </div>
                    @foreach ($regimes->sortKeys() as $regime => $info)
                        <div class="col-md-4">
                            <div class="calc-result tone-{{ $regimeTone[$regime] ?? 'indigo' }}" id="calc-{{ $regime }}">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rev-compare-label">{{ $info['label'] }}</span>
                                    <span class="tag tone-success ms-auto" data-role="best" hidden>Lower tax</span>
                                </div>
                                <div class="rev-compare-value" data-role="tax">—</div>
                                <div class="rev-compare-meta" data-role="meta"></div>
                                <div class="run-lines" data-role="lines"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade" id="slab-modal" tabindex="-1" aria-labelledby="slab-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="slab-form">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="slab-modal-title">Change slab</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="font-13 text-muted mb-3"><strong id="slab-modal-who" class="text-body"></strong> · {{ $fyLabel($financialYear) }}</p>
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label" for="edit-min">From</label>
                            <input type="number" step="0.01" min="0" name="min_income" id="edit-min" class="form-control" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label" for="edit-max">To</label>
                            <input type="number" step="0.01" min="0" name="max_income" id="edit-max" class="form-control" placeholder="No limit">
                        </div>
                        <div class="col-4">
                            <label class="form-label" for="edit-rate">Rate %</label>
                            <input type="number" step="0.01" min="0" max="100" name="tax_rate_percent" id="edit-rate" class="form-control" required>
                        </div>
                    </div>
                    <div class="font-12 text-muted mt-2">Leave "To" empty for the top slab. Slabs of one regime must not overlap.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save slab</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 0 }); };

            var modal = el('slab-modal');
            if (modal) {
                modal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    el('slab-form').action = b.dataset.action;
                    el('slab-modal-who').textContent = b.dataset.label;
                    el('edit-min').value = b.dataset.min; el('edit-max').value = b.dataset.max; el('edit-rate').value = b.dataset.rate;
                });
            }

            var income = el('calc-income');
            if (!income) return;
            var slabs = @json($slabJson);
            var investments = el('calc-investments');

            function calculate() {
                var gross = Math.max(0, parseFloat(income.value) || 0);
                var results = {};
                Object.keys(slabs).forEach(function (regime) {
                    var box = el('calc-' + regime);
                    if (!box) return;
                    var bands = slabs[regime];
                    var taxable = regime === 'old' ? Math.max(0, gross - Math.max(0, parseFloat(investments.value) || 0)) : gross;
                    var lines = '', tax = 0;
                    // Each band taxes only the income that falls inside it.
                    bands.forEach(function (b) {
                        var top = b.max === null ? taxable : Math.min(taxable, b.max);
                        var part = top > b.min ? (top - b.min) * b.rate / 100 : 0;
                        tax += part;
                        if (top > b.min) lines += '<div><span>' + fmt(b.min) + (b.max === null ? ' and above' : ' – ' + fmt(b.max)) + ' <em>at ' + b.rate + '%</em></span><strong>' + fmt(part) + '</strong></div>';
                    });
                    box.querySelector('[data-role="tax"]').textContent = bands.length ? fmt(tax) : 'No slabs';
                    box.querySelector('[data-role="meta"]').textContent = bands.length ? fmt(tax / 12) + ' a month · ' + (gross > 0 ? Math.round(tax / gross * 1000) / 10 : 0) + '% of income' + (regime === 'old' ? ' · taxable ' + fmt(taxable) : '') : '';
                    box.querySelector('[data-role="lines"]').innerHTML = lines;
                    if (bands.length) results[regime] = tax;
                });
                var keys = Object.keys(results);
                var best = keys.length > 1 && results[keys[0]] !== results[keys[1]] ? (results[keys[0]] < results[keys[1]] ? keys[0] : keys[1]) : null;
                Object.keys(slabs).forEach(function (regime) {
                    var box = el('calc-' + regime);
                    if (box) box.querySelector('[data-role="best"]').hidden = regime !== best;
                });
            }

            [income, investments].forEach(function (input) { input.addEventListener('input', calculate); });
            document.querySelectorAll('[data-income]').forEach(function (chip) {
                chip.addEventListener('click', function () { income.value = chip.dataset.income; calculate(); });
            });
            calculate();
        })();
    </script>
@endpush
