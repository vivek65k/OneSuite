@extends('layouts.admin')

@section('title', 'HRM Dashboard')

@php
    use App\Support\Attendance;
    use App\Support\Dashboard\DashboardKit as Kit;
    use App\Support\LeaveTypeStyle;
    use Illuminate\Support\Str;

    $today = today();
    $user = auth()->user();
    $inbox = $data['inbox'];
    $dates = $data['dates'];
    $around = $data['around'];
    $coverage = $data['coverage'];
    $week = $data['week'];
    $kpis = $data['kpis'];
    $countdown = fn (int $days, bool $overdue) => $overdue
        ? ['tone' => 'danger', 'text' => 'Overdue '.abs($days).'d']
        : ($days === 0 ? ['tone' => 'warning', 'text' => 'Today'] : ['tone' => $days <= 7 ? 'warning' : 'lime', 'text' => 'in '.$days.'d']);
    $outToday = $around ? ($around['groups']['leave'] ?? collect())->where('status', 'approved')->count() + ($around['groups']['travel'] ?? collect())->count() : null;
@endphp

@section('content')
    <x-page-title title="HRM Dashboard" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2"><i class="ti ti-alert-circle font-18"></i> {{ $errors->first() }}</div>
    @endif

    {{-- Greeting hero --}}
    <div class="section-hero hrm-hero tone-teal">
        <span class="section-hero-icon"><i class="ti ti-smart-home"></i></span>
        <div class="section-hero-text">
            <h4>{{ Kit::greeting() }}, {{ Kit::firstName($user->name) }}</h4>
            <p>
                {{ $today->format('l, j F Y') }}@if ($branchName) · {{ $branchName }}@endif
                @if (! $inbox['total'] && ! $dates['overdue'])
                    · Nothing needs you right now.
                @endif
            </p>
            <div class="hero-chips">
                @forelse ($data['highlights'] as $chip)
                    <a href="{{ $chip['href'] }}" class="highlight-chip tone-{{ $chip['tone'] }}"><i class="ti {{ $chip['icon'] }}"></i> {{ $chip['text'] }}</a>
                @empty
                    <span class="highlight-chip tone-slate"><i class="ti ti-mood-smile"></i> Nothing urgent today</span>
                @endforelse
            </div>
        </div>
        <div class="section-hero-stats">
            <a href="#action-inbox" class="{{ $inbox['total'] ? 'is-alert' : '' }}"><strong>{{ $inbox['total'] }}</strong><span>Waiting on you</span></a>
            <a href="#dates" class="{{ $dates['overdue'] ? 'is-alert' : '' }}"><strong>{{ $dates['overdue'] }}</strong><span>Overdue</span></a>
            @if ($around)
                <a href="{{ route('admin.leave-requests.calendar', ['month' => $today->format('Y-m')]) }}"><strong>{{ $outToday }}</strong><span>Out today</span></a>
                @if ($around['off_day'])
                    <a href="{{ route('admin.attendance.daily', ['date' => $today->toDateString()]) }}" title="{{ $around['off_day'] }}"><strong>—</strong><span>{{ Str::limit($around['off_day'], 14) }}</span></a>
                @else
                    <a href="{{ route('admin.attendance.daily', ['date' => $today->toDateString(), 'status' => 'unmarked']) }}" class="{{ $around['not_marked'] && ! $around['before_first_shift'] ? 'is-alert' : '' }}"><strong>{{ $around['not_marked'] }}</strong><span>Not marked</span></a>
                @endif
            @endif
        </div>
    </div>

    @if ($myTeam)
        <a href="{{ route('admin.team-approvals.index') }}" class="my-team-strip">
            <i class="ti ti-users"></i>
            <span>Your team: <strong>{{ $myTeam['in'] }}</strong> in · <strong>{{ $myTeam['away'] }}</strong> away · <strong>{{ $myTeam['waiting'] }}</strong> {{ Str::plural('request', $myTeam['waiting']) }} waiting on you</span>
            <i class="ti ti-arrow-right ms-auto"></i>
        </a>
    @endif

    {{-- ============================== Needs your attention --}}
    <div class="dash-section-title"><i class="ti ti-bell-ringing"></i> Needs your attention</div>
    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card side-list tone-amber h-100" id="action-inbox">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-inbox"></i></span>
                    <span class="fw-semibold">Waiting for your approval</span>
                    @if ($inbox['total'])
                        <span class="side-list-count">{{ $inbox['total'] }}</span>
                    @endif
                    @can('viewAny', App\Models\LeaveRequest::class)
                        <a href="{{ route('admin.leave-requests.approval', ['sort' => 'oldest', 'per_page' => 25]) }}" class="ms-auto font-12 text-decoration-none">Leave approvals <i class="ti ti-arrow-right"></i></a>
                    @endcan
                </div>
                @forelse ($inbox['rows'] as $row)
                    <a href="{{ route($row['route'], $row['params']) }}" class="side-list-item attention-row is-{{ $row['severity'] }}">
                        <span class="attention-icon tone-{{ $row['tone'] }}"><i class="ti {{ $row['icon'] }}"></i></span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold font-13">{{ $row['label'] }}</div>
                            @if ($row['meta'])
                                <div class="attention-row-meta">{{ $row['meta'] }}</div>
                            @endif
                        </div>
                        @if ($row['oldest'])
                            <span class="tag {{ $row['oldest'] > 5 ? 'tone-danger' : ($row['oldest'] > 2 ? 'tone-warning' : 'tag-outline tone-slate') }}">oldest {{ $row['oldest'] }}d</span>
                        @endif
                        <span class="count-pill tone-{{ $row['tone'] }}">{{ $row['count'] }}</span>
                        <i class="ti ti-arrow-right text-muted"></i>
                    </a>
                    @if (collect($row['children'])->isNotEmpty())
                        <div class="attention-row-children">
                            @foreach ($row['children'] as $child)
                                @php
                                    $r = $child['request'];
                                    $style = LeaveTypeStyle::for($r->leaveType?->name, $r->leaveType?->code);
                                @endphp
                                <div class="attention-child">
                                    <span class="when-block tone-{{ $style['tone'] }}"><span class="when-block-month">{{ $r->start_date->format('M') }}</span><span class="when-block-day">{{ $r->start_date->format('j') }}</span></span>
                                    <x-avatar :person="$r->employee" />
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-semibold font-13 text-truncate">{{ $r->employee?->name }}</div>
                                        <div class="list-subtext">
                                            {{ $r->leaveType?->name }} · {{ $r->half_day ? (App\Models\LeaveRequest::HALF_DAYS[$r->half_day] ?? 'Half day') : Kit::days((float) $r->days).' '.Str::plural('day', (float) $r->days) }}
                                        </div>
                                        <div class="attention-tags">
                                            @if ($child['past'])
                                                <span class="tag tone-danger">Already started</span>
                                            @elseif ($child['starts_in'] === 0)
                                                <span class="tag tone-warning">Starts today</span>
                                            @else
                                                <span class="tag tone-warning">In {{ $child['starts_in'] }}d</span>
                                            @endif
                                            @if ($child['balance_issue'])
                                                <span class="tag tone-rose">{{ $child['balance_issue'] }}</span>
                                            @endif
                                            <span class="tag tag-outline tone-slate">Waiting {{ $child['waiting'] }}d</span>
                                        </div>
                                    </div>
                                    @can('update', $r)
                                        <form method="POST" action="{{ route('admin.leave-requests.approve', $r) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm review-btn is-approve" title="Approve"><i class="ti ti-check"></i></button>
                                        </form>
                                        <a href="{{ route('admin.leave-requests.approval', ['sort' => 'oldest', 'per_page' => 25]) }}" class="btn btn-sm review-btn is-reject" title="Reject with a reason"><i class="ti ti-x"></i></a>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    @endif
                @empty
                    <div class="side-list-empty"><i class="ti ti-circle-check"></i> All caught up. Nothing is waiting for your approval.</div>
                @endforelse
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card side-list tone-rose h-100" id="dates">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-alarm"></i></span>
                    <span class="fw-semibold">Dates you can't miss</span>
                    @if ($dates['overdue'])
                        <span class="side-list-count">{{ $dates['overdue'] }} overdue</span>
                    @endif
                </div>
                @forelse ($dates['rows'] as $item)
                    @php $cd = $countdown($item['days'], $item['overdue']); @endphp
                    <div class="side-list-item date-row">
                        <span class="when-block tone-{{ $item['tone'] }}"><span class="when-block-month">{{ $item['date']->format('M') }}</span><span class="when-block-day">{{ $item['date']->format('j') }}</span></span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold font-13 text-truncate"><a href="{{ $item['link'] }}" class="text-body">{{ $item['employee']?->name }}</a></div>
                            <div class="list-subtext text-truncate"><i class="ti {{ $item['icon'] }}"></i> {{ $item['title'] }}@if (! empty($item['owner'])) · Owner: {{ $item['owner'] }}@endif</div>
                            @if ($item['type'] === 'probation')
                                <span class="period-bar tone-sky d-block mt-1" title="{{ $item['progress'] }}% of probation done"><span class="period-bar-fill" style="width: {{ $item['progress'] }}%"></span></span>
                            @elseif ($item['type'] === 'exit' && ($item['clearance'] || ! $item['exit_interview']))
                                <div class="attention-tags">
                                    @if ($item['clearance'])
                                        <span class="tag tag-outline tone-slate">Clearance {{ $item['clearance'][0] }}/{{ $item['clearance'][1] }}</span>
                                    @endif
                                    @unless ($item['exit_interview'])
                                        <span class="tag tone-amber">Exit interview pending</span>
                                    @endunless
                                </div>
                            @endif
                        </div>
                        <span class="countdown tone-{{ $cd['tone'] }}">{{ $cd['text'] }}</span>
                        @if ($item['type'] === 'probation')
                            @can('update', $item['model'])
                                <form method="POST" action="{{ route('admin.probation-records.status', $item['model']) }}" onsubmit="return confirm('Confirm {{ addslashes($item['employee']?->name) }} after probation?');">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="confirmed">
                                    <button type="submit" class="btn btn-sm review-btn is-approve" title="Confirm"><i class="ti ti-check"></i></button>
                                </form>
                            @endcan
                        @elseif ($item['type'] === 'task')
                            <form method="POST" action="{{ route('admin.onboarding.tasks.toggle', $item['model']) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm review-btn is-approve" title="Mark done"><i class="ti ti-check"></i></button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="side-list-empty"><i class="ti ti-circle-check"></i> No reviews, expiries, exits or onboarding deadlines in the next 14 days.</div>
                    @if ($dates['can']['probation'] && ! $dates['sources']['probation'])
                        <a href="{{ route('admin.probation-records.create') }}" class="side-list-empty is-neutral"><i class="ti ti-shield-check"></i> Start tracking probation to get review reminders</a>
                    @endif
                    @if ($dates['can']['documents'] && ! $dates['sources']['documents'])
                        <div class="side-list-empty is-neutral"><i class="ti ti-file-alert"></i> Add expiry dates to passports, visas and contracts to get reminders</div>
                    @endif
                @endforelse
                @if ($dates['more'])
                    <div class="px-3 py-2 list-subtext">+{{ $dates['more'] }} more</div>
                @endif
                @if ($dates['can']['probation'] || $dates['can']['documents'] || $dates['can']['onboarding'])
                    <div class="side-list-foot">
                        @if ($dates['can']['probation'])<a href="{{ route('admin.probation-records.index', ['review' => 'next_30']) }}">Probation</a>@endif
                        @if ($dates['can']['documents'])<a href="{{ route('admin.document-expiry.index', ['expiry_status' => 'expiring_soon']) }}">Document expiry</a>@endif
                        @if ($dates['can']['onboarding'])<a href="{{ route('admin.onboarding.overview') }}">Onboarding</a>@endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============================== Today --}}
    @if ($around || $coverage)
        <div class="dash-section-title"><i class="ti ti-sun"></i> Today</div>
        <div class="row g-3">
            @if ($around)
                <div class="{{ $coverage ? 'col-xl-8' : 'col-12' }}">
                    <div class="card side-list tone-teal h-100">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-users"></i></span>
                            <span class="fw-semibold">Who's around today</span>
                            <span class="side-list-count">{{ $around['in'] }}/{{ $around['headcount'] }} in</span>
                            <a href="{{ route('admin.attendance.daily', ['date' => $today->toDateString()]) }}" class="ms-auto font-12 text-decoration-none">Daily sheet <i class="ti ti-arrow-right"></i></a>
                        </div>
                        @if (! $around['headcount'])
                            <div class="empty-state tone-teal">
                                <span class="empty-state-icon"><i class="ti ti-users"></i></span>
                                <h5>No active employees yet</h5>
                                @can('create', App\Models\Employee::class)
                                    <a href="{{ route('admin.employees.create') }}" class="btn btn-de-primary btn-sm text-light"><i class="ti ti-user-plus"></i> Add employee</a>
                                @endcan
                            </div>
                        @else
                            <div class="card-body pb-2">
                                @if ($around['off_day'])
                                    <div class="off-day-banner"><i class="ti ti-confetti"></i> {{ $around['off_day'] }} — no attendance marking needed today.</div>
                                @else
                                    @php
                                        $c = $around['counts'];
                                        $segments = [
                                            ['status' => 'present', 'n' => $c->get('present', 0) + $c->get('half_day', 0), 'tone' => 'success', 'label' => 'Present'],
                                            ['status' => 'wfh', 'n' => $c->get('wfh', 0), 'tone' => 'sky', 'label' => 'WFH'],
                                            ['status' => 'on_leave', 'n' => $c->get('on_leave', 0), 'tone' => 'violet', 'label' => 'On leave'],
                                            ['status' => 'absent', 'n' => $c->get('absent', 0), 'tone' => 'danger', 'label' => 'Absent'],
                                        ];
                                    @endphp
                                    <div class="stack-bar stack-bar-lg {{ $around['not_marked'] === $around['headcount'] ? 'is-all-unmarked' : '' }}" role="img" aria-label="Today's attendance">
                                        @foreach ($segments as $s)
                                            @if ($s['n'])
                                                <span class="tone-{{ $s['tone'] }}" style="flex: {{ $s['n'] }}" title="{{ $s['label'] }}: {{ $s['n'] }}"></span>
                                            @endif
                                        @endforeach
                                        @if ($around['not_marked'])
                                            <span class="is-unmarked" style="flex: {{ $around['not_marked'] }}" title="Not marked: {{ $around['not_marked'] }}"></span>
                                        @endif
                                    </div>
                                    <div class="stack-legend">
                                        @foreach ($segments as $s)
                                            <a href="{{ route('admin.attendance.daily', ['date' => $today->toDateString(), 'status' => $s['status']]) }}"><i class="legend-dot tone-{{ $s['tone'] }}"></i>{{ $s['label'] }} <strong>{{ $s['n'] }}</strong></a>
                                        @endforeach
                                        <a href="{{ route('admin.attendance.daily', ['date' => $today->toDateString(), 'status' => 'unmarked']) }}"><i class="legend-dot is-unmarked"></i>Not marked <strong>{{ $around['not_marked'] }}</strong></a>
                                    </div>
                                    @if ($around['before_first_shift'])
                                        <div class="list-subtext mt-1"><i class="ti ti-clock"></i> First shift starts at {{ App\Support\ShiftTime::format($around['first_shift']) }}.</div>
                                    @elseif ($around['not_marked'] === $around['headcount'])
                                        <div class="list-subtext mt-1"><i class="ti ti-clock"></i> Nobody has checked in yet.</div>
                                    @endif
                                @endif
                            </div>

                            @php
                                $leaveGroup = $around['groups']['leave'] ?? collect();
                                $wfhGroup = $around['groups']['wfh'];
                                $travelGroup = $around['groups']['travel'] ?? collect();
                                $lateGroup = $around['groups']['late'];
                            @endphp
                            @if ($leaveGroup->isNotEmpty())
                                <div class="presence-row">
                                    <span class="attention-icon tone-violet"><i class="ti ti-calendar-off"></i></span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold font-13">On leave</div>
                                        <div class="list-subtext text-truncate">{{ $leaveGroup->take(3)->map(fn ($l) => $l->employee?->name.($l->status === 'pending' ? ' (pending)' : ' · back '.$l->end_date->copy()->addDay()->format('D, M j')))->implode(', ') }}</div>
                                    </div>
                                    <div class="face-stack">
                                        @foreach ($leaveGroup->take(6) as $l)
                                            <x-avatar :person="$l->employee" :class="$l->status === 'pending' ? 'is-pending' : ''" />
                                        @endforeach
                                    </div>
                                    <span class="count-pill tone-violet">{{ $leaveGroup->count() }}</span>
                                </div>
                            @endif
                            @if ($wfhGroup->isNotEmpty())
                                <div class="presence-row">
                                    <span class="attention-icon tone-sky"><i class="ti ti-home"></i></span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold font-13">Working from home</div>
                                        <div class="list-subtext text-truncate">{{ $wfhGroup->take(3)->pluck('name')->implode(', ') }}</div>
                                    </div>
                                    <div class="face-stack">@foreach ($wfhGroup->take(6) as $p)<x-avatar :person="$p" />@endforeach</div>
                                    <span class="count-pill tone-sky">{{ $wfhGroup->count() }}</span>
                                </div>
                            @endif
                            @if ($travelGroup->isNotEmpty())
                                <div class="presence-row">
                                    <span class="attention-icon tone-teal"><i class="ti ti-plane-departure"></i></span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold font-13">Travelling</div>
                                        <div class="list-subtext text-truncate">{{ $travelGroup->take(3)->map(fn ($t) => $t->employee?->name.' · '.$t->to_location)->implode(', ') }}</div>
                                    </div>
                                    <div class="face-stack">@foreach ($travelGroup->take(6) as $t)<x-avatar :person="$t->employee" />@endforeach</div>
                                    <span class="count-pill tone-teal">{{ $travelGroup->count() }}</span>
                                </div>
                            @endif
                            @if ($lateGroup->isNotEmpty())
                                <div class="presence-row">
                                    <span class="attention-icon tone-amber"><i class="ti ti-alarm"></i></span>
                                    <div class="min-w-0">
                                        <div class="fw-semibold font-13">Late or not checked in</div>
                                        <div class="attention-tags">
                                            @foreach ($lateGroup->take(4) as $late)
                                                <span class="tag {{ $late['kind'] === 'no_show' ? 'tone-danger' : 'tone-amber' }}" title="{{ $late['row']->name }}">
                                                    {{ Str::limit($late['row']->name, 16) }} · {{ $late['kind'] === 'no_show' ? 'no check-in, '.$late['row']->code.' '.App\Support\ShiftTime::format($late['row']->start_time) : '+'.$late['minutes'].' min' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="face-stack">@foreach ($lateGroup->take(6) as $late)<x-avatar :person="$late['row']" />@endforeach</div>
                                    <span class="count-pill tone-amber">{{ $lateGroup->count() }}</span>
                                </div>
                            @endif
                            @if ($leaveGroup->isEmpty() && $wfhGroup->isEmpty() && $travelGroup->isEmpty() && $lateGroup->isEmpty())
                                <div class="side-list-empty"><i class="ti ti-mood-smile"></i> Nobody is on leave, travelling or late today.</div>
                            @endif
                            @if ($around['missed_punch_out'])
                                <div class="side-list-foot">
                                    <a href="{{ route('admin.attendance.punch-logs', ['from' => $today->copy()->subDays(7)->toDateString(), 'to' => $today->copy()->subDay()->toDateString(), 'per_page' => 25]) }}"><i class="ti ti-logout"></i> {{ $around['missed_punch_out'] }} missed punch-outs in the last 7 days</a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

            @if ($coverage)
                <div class="{{ $around ? 'col-xl-4' : 'col-12' }}">
                    <div class="card side-list tone-indigo h-100">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-briefcase"></i></span>
                            <span class="fw-semibold">Shift coverage</span>
                            <a href="{{ route('admin.shift-roster.weekly', ['view' => 'day', 'start' => $today->copy()->startOfWeek()->toDateString()]) }}" class="ms-auto font-12 text-decoration-none">Schedule <i class="ti ti-arrow-right"></i></a>
                        </div>
                        @forelse ($coverage['rows'] as $row)
                            @php $low = $row['started'] && $row['rostered'] && $row['in'] / $row['rostered'] < 0.5; @endphp
                            <div class="side-list-item">
                                <span class="attention-icon tone-{{ $row['style']['tone'] }}"><i class="ti {{ $row['style']['icon'] }}"></i></span>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold font-13">{{ $row['shift']->code }} <span class="list-subtext">{{ App\Support\ShiftTime::format($row['shift']->start_time) }}–{{ App\Support\ShiftTime::format($row['shift']->end_time) }}</span></div>
                                    <div class="mini-progress">
                                        <span class="mini-progress-bar is-toned tone-{{ $low ? 'danger' : $row['style']['tone'] }}"><span style="width: {{ $row['rostered'] ? round($row['in'] / $row['rostered'] * 100) : 0 }}%"></span></span>
                                        <span class="mini-progress-label">{{ $row['in'] }} / {{ $row['rostered'] }} in</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            @if (! $coverage['has_shifts'])
                                <div class="side-list-empty is-neutral"><i class="ti ti-clock"></i> No shifts set up yet.
                                    @can('create', App\Models\Shift::class)<a href="{{ route('admin.shifts.create') }}">Create a shift</a>@endcan
                                </div>
                            @else
                                <div class="side-list-empty is-neutral"><i class="ti ti-calendar-off"></i> Nobody is rostered today.
                                    <a href="{{ route('admin.shift-roster.assign') }}">Assign shifts</a>
                                </div>
                            @endif
                        @endforelse
                        <div class="side-list-foot flex-column align-items-start">
                            @if ($coverage['unrostered'])
                                <a href="{{ route('admin.shift-roster.assign') }}"><i class="ti ti-user-plus"></i> {{ $coverage['unrostered'] }} unrostered today</a>
                            @endif
                            @if ($coverage['tonight'] !== null)
                                <a href="{{ route('admin.shift-roster.night') }}"><i class="ti ti-moon"></i> {{ $coverage['tonight'] }} on nights tonight{{ $coverage['long_streaks'] ? ' · '.$coverage['long_streaks'].' on long streaks' : '' }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ============================== This week --}}
    <div class="dash-section-title"><i class="ti ti-calendar-stats"></i> This week</div>
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card side-list tone-violet h-100">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-calendar-stats"></i></span>
                    <span class="fw-semibold">Week planner · {{ $week['days'][0]['date']->format('M j') }} – {{ $week['days'][6]['date']->format('M j') }}</span>
                    @if ($week['show_leave'])
                        <a href="{{ route('admin.leave-requests.calendar', ['month' => $today->format('Y-m')]) }}" class="ms-auto font-12 text-decoration-none">Leave calendar <i class="ti ti-arrow-right"></i></a>
                    @endif
                </div>
                <div class="week-days week-days-compact">
                    @foreach ($week['days'] as $day)
                        <div class="week-day {{ $day['date']->isToday() ? 'is-today' : '' }} {{ $day['off'] || $day['holiday'] ? 'is-weekend' : '' }}">
                            <div class="week-day-head">
                                <span class="week-day-dow">{{ $day['date']->format('D') }}</span>
                                <span class="week-day-date">{{ $day['date']->format('j') }}</span>
                            </div>
                            @if ($day['holiday'])
                                <span class="week-day-holiday" title="{{ $day['holiday'] }}"><i class="ti ti-confetti"></i> {{ $day['holiday'] }}</span>
                            @endif
                            @if ($day['people']->isNotEmpty())
                                <div class="face-stack face-stack-sm">
                                    @foreach ($day['people']->take(4) as $l)
                                        <x-avatar :person="$l->employee" :class="$l->status === 'pending' ? 'is-pending' : ''" :title="$l->employee?->name.' · '.($l->leaveType?->name ?? 'Leave').($l->status === 'pending' ? ' (pending)' : '')" />
                                    @endforeach
                                    @if ($day['people']->count() > 4)
                                        <span class="face-stack-more">+{{ $day['people']->count() - 4 }}</span>
                                    @endif
                                </div>
                                <div class="week-day-meta">{{ $day['people']->count() }} away</div>
                            @elseif (! $day['holiday'])
                                <div class="week-day-empty">—</div>
                            @endif
                            @if ($day['marks'])
                                <div class="week-day-marks">
                                    @foreach ($day['marks'] as $mark)
                                        <i class="ti {{ $mark['icon'] }} tone-text-{{ $mark['tone'] }}" title="{{ $mark['title'] }}"></i>
                                    @endforeach
                                </div>
                            @endif
                            @if ($day['rostered'] !== null)
                                <a href="{{ route('admin.shift-roster.weekly', ['view' => 'day', 'start' => $day['date']->copy()->startOfWeek()->toDateString()]) }}" class="week-day-meta">{{ $day['rostered'] }} rostered</a>
                                @if (($day['off'] || $day['holiday']) && $day['rostered'] > 0)
                                    <span class="week-flag">Rostered on {{ $day['holiday'] ? 'holiday' : 'day off' }}</span>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="side-list-foot">
                    <span>{{ $week['away'] ? $week['away'].' '.Str::plural('person', $week['away']).' away this week' : 'No one is scheduled to be away this week' }}</span>
                    @if ($week['next_week'] && $week['next_week']['unscheduled'])
                        <a href="{{ route('admin.shift-roster.planning', ['start' => $week['next_week']['start']->toDateString()]) }}" class="ms-auto"><i class="ti ti-calendar-plus"></i> Next week: {{ $week['next_week']['unscheduled'] }} without a shift</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4 d-flex flex-column gap-3">
            @if ($data['celebrations'])
                @php
                    $celebrations = $data['celebrations'];
                    $groups = $celebrations['items']->groupBy(fn ($c) => $c['date']->isToday() ? 'Today' : ($c['date']->lte($today->copy()->endOfWeek()) ? 'This week' : 'Later'));
                @endphp
                <div class="card side-list tone-pink" id="celebrations">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-gift"></i></span>
                        <span class="fw-semibold">Celebrations & new faces</span>
                    </div>
                    @foreach (['Today', 'This week', 'Later'] as $label)
                        @if ($groups->has($label))
                            <div class="side-list-subhead">{{ $label }}</div>
                            @foreach ($groups[$label] as $c)
                                <div class="side-list-item">
                                    <span class="when-block tone-{{ $c['kind'] === 'birthday' ? 'pink' : ($c['milestone'] ? 'violet' : 'amber') }}"><span class="when-block-month">{{ $c['date']->format('M') }}</span><span class="when-block-day">{{ $c['date']->format('j') }}</span></span>
                                    <x-avatar :person="$c['employee']" />
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-semibold font-13 text-truncate"><a href="{{ route('admin.employees.show', $c['employee']) }}" class="text-body">{{ $c['employee']->name }}</a></div>
                                        <div class="list-subtext"><i class="ti {{ $c['kind'] === 'birthday' ? 'ti-gift' : 'ti-award' }}"></i> {{ $c['kind'] === 'birthday' ? 'Birthday' : $c['years'].' '.Str::plural('year', $c['years']).' with us' }}</div>
                                    </div>
                                    @if ($c['date']->isToday())
                                        <span class="countdown tone-pink">Today</span>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    @endforeach
                    @if ($celebrations['joiners']->isNotEmpty())
                        <div class="side-list-subhead">Welcome aboard</div>
                        @foreach ($celebrations['joiners'] as $joiner)
                            <div class="side-list-item">
                                <span class="when-block tone-teal"><span class="when-block-month">{{ $joiner->joining_date->format('M') }}</span><span class="when-block-day">{{ $joiner->joining_date->format('j') }}</span></span>
                                <x-avatar :person="$joiner" />
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold font-13 text-truncate"><a href="{{ route('admin.employees.show', $joiner) }}" class="text-body">{{ $joiner->name }}</a></div>
                                    <div class="list-subtext text-truncate">{{ $joiner->designation ?: ($joiner->department?->name ?? 'New joiner') }}</div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                    @if ($celebrations['items']->isEmpty() && $celebrations['joiners']->isEmpty())
                        <div class="side-list-empty is-neutral"><i class="ti ti-calendar-event"></i> No birthdays, anniversaries or new joiners in the next 14 days.</div>
                    @endif
                    @if ($celebrations['no_dob'])
                        <a href="#data-health" class="side-list-foot"><i class="ti ti-info-circle"></i> {{ $celebrations['no_dob'] }} of {{ $kpis['headcount'] ?? '' }} people have no birthday on file</a>
                    @endif
                </div>
            @endif

            <div class="card side-list tone-pink" id="holidays">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-confetti"></i></span>
                    <span class="fw-semibold">Upcoming holidays</span>
                    @can('view_any_holiday')
                        <a href="{{ route('admin.holidays.index', ['year' => $today->year]) }}" class="ms-auto font-12 text-decoration-none">Calendar <i class="ti ti-arrow-right"></i></a>
                    @endcan
                </div>
                @forelse ($data['holidays'] as $i => $holiday)
                    @php
                        $daysAway = (int) $today->diffInDays($holiday['date']);
                        $when = $holiday['date']->isToday() ? 'today' : ($holiday['date']->isTomorrow() ? 'tomorrow' : 'in '.$daysAway.' days');
                    @endphp
                    @if ($i === 0)
                        <div class="card-body">
                            <div class="holiday-card is-static holiday-card-compact">
                                <div class="holiday-card-month">{{ $holiday['date']->format('M Y') }}</div>
                                <div class="holiday-card-day">{{ $holiday['date']->format('j') }}</div>
                                <div class="holiday-card-name">{{ $holiday['name'] }}</div>
                                <div class="holiday-card-when">{{ $holiday['date']->format('l') }} · {{ $when }}</div>
                                @if ($holiday['long_weekend'] || $holiday['on_weekend'])
                                    <div class="holiday-card-tags"><span class="tag tone-amber">{{ $holiday['long_weekend'] ? 'Long weekend' : 'Falls on a weekend' }}</span></div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="side-list-item">
                            <span class="when-block tone-pink"><span class="when-block-month">{{ $holiday['date']->format('M') }}</span><span class="when-block-day">{{ $holiday['date']->format('j') }}</span></span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold font-13 text-truncate">{{ $holiday['name'] }}</div>
                                <div class="list-subtext">{{ $holiday['date']->format('l') }} · {{ $when }}</div>
                            </div>
                            @if ($holiday['long_weekend'])
                                <span class="tag tone-amber">Long weekend</span>
                            @endif
                        </div>
                    @endif
                @empty
                    <div class="side-list-empty is-neutral"><i class="ti ti-confetti"></i> No holidays in the next few months.
                        @can('create_holiday')<a href="{{ route('admin.holidays.create') }}">Add holidays</a>@endcan
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ============================== Month close & setup --}}
    @if ($data['payroll'] || $data['hiring'] || $data['health'])
        <div class="dash-section-title"><i class="ti ti-flag"></i> Month close & setup</div>
        <div class="row g-3">
            @if ($data['payroll'])
                @php
                    $pay = $data['payroll'];
                    $state = ! $pay['current'] ? 'none' : ($pay['current']->status === 'paid' ? 'paid' : 'processed');
                    $payTone = ['none' => 'amber', 'processed' => 'warning', 'paid' => 'success'][$state];
                @endphp
                <div class="col-xl-4">
                    <div class="card side-list tone-{{ $payTone }} h-100">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-report-money"></i></span>
                            <span class="fw-semibold">Payroll · {{ $today->format('F Y') }}</span>
                        </div>
                        <div class="card-body">
                            <div class="pay-state tone-{{ $payTone }}">
                                @if ($state === 'none')
                                    <i class="ti ti-clock"></i> Not run yet · {{ $pay['days_left'] }} {{ Str::plural('day', $pay['days_left']) }} left this month
                                @elseif ($state === 'processed')
                                    <i class="ti ti-loader"></i> Processed, not paid yet
                                @else
                                    <i class="ti ti-circle-check"></i> Paid
                                @endif
                            </div>
                            @if ($pay['last'])
                                <div class="policy-card-stats mt-3">
                                    <div><strong>{{ Kit::money((float) $pay['last']->total_gross) }}</strong><span>Gross</span></div>
                                    <div><strong>{{ Kit::money((float) $pay['last']->total_net) }}</strong><span>Net</span></div>
                                    <div><strong>{{ Kit::money($pay['cost']) }}</strong><span>Employer cost</span></div>
                                </div>
                                <div class="list-subtext mt-1">
                                    Last run {{ Carbon\Carbon::create($pay['last']->year, $pay['last']->month, 1)->format('F Y') }}
                                    @if ($pay['change'] !== null) · {{ $pay['change'] >= 0 ? '+' : '' }}{{ $pay['change'] }}% gross vs previous @endif
                                </div>
                            @else
                                <div class="side-list-empty is-neutral px-0"><i class="ti ti-report-money"></i> No payroll has been run for this branch yet.</div>
                            @endif
                            @if ($pay['structures'])
                                @php $covered = $pay['structures']['total'] - $pay['structures']['missing']; @endphp
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between font-12 mb-1"><span>Salary structures</span><strong>{{ $covered }}/{{ $pay['structures']['total'] }}</strong></div>
                                    <span class="mini-progress-bar is-toned tone-{{ $pay['structures']['missing'] ? 'amber' : 'success' }} d-block"><span style="width: {{ $pay['structures']['total'] ? round($covered / $pay['structures']['total'] * 100) : 0 }}%"></span></span>
                                    @if ($pay['structures']['missing'])
                                        <div class="list-subtext mt-1">Missing: {{ $pay['structures']['names']->pluck('name')->implode(', ') }}{{ $pay['structures']['missing'] > 3 ? ' +'.($pay['structures']['missing'] - 3) : '' }}
                                            · <a href="{{ route('admin.salary-structures.index') }}">Set up</a></div>
                                    @endif
                                </div>
                            @endif
                            @if ($pay['pending_inputs'])
                                <span class="tag tone-amber mt-2"><i class="ti ti-alert-circle"></i>{{ $pay['pending_inputs'] }} bonuses/deductions pending for this month</span>
                            @endif
                        </div>
                        <div class="side-list-foot">
                            <a href="{{ $pay['current'] ? route('admin.payroll-runs.show', $pay['current']) : route('admin.payroll-runs.index') }}" class="btn btn-sm btn-light"><i class="ti ti-report-money"></i> {{ $pay['current'] ? 'Open this month\'s run' : 'Run payroll' }}</a>
                        </div>
                    </div>
                </div>
            @endif

            @if ($data['hiring'])
                @php $hiring = $data['hiring']; @endphp
                <div class="col-xl-4">
                    <div class="card side-list tone-violet h-100">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-briefcase"></i></span>
                            <span class="fw-semibold">Hiring & onboarding</span>
                            @isset($hiring['pipeline'])
                                <a href="{{ route('admin.candidates.pipeline') }}" class="ms-auto tag tag-outline tone-violet">{{ $hiring['pipeline'] }} in pipeline</a>
                            @endisset
                        </div>
                        <div class="card-body pb-2">
                            <div class="policy-card-stats">
                                @isset($hiring['interviews_today'])
                                    <div><strong>{{ $hiring['interviews_today'] }}</strong><span>Interviews today</span></div>
                                @endisset
                                @isset($hiring['offers_pending'])
                                    <div><strong>{{ $hiring['offers_pending'] }}</strong><span>Offers pending</span></div>
                                @endisset
                                @isset($hiring['openings'])
                                    <div><strong>{{ $hiring['openings'] }}</strong><span>Openings · {{ $hiring['open_roles'] }} {{ Str::plural('role', $hiring['open_roles']) }}</span></div>
                                @endisset
                            </div>
                        </div>
                        @if (! empty($hiring['upcoming']) && $hiring['upcoming']->isNotEmpty())
                            <div class="side-list-subhead">Coming up</div>
                            @foreach ($hiring['upcoming'] as $interview)
                                <div class="side-list-item">
                                    <span class="when-time">{{ $interview->scheduled_at->isToday() ? $interview->scheduled_at->format('g:i A') : 'Tmrw '.$interview->scheduled_at->format('g:i') }}</span>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-semibold font-13 text-truncate">{{ $interview->candidate?->name }}</div>
                                        <div class="list-subtext text-truncate">{{ $interview->candidate?->jobPosition?->title }} · {{ $interview->round }}@if ($interview->interviewer_name) · {{ $interview->interviewer_name }}@endif</div>
                                    </div>
                                    @if ($interview->status === 'rescheduled')
                                        <span class="tag tone-amber">Rescheduled</span>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                        @foreach ($hiring['actions'] as $action)
                            <a href="{{ route($action['route'], $action['params']) }}" class="side-list-item attention-row is-warning">
                                <span class="attention-icon tone-amber"><i class="ti {{ $action['icon'] }}"></i></span>
                                <span class="flex-grow-1 font-13">{{ $action['label'] }}</span>
                                <span class="count-pill tone-amber">{{ $action['count'] }}</span>
                            </a>
                        @endforeach
                        @if (isset($hiring['onboarding']) && $hiring['onboarding']['open'])
                            <a href="{{ route('admin.onboarding.overview') }}" class="side-list-item attention-row {{ $hiring['onboarding']['overdue'] ? 'is-danger' : 'is-info' }}">
                                <span class="attention-icon tone-teal"><i class="ti ti-list-check"></i></span>
                                <span class="flex-grow-1 font-13">Onboarding tasks open{{ $hiring['onboarding']['overdue'] ? ' · '.$hiring['onboarding']['overdue'].' overdue' : '' }}</span>
                                <span class="count-pill tone-teal">{{ $hiring['onboarding']['open'] }}</span>
                            </a>
                        @endif
                        @if (! $hiring['actions'] && empty($hiring['onboarding']['open']) && (empty($hiring['upcoming']) || $hiring['upcoming']->isEmpty()))
                            <div class="side-list-empty"><i class="ti ti-circle-check"></i> You're all caught up on hiring.</div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($data['health'])
                @php $health = $data['health']; @endphp
                <div class="col-xl-4">
                    <div class="card side-list tone-slate h-100" id="data-health">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-list-check"></i></span>
                            <span class="fw-semibold">Setup & data health</span>
                        </div>
                        @isset($health['leave'])
                            <div class="side-list-subhead d-flex align-items-center">Leave setup <span class="ms-auto">{{ $health['leave']['done'] }} of 4 done</span></div>
                            <div class="px-3 pb-1"><span class="mini-progress-bar is-toned tone-{{ $health['leave']['done'] === 4 ? 'success' : 'amber' }} d-block"><span style="width: {{ $health['leave']['done'] * 25 }}%"></span></span></div>
                            @foreach ($health['leave']['steps'] as $step)
                                <a href="{{ route($step['route'], $step['params']) }}" class="side-list-item health-row">
                                    <span class="status-dot status-dot-{{ $step['done'] ? 'success' : 'danger' }}"></span>
                                    <span class="flex-grow-1 font-13 {{ $step['done'] ? '' : 'text-danger fw-semibold' }}">{{ $step['label'] }}</span>
                                    <i class="ti ti-arrow-right text-muted"></i>
                                </a>
                            @endforeach
                        @endisset
                        @isset($health['records'])
                            <div class="side-list-subhead">Employee records</div>
                            @forelse ($health['records'] as $rec)
                                <a href="{{ route($rec['key'] === 'no_probation' ? 'admin.probation-records.create' : 'admin.employees.index', $rec['key'] === 'no_probation' ? [] : ['per_page' => 25]) }}" class="side-list-item health-row">
                                    <span class="attention-icon tone-slate"><i class="ti {{ $rec['icon'] }}"></i></span>
                                    <span class="flex-grow-1 font-13">{{ $rec['label'] }}</span>
                                    <span class="count-pill tone-amber">{{ $rec['count'] }}</span>
                                </a>
                            @empty
                                <div class="side-list-empty"><i class="ti ti-shield-check"></i> Employee records are complete.</div>
                            @endforelse
                        @endisset
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ============================== Trends --}}
    @if ($kpis)
        <div class="dash-section-title"><i class="ti ti-chart-line"></i> Trends</div>
        <div class="kpi-strip">
            <x-kpi-card label="Headcount" :value="$kpis['headcount']" icon="ti-users" tone="indigo"
                        :hint="$kpis['headcount'] ? '+'.$kpis['joined_mtd'].' / −'.$kpis['exits']['mtd'].' this month' : 'No employees yet'" :href="route('admin.employees.index', ['per_page' => 25])" />
            <x-kpi-card label="Joiners · {{ $today->format('F') }}" :value="$kpis['joined_mtd']" icon="ti-user-plus" tone="teal"
                        :hint="$kpis['joining_30'] ? $kpis['joining_30'].' joining in the next 30 days' : 'No one joining in the next 30 days'"
                        :href="route('admin.employees.index', ['joined_from' => $today->copy()->startOfMonth()->toDateString(), 'joined_until' => $today->toDateString(), 'per_page' => 25])" />
            @can('view_any_resignation')
                @php $rate = $kpis['exits']['rate']; @endphp
                <x-kpi-card label="Exits · {{ $today->format('F') }}" :value="$kpis['exits']['mtd']" icon="ti-logout" :tone="$rate !== null && $rate > 15 ? 'danger' : 'rose'"
                            :hint="$kpis['exits']['notice'].' serving notice · '.($rate === null ? '—' : $rate.'%').' attrition (12m)'" :href="route('admin.attrition-reports.index')" />
            @endcan
            @if ($kpis['attendance_allowed'])
                <x-kpi-card label="Attendance · {{ $today->format('F') }}" :value="Kit::percent($kpis['attendance'])" icon="ti-calendar-stats" tone="success"
                            :hint="$kpis['attendance'] === null ? 'No attendance marked in '.$today->format('F').' yet' : 'Present, WFH and half days'" :href="route('admin.attendance.reports', ['month' => $today->format('Y-m')])" />
            @endif
        </div>

        <div class="row g-3">
            @if ($data['trend'])
                <div class="col-xl-8">
                    <div class="card side-list tone-indigo h-100">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-chart-bar"></i></span>
                            <span class="fw-semibold">Joiners vs exits · last 12 months</span>
                            @can('view_any_resignation')
                                <a href="{{ route('admin.attrition-reports.index') }}" class="ms-auto font-12 text-decoration-none">Attrition report <i class="ti ti-arrow-right"></i></a>
                            @endcan
                        </div>
                        @if ($data['trend']['empty'])
                            <div class="empty-state tone-indigo">
                                <span class="empty-state-icon"><i class="ti ti-chart-bar"></i></span>
                                <h5>Headcount steady at {{ $kpis['headcount'] }}</h5>
                                <p>No joiners or exits in the last 12 months.</p>
                            </div>
                        @else
                            <div class="card-body pt-2">
                                <div class="chart-box" id="trendChart"></div>
                                <div class="list-subtext"><i class="ti ti-info-circle"></i> Exits count accepted resignations by last working day.</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            @if ($data['departments'])
                <div class="col-xl-4">
                    <div class="card side-list tone-teal h-100">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-sitemap"></i></span>
                            <span class="fw-semibold">Department strength</span>
                        </div>
                        <div class="ot-leaders dept-bars">
                            @forelse ($data['departments']['rows'] as $dept)
                                @php
                                    $href = $dept['name'] === 'Unassigned' ? '#data-health' : (! empty($dept['others']) ? route('admin.employees.index', ['per_page' => 25]) : route('admin.employees.index', ['department_id' => $dept['id'], 'per_page' => 25]));
                                @endphp
                                <a href="{{ $href }}" class="ot-leader">
                                    <span class="ot-leader-body">
                                        <span class="ot-leader-name {{ $dept['name'] === 'Unassigned' ? 'text-warning' : '' }}">{{ $dept['name'] }}</span>
                                        <span class="week-hours-bar"><span style="width: {{ round($dept['total'] / $data['departments']['max'] * 100) }}%"></span></span>
                                    </span>
                                    <span class="hours-figure">{{ $dept['total'] }}</span>
                                </a>
                            @empty
                                <div class="side-list-empty is-neutral"><i class="ti ti-sitemap"></i> No departments assigned yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            @if ($data['mix'])
                @php
                    $mix = $data['mix'];
                    $mixTones = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky', 'lime', 'slate'];
                    $bars = [
                        'Employment type' => $mix['types']->map(fn ($t) => ['label' => $t['label'], 'count' => $t['count'], 'href' => $t['slug'] !== 'unspecified' ? route('admin.employees.index', ['employment_type' => $t['slug'], 'per_page' => 25]) : null]),
                        'Gender' => $mix['genders']->map(fn ($g) => ['label' => $g['label'], 'count' => $g['count'], 'href' => null]),
                        'Tenure' => $mix['tenure']->map(fn ($n, $label) => ['label' => $label, 'count' => $n, 'href' => null])->values(),
                        'Age' => $mix['age']->map(fn ($n, $label) => ['label' => $label, 'count' => $n, 'href' => null])->values(),
                    ];
                @endphp
                <div class="col-xl-6">
                    <div class="card side-list tone-sky h-100">
                        <div class="side-list-head">
                            <span class="side-list-icon"><i class="ti ti-chart-donut"></i></span>
                            <span class="fw-semibold">Workforce mix</span>
                            @if (Route::has('admin.diversity-reports.index'))
                                <a href="{{ route('admin.diversity-reports.index') }}" class="ms-auto font-12 text-decoration-none">Diversity report <i class="ti ti-arrow-right"></i></a>
                            @endif
                        </div>
                        <div class="card-body">
                            @if (! $mix['total'])
                                <div class="side-list-empty is-neutral px-0"><i class="ti ti-chart-donut"></i> Not enough data yet.</div>
                            @else
                                @foreach ($bars as $title => $segments)
                                    @php $sum = $segments->sum('count'); @endphp
                                    <div class="mix-row">
                                        <div class="mix-row-title">{{ $title }}
                                            @if ($title === 'Age' && $mix['age_missing'])
                                                <a href="#data-health" class="list-subtext ms-1">{{ $mix['age_missing'] }} of {{ $mix['total'] }} without date of birth</a>
                                            @endif
                                        </div>
                                        @if ($sum)
                                            <div class="stack-bar">
                                                @foreach ($segments->filter(fn ($s) => $s['count']) as $i => $s)
                                                    <span class="tone-{{ $mixTones[$loop->index % count($mixTones)] }}" style="flex: {{ $s['count'] }}" title="{{ $s['label'] }}: {{ $s['count'] }}"></span>
                                                @endforeach
                                            </div>
                                            <div class="stack-legend">
                                                @foreach ($segments->filter(fn ($s) => $s['count']) as $s)
                                                    @if ($s['href'])
                                                        <a href="{{ $s['href'] }}"><i class="legend-dot tone-{{ $mixTones[$loop->index % count($mixTones)] }}"></i>{{ $s['label'] }} <strong>{{ $s['count'] }}</strong></a>
                                                    @else
                                                        <span><i class="legend-dot tone-{{ $mixTones[$loop->index % count($mixTones)] }}"></i>{{ $s['label'] }} <strong>{{ $s['count'] }}</strong></span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="list-subtext">{{ $title === 'Age' ? 'Add dates of birth to see the age mix.' : 'Not enough data yet.' }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="col-xl-6">
                @php $watch = $data['watch']; @endphp
                <div class="card side-list tone-amber h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-mood-smile"></i></span>
                        <span class="fw-semibold">Leave & wellbeing watch</span>
                    </div>
                    @isset($watch['lapse'])
                        <a href="{{ route('admin.leave-reports.index', ['year' => $today->year]) }}" class="side-list-item attention-row is-warning">
                            <span class="attention-icon tone-amber"><i class="ti ti-calendar-time"></i></span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold font-13">{{ Kit::days($watch['lapse']['days']) }} leave days at risk of lapsing on Dec 31</div>
                                <div class="attention-row-meta">{{ $watch['lapse']['people'] }} {{ Str::plural('person', $watch['lapse']['people']) }} · nudge them to plan time off or encash</div>
                            </div>
                            <i class="ti ti-arrow-right text-muted"></i>
                        </a>
                    @endisset
                    @isset($watch['low'])
                        <a href="{{ route('admin.leave-balances.index', ['year' => $today->year]) }}" class="side-list-item attention-row is-info">
                            <span class="attention-icon tone-violet"><i class="ti ti-umbrella"></i></span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold font-13">{{ $watch['low']['count'] }} {{ Str::plural('person', $watch['low']['count']) }} with 2 days or less left</div>
                                <div class="attention-row-meta">{{ $watch['low']['rows']->map(fn ($r) => $r['employee']?->name.' · '.$r['type']?->code.' '.Kit::days(max(0, $r['left'])))->implode(', ') }}</div>
                            </div>
                            <i class="ti ti-arrow-right text-muted"></i>
                        </a>
                    @endisset
                    @isset($watch['no_leave'])
                        <a href="{{ route('admin.leave-reports.index', ['year' => $today->year]) }}" class="side-list-item attention-row is-info">
                            <span class="attention-icon tone-teal"><i class="ti ti-mood-smile"></i></span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold font-13">{{ $watch['no_leave']['count'] }} {{ Str::plural('person', $watch['no_leave']['count']) }} with no leave in 90 days</div>
                                <div class="attention-row-meta">Burnout risk · encourage a break</div>
                            </div>
                            <div class="face-stack">@foreach ($watch['no_leave']['people'] as $p)<x-avatar :person="$p" />@endforeach</div>
                        </a>
                    @endisset
                    @isset($watch['absences'])
                        <a href="{{ route('admin.attendance.reports', ['month' => $today->format('Y-m')]) }}" class="side-list-item attention-row is-danger">
                            <span class="attention-icon tone-danger"><i class="ti ti-user-x"></i></span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold font-13">{{ $watch['absences']['count'] }} {{ Str::plural('person', $watch['absences']['count']) }} absent 3+ times in 30 days</div>
                                <div class="attention-row-meta">Worth a check-in</div>
                            </div>
                            <div class="face-stack">@foreach ($watch['absences']['people'] as $p)<x-avatar :person="$p" />@endforeach</div>
                        </a>
                    @endisset
                    @if (! $watch)
                        <div class="side-list-empty"><i class="ti ti-circle-check"></i> No leave or absence concerns right now.</div>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endsection

@if ($data['trend'] && ! $data['trend']['empty'])
    @push('styles')
        <link href="{{ asset('vendor/metrica/assets/libs/apexcharts/apexcharts.css') }}" rel="stylesheet">
    @endpush
    @push('scripts')
        <script src="{{ asset('vendor/metrica/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
        <script>
            (function () {
                var el = document.getElementById('trendChart');
                if (!el || typeof ApexCharts === 'undefined') return;
                var css = getComputedStyle(document.documentElement);
                var muted = (css.getPropertyValue('--text-3') || '#8a94a6').trim();
                var grid = (css.getPropertyValue('--border-subtle') || '#eef1f8').trim();
                new ApexCharts(el, {
                    chart: { type: 'line', height: 260, toolbar: { show: false }, fontFamily: 'inherit', foreColor: muted },
                    series: [
                        { name: 'Joiners', type: 'column', data: @json($data['trend']['joins']) },
                        { name: 'Exits', type: 'column', data: @json($data['trend']['exits']) },
                        { name: 'Headcount', type: 'line', data: @json($data['trend']['headcount']) },
                    ],
                    colors: ['#0f9d70', '#e24343', '#5369f8'],
                    stroke: { width: [0, 0, 3], curve: 'smooth' },
                    plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
                    markers: { size: [0, 0, 4], strokeColors: '#fff' },
                    dataLabels: { enabled: false },
                    grid: { borderColor: grid, strokeDashArray: 4 },
                    xaxis: { categories: @json($data['trend']['labels']), axisBorder: { show: false }, axisTicks: { show: false } },
                    yaxis: [
                        { seriesName: 'Joiners', min: 0, forceNiceScale: true, labels: { formatter: function (v) { return Math.round(v); } }, title: { text: 'Joiners / exits' } },
                        { seriesName: 'Joiners', show: false, min: 0, forceNiceScale: true },
                        { opposite: true, seriesName: 'Headcount', min: 0, forceNiceScale: true, labels: { formatter: function (v) { return Math.round(v); } }, title: { text: 'Headcount' } },
                    ],
                    legend: { position: 'top', horizontalAlign: 'right' },
                    tooltip: { shared: true, intersect: false },
                }).render();
            })();
        </script>
    @endpush
@endif
