@extends('layouts.admin')

@section('title', 'Notice Period')

@php
    use App\Models\Resignation;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $pageUrl = fn (array $overrides = []) => route('admin.notice-period.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'Everyone on notice', 'tone' => 'indigo', 'count' => $counts['all']],
        'week' => ['label' => 'Leaving this week', 'tone' => 'amber', 'count' => $counts['week']],
        'overdue' => ['label' => 'Past last day', 'tone' => 'rose', 'count' => $counts['overdue']],
        'short' => ['label' => 'Early release', 'tone' => 'teal', 'count' => $counts['short']],
    ];
    $filterValues = ['department_id' => $departments[$state['department_id']] ?? null, 'search' => $state['search']];
    $activeFilters = collect(['department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $next = $kpis['next'];
    $canSeeClearance = \Illuminate\Support\Facades\Route::has('admin.clearances.index');
@endphp

@section('content')
    <x-page-title title="Notice Period" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Serving notice" :value="$kpis['serving']" icon="ti-history" tone="indigo" :hint="$next ? 'Next to leave: '.Str::limit($next['name'], 18).' on '.$next['resignation']->last_working_day->format('M j') : ($kpis['serving'] ? 'All past their last day' : 'Nobody is on notice')" />
        <x-kpi-card label="Leaving this week" :value="$kpis['week']" icon="ti-calendar-event" :tone="$kpis['week'] ? 'amber' : 'teal'" :hint="$kpis['week'] ? 'Finish handover and clearance' : 'Nobody leaves in the next 7 days'" />
        <x-kpi-card label="Past last day" :value="$kpis['overdue']" icon="ti-alert-triangle" :tone="$kpis['overdue'] ? 'rose' : 'success'" :hint="$kpis['overdue'] ? 'Gone, but the separation is not completed' : 'Every finished notice is closed'" />
        <x-kpi-card label="Early releases" :value="$kpis['short']" icon="ti-player-skip-forward" tone="teal" :hint="$kpis['short'] ? $kpis['short_days'].' '.Str::plural('day', $kpis['short_days']).' of notice not served'.($kpis['recovery'] > 0 ? ' · worth about '.$money($kpis['recovery']) : '') : 'Everyone is serving their full notice'" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.notice-period.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Notice status">
                    @foreach ($tabs as $value => $tab)
                        @if ($value === '' || $tab['count'] || $state['tab'] === $value)
                            <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                                {{ $tab['label'] }}
                                <span class="list-tab-count">{{ $tab['count'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search employee" aria-label="Search employees">
                    </div>
                    @if ($departments->count() > 1 || $state['department_id'])
                        <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected($state['department_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $pageUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Employee</th>
                        <th>Notice</th>
                        <th style="min-width: 200px">Last working day</th>
                        <th>Compared with notice</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $r = $row['resignation'];
                            $person = $r->employee;
                            $daysLeft = $row['days_left'];
                            $total = max(1, (int) $r->resignation_date->diffInDays($r->last_working_day));
                            $served = min(100, max(0, (int) round((int) $r->resignation_date->diffInDays(today(), false) / $total * 100)));
                            $cleared = $r->clearances->where('is_cleared', true)->count();
                            $clearTotal = $r->clearances->count();
                            $own = $ownEmployeeId !== null && (int) $r->employee_id === (int) $ownEmployeeId;
                        @endphp
                        <tr class="{{ $daysLeft < 0 || ($daysLeft <= 7) ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <x-avatar :person="$person" />
                                    <div class="list-user-text">
                                        <div class="list-user-name">{{ $row['name'] }}</div>
                                        <div class="list-user-meta">{{ collect([$person?->employee_code, $person?->designation, $person?->department?->name])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                {{ $r->notice_period_days }} {{ Str::plural('day', (int) $r->notice_period_days) }}
                                <div class="payslip-parts">resigned {{ $r->resignation_date->format('M j') }} · ends {{ $row['notice_ends']->format('M j, Y') }}</div>
                            </td>
                            <td>
                                <div class="text-nowrap fw-semibold">{{ $r->last_working_day->format('D, M j, Y') }}</div>
                                @if ($daysLeft >= 0)
                                    <div class="mini-progress tone-{{ $daysLeft <= 7 ? 'rose' : 'indigo' }}">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ $served }}%"></span></div>
                                        <span class="mini-progress-label">{{ $daysLeft === 0 ? 'today' : $daysLeft.' d left' }}</span>
                                    </div>
                                @else
                                    <div class="payslip-parts text-danger">passed {{ abs($daysLeft) }} {{ Str::plural('day', abs($daysLeft)) }} ago · separation not completed</div>
                                @endif
                            </td>
                            <td>
                                @if ($row['shortfall'])
                                    <span class="tag tone-teal"><i class="ti ti-player-skip-forward"></i>{{ $row['shortfall'] }} {{ Str::plural('day', $row['shortfall']) }} early</span>
                                    <div class="payslip-parts">{{ $row['recovery'] ? 'unserved notice worth about '.$money($row['recovery']) : 'no salary structure to value it' }}</div>
                                @elseif ($row['extra'])
                                    <span class="tag tone-amber"><i class="ti ti-clock"></i>{{ $row['extra'] }} {{ Str::plural('day', $row['extra']) }} beyond notice</span>
                                @else
                                    <span class="tag tone-success"><i class="ti ti-circle-check"></i>Full notice</span>
                                @endif
                                @if ($clearTotal)
                                    <div class="payslip-parts">Clearance {{ $cleared }}/{{ $clearTotal }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @can('update', $r)
                                        @if ($own)
                                            <span class="font-12 text-muted" title="Someone else has to change your own last day">Your own notice</span>
                                        @elseif ($row['locked'])
                                            <span class="font-12 text-muted" title="The final settlement was worked out to this date">Settlement {{ $r->finalSettlement->status }}</span>
                                        @else
                                            <button type="button" class="btn btn-sm review-btn" data-bs-toggle="modal" data-bs-target="#notice-modal"
                                                    data-action="{{ route('admin.notice-period.update', $r) }}" data-who="{{ $row['name'] }}" data-resigned="{{ $r->resignation_date->toDateString() }}" data-notice="{{ (int) $r->notice_period_days }}"
                                                    data-ends="{{ $row['notice_ends']->toDateString() }}" data-last="{{ $r->last_working_day->toDateString() }}" data-daily="{{ $row['monthly'] ? round($row['monthly'] / 30, 2) : '' }}"><i class="ti ti-calendar-event"></i> Change last day</button>
                                        @endif
                                    @endcan
                                    @if ($canSeeClearance && $clearTotal && $cleared < $clearTotal)
                                        <a href="{{ route('admin.clearances.index') }}" class="btn-icon" title="Clearance checklist ({{ $cleared }} of {{ $clearTotal }} done)"><i class="ti ti-list-check font-16"></i></a>
                                    @endif
                                    @if ($daysLeft <= 0)
                                        <a href="{{ route('admin.resignations.index', ['tab' => 'accepted']) }}" class="btn-icon btn-icon-accent" title="Complete the separation on the Resignation page"><i class="ti ti-flag font-16"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-history font-24 d-block mb-2"></i>
                                @if ($activeFilters->isNotEmpty() || $state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">Nobody matches</div>
                                    <span class="font-13"><a href="{{ route('admin.notice-period.index') }}">Show everyone on notice</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">Nobody is serving notice</div>
                                    <span class="font-13">People appear here once their resignation is accepted, until the separation is completed.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($canUpdate)
        <div class="modal fade" id="notice-modal" tabindex="-1" aria-labelledby="notice-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="notice-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="notice-modal-title">Change last working day</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="notice-intro"></div>
                        <label class="form-label" for="notice-last">Last working day</label>
                        <input type="date" name="last_working_day" id="notice-last" class="form-control" style="max-width: 220px" required>
                        <div class="quick-chips">
                            <button type="button" class="quick-chip" data-set="today">Release today</button>
                            <button type="button" class="quick-chip" data-set="full">Full notice</button>
                            <button type="button" class="quick-chip" data-set="week">One more week</button>
                        </div>
                        <div class="run-note tone-indigo mt-3" id="notice-effect"><i class="ti ti-info-circle"></i><span></span></div>
                        <label class="form-label mt-3" for="notice-note">Why <span class="text-muted fw-normal">(optional, kept on the record)</span></label>
                        <input type="text" name="note" id="notice-note" maxlength="300" class="form-control" placeholder="e.g. Handover finished early">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var modal = el('notice-modal');
            if (!modal) return;
            var today = @json(today()->toDateString());
            var maxDays = {{ (int) $maxDays }};
            var addDays = function (iso, n) { var d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + n); return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); };
            var gap = function (a, b) { return Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000); };
            var nice = function (s) { return new Date(s + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); };
            var fmt = function (n) { return Number(n).toLocaleString('en-US', { maximumFractionDigits: 0 }); };
            var current = {};

            // What the chosen date means for the notice, in a sentence.
            function effect() {
                var last = el('notice-last').value, text = '';
                if (!last) text = 'Pick the last working day.';
                else if (last < current.resigned) text = 'That is before the resignation date. Saving will be refused.';
                else if (gap(current.resigned, last) > maxDays) text = 'That is more than a year after the resignation. Saving will be refused.';
                else if (last === current.last) text = 'This is the current last working day; nothing changes.';
                else {
                    var short = last < current.ends ? gap(last, current.ends) : 0, extra = last > current.ends ? gap(current.ends, last) : 0;
                    text = short ? short + (short === 1 ? ' day' : ' days') + ' short of the ' + current.notice + '-day notice' + (current.daily ? ', worth about ' + fmt(current.daily * short) + ' at their pay' : '') + '. Any recovery is decided in the final settlement.'
                        : (extra ? extra + (extra === 1 ? ' day' : ' days') + ' beyond the ' + current.notice + '-day notice.' : 'Exactly the full ' + current.notice + '-day notice.');
                    if (last < today) text += ' This date has already passed, so the separation can be completed straight away.';
                }
                el('notice-effect').querySelector('span').textContent = text;
            }

            modal.addEventListener('show.bs.modal', function (event) {
                var b = event.relatedTarget;
                if (!b) return;
                current = { resigned: b.dataset.resigned, ends: b.dataset.ends, last: b.dataset.last, notice: b.dataset.notice, daily: parseFloat(b.dataset.daily) || 0 };
                el('notice-form').action = b.dataset.action;
                el('notice-intro').textContent = (b.dataset.who || 'The employee') + ' resigned on ' + nice(current.resigned) + ' with ' + current.notice + ' days notice, which ends on ' + nice(current.ends) + '.';
                el('notice-last').value = current.last; el('notice-last').min = current.resigned; el('notice-last').max = addDays(current.resigned, maxDays);
                el('notice-note').value = '';
                effect();
            });
            el('notice-last').addEventListener('input', effect);
            modal.querySelectorAll('[data-set]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var to = chip.dataset.set;
                    el('notice-last').value = to === 'today' ? (today < current.resigned ? current.resigned : today) : (to === 'full' ? current.ends : addDays(el('notice-last').value || current.last, 7));
                    effect();
                });
            });
        })();
    </script>
@endpush
