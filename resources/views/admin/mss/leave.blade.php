@extends('layouts.admin')

@section('title', 'Team Leave')

@php
    use App\Http\Controllers\Admin\Mss\TeamLeaveController as TeamLeave;
    use App\Models\LeaveRequest;
    use App\Support\LeaveTypeStyle;
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
    $dayWord = fn ($n) => $num($n).' '.((float) $n === 1.0 ? 'day' : 'days');
    $pageUrl = fn (array $overrides = []) => route('admin.team-leave.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $monthUrl = fn ($date) => $pageUrl(['month' => $date->format('Y-m') === now()->format('Y-m') ? null : $date->format('Y-m')]);
    $tabs = [
        '' => ['label' => 'All requests', 'tone' => 'indigo', 'count' => $counts['all']],
        'pending' => ['label' => 'Waiting for approval', 'tone' => 'amber', 'count' => $counts['pending']],
        'upcoming' => ['label' => 'Upcoming', 'tone' => 'sky', 'count' => $counts['upcoming']],
        'taken' => ['label' => 'Taken', 'tone' => 'success', 'count' => $counts['taken']],
        'closed' => ['label' => 'Rejected or cancelled', 'tone' => 'slate', 'count' => $counts['closed']],
    ];
    $statusTones = ['pending' => 'amber', 'approved' => 'success', 'rejected' => 'rose', 'cancelled' => 'slate'];
    $statusIcons = ['pending' => 'ti-clock', 'approved' => 'ti-circle-check', 'rejected' => 'ti-circle-x', 'cancelled' => 'ti-x'];
    $names = fn ($people, int $limit = 3) => $people->take($limit)->map(fn ($p) => Str::before($p->name, ' '))->implode(', ').($people->count() > $limit ? ' +'.($people->count() - $limit) : '');
    $awayToday = $kpis['away_today'];
    $soon = $kpis['soon'];
@endphp

@section('content')
    <x-page-title title="Team Leave" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    @if ($everyone->isEmpty())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-users font-24 d-block mb-2"></i>
                <div class="fw-semibold text-body mb-1">Nobody reports to you</div>
                <span class="font-13">This page shows the leave of the people below you in the reporting line. HR sets who reports to whom on each employee's profile.</span>
            </div>
        </div>
    @else
        <div class="kpi-strip">
            <x-kpi-card label="On leave today" :value="$awayToday->count()" icon="ti-plane" :tone="$awayToday->count() ? 'violet' : 'success'" :hint="$awayToday->count() ? $names($awayToday) : 'Everyone is in (of '.$kpis['people'].')'" />
            <x-kpi-card label="Waiting for me" :value="$kpis['mine']" icon="ti-clock" :tone="$kpis['mine'] ? 'amber' : 'success'" :hint="$kpis['pending'] ? $kpis['pending'].' pending in all · '.$dayWord($kpis['pending_days']) : 'No requests are waiting'" :href="$kpis['pending'] ? $pageUrl(['tab' => 'pending']) : null" />
            <x-kpi-card label="Away in the next {{ TeamLeave::SOON_DAYS }} days" :value="$soon->count()" icon="ti-calendar-event" tone="sky" :hint="$soon->count() ? $names($soon) : 'Nobody has approved leave coming up'" :href="$soon->count() ? $pageUrl(['tab' => 'upcoming']) : null" />
            <x-kpi-card label="Taken in {{ $year }}" :value="$dayWord($kpis['taken_year'])" icon="ti-calendar" tone="indigo" :hint="$kpis['people'] ? 'about '.$num($kpis['taken_year'] / max(1, $kpis['people'])).' per person, approved' : 'Approved leave'" />
        </div>

        <div class="card list-view">
            <div class="list-toolbar">
                <div class="month-nav pb-0">
                    <a href="{{ $monthUrl($start->copy()->subMonth()) }}" class="btn-icon" title="Previous month"><i class="ti ti-chevron-left font-18"></i></a>
                    <div class="month-nav-label"><span class="fw-bold">{{ $start->format('F') }}</span><span class="text-muted">{{ $start->format('Y') }}</span></div>
                    @if ($canGoForward)
                        <a href="{{ $monthUrl($start->copy()->addMonth()) }}" class="btn-icon" title="Next month"><i class="ti ti-chevron-right font-18"></i></a>
                    @else
                        <span class="btn-icon disabled opacity-50" title="Leave can't be booked further ahead than this" aria-disabled="true"><i class="ti ti-chevron-right font-18"></i></span>
                    @endif
                    @unless ($isCurrent)
                        <a href="{{ $pageUrl(['month' => null]) }}" class="btn btn-light btn-sm ms-1">This month</a>
                    @endunless
                </div>
                <form method="GET" action="{{ route('admin.team-leave.index') }}" class="list-toolbar-actions">
                    @if ($state['month'] !== '')
                        <input type="hidden" name="month" value="{{ $state['month'] }}">
                    @endif
                    @if ($state['tab'] !== '')
                        <input type="hidden" name="tab" value="{{ $state['tab'] }}">
                    @endif
                    <select name="employee_id" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Team member">
                        <option value="">Whole team ({{ $everyone->count() }})</option>
                        @foreach ($everyone as $person)
                            <option value="{{ $person->id }}" @selected($focus?->id === $person->id)>{{ $person->name }}{{ in_array($person->status, ['terminated', 'inactive'], true) ? ' (left)' : '' }}</option>
                        @endforeach
                    </select>
                    @if ($hasIndirect && ! $focus)
                        <select name="scope" class="form-select form-select-sm list-inline-select" onchange="this.form.submit()" aria-label="Who to show">
                            <option value="">Everyone below me</option>
                            <option value="direct" @selected($state['scope'] === 'direct')>Direct reports only</option>
                        </select>
                    @endif
                </form>
            </div>
            @if ($focus || $state['scope'] !== '')
                <div class="list-subbar">
                    <div class="list-chips">
                        @if ($focus)
                            <a href="{{ $pageUrl(['employee_id' => null]) }}" class="filter-chip" title="Show the whole team">Person: <strong>{{ $focus->name }}</strong> <i class="ti ti-x"></i></a>
                        @else
                            <a href="{{ $pageUrl(['scope' => null]) }}" class="filter-chip" title="Remove filter">Showing: <strong>Direct reports only</strong> <i class="ti ti-x"></i></a>
                        @endif
                    </div>
                </div>
            @endif

            <div class="list-segment-bar">
                <div class="stack-legend m-0">
                    <span><span class="heat-cell heat-cell-sm tone-violet">L</span> Approved leave</span>
                    <span><span class="heat-cell heat-cell-sm tone-amber" style="border-style: dashed">?</span> Requested, not decided</span>
                    <span><span class="heat-cell heat-cell-sm tone-pink">HO</span> Holiday</span>
                    <span><span class="heat-cell heat-cell-sm tone-slate">WK</span> Weekly off</span>
                    <span><span class="heat-cell heat-cell-sm is-clash">&nbsp;</span> Half the team or more away</span>
                </div>
            </div>

            <div class="table-responsive heatmap-wrap">
                <table class="table list-table heatmap mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="heatmap-name">Team member</th>
                            @foreach ($dates as $date)
                                <th class="heatmap-day {{ $date->isWeekend() ? 'is-weekend' : '' }} {{ $date->isToday() ? 'is-today' : '' }}" title="{{ $date->format('l, M j') }}{{ ($away[$date->toDateString()] ?? 0) ? ' · '.$away[$date->toDateString()].' away' : '' }}"><span>{{ $date->format('D')[0] }}</span>{{ $date->day }}</th>
                            @endforeach
                            <th class="heatmap-total" title="Approved leave days in {{ $start->format('F') }}">Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($board as $row)
                            @php $person = $row['employee']; @endphp
                            <tr>
                                <td class="heatmap-name">
                                    <a href="{{ $pageUrl(['employee_id' => $person->id, 'scope' => null]) }}" class="list-user text-body" title="See only {{ $person->name }}">
                                        <x-avatar :person="$person" />
                                        <div class="list-user-text">
                                            <div class="list-user-name">{{ $person->name }}</div>
                                            <div class="list-user-meta">{{ collect([$person->designation ?: $person->department?->name, $person->is_direct ? null : 'indirect'])->filter()->implode(' · ') ?: $person->employee_code }}</div>
                                        </div>
                                    </a>
                                </td>
                                @foreach ($dates as $date)
                                    @php
                                        $key = $date->toDateString();
                                        $cell = $row['cells'][$key];
                                        $leave = $cell['leave'];
                                        $isThin = $thin->contains($key);
                                    @endphp
                                    <td class="heatmap-day {{ $cell['off'] ? 'is-weekend' : '' }} {{ $date->isToday() ? 'is-today' : '' }}">
                                        @if ($leave)
                                            @php $style = LeaveTypeStyle::for($leave->leaveType?->name, $leave->leaveType?->code); @endphp
                                            <span class="heat-cell tone-{{ $leave->status === 'approved' ? $style['tone'] : 'amber' }} {{ $isThin && $leave->status === 'approved' ? 'is-clash' : '' }}" @if ($leave->status === 'pending') style="border-style: dashed" @endif
                                                  title="{{ $person->name }} · {{ $date->format('D, M j') }}: {{ $leave->leaveType?->name ?? 'Leave' }}{{ (float) $leave->days === 0.5 ? ' (half day)' : '' }}, {{ $leave->status === 'approved' ? 'approved' : 'requested, not decided' }}{{ $isThin && $leave->status === 'approved' ? ' · '.$away[$key].' of '.$board->count().' away' : '' }}">{{ $leave->status === 'approved' ? (Str::upper(Str::substr((string) ($leave->leaveType?->code ?: 'L'), 0, 2))) : '?' }}</span>
                                        @elseif ($cell['holiday'])
                                            <span class="heat-cell tone-pink" title="{{ $date->format('D, M j') }}: {{ $cell['holiday'] }}">HO</span>
                                        @elseif ($cell['off'])
                                            <span class="heat-cell tone-slate" title="{{ $date->format('D, M j') }}: weekly off">WK</span>
                                        @else
                                            <span class="heat-cell is-future"></span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="heatmap-total"><span class="heatmap-count {{ $row['days'] ? 'tone-violet' : '' }}">{{ $num($row['days']) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $dates->count() + 2 }}" class="text-center text-muted py-5">
                                    <i class="ti ti-users font-24 d-block mb-2"></i>
                                    <div class="fw-semibold text-body mb-1">Nobody matches</div>
                                    <span class="font-13"><a href="{{ route('admin.team-leave.index') }}">Show the whole team</a></span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($thin->isNotEmpty())
                <div class="list-subbar"><span class="font-13 text-danger"><i class="ti ti-alert-triangle"></i> Thin cover on {{ $thin->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('M j'))->implode(', ') }}: half the team or more is on approved leave.</span></div>
            @endif
        </div>

        <div class="card list-view">
            <div class="list-toolbar">
                <nav class="list-tabs" aria-label="Request status">
                    @foreach ($tabs as $value => $tab)
                        @if ($value === '' || $tab['count'] || $state['tab'] === $value)
                            <a href="{{ $pageUrl(['tab' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $state['tab'] === $value ? 'active' : '' }}">
                                {{ $tab['label'] }}
                                <span class="list-tab-count">{{ $tab['count'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
            </div>
            <div class="table-responsive table-responsive-sticky">
                <table class="table table-hover list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Team member</th>
                            <th>Leave</th>
                            <th>Dates</th>
                            <th style="width: 24%">Reason</th>
                            <th>Status</th>
                            <th class="text-end"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $r)
                            @php
                                $person = $r->employee;
                                $style = LeaveTypeStyle::for($r->leaveType?->name, $r->leaveType?->code);
                                $step = $flow[$r->id] ?? null;
                                // Other people on the team with approved leave on any of the same days.
                                $overlap = $r->overlap;
                            @endphp
                            <tr class="{{ $r->status === 'pending' ? 'is-pending-row' : '' }}">
                                <td>
                                    <div class="list-user">
                                        <x-avatar :person="$person" />
                                        <div class="list-user-text">
                                            <div class="list-user-name">{{ $person->name }}</div>
                                            <div class="list-user-meta">{{ $person->designation ?: $person->employee_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="tag tone-{{ $style['tone'] }}"><i class="ti {{ $style['icon'] }}"></i>{{ $r->leaveType?->name ?? 'Removed leave type' }}</span>
                                    <div class="payslip-parts">{{ $dayWord($r->days) }}{{ $r->half_day ? ' · '.(LeaveRequest::HALF_DAYS[$r->half_day] ?? $r->half_day) : '' }}</div>
                                </td>
                                <td class="text-nowrap">
                                    {{ $r->start_date->format('D, M j') }}{{ $r->end_date->isSameDay($r->start_date) ? '' : ' – '.$r->end_date->format('D, M j') }}
                                    <div class="payslip-parts">
                                        @if ($r->status === 'approved' && $r->start_date->lte(today()) && $r->end_date->gte(today()))
                                            away now, back {{ $r->end_date->copy()->addDay()->format('M j') }}
                                        @elseif ($r->start_date->gt(today()))
                                            starts in {{ (int) today()->diffInDays($r->start_date) }} {{ Str::plural('day', (int) today()->diffInDays($r->start_date)) }}
                                        @else
                                            {{ $r->start_date->format('Y') }}
                                        @endif
                                    </div>
                                    @if ($overlap->isNotEmpty())
                                        <div class="payslip-parts text-warning"><i class="ti ti-alert-triangle"></i> {{ $names($overlap) }} also away</div>
                                    @endif
                                </td>
                                <td><span title="{{ Str::length((string) $r->reason) > 80 ? $r->reason : '' }}">{{ Str::limit((string) $r->reason, 80) }}</span></td>
                                <td>
                                    <span class="tag tone-{{ $statusTones[$r->status] ?? 'slate' }}"><i class="ti {{ $statusIcons[$r->status] ?? 'ti-point' }}"></i>{{ LeaveRequest::STATUSES[$r->status] ?? Str::headline($r->status) }}</span>
                                    @if ($r->status === 'pending' && $step)
                                        <div class="payslip-parts">{{ $step['mine'] ? 'waiting on you' : ($step['name'] ? 'waiting on '.$step['name'] : 'waiting on HR') }}{{ $step['required'] > 1 ? ' · step '.min($step['done'] + 1, $step['required']).' of '.$step['required'] : '' }}</div>
                                    @elseif ($r->reviewedBy || $r->reviewed_at)
                                        <div class="payslip-parts">{{ collect([$r->reviewedBy?->name ? 'by '.$r->reviewedBy->name : null, $r->reviewed_at?->format('M j')])->filter()->implode(' · ') }}</div>
                                    @endif
                                    @if ($r->remarks)
                                        <div class="payslip-parts {{ $r->status === 'rejected' ? 'text-danger' : '' }}" title="{{ $r->remarks }}">“{{ Str::limit($r->remarks, 60) }}”</div>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($r->status === 'pending' && $step && $step['can'])
                                        <div class="list-row-actions">
                                            <form action="{{ route('admin.team-approvals.leave.approve', $r) }}" method="POST" class="d-inline" onsubmit="return confirm(@js('Approve '.$dayWord($r->days).' of '.($r->leaveType?->name ?? 'leave').' for '.$person->name.'?'.($overlap->isNotEmpty() ? ' '.$names($overlap).' will also be away.' : '')))">
                                                @csrf
                                                <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-check"></i> Approve</button>
                                            </form>
                                            <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="modal" data-bs-target="#reject-modal" data-action="{{ route('admin.team-approvals.leave.reject', $r) }}" data-who="{{ $person->name }}" data-what="{{ $dayWord($r->days) }} of {{ $r->leaveType?->name ?? 'leave' }} from {{ $r->start_date->format('M j') }}"><i class="ti ti-x"></i> Reject</button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="ti ti-calendar-off font-24 d-block mb-2"></i>
                                    @if ($state['tab'] !== '')
                                        <div class="fw-semibold text-body mb-1">{{ $state['tab'] === 'pending' ? 'No requests are waiting' : 'Nothing here' }}</div>
                                        <span class="font-13"><a href="{{ $pageUrl(['tab' => null]) }}">Show all requests</a></span>
                                    @else
                                        <div class="fw-semibold text-body mb-1">No leave requests {{ $focus ? 'from '.$focus->name : 'from your team' }} yet</div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card list-view">
            <div class="list-toolbar"><span class="list-toolbar-title">Leave left in {{ $year }}</span></div>
            <div class="table-responsive">
                <table class="table list-table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Team member</th>
                            @foreach ($types as $type)
                                <th class="text-end">{{ $type->name }}</th>
                            @endforeach
                            @if ($types->isEmpty())
                                <th>Balance</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($balances as $row)
                            <tr>
                                <td>
                                    <div class="list-user">
                                        <x-avatar :person="$row['employee']" />
                                        <div class="list-user-text"><div class="list-user-name">{{ $row['employee']->name }}</div></div>
                                    </div>
                                </td>
                                @foreach ($types as $type)
                                    @php $b = $row['types'][$type->name] ?? null; @endphp
                                    <td class="text-end">
                                        @if ($b)
                                            <span class="fw-semibold {{ $b['left'] <= 0 ? 'text-danger' : '' }}">{{ $num($b['left']) }}</span> <span class="text-muted font-12">of {{ $num($b['total']) }}</span>
                                            @if ($b['pending'] > 0)
                                                <div class="payslip-parts text-warning">{{ $num($b['pending']) }} requested</div>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                @if ($types->isEmpty())
                                    <td class="text-muted">No leave allocated for {{ $year }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="reject-modal" tabindex="-1" aria-labelledby="reject-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="#" class="modal-content" id="reject-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="reject-modal-title">Reject leave request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="font-13 text-muted mb-3" id="reject-intro"></div>
                        <label class="form-label" for="reject-remarks">Why? <span class="text-muted fw-normal">(the employee sees this)</span></label>
                        <input type="text" name="remarks" id="reject-remarks" maxlength="500" class="form-control" placeholder="e.g. Two others are already away that week" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="ti ti-x font-14 me-1"></i> Reject request</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var modal = document.getElementById('reject-modal');
            if (!modal) return;
            modal.addEventListener('show.bs.modal', function (event) {
                var b = event.relatedTarget;
                if (!b) return;
                document.getElementById('reject-form').action = b.dataset.action;
                document.getElementById('reject-intro').textContent = b.dataset.who + ': ' + b.dataset.what + '.';
                document.getElementById('reject-remarks').value = '';
            });
        })();
    </script>
@endpush
