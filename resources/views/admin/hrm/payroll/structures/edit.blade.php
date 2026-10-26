@extends('layouts.admin')

@section('title', 'Salary Structure')

@php
    $money = fn ($n) => number_format((float) $n);
    $currentTotal = $structure ? (float) $structure->items->sum('amount') : 0.0;
    $cancelUrl = route('admin.salary-structures.index');
    $tones = ['indigo', 'teal', 'amber', 'pink', 'violet', 'sky', 'lime', 'slate'];
    // A common split for the "start from a total" helper; anything not listed stays empty.
    $split = ['BASIC' => 50, 'HRA' => 20, 'CONVEYANCE' => 5, 'MEDICAL' => 5, 'SPECIAL' => 20];
    $droppedItems = $structure ? $structure->items->reject(fn ($i) => $components->contains('id', $i->salary_component_id)) : collect();
@endphp

@section('content')
    <x-page-title title="Salary Structure" :breadcrumbs="['Salary Structure' => $cancelUrl]" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.salary-structures.update', $employee) }}" id="structure-form">
        @csrf

        <div class="record-header">
            <a href="{{ $cancelUrl }}" class="btn-icon record-back" title="Back"><i class="ti ti-arrow-left font-18"></i></a>
            <x-avatar :person="$employee" class="record-avatar" />
            <div class="record-header-text">
                <div class="record-header-title">{{ $employee->name }}</div>
                <div class="record-header-meta">
                    <span class="list-code">{{ $employee->employee_code }}</span>
                    <span>{{ collect([$employee->designation, $employee->department?->name, $employee->branch?->name])->filter()->implode(' · ') }}</span>
                    @if ($structure)
                        <span class="status-dot status-dot-success">{{ $money($currentTotal) }} a month since {{ $structure->effective_date->format('M j, Y') }}</span>
                    @else
                        <span class="status-dot status-dot-danger">No salary structure yet</span>
                    @endif
                </div>
            </div>
            @if ($canEdit)
                <div class="record-header-actions">
                    <a href="{{ $cancelUrl }}" class="btn btn-light btn-sm px-3">Cancel</a>
                    <button type="submit" class="btn btn-de-primary btn-sm text-light px-3"><i class="ti ti-check font-14 me-1"></i> {{ $structure ? 'Save revision' : 'Save structure' }}</button>
                </div>
            @endif
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3 font-13">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif
        @unless ($canEdit)
            <div class="alert alert-info d-flex align-items-center gap-2"><i class="ti ti-lock font-18"></i> {{ in_array($employee->status, ['terminated', 'inactive'], true) ? 'This employee is no longer active, so the structure is read-only.' : 'You can view this structure but not change it.' }}</div>
        @endunless

        <fieldset @disabled(! $canEdit)>
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card form-section tone-indigo">
                        <div class="form-section-head">
                            <span class="form-section-icon"><i class="ti ti-calendar-event"></i></span>
                            <div>
                                <h5 class="form-section-title">Effective from</h5>
                                <p class="form-section-sub">Saving creates a new revision; earlier structures are kept as history.</p>
                            </div>
                        </div>
                        <div class="form-section-body">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <input type="date" name="effective_date" id="effective_date" class="form-control @error('effective_date') is-invalid @enderror" style="max-width: 200px"
                                       value="{{ old('effective_date', now()->toDateString()) }}" @if ($structure) min="{{ $structure->effective_date->toDateString() }}" @endif required>
                                <div class="quick-chips mt-0">
                                    <button type="button" class="quick-chip" data-date="{{ now()->toDateString() }}">Today</button>
                                    <button type="button" class="quick-chip" data-date="{{ now()->startOfMonth()->toDateString() }}">1st of this month</button>
                                    <button type="button" class="quick-chip" data-date="{{ now()->addMonthNoOverflow()->startOfMonth()->toDateString() }}">1st of next month</button>
                                </div>
                            </div>
                            <div class="form-hint" id="date-hint" hidden><i class="ti ti-alert-triangle"></i> <span>Payroll uses the latest saved structure straight away, even if this date is in the future.</span></div>
                        </div>
                    </div>

                    <div class="card form-section tone-teal mb-0">
                        <div class="form-section-head">
                            <span class="form-section-icon"><i class="ti ti-cash"></i></span>
                            <div class="flex-grow-1">
                                <h5 class="form-section-title">Monthly earnings</h5>
                                <p class="form-section-sub">Amounts per month. Leave a component empty to leave it out.</p>
                            </div>
                            <a href="{{ route('admin.salary-components.index') }}" class="btn btn-light btn-sm text-nowrap flex-shrink-0"><i class="ti ti-list font-14 me-1"></i> Components</a>
                        </div>
                        <div class="form-section-body">
                            @if ($components->isEmpty())
                                <div class="empty-state tone-teal">
                                    <span class="empty-state-icon"><i class="ti ti-cash"></i></span>
                                    <h5>No earning components yet</h5>
                                    <p>Add components such as Basic and HRA for this employee's branch first.</p>
                                    <a href="{{ route('admin.salary-components.index') }}" class="btn btn-de-primary btn-sm text-light">Add components</a>
                                </div>
                            @else
                                @if ($canEdit)
                                    <div class="struct-split">
                                        <label class="form-label mb-0" for="split-total">Start from a monthly total</label>
                                        <div class="input-group input-group-sm" style="max-width: 200px">
                                            <input type="number" min="0" step="100" class="form-control" id="split-total" placeholder="e.g. 60000">
                                            <button type="button" class="btn btn-light" id="split-apply">Split</button>
                                        </div>
                                        <span class="list-subtext">Basic 50% · HRA 20% · Conveyance 5% · Medical 5% · Special 20% (where those components exist)</span>
                                    </div>
                                @endif

                                <div class="struct-rows">
                                    @foreach ($components as $i => $component)
                                        @php $value = old('amounts.'.$component->id, isset($amounts[$component->id]) ? rtrim(rtrim(number_format((float) $amounts[$component->id], 2, '.', ''), '0'), '.') : ''); @endphp
                                        <div class="struct-row tone-{{ $tones[$i % count($tones)] }}" data-code="{{ $component->code }}" data-name="{{ $component->name }}" data-tone="{{ $tones[$i % count($tones)] }}">
                                            <span class="comp-code tone-{{ $tones[$i % count($tones)] }}">{{ \Illuminate\Support\Str::limit($component->code, 8, '') }}</span>
                                            <div class="struct-row-name">
                                                <label for="amount-{{ $component->id }}" class="fw-semibold font-13 mb-0">{{ $component->name }}</label>
                                                <span class="struct-row-bar"><span data-share-bar></span></span>
                                            </div>
                                            <span class="struct-row-share" data-share>—</span>
                                            <input type="number" step="0.01" min="0" max="99999999" name="amounts[{{ $component->id }}]" id="amount-{{ $component->id }}"
                                                   class="form-control text-end struct-row-input" value="{{ $value }}" placeholder="0" data-amount data-original="{{ isset($amounts[$component->id]) ? (float) $amounts[$component->id] : 0 }}">
                                        </div>
                                    @endforeach
                                </div>
                                @if ($droppedItems->isNotEmpty())
                                    <div class="form-hint"><i class="ti ti-info-circle"></i> <span>The current structure also has {{ $droppedItems->map(fn ($i) => ($i->component?->name ?? 'a removed component').' ('.$money($i->amount).')')->implode(', ') }}, which {{ $droppedItems->count() === 1 ? 'is' : 'are' }} no longer active and will be left out of the new revision.</span></div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="leave-summary struct-summary" id="summary">
                        <div class="leave-summary-label">{{ $structure ? 'New structure' : 'Salary structure' }}</div>
                        <div class="leave-summary-days"><span id="sum-monthly">0</span><small> / month</small></div>
                        <div class="leave-summary-dates"><span id="sum-annual">0</span> a year</div>
                        <div class="stack-bar stack-bar-lg mt-3" id="sum-bar"></div>
                        <div class="leave-balance">
                            @if ($structure)
                                <div class="leave-balance-row"><span>Current</span><strong>{{ $money($currentTotal) }}</strong></div>
                                <div class="leave-balance-row"><span>Change</span><strong id="sum-change">—</strong></div>
                            @endif
                            <div class="leave-balance-row {{ $structure ? 'is-total' : '' }}"><span>Components used</span><strong id="sum-count">0</strong></div>
                        </div>
                        <div class="leave-alert is-warning" id="sum-warn" hidden><i class="ti ti-alert-triangle"></i> <span></span></div>
                        @if ($canEdit)
                            <button type="submit" class="btn btn-light w-100 mt-3 fw-semibold" id="sum-submit"><i class="ti ti-check font-14 me-1"></i> {{ $structure ? 'Save revision' : 'Save structure' }}</button>
                        @endif
                    </div>
                </div>
            </div>
        </fieldset>
    </form>

    @if ($history->isNotEmpty())
        <div class="card side-list tone-slate mt-3">
            <div class="side-list-head">
                <span class="side-list-icon"><i class="ti ti-history"></i></span>
                <span class="fw-semibold">Revision history</span>
                <span class="side-list-count">{{ $history->count() }}</span>
            </div>
            @foreach ($history as $i => $past)
                @php
                    $total = (float) $past->items->sum('amount');
                    $older = $history->get($i + 1);
                    $olderTotal = $older ? (float) $older->items->sum('amount') : null;
                    $delta = $olderTotal ? round(($total - $olderTotal) / $olderTotal * 100, 1) : null;
                @endphp
                <div class="side-list-item">
                    <span class="when-block tone-{{ $past->is_current ? 'success' : 'slate' }}"><span class="when-block-month">{{ $past->effective_date->format('M') }}</span><span class="when-block-day">{{ $past->effective_date->format('j') }}</span></span>
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-semibold font-13">{{ $money($total) }} a month <span class="list-subtext">· from {{ $past->effective_date->format('M j, Y') }}</span></div>
                        <div class="list-subtext text-truncate">{{ $past->items->sortByDesc('amount')->map(fn ($item) => ($item->component?->name ?? 'Removed component').' '.$money($item->amount))->implode(' · ') ?: 'No components' }}</div>
                    </div>
                    @if ($delta !== null)
                        <span class="tag {{ $delta >= 0 ? 'tone-success' : 'tone-rose' }}"><i class="ti {{ $delta >= 0 ? 'ti-trending-up' : 'ti-trending-down' }}"></i>{{ $delta >= 0 ? '+' : '' }}{{ $delta }}%</span>
                    @endif
                    <span class="tag {{ $past->is_current ? 'tone-success' : 'tag-outline tone-slate' }}">{{ $past->is_current ? 'Current' : 'Earlier' }}</span>
                </div>
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var rows = Array.prototype.slice.call(document.querySelectorAll('.struct-row'));
            if (!rows.length) return;
            var $ = function (id) { return document.getElementById(id); };
            var current = {{ $currentTotal }}, split = @json($split);
            var date = $('effective_date'), today = '{{ now()->toDateString() }}';

            function fmt(n) { return Math.round(n).toLocaleString(); }
            function val(row) { return parseFloat(row.querySelector('[data-amount]').value) || 0; }

            function refresh() {
                var total = rows.reduce(function (sum, row) { return sum + val(row); }, 0), used = 0;
                var bar = $('sum-bar'); bar.innerHTML = '';
                rows.forEach(function (row) {
                    var v = val(row), share = total ? v / total * 100 : 0;
                    row.classList.toggle('is-empty', !v);
                    row.querySelector('[data-share]').textContent = v ? Math.round(share) + '%' : '—';
                    row.querySelector('[data-share-bar]').style.width = share + '%';
                    if (v) {
                        used++;
                        var seg = document.createElement('span');
                        seg.className = 'tone-' + row.dataset.tone; seg.style.flex = v; seg.title = row.dataset.name + ': ' + fmt(v);
                        bar.appendChild(seg);
                    }
                });
                $('sum-monthly').textContent = fmt(total);
                $('sum-annual').textContent = fmt(total * 12);
                $('sum-count').textContent = used + ' of ' + rows.length;

                var warn = '';
                var change = $('sum-change');
                if (change) {
                    var diff = total - current, pct = current ? diff / current * 100 : 0;
                    change.textContent = !total ? '—' : (diff === 0 ? 'No change' : (diff > 0 ? '+' : '−') + fmt(Math.abs(diff)) + ' (' + (diff > 0 ? '+' : '−') + Math.abs(Math.round(pct * 10) / 10) + '%)');
                    if (total && diff < 0) warn = 'This is a pay cut of ' + fmt(-diff) + ' a month.';
                    else if (total && pct > 50) warn = 'This is a ' + Math.round(pct) + '% raise - double-check the amounts.';
                }
                if (!total) warn = 'Enter an amount for at least one component.';
                $('sum-warn').hidden = !warn;
                $('sum-warn').querySelector('span').textContent = warn;
                var submit = $('sum-submit'); if (submit) submit.disabled = !total;
                $('date-hint').hidden = !(date.value && date.value > today);
            }

            var apply = $('split-apply');
            if (apply) {
                apply.addEventListener('click', function () {
                    var total = parseFloat($('split-total').value) || 0;
                    if (!total) return;
                    // Only use the percentages of components that exist; scale them to 100%.
                    var present = rows.filter(function (r) { return split[r.dataset.code] !== undefined; });
                    if (!present.length) { rows[0].querySelector('[data-amount]').value = total; refresh(); return; }
                    var sum = present.reduce(function (s, r) { return s + split[r.dataset.code]; }, 0), given = 0;
                    rows.forEach(function (r) { r.querySelector('[data-amount]').value = ''; });
                    present.forEach(function (r, i) {
                        var amount = i === present.length - 1 ? total - given : Math.round(total * split[r.dataset.code] / sum);
                        given += amount;
                        r.querySelector('[data-amount]').value = amount;
                    });
                    refresh();
                });
            }
            document.querySelectorAll('[data-date]').forEach(function (chip) {
                chip.addEventListener('click', function () { if (!date.min || chip.dataset.date >= date.min) date.value = chip.dataset.date; refresh(); });
            });
            document.getElementById('structure-form').addEventListener('input', refresh);
            refresh();
        })();
    </script>
@endpush
