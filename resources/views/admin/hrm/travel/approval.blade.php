@extends('layouts.admin')

@section('title', 'Travel Approval')

@php
    use App\Models\TravelRequest;
    use Illuminate\Support\Str;

    $money = fn ($n) => number_format((float) $n);
    $listUrl = fn (array $overrides = []) => route('admin.travel-approval.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $modeMeta = ['flight' => ['indigo', 'ti-plane'], 'train' => ['teal', 'ti-train'], 'bus' => ['amber', 'ti-bus'], 'car' => ['success', 'ti-car'], 'other' => ['slate', 'ti-map-pin']];
    $windows = ['' => ['All pending', $counts['all']], 'week' => ['Leaving within a week', $counts['week']], 'month' => ['Within 30 days', $counts['month']]];
    $filterValues = [
        'mode' => TravelRequest::MODES[$state['mode']] ?? null,
        'employee_id' => $employees[$state['employee_id']] ?? null,
        'department_id' => $departments[$state['department_id']] ?? null,
        'search' => $state['search'],
    ];
    $activeFilters = collect(['mode' => 'Travelling by', 'employee_id' => 'Employee', 'department_id' => 'Department', 'search' => 'Search'])
        ->filter(fn ($label, $key) => filled($state[$key]))
        ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => $filterValues[$key] ?? 'Unknown']);
    $clearAllUrl = $listUrl(['mode' => null, 'employee_id' => null, 'department_id' => null, 'search' => null]);
    $actable = $requests->getCollection()->filter(fn ($r) => $stages[$r->id]['can_act'] ?? false)->count();
    $oldestDays = $kpis['oldest'] ? (int) $kpis['oldest']->copy()->startOfDay()->diffInDays(today()) : null;
@endphp

