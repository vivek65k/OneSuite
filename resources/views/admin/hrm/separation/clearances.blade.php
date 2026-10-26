@extends('layouts.admin')

@section('title', 'Clearance')

@php
    use App\Models\SeparationClearance;
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.clearances.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'On notice', 'tone' => 'indigo', 'count' => $counts['all']],
        'open' => ['label' => 'Items pending', 'tone' => 'amber', 'count' => $counts['open']],
        'ready' => ['label' => 'Fully cleared', 'tone' => 'success', 'count' => $counts['ready']],
        'closed' => ['label' => 'Closed', 'tone' => 'slate', 'count' => $counts['closed']],
    ];
    $filterValues = ['waiting' => SeparationClearance::DEPARTMENTS[$state['waiting']] ?? null, 'search' => $state['search']];
    $activeFilters = collect(['waiting' => 'Waiting on', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $icons = ['it' => 'ti-device-laptop', 'finance' => 'ti-cash', 'admin' => 'ti-building', 'hr' => 'ti-users', 'manager' => 'ti-user-check'];
    $hintLinks = ['assets' => ['admin.asset-returns.index', 'Asset Return'], 'interview' => ['admin.exit-interviews.index', 'Exit Interviews']];
@endphp

@section('content')
    <x-page-title title="Clearance" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="In clearance" :value="$kpis['people']" icon="ti-list-check" tone="indigo" :hint="$kpis['people'] ? $kpis['items'].' sign-offs across '.$kpis['people'].' '.Str::plural('person', $kpis['people']) : 'Nobody is serving notice'" />
        <x-kpi-card label="Items pending" :value="$kpis['pending']" icon="ti-clock" :tone="$kpis['pending'] ? 'amber' : 'success'" :hint="$kpis['slowest'] ? 'Most waiting on '.$kpis['slowest'][0].' ('.$kpis['slowest'][1].')' : 'Nothing is waiting'" />
        <x-kpi-card label="Fully cleared" :value="$kpis['ready']" icon="ti-circle-check" tone="success" :hint="$kpis['ready_now'] ? $kpis['ready_now'].' can be completed now' : ($kpis['ready'] ? 'Waiting for the last working day' : 'Nobody has every sign-off yet')" />
        <x-kpi-card label="Left, not cleared" :value="$kpis['late']" icon="ti-alert-triangle" :tone="$kpis['late'] ? 'rose' : 'teal'" :hint="$kpis['late'] ? 'Last day has passed with items open' : 'Nobody has left with items open'" />
    </div>

    <div class="card list-view">
        <form method="GET" action="{{ route('admin.clearances.index') }}">
            @if ($state['tab'] !== '')
                <input type="hidden" name="tab" value="{{ $state['tab'] }}">
            @endif
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Clearance status">
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
                    <select name="waiting" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Waiting on">
                        <option value="">Waiting on anyone</option>
                        @foreach (SeparationClearance::DEPARTMENTS as $key => $label)
                            <option value="{{ $key }}" @selected($state['waiting'] === $key)>Waiting on {{ $label }}</option>
                        @endforeach
                    </select>
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

        @if ($rows->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="ti ti-list-check font-24 d-block mb-2"></i>
                @if ($activeFilters->isNotEmpty() || $state['tab'] !== '')
                    <div class="fw-semibold text-body mb-1">Nobody matches</div>
                    <span class="font-13"><a href="{{ route('admin.clearances.index') }}">Show everyone on notice</a></span>
                @else
                    <div class="fw-semibold text-body mb-1">No clearance checklists are open</div>
                    <span class="font-13">A checklist is created for each department when a resignation is accepted.</span>
                @endif
            </div>
        @endif
    </div>

    @foreach ($rows as $row)
        @php
            $r = $row['resignation'];
            $person = $r->employee;
            $daysLeft = $row['days_left'];
            $closed = $row['stage'] === 'closed';
            $own = $ownEmployeeId !== null && (int) $r->employee_id === (int) $ownEmployeeId;
            $canAct = $canUpdate && ! $closed && ! $own;
            $percent = $row['total'] ? (int) round($row['done'] / $row['total'] * 100) : 0;
            $openHints = $row['items']->where('is_cleared', false)->filter(fn ($i) => in_array($row['hints'][$i->department][0] ?? null, ['bad'], true))->count();
        @endphp
        <div class="card list-view">
            <div class="list-toolbar">
                <div class="list-user">
                    <x-avatar :person="$person" />
                    <div class="list-user-text">
                        <div class="list-user-name">{{ $row['name'] }}</div>
                        <div class="list-user-meta">{{ collect([$person?->employee_code, $person?->designation, $person?->department?->name])->filter()->implode(' · ') }}</div>
                    </div>
                </div>
                <div class="list-toolbar-actions">
                    @if ($closed)
                        <span class="tag tone-slate"><i class="ti ti-lock"></i>Resignation {{ $r->status }}</span>
                    @elseif ($daysLeft < 0)
                        <span class="tag tone-rose"><i class="ti ti-alert-triangle"></i>Last day was {{ $r->last_working_day->format('M j') }} · {{ abs($daysLeft) }} {{ Str::plural('day', abs($daysLeft)) }} ago</span>
                    @else
                        <span class="tag tone-{{ $daysLeft <= 7 ? 'amber' : 'indigo' }}"><i class="ti ti-calendar-event"></i>Last day {{ $r->last_working_day->format('M j, Y') }} · {{ $daysLeft === 0 ? 'today' : 'in '.$daysLeft.' '.Str::plural('day', $daysLeft) }}</span>
                    @endif
                    <div class="mini-progress tone-{{ $percent === 100 ? 'success' : 'indigo' }}" style="min-width: 150px">
                        <div class="mini-progress-bar is-toned"><span style="width: {{ $percent }}%"></span></div>
                        <span class="mini-progress-label">{{ $row['done'] }} of {{ $row['total'] }} cleared</span>
                    </div>
                    @if ($canAct && $row['done'] < $row['total'])
                        <form action="{{ route('admin.clearances.clear-all', $r) }}" method="POST" class="d-inline"
                              onsubmit="return confirm(@js('Mark all '.($row['total'] - $row['done']).' open items cleared for '.$row['name'].'?'.($openHints ? ' '.$openHints.' of them still '.($openHints === 1 ? 'has' : 'have').' something outstanding.' : '')))">
                            @csrf
                            <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-checks"></i> Clear all</button>
                        </form>
                    @elseif ($row['stage'] === 'ready')
                        <a href="{{ route('admin.resignations.index', ['tab' => 'accepted']) }}" class="btn btn-sm review-btn" title="{{ $daysLeft > 0 ? 'Can be completed once the last working day arrives' : 'Complete the separation on the Resignation page' }}"><i class="ti ti-flag"></i> {{ $daysLeft > 0 ? 'Ready to complete' : 'Complete separation' }}</a>
                    @elseif ($own)
                        <span class="font-12 text-muted" title="Someone else has to sign off your own exit">Your own exit</span>
                    @endif
                </div>
            </div>
            <div class="table-responsive">
                <table class="table list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 22%">Department</th>
                            <th style="width: 26%">Sign-off</th>
                            <th>To check</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($row['items'] as $item)
                            @php
                                $label = SeparationClearance::DEPARTMENTS[$item->department] ?? Str::headline($item->department);
                                $hint = $row['hints'][$item->department] ?? null;
                                $link = $hint && $hint[2] && \Illuminate\Support\Facades\Route::has($hintLinks[$hint[2]][0]) ? $hintLinks[$hint[2]] : null;
                            @endphp
                            <tr class="{{ ! $item->is_cleared && ! $closed ? 'is-pending-row' : '' }}">
                                <td>
                                    <div class="list-user">
                                        <span class="list-avatar list-avatar-square tone-{{ $item->is_cleared ? 'success' : 'amber' }}"><i class="ti {{ $icons[$item->department] ?? 'ti-list-check' }}"></i></span>
                                        <div class="list-user-text"><div class="list-user-name">{{ $label }}</div></div>
                                    </div>
                                </td>
                                <td>
                                    @if ($item->is_cleared)
                                        <span class="tag tone-success"><i class="ti ti-circle-check"></i>Cleared</span>
                                        <div class="payslip-parts">{{ collect([$item->clearedBy?->name ? 'by '.$item->clearedBy->name : null, $item->cleared_at?->format('M j, Y')])->filter()->implode(' · ') }}</div>
                                    @else
                                        <span class="tag tone-{{ $closed ? 'slate' : 'amber' }}"><i class="ti ti-clock"></i>{{ $closed ? 'Never cleared' : 'Pending' }}</span>
                                    @endif
                                    @if ($item->remarks)
                                        <div class="payslip-parts" title="{{ $item->remarks }}">{{ Str::limit($item->remarks, 70) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($hint && ! $closed)
                                        <span class="font-13 {{ ['bad' => 'text-danger', 'warn' => 'text-warning', 'good' => 'text-muted'][$hint[0]] }}"><i class="ti {{ $hint[0] === 'good' ? 'ti-check' : 'ti-alert-circle' }}"></i> {{ $hint[1] }}</span>
                                        @if ($link && $hint[0] !== 'good')
                                            <a href="{{ route($link[0]) }}" class="font-12 ms-1">{{ $link[1] }}</a>
                                        @endif
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($canAct)
                                        <div class="list-row-actions">
                                            @if ($item->is_cleared)
                                                <button type="button" class="btn-icon btn-icon-danger" title="Reopen this sign-off" data-bs-toggle="modal" data-bs-target="#clearance-modal"
                                                        data-action="{{ route('admin.clearances.toggle', $item) }}" data-cleared="0" data-what="{{ $label }}" data-who="{{ $row['name'] }}"><i class="ti ti-rotate font-16"></i></button>
                                            @else
                                                <button type="button" class="btn-icon" title="Clear with a note" data-bs-toggle="modal" data-bs-target="#clearance-modal"
                                                        data-action="{{ route('admin.clearances.toggle', $item) }}" data-cleared="1" data-what="{{ $label }}" data-who="{{ $row['name'] }}" data-warn="{{ ($hint[0] ?? '') === 'bad' ? $hint[1] : '' }}"><i class="ti ti-edit font-16"></i></button>
                                                <form action="{{ route('admin.clearances.toggle', $item) }}" method="POST" class="d-inline"
                                                      @if (($hint[0] ?? '') === 'bad') onsubmit="return confirm(@js($hint[1].'. Mark '.$label.' cleared anyway?'))" @endif>
                                                    @csrf
                                                    <input type="hidden" name="cleared" value="1">
                                                    <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-check"></i> Clear</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    @if ($canUpdate)
        <div class="modal fade" id="clearance-modal" tabindex="-1" aria-labelledby="clearance-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="clearance-form">
                    @csrf
                    <input type="hidden" name="cleared" id="clearance-cleared" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title" id="clearance-modal-title">Clearance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="clearance-intro"></div>
                        <div class="run-note tone-rose mb-3 d-none" id="clearance-warn"><i class="ti ti-alert-circle"></i><span></span></div>
                        <label class="form-label" for="clearance-remarks"><span id="clearance-label">Note</span> <span class="text-muted fw-normal">(optional, kept on the record)</span></label>
                        <input type="text" name="remarks" id="clearance-remarks" maxlength="300" class="form-control">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-de-primary text-light" id="clearance-submit">Save</button>
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
            var modal = el('clearance-modal');
            if (!modal) return;
            modal.addEventListener('show.bs.modal', function (event) {
                var b = event.relatedTarget;
                if (!b) return;
                var clearing = b.dataset.cleared === '1';
                el('clearance-form').action = b.dataset.action;
                el('clearance-cleared').value = clearing ? '1' : '0';
                el('clearance-modal-title').textContent = (clearing ? 'Clear ' : 'Reopen ') + b.dataset.what + ' sign-off';
                el('clearance-intro').textContent = clearing
                    ? 'Confirms that ' + b.dataset.who + ' owes nothing to ' + b.dataset.what + '.'
                    : 'Takes back the ' + b.dataset.what + ' sign-off for ' + b.dataset.who + '. The separation cannot be completed until it is cleared again.';
                el('clearance-warn').classList.toggle('d-none', !b.dataset.warn);
                el('clearance-warn').querySelector('span').textContent = b.dataset.warn ? b.dataset.warn + '. You can still clear it; say why below.' : '';
                el('clearance-label').textContent = clearing ? 'Note' : 'Why it is reopened';
                el('clearance-remarks').value = '';
                el('clearance-remarks').placeholder = clearing ? 'e.g. Laptop returned, charger deducted' : 'e.g. Access card still not handed in';
                el('clearance-submit').textContent = clearing ? 'Mark cleared' : 'Reopen';
            });
        })();
    </script>
@endpush
