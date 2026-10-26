@extends('layouts.admin')

@section('title', 'Approvals')

@php
    use App\Http\Controllers\Admin\Mss\TeamApprovalController as Approvals;
    use Illuminate\Support\Str;

    $types = Approvals::TYPES;
    $pageUrl = fn (array $overrides = []) => route('admin.team-approvals.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'Everything pending', 'tone' => 'indigo', 'count' => $counts['all']],
        'mine' => ['label' => 'Waiting for me', 'tone' => 'amber', 'count' => $counts['mine']],
        'others' => ['label' => 'Waiting on someone else', 'tone' => 'slate', 'count' => $counts['others']],
    ];
    $filterValues = ['type' => $types[$state['type']]['label'] ?? null, 'employee_id' => $people->firstWhere('id', $state['employee_id'])?->name];
    $activeFilters = collect(['type' => 'Kind', 'employee_id' => 'Person'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    // Ready-made reasons per kind of request, so a rejection takes one click and still says why.
    $presets = [
        'leave' => ['Too many of the team away then', 'Not enough leave balance', 'Critical deadline that week'],
        'regularization' => ['Times do not match the records', 'Please give more detail'],
        'wfh' => ['Needed in the office that day', 'Too many of the team away then'],
        'overtime' => ['Not agreed in advance', 'Hours do not match attendance'],
        'expense' => ['Not a business expense', 'Above the allowed limit', 'Already claimed'],
        'travel' => ['Can be done remotely', 'No budget this month', 'Dates clash with other work'],
    ];
    $routeFor = fn (array $item, string $what) => route('admin.team-approvals.'.$item['type'].'.'.$what, $item['id']);
@endphp

@section('content')
    <x-page-title title="Approvals" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    @unless ($hasTeam)
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-users font-24 d-block mb-2"></i>
                <div class="fw-semibold text-body mb-1">Nobody reports to you</div>
                <span class="font-13">Requests from the people below you in the reporting line come here for your approval. HR sets who reports to whom on each employee's profile.</span>
            </div>
        </div>
    @else
        <div class="kpi-strip">
            <x-kpi-card label="Waiting for me" :value="$kpis['mine']" icon="ti-clock" :tone="$kpis['mine'] ? 'amber' : 'success'" :hint="$kpis['mine'] ? Str::limit($kpis['mine_types']->implode(', '), 46) : 'You are all caught up'" :href="$kpis['mine'] ? $pageUrl(['tab' => 'mine', 'type' => null, 'employee_id' => null]) : null" />
            <x-kpi-card label="Oldest waiting" :value="$kpis['mine'] ? ($kpis['oldest'] === 0 ? 'Today' : $kpis['oldest'].' '.Str::plural('day', $kpis['oldest'])) : '—'" icon="ti-history" :tone="$kpis['slow'] ? 'rose' : 'teal'" :hint="$kpis['slow'] ? $kpis['slow'].' waiting '.Approvals::SLOW_DAYS.' days or more' : ($kpis['mine'] ? 'Nothing has waited long' : 'Nothing is waiting on you')" />
            <x-kpi-card label="Starting within 3 days" :value="$kpis['starting_soon']" icon="ti-calendar-event" :tone="$kpis['starting_soon'] ? 'rose' : 'indigo'" :hint="$kpis['starting_soon'] ? 'Leave, travel or work from home that is about to begin' : 'Nothing urgent by date'" />
            <x-kpi-card label="Decided by me this week" :value="$kpis['decided_week']" icon="ti-checks" tone="indigo" :hint="$kpis['others'] ? $kpis['others'].' more waiting on someone else' : 'Approved or rejected in the last 7 days'" />
        </div>

        <div class="card list-view">
            <form method="GET" action="{{ route('admin.team-approvals.index') }}">
                @if ($state['tab'] !== '')
                    <input type="hidden" name="tab" value="{{ $state['tab'] }}">
                @endif
                <div class="list-toolbar">
                    <nav class="list-tabs" aria-label="Whose turn">
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
                        <select name="type" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Kind of request">
                            <option value="">Every kind</option>
                            @foreach ($types as $value => $type)
                                @if (($typeCounts[$value] ?? 0) || $state['type'] === $value)
                                    <option value="{{ $value }}" @selected($state['type'] === $value)>{{ $type['label'] }} ({{ $typeCounts[$value] ?? 0 }})</option>
                                @endif
                            @endforeach
                        </select>
                        @if ($people->count() > 1 || $state['employee_id'])
                            <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Team member">
                                <option value="">Everyone</option>
                                @foreach ($people as $person)
                                    <option value="{{ $person->id }}" @selected($state['employee_id'] === $person->id)>{{ $person->name }}</option>
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

            @if ($actable > 1)
                <form method="POST" action="{{ route('admin.team-approvals.bulk-approve') }}" id="bulk-approve-form" class="bulk-bar" onsubmit="return confirm('Approve all selected requests?');">
                    @csrf
                    <label class="bulk-select">
                        <input type="checkbox" class="form-check-input" id="select-all" aria-label="Select all on this page">
                        <span id="bulk-summary">Select requests</span>
                    </label>
                    <div class="bulk-actions">
                        <button type="submit" class="btn btn-sm review-btn is-approve" disabled data-bulk-action><i class="ti ti-checks"></i> Approve selected</button>
                    </div>
                </form>
            @endif
        </div>

        @if ($items->isEmpty())
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="ti ti-checks font-24 d-block mb-2"></i>
                    @if ($activeFilters->isNotEmpty() || $state['tab'] !== '')
                        <div class="fw-semibold text-body mb-1">{{ $state['tab'] === 'mine' ? 'Nothing is waiting on you' : 'No requests match' }}</div>
                        <span class="font-13"><a href="{{ route('admin.team-approvals.index') }}">Show everything pending</a></span>
                    @else
                        <div class="fw-semibold text-body mb-1">All caught up</div>
                        <span class="font-13">Your team has no requests waiting for a decision.</span>
                    @endif
                </div>
            </div>
        @else
            <div class="approval-list">
                @foreach ($items as $item)
                    @php
                        $type = $types[$item['type']];
                        $employee = $item['employee'];
                        $flow = $item['flow'];
                        $facts = $context[$item['key']] ?? [];
                        $startsIn = $item['date'] ? (int) today()->diffInDays($item['date'], false) : null;
                        $dated = in_array($item['type'], ['leave', 'wfh', 'travel'], true);
                    @endphp
                    <article class="approval-card tone-{{ $type['tone'] }} {{ $item['waited'] >= Approvals::SLOW_DAYS && $item['can_act'] ? 'is-urgent' : '' }}">
                        @if ($actable > 1 && $item['can_act'])
                            <input type="checkbox" class="form-check-input approval-select" name="items[]" value="{{ $item['key'] }}" form="bulk-approve-form" data-row-select aria-label="Select the {{ strtolower($type['label']) }} request of {{ $employee->name }}">
                        @endif

                        <div class="approval-who">
                            <x-avatar :person="$employee" />
                            <div class="min-w-0">
                                <div class="list-user-name">{{ $employee->name }}</div>
                                <div class="list-user-meta">{{ $employee->designation ?: ($employee->department?->name ?? $employee->employee_code) }}</div>
                                <div class="list-subtext mt-1"><i class="ti ti-clock"></i> asked {{ $item['waited'] === 0 ? 'today' : $item['waited'].' '.Str::plural('day', $item['waited']).' ago' }}</div>
                            </div>
                        </div>

                        <div class="approval-leave">
                            <span class="tag tone-{{ $type['tone'] }}"><i class="ti {{ $type['icon'] }}"></i>{{ $type['label'] }}</span>
                            <div class="approval-dates">
                                @if ($item['date'])
                                    <span class="when-block tone-{{ $type['tone'] }}"><span class="when-block-month">{{ $item['date']->format('M') }}</span><span class="when-block-day">{{ $item['date']->format('j') }}</span></span>
                                @endif
                                <span class="approval-days">{{ $item['headline'] }}</span>
                            </div>
                            <div class="font-12 text-muted">{{ $item['when'] }}</div>
                            @if ($item['waited'] >= Approvals::SLOW_DAYS && $item['can_act'])
                                <span class="countdown tone-danger"><i class="ti ti-alert-triangle"></i> Waiting {{ $item['waited'] }}d</span>
                            @elseif ($dated && $startsIn !== null && $startsIn <= 3)
                                <span class="countdown tone-{{ $startsIn < 0 ? 'danger' : 'amber' }}"><i class="ti ti-calendar-event"></i> {{ $startsIn < 0 ? 'Started '.abs($startsIn).'d ago' : ($startsIn === 0 ? 'Starts today' : 'Starts in '.$startsIn.'d') }}</span>
                            @endif
                        </div>

                        <div class="approval-context">
                            @if ($item['reason'])
                                <blockquote class="approval-reason" title="{{ $item['reason'] }}">“{{ Str::limit($item['reason'], 140) }}”</blockquote>
                            @else
                                <blockquote class="approval-reason text-muted">No reason given.</blockquote>
                            @endif
                            <div class="approval-facts">
                                @foreach ($facts as [$tone, $icon, $fact])
                                    <span class="approval-fact {{ $tone ? 'is-'.$tone : '' }}"><i class="ti {{ $icon }}"></i> {{ $fact }}</span>
                                @endforeach
                                @if (! $item['can_act'])
                                    <span class="approval-fact is-good"><i class="ti ti-check"></i> You approved · waiting for {{ $flow['waiting'] ?? 'the next approver' }}</span>
                                @elseif ($flow['required'] > 1)
                                    <span class="approval-fact"><i class="ti ti-sitemap"></i> Step {{ min($flow['done'] + 1, $flow['required']) }} of {{ $flow['required'] }}{{ $item['my_turn'] ? ' · your turn' : ' · waiting for '.($flow['waiting'] ?? 'approval') }}</span>
                                @elseif (! $item['my_turn'])
                                    <span class="approval-fact"><i class="ti ti-sitemap"></i> Waiting for {{ $flow['waiting'] ?? 'their manager' }}; you can decide for them</span>
                                @endif
                            </div>
                        </div>

                        <div class="approval-actions">
                            @if ($item['can_act'])
                                <form action="{{ $routeFor($item, 'approve') }}" method="POST" onsubmit="this.querySelector('button').disabled = true">
                                    @csrf
                                    <button type="submit" class="btn btn-sm review-btn is-approve w-100"><i class="ti ti-check"></i> Approve</button>
                                </form>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm review-btn is-reject w-100" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                        <i class="ti ti-x"></i> Reject
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                        <form action="{{ $routeFor($item, 'reject') }}" method="POST">
                                            @csrf
                                            <label class="form-label font-12 fw-semibold">Reason for rejecting</label>
                                            <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" minlength="3" maxlength="500" placeholder="{{ Str::before($employee->name, ' ') }} is told this" required></textarea>
                                            <div class="quick-chips mt-0 mb-2">
                                                @foreach ($presets[$item['type']] as $preset)
                                                    <button type="button" class="quick-chip" onclick="this.closest('form').querySelector('textarea').value = this.textContent">{{ $preset }}</button>
                                                @endforeach
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-danger w-100">Reject request</button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <span class="tag tone-success"><i class="ti ti-check"></i>Approved by you</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @endunless
@endsection

@push('scripts')
    <script>
        (function () {
            var all = document.getElementById('select-all');
            if (!all) return;
            var rows = Array.prototype.slice.call(document.querySelectorAll('[data-row-select]'));
            var action = document.querySelector('[data-bulk-action]');
            var summary = document.getElementById('bulk-summary');
            function refresh() {
                var picked = rows.filter(function (box) { return box.checked; });
                action.disabled = picked.length === 0;
                summary.textContent = picked.length ? picked.length + ' selected' : 'Select requests';
                all.checked = picked.length > 0 && picked.length === rows.length;
                all.indeterminate = picked.length > 0 && picked.length < rows.length;
                rows.forEach(function (box) { box.closest('.approval-card').classList.toggle('is-selected', box.checked); });
            }
            all.addEventListener('change', function () { rows.forEach(function (box) { box.checked = all.checked; }); refresh(); });
            rows.forEach(function (box) { box.addEventListener('change', refresh); });
            refresh();
        })();
    </script>
@endpush