@section('content')
    <x-page-title title="Travel Approval" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="Waiting for approval" :value="$kpis['waiting']" icon="ti-clock" :tone="$kpis['waiting'] ? 'amber' : 'success'" :hint="$kpis['waiting'] ? 'Travel requests to decide' : 'Nothing to approve'" />
        <x-kpi-card label="Leaving within a week" :value="$kpis['week']" icon="ti-alert-triangle" :tone="$kpis['week'] ? 'rose' : 'success'" :hint="$kpis['started'] ? $kpis['started'].' '.($kpis['started'] === 1 ? 'trip has' : 'trips have').' already started' : ($kpis['week'] ? 'Decide these first' : 'Nothing urgent')" />
        <x-kpi-card label="Estimated cost waiting" :value="$kpis['cost'] > 0 ? $money($kpis['cost']) : '—'" icon="ti-receipt" tone="indigo" :hint="$kpis['cost'] > 0 ? 'If everything is approved' : 'No estimates entered'" />
        <x-kpi-card label="Waiting longest" :value="$oldestDays !== null ? ($oldestDays === 0 ? 'Today' : $oldestDays.' '.Str::plural('day', $oldestDays)) : '—'" icon="ti-history" :tone="$oldestDays !== null && $oldestDays > 3 ? 'amber' : 'teal'" :hint="$oldestDays !== null ? 'Since the oldest request was made' : 'No request is waiting'" />
    </div>

    <div class="card list-view mb-3">
        <form method="GET" action="{{ route('admin.travel-approval.index') }}">
            @foreach (['window' => $state['window'], 'sort' => $state['sort']] as $key => $value)
                @if ($value !== '')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Leaving">
                    @foreach ($windows as $value => [$label, $count])
                        <a href="{{ $listUrl(['window' => $value ?: null]) }}" class="list-tab tone-{{ $value === 'week' ? 'rose' : 'indigo' }} {{ $state['window'] === $value ? 'active' : '' }}">
                            {{ $label }}
                            <span class="list-tab-count">{{ $count }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="list-toolbar-actions">
                    <div class="list-search">
                        <i class="ti ti-search"></i>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ $state['search'] }}" placeholder="Search name, place or purpose" aria-label="Search requests">
                    </div>
                    <select name="mode" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Travelling by">
                        <option value="">Any transport</option>
                        @foreach (TravelRequest::MODES as $value => $label)
                            <option value="{{ $value }}" @selected($state['mode'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($departments->count() > 1)
                        <select name="department_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Department">
                            <option value="">All departments</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected($state['department_id'] === $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Employee">
                        <option value="">All employees</option>
                        @foreach ($employees as $id => $name)
                            <option value="{{ $id }}" @selected($state['employee_id'] === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-arrows-sort font-14 me-1"></i> {{ $state['sort'] === 'oldest' ? 'Waiting longest' : 'Leaving soonest' }}</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item {{ $state['sort'] !== 'oldest' ? 'active' : '' }}" href="{{ $listUrl(['sort' => null]) }}">Leaving soonest</a></li>
                            <li><a class="dropdown-item {{ $state['sort'] === 'oldest' ? 'active' : '' }}" href="{{ $listUrl(['sort' => 'oldest']) }}">Waiting longest</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>

        @if ($activeFilters->isNotEmpty())
            <div class="list-subbar">
                <div class="list-chips">
                    @foreach ($activeFilters as $filter)
                        <a href="{{ $listUrl([$filter['key'] => null]) }}" class="filter-chip" title="Remove filter">{{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong> <i class="ti ti-x"></i></a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($canReview && $actable > 1)
            <form method="POST" action="{{ route('admin.travel-approval.bulk-approve') }}" id="bulk-approve-form" class="bulk-bar" onsubmit="return confirm('Approve all selected travel requests?');">
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

    @if ($requests->isEmpty())
        <div class="card">
            <div class="empty-state">
                <span class="empty-state-icon tone-success"><i class="ti ti-circle-check"></i></span>
                <h5>{{ $activeFilters->isNotEmpty() || $state['window'] !== '' ? 'No pending requests match' : 'All caught up' }}</h5>
                <p>
                    @if ($activeFilters->isNotEmpty() || $state['window'] !== '')
                        <a href="{{ route('admin.travel-approval.index') }}">Show every pending request</a>
                    @else
                        There are no travel requests waiting for approval.
                    @endif
                </p>
            </div>
        </div>
    @else
        <div class="approval-list">
            @foreach ($requests as $r)
                @php
                    $employee = $r->employee;
                    $meta = $modeMeta[$r->mode_of_travel] ?? $modeMeta['other'];
                    $stage = $stages[$r->id];
                    $startsIn = (int) today()->diffInDays($r->start_date, false);
                    $days = (int) $r->start_date->diffInDays($r->end_date) + 1;
                    $past = $history[$r->employee_id] ?? ['trips' => 0, 'cost' => 0.0];
                    $onLeave = $leaveClashes[$r->id] ?? collect();
                    $ordinal = fn (int $n) => $n.(in_array($n % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th'));
                @endphp
                <article class="approval-card tone-{{ $meta[0] }} {{ $startsIn <= 7 ? 'is-urgent' : '' }}">
                    @if ($canReview && $actable > 1 && $stage['can_act'])
                        <input type="checkbox" class="form-check-input approval-select" name="ids[]" value="{{ $r->id }}" form="bulk-approve-form" data-row-select aria-label="Select the request of {{ $employee?->name }}">
                    @endif

                    <div class="approval-who">
                        <x-avatar :person="$employee" />
                        <div class="min-w-0">
                            <div class="list-user-name">{{ $employee?->name ?? 'Removed employee' }}</div>
                            <div class="list-user-meta">{{ $employee?->department?->name ?? $employee?->employee_code }}</div>
                            <div class="list-subtext mt-1"><i class="ti ti-clock"></i> asked {{ $r->created_at?->diffForHumans() ?? 'earlier' }}</div>
                        </div>
                    </div>

                    <div class="approval-leave">
                        <span class="tag tone-{{ $meta[0] }}"><i class="ti {{ $meta[1] }}"></i>{{ $r->from_location }} to {{ $r->to_location }}</span>
                        <div class="approval-dates">
                            <span class="when-block tone-{{ $meta[0] }}"><span class="when-block-month">{{ $r->start_date->format('M') }}</span><span class="when-block-day">{{ $r->start_date->format('j') }}</span></span>
                            @unless ($r->start_date->isSameDay($r->end_date))
                                <i class="ti ti-arrow-right text-muted"></i>
                                <span class="when-block tone-{{ $meta[0] }}"><span class="when-block-month">{{ $r->end_date->format('M') }}</span><span class="when-block-day">{{ $r->end_date->format('j') }}</span></span>
                            @endunless
                            <span class="approval-days">{{ $days }}<small>{{ $days === 1 ? 'day' : 'days' }}</small></span>
                        </div>
                        @if ($startsIn < 0)
                            <span class="countdown tone-danger"><i class="ti ti-alert-triangle"></i> Left {{ abs($startsIn) }}d ago</span>
                        @elseif ($startsIn === 0)
                            <span class="countdown tone-danger"><i class="ti ti-alert-triangle"></i> Leaves today</span>
                        @elseif ($startsIn <= 7)
                            <span class="countdown tone-amber"><i class="ti ti-clock"></i> Leaves in {{ $startsIn }}d</span>
                        @else
                            <span class="countdown tone-slate">Leaves {{ $r->start_date->format('D, M j') }}</span>
                        @endif
                    </div>

                    <div class="approval-context">
                        <blockquote class="approval-reason" title="{{ $r->purpose }}">“{{ Str::limit($r->purpose, 140) }}”</blockquote>
                        <div class="approval-facts">
                            @if ($r->estimated_cost !== null)
                                <span class="approval-fact"><i class="ti ti-receipt"></i> Estimated <strong>{{ $money($r->estimated_cost) }}</strong>{{ $days > 1 ? ' · '.$money((float) $r->estimated_cost / $days).' a day' : '' }}</span>
                            @else
                                <span class="approval-fact is-warn"><i class="ti ti-receipt"></i> No cost estimate</span>
                            @endif
                            <span class="approval-fact" title="Approved trips starting in {{ today()->year }}">
                                <i class="ti ti-plane"></i> {{ $past['trips'] ? $ordinal($past['trips'] + 1).' trip this year · '.$money($past['cost']).' approved so far' : 'First trip this year' }}
                            </span>
                            @if ($onLeave->isNotEmpty())
                                <span class="approval-fact is-bad" title="{{ $onLeave->map(fn ($l) => $l->start_date->format('M j').'–'.$l->end_date->format('M j').' ('.$l->status.')')->implode(', ') }}">
                                    <i class="ti ti-calendar-off"></i> On leave {{ $onLeave->first()->start_date->format('M j') }}{{ $onLeave->first()->start_date->isSameDay($onLeave->first()->end_date) ? '' : '–'.$onLeave->first()->end_date->format('M j') }} ({{ $onLeave->first()->status }}){{ $onLeave->count() > 1 ? ' +'.($onLeave->count() - 1) : '' }}
                                </span>
                            @endif
                            @if ($stage['required'] > 1)
                                <span class="approval-fact {{ $stage['can_act'] ? '' : 'is-good' }}"><i class="ti ti-sitemap"></i> Level {{ min($stage['done'] + 1, $stage['required']) }} of {{ $stage['required'] }}{{ $stage['waiting'] ? ' · waiting for '.$stage['waiting'] : '' }}</span>
                            @elseif ($stage['waiting'])
                                <span class="approval-fact"><i class="ti ti-sitemap"></i> Reports to {{ $stage['waiting'] }}</span>
                            @endif
                        </div>
                    </div>

                    @if ($canReview)
                        <div class="approval-actions">
                            @if ($stage['can_act'])
                                <form action="{{ route('admin.travel-approval.approve', $r) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm review-btn is-approve w-100"><i class="ti ti-check"></i> Approve</button>
                                </form>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm review-btn is-reject w-100" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
                                        <i class="ti ti-x"></i> Reject
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                        <form action="{{ route('admin.travel-approval.reject', $r) }}" method="POST">
                                            @csrf
                                            <label class="form-label font-12 fw-semibold">Reason for rejecting</label>
                                            <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" maxlength="1000" placeholder="Shared with {{ $employee?->name }}" required></textarea>
                                            <div class="quick-chips mt-0 mb-2">
                                                @foreach (['No budget this quarter', 'Can be done by video call', 'Combine with another trip'] as $preset)
                                                    <button type="button" class="quick-chip" onclick="this.closest('form').querySelector('textarea').value = this.textContent">{{ $preset }}</button>
                                                @endforeach
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-danger w-100"><i class="ti ti-x font-14 me-1"></i> Reject request</button>
                                        </form>
                                    </div>
                                </div>
                            @elseif ($stage['own'])
                                <span class="font-12 text-muted text-center">Your own request. Someone else has to review it.</span>
                            @else
                                <span class="font-12 text-muted text-center">You have approved this. {{ $stage['waiting'] ? 'Waiting for '.$stage['waiting'].'.' : '' }}</span>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        @if ($requests->hasPages())
            <div class="list-footer job-grid-footer">
                <div class="ms-auto">{{ $requests->links() }}</div>
            </div>
        @endif
    @endif
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
                var picked = rows.filter(function (box) { return box.checked; }).length;
                action.disabled = picked === 0;
                summary.textContent = picked ? picked + ' selected' : 'Select requests';
                all.checked = picked > 0 && picked === rows.length;
                all.indeterminate = picked > 0 && picked < rows.length;
                rows.forEach(function (box) { box.closest('.approval-card').classList.toggle('is-selected', box.checked); });
            }
            all.addEventListener('change', function () { rows.forEach(function (box) { box.checked = all.checked; }); refresh(); });
            rows.forEach(function (box) { box.addEventListener('change', refresh); });
            refresh();
        })();
    </script>
@endpush
