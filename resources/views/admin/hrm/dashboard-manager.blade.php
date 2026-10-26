@extends('layouts.admin')

@section('title', 'My Team')

@php
    use App\Support\Attendance;
    use App\Support\Dashboard\DashboardKit as Kit;
    use App\Support\LeaveTypeStyle;
    use App\Support\ShiftTime;
    use Illuminate\Support\Str;

    $today = today();
    $waiting = $data['waiting'];
    $todayRows = $data['today'];
    $in = $todayRows->where('state', 'in')->count();
    $away = $todayRows->where('state', 'away')->count();
    $unmarked = $todayRows->where('state', 'unmarked')->count();
    $pulse = $data['pulse'];
    $myLeave = $me['leave']['balances']->take(3);
@endphp

@section('content')
    <x-page-title title="My Team" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-center gap-2"><i class="ti ti-alert-circle font-18"></i> {{ $errors->first() }}</div>
    @endif

    <div class="section-hero hrm-hero tone-indigo">
        <span class="section-hero-icon"><i class="ti ti-users"></i></span>
        <div class="section-hero-text">
            <h4>{{ Kit::greeting() }}, {{ Kit::firstName($manager->name) }}</h4>
            <p>{{ $today->format('l, j F Y') }} · Your team · {{ $data['size'] }} {{ Str::plural('person', $data['size']) }}</p>
            <div class="hero-chips">
                @foreach ($data['chips'] as $chip)
                    <a href="{{ $chip['href'] }}" class="highlight-chip tone-{{ $chip['tone'] }}"><i class="ti {{ $chip['icon'] }}"></i> {{ $chip['text'] }}</a>
                @endforeach
                <a href="{{ route('admin.team-approvals.index') }}" class="highlight-chip tone-slate"><i class="ti ti-inbox"></i> Team approvals</a>
                <a href="{{ route('admin.team-attendance.index', ['month' => $today->format('Y-m')]) }}" class="highlight-chip tone-slate"><i class="ti ti-calendar-stats"></i> Team attendance</a>
                <a href="{{ route('admin.my-leave.index') }}" class="highlight-chip tone-slate"><i class="ti ti-calendar-plus"></i> Apply leave</a>
            </div>
        </div>
        <div class="section-hero-stats">
            <a href="#team-waiting" class="{{ $waiting['total'] ? 'is-alert' : '' }}"><strong>{{ $waiting['total'] }}</strong><span>Waiting on you</span></a>
            <a href="#team-today"><strong>{{ $in }}</strong><span>In today</span></a>
            <a href="#team-today"><strong>{{ $away }}</strong><span>Away</span></a>
            <a href="#team-today" class="{{ $unmarked && $todayRows->where('overdue', true)->count() ? 'is-alert' : '' }}"><strong>{{ $unmarked }}</strong><span>Not marked</span></a>
        </div>
    </div>

    <div class="dash-section-title"><i class="ti ti-bell-ringing"></i> Needs your attention</div>
    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card side-list tone-amber h-100" id="team-waiting">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-inbox"></i></span>
                    <span class="fw-semibold">Waiting on you</span>
                    @if ($waiting['total'])
                        <span class="side-list-count">{{ $waiting['total'] }}</span>
                    @endif
                    <a href="{{ route('admin.team-approvals.index') }}" class="ms-auto font-12 text-decoration-none">All team approvals <i class="ti ti-arrow-right"></i></a>
                </div>
                @forelse ($waiting['rows'] as $row)
                    <a href="{{ route('admin.team-approvals.index') }}" class="side-list-item attention-row is-{{ $row['severity'] }}">
                        <span class="attention-icon tone-{{ $row['tone'] }}"><i class="ti {{ $row['icon'] }}"></i></span>
                        <span class="flex-grow-1 fw-semibold font-13">{{ $row['label'] }}</span>
                        @if ($row['oldest'])
                            <span class="tag {{ $row['oldest'] > 3 ? 'tone-danger' : 'tag-outline tone-slate' }}">oldest {{ $row['oldest'] }}d</span>
                        @endif
                        <span class="count-pill tone-{{ $row['tone'] }}">{{ $row['count'] }}</span>
                    </a>
                    @if ($row['children']->isNotEmpty())
                        <div class="attention-row-children">
                            @foreach ($row['children'] as $r)
                                @php $style = LeaveTypeStyle::for($r->leaveType?->name, $r->leaveType?->code); @endphp
                                <div class="attention-child">
                                    <span class="when-block tone-{{ $style['tone'] }}"><span class="when-block-month">{{ $r->start_date->format('M') }}</span><span class="when-block-day">{{ $r->start_date->format('j') }}</span></span>
                                    <x-avatar :person="$r->employee" />
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-semibold font-13 text-truncate">{{ $r->employee?->name }}</div>
                                        <div class="list-subtext">{{ $r->leaveType?->name }} · {{ $r->half_day ? (App\Models\LeaveRequest::HALF_DAYS[$r->half_day] ?? 'Half day') : Kit::days((float) $r->days).' '.Str::plural('day', (float) $r->days) }}
                                            @if ($r->start_date->lt($today)) · <span class="text-danger">already started</span>@endif</div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.team-approvals.leave.approve', $r) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm review-btn is-approve" title="Approve"><i class="ti ti-check"></i></button>
                                    </form>
                                    <div class="dropdown d-inline-block">
                                        <button type="button" class="btn btn-sm review-btn is-reject" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-popper-config='{"strategy":"fixed"}' title="Reject"><i class="ti ti-x"></i></button>
                                        <div class="dropdown-menu dropdown-menu-end p-3 reject-menu">
                                            <form method="POST" action="{{ route('admin.team-approvals.leave.reject', $r) }}">
                                                @csrf
                                                <label class="form-label font-12 fw-semibold">Reason (optional)</label>
                                                <textarea name="remarks" rows="2" class="form-control form-control-sm mb-2" maxlength="1000"></textarea>
                                                <button type="submit" class="btn btn-sm btn-danger w-100">Reject request</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @empty
                    <div class="side-list-empty"><i class="ti ti-circle-check"></i> Nothing waiting for you. Your team's requests are all reviewed.</div>
                @endforelse
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card side-list tone-teal h-100" id="team-today">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-sun"></i></span>
                    <span class="fw-semibold">My team today</span>
                    <span class="side-list-count">{{ $in }}/{{ $todayRows->count() }} in</span>
                </div>
                @forelse ($todayRows as $row)
                    @php
                        $member = $row['member'];
                        $status = $row['record']?->status;
                        $meta = Attendance::meta($status);
                    @endphp
                    <div class="side-list-item">
                        <x-avatar :person="$member" />
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold font-13 text-truncate">
                                @can('view', $member)
                                    <a href="{{ route('admin.employees.show', $member) }}" class="text-body">{{ $member->name }}</a>
                                @else
                                    {{ $member->name }}
                                @endcan
                            </div>
                            <div class="list-subtext">
                                @if ($row['shift'])
                                    <span class="tag tone-{{ ShiftTime::style($row['shift'])['tone'] }}">{{ $row['shift']->code }}</span>
                                @endif
                                @if ($row['record']?->check_in_time)
                                    in {{ Attendance::formatTime($row['record']->check_in_time) }}
                                @elseif ($row['overdue'])
                                    <span class="text-danger">no check-in yet</span>
                                @else
                                    {{ $member->designation ?: ($member->department?->name ?? '') }}
                                @endif
                            </div>
                        </div>
                        @if ($row['late'])
                            <span class="tag tone-amber">Late +{{ $row['late'] }}m</span>
                        @endif
                        @if ($status)
                            <span class="tag tone-{{ $meta['tone'] }}">{{ Attendance::label($status) }}</span>
                        @else
                            <span class="tag tag-outline tone-slate">Not marked</span>
                        @endif
                    </div>
                @empty
                    <div class="side-list-empty is-neutral"><i class="ti ti-users"></i> No active team members.</div>
                @endforelse
                <div class="side-list-foot"><a href="{{ route('admin.team-attendance.index', ['month' => $today->format('Y-m')]) }}">Team attendance <i class="ti ti-arrow-right"></i></a></div>
            </div>
        </div>
    </div>

    <div class="dash-section-title"><i class="ti ti-calendar-stats"></i> This week</div>
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card side-list tone-violet h-100">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-calendar-stats"></i></span>
                    <span class="fw-semibold">Team this week</span>
                    <span class="side-list-count">Out today {{ $data['week']['out_today'] }} of {{ $data['size'] }}</span>
                    <a href="{{ route('admin.team-leave.index') }}" class="ms-auto font-12 text-decoration-none">Team leave <i class="ti ti-arrow-right"></i></a>
                </div>
                @if ($data['week']['grid']->isEmpty())
                    <div class="side-list-empty is-neutral"><i class="ti ti-users"></i> No team members to show.</div>
                @else
                    <div class="table-responsive">
                        <table class="table list-table team-week mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Member</th>
                                    @foreach ($data['week']['days'] as $day)
                                        <th class="{{ $day['holiday'] ? 'is-holiday' : '' }} {{ $day['date']->isToday() ? 'is-today' : '' }}" title="{{ $day['holiday'] }}">{{ $day['date']->format('D') }}<br><span class="list-subtext">{{ $day['date']->format('j') }}</span></th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data['week']['grid'] as $row)
                                    <tr>
                                        <td><div class="list-user"><x-avatar :person="$row['member']" /><span class="fw-semibold font-13 text-truncate">{{ $row['member']->name }}</span></div></td>
                                        @foreach ($row['cells'] as $i => $cell)
                                            @php $day = $data['week']['days'][$i]; @endphp
                                            <td class="{{ $day['holiday'] ? 'is-holiday' : '' }} {{ $day['off'] ? 'is-weekend' : '' }}">
                                                @if ($cell['leave'])
                                                    <span class="tag {{ $cell['leave']->status === 'pending' ? 'tag-outline' : '' }} tone-violet" title="{{ $cell['leave']->leaveType?->name }} {{ $cell['leave']->status === 'pending' ? '(pending)' : '' }}">{{ $cell['leave']->leaveType?->code ?? 'Leave' }}</span>
                                                @elseif ($cell['shift'])
                                                    <span class="tag tone-{{ ShiftTime::style($cell['shift'])['tone'] }}" title="{{ $cell['shift']->name }}">{{ $cell['shift']->code }}</span>
                                                @elseif ($day['holiday'])
                                                    <i class="ti ti-confetti tone-text-pink" title="{{ $day['holiday'] }}"></i>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-xl-4 d-flex flex-column gap-3">
            <div class="card side-list tone-pink" id="team-celebrations">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-gift"></i></span>
                    <span class="fw-semibold">Team celebrations</span>
                </div>
                @forelse ($data['celebrations'] as $c)
                    <div class="side-list-item">
                        <span class="when-block tone-{{ $c['kind'] === 'birthday' ? 'pink' : 'amber' }}"><span class="when-block-month">{{ $c['date']->format('M') }}</span><span class="when-block-day">{{ $c['date']->format('j') }}</span></span>
                        <x-avatar :person="$c['employee']" />
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold font-13 text-truncate">{{ $c['employee']->name }}</div>
                            <div class="list-subtext">{{ $c['kind'] === 'birthday' ? 'Birthday' : $c['years'].' '.Str::plural('year', $c['years']).' with us' }}</div>
                        </div>
                        @if ($c['date']->isToday())<span class="countdown tone-pink">Today</span>@endif
                    </div>
                @empty
                    <div class="side-list-empty is-neutral"><i class="ti ti-calendar-event"></i> No team birthdays or anniversaries in the next 14 days.</div>
                @endforelse
            </div>
            <div class="card side-list tone-pink" id="team-holidays">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-confetti"></i></span>
                    <span class="fw-semibold">Upcoming holidays</span>
                </div>
                @forelse ($data['holidays'] as $holiday)
                    <div class="side-list-item">
                        <span class="when-block tone-pink"><span class="when-block-month">{{ $holiday['date']->format('M') }}</span><span class="when-block-day">{{ $holiday['date']->format('j') }}</span></span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold font-13 text-truncate">{{ $holiday['name'] }}</div>
                            <div class="list-subtext">{{ $holiday['date']->format('l') }}{{ $holiday['long_weekend'] ? ' · long weekend' : '' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="side-list-empty is-neutral"><i class="ti ti-confetti"></i> No holidays coming up.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="dash-section-title"><i class="ti ti-flag"></i> Coming up & team pulse</div>
    <div class="kpi-strip">
        <x-kpi-card label="Team size" :value="$data['size']" icon="ti-users" tone="indigo" :hint="$pulse['on_leave'].' on leave · '.$pulse['joining'].' joining'" :href="route('admin.team-reports.index')" />
        <x-kpi-card label="Attendance · {{ $today->format('F') }}" :value="Kit::percent($pulse['attendance'])" icon="ti-calendar-stats" tone="success" :hint="$pulse['attendance'] === null ? 'Nothing marked yet' : 'Present, WFH and half days'" />
        <x-kpi-card label="Absences · {{ $today->format('F') }}" :value="$pulse['absences']" icon="ti-user-x" :tone="$pulse['absences'] ? 'danger' : 'success'" hint="Days marked absent" />
        <x-kpi-card label="Approved overtime" :value="Kit::days($pulse['overtime']).' h'" icon="ti-alarm" tone="pink" :hint="$today->format('F')" />
    </div>
    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card side-list tone-rose h-100">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-alarm"></i></span>
                    <span class="fw-semibold">Coming up for your team · 30 days</span>
                </div>
                @forelse ($data['coming'] as $item)
                    <div class="side-list-item">
                        <span class="when-block tone-{{ $item['tone'] }}"><span class="when-block-month">{{ $item['date']->format('M') }}</span><span class="when-block-day">{{ $item['date']->format('j') }}</span></span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold font-13 text-truncate">{{ $item['employee']?->name }}</div>
                            <div class="list-subtext text-truncate"><i class="ti {{ $item['icon'] }}"></i> {{ $item['title'] }}</div>
                        </div>
                        <span class="countdown tone-{{ $item['overdue'] ? 'danger' : ($item['days'] <= 7 ? 'warning' : 'lime') }}">{{ $item['overdue'] ? 'Overdue '.abs($item['days']).'d' : ($item['days'] ? 'in '.$item['days'].'d' : 'Today') }}</span>
                        @if ($item['type'] === 'task')
                            <form method="POST" action="{{ route('admin.onboarding.tasks.toggle', $item['model']) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm review-btn is-approve" title="Mark done"><i class="ti ti-check"></i></button>
                            </form>
                        @elseif (! empty($item['link']))
                            <a href="{{ $item['link'] }}" class="btn-icon" title="Open"><i class="ti ti-arrow-right"></i></a>
                        @endif
                    </div>
                @empty
                    <div class="side-list-empty"><i class="ti ti-circle-check"></i> Nothing due for your team in the next 30 days.</div>
                @endforelse
            </div>
        </div>
        <div class="col-xl-5 d-flex flex-column gap-3">
            <div class="card side-list tone-violet">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-umbrella"></i></span>
                    <span class="fw-semibold">Leave running low</span>
                    <a href="{{ route('admin.team-leave.index') }}" class="ms-auto font-12 text-decoration-none">Team leave <i class="ti ti-arrow-right"></i></a>
                </div>
                @forelse ($data['low'] as $row)
                    <div class="side-list-item">
                        <x-avatar :person="$row['employee']" />
                        <span class="flex-grow-1 font-13 fw-semibold text-truncate">{{ $row['employee']?->name }}</span>
                        <span class="tag tone-violet">{{ $row['type']?->code }} · {{ Kit::days(max(0, $row['left'])) }} left</span>
                    </div>
                @empty
                    <div class="side-list-empty"><i class="ti ti-circle-check"></i> Everyone on your team has leave left.</div>
                @endforelse
            </div>
            <div class="card side-list tone-indigo">
                <div class="side-list-head">
                    <span class="side-list-icon"><i class="ti ti-user"></i></span>
                    <span class="fw-semibold">Just for you</span>
                    <a href="{{ route('admin.my-leave.index') }}" class="ms-auto btn btn-sm btn-light"><i class="ti ti-calendar-plus"></i> Apply leave</a>
                </div>
                @if ($me['day']['shift'])
                    <div class="side-list-item">
                        <span class="attention-icon tone-{{ ShiftTime::style($me['day']['shift'])['tone'] }}"><i class="ti {{ ShiftTime::style($me['day']['shift'])['icon'] }}"></i></span>
                        <span class="flex-grow-1 font-13">Today: <strong>{{ $me['day']['shift']->code }}</strong> {{ ShiftTime::format($me['day']['shift']->start_time) }}–{{ ShiftTime::format($me['day']['shift']->end_time) }}</span>
                        @if ($me['day']['record'])<span class="tag tone-{{ Attendance::meta($me['day']['record']->status)['tone'] }}">{{ Attendance::label($me['day']['record']->status) }}</span>@endif
                    </div>
                @endif
                @forelse ($myLeave as $b)
                    <div class="side-list-item">
                        <span class="flex-grow-1 font-13">{{ $b['type']?->name }}</span>
                        <span class="tag tone-{{ $b['left'] <= 2 ? 'warning' : 'success' }}">{{ Kit::days($b['left']) }} left</span>
                    </div>
                @empty
                    <div class="side-list-empty is-neutral"><i class="ti ti-umbrella"></i> No leave balance for {{ $today->year }} yet.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
