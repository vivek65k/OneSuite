@extends('layouts.admin')

@section('title', 'My Dashboard')

@php
    use App\Support\Attendance;
    use App\Support\Dashboard\DashboardKit as Kit;
    use App\Support\LeaveTypeStyle;
    use App\Support\ShiftTime;
    use Illuminate\Support\Str;

    $today = today();
@endphp

@section('content')
    <x-page-title title="My Dashboard" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif

    @if (! $employee)
        <div class="card">
            <div class="empty-state tone-slate">
                <span class="empty-state-icon"><i class="ti ti-user-off"></i></span>
                <h5>No employee profile is linked to your account yet</h5>
                <p>Ask HR to link your login to your employee record to see your leave, attendance and pay here.</p>
            </div>
        </div>
        @if ($holidays && $holidays->isNotEmpty())
            <div class="card side-list tone-pink mt-3">
                <div class="side-list-head"><span class="side-list-icon"><i class="ti ti-confetti"></i></span><span class="fw-semibold">Upcoming holidays</span></div>
                @foreach ($holidays as $holiday)
                    <div class="side-list-item">
                        <span class="when-block tone-pink"><span class="when-block-month">{{ $holiday['date']->format('M') }}</span><span class="when-block-day">{{ $holiday['date']->format('j') }}</span></span>
                        <span class="flex-grow-1 fw-semibold font-13">{{ $holiday['name'] }}</span>
                        <span class="list-subtext">{{ $holiday['date']->format('l') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        @php
            $profile = $data['profile'];
            $e = $profile['employee'];
            $day = $data['day'];
            $leave = $data['leave'];
            $month = $data['month'];
            $pay = $data['pay'];
            $team = $data['team'];
        @endphp

        {{-- Profile hero --}}
        <div class="profile-hero">
            <x-avatar :person="$e" />
            <div class="flex-grow-1 min-w-0">
                <h4 class="mb-0 fw-bold">{{ Kit::greeting() }}, {{ Kit::firstName($e->name) }}</h4>
                <div class="list-subtext">{{ collect([$e->designation, $e->department?->name, $e->branch?->name])->filter()->implode(' · ') ?: $today->format('l, j F Y') }}</div>
                <div class="profile-hero-facts">
                    @if ($e->manager)<span><i class="ti ti-hierarchy"></i> Reports to {{ $e->manager->name }}</span>@endif
                    @if ($profile['tenure'])<span><i class="ti ti-award"></i> {{ $profile['tenure'] }} with us · next anniversary {{ $profile['next_anniversary']?->format('M j') }}</span>@endif
                    <span><i class="ti ti-id"></i> {{ $e->employee_code }}</span>
                </div>
                @if ($profile['birthday_today'])
                    <div class="highlight-chip tone-pink mt-2"><i class="ti ti-gift"></i> Happy birthday!</div>
                @elseif ($profile['anniversary_years'] >= 1)
                    <div class="highlight-chip tone-amber mt-2"><i class="ti ti-confetti"></i> {{ $profile['anniversary_years'] }} {{ Str::plural('year', $profile['anniversary_years']) }} with us today</div>
                @endif
                <div class="hero-chips">
                    <a href="{{ route('admin.my-leave.index') }}" class="highlight-chip tone-violet"><i class="ti ti-calendar-plus"></i> Apply leave</a>
                    <a href="{{ route('admin.my-requests.index', ['type' => 'wfh']) }}" class="highlight-chip tone-sky"><i class="ti ti-home"></i> Request WFH</a>
                    <a href="{{ route('admin.my-requests.index', ['type' => 'overtime']) }}" class="highlight-chip tone-pink"><i class="ti ti-alarm"></i> Claim overtime</a>
                    <a href="{{ route('admin.my-requests.index', ['type' => 'regularization']) }}" class="highlight-chip tone-amber"><i class="ti ti-edit"></i> Fix attendance</a>
                    <a href="{{ route('admin.my-expenses.index') }}" class="highlight-chip tone-lime"><i class="ti ti-receipt-2"></i> Claim expense</a>
                    <a href="{{ route('admin.my-travel.index') }}" class="highlight-chip tone-teal"><i class="ti ti-plane"></i> Request travel</a>
                </div>
            </div>
            <a href="{{ route('admin.my-profile.index') }}" class="text-center text-decoration-none" title="{{ $profile['missing'] ? 'Missing: '.implode(', ', $profile['missing']) : 'Profile complete' }}">
                <div class="progress-ring tone-{{ $profile['completeness'] >= 80 ? 'success' : 'amber' }}" style="--value: {{ $profile['completeness'] }}">
                    <span>{{ $profile['completeness'] }}<small>%</small></span>
                </div>
                <div class="list-subtext mt-1">Profile complete</div>
            </a>
        </div>

        <div class="row g-3">
            {{-- My day --}}
            <div class="col-xl-5">
                <div class="card side-list tone-teal h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-sun"></i></span>
                        <span class="fw-semibold">My day · {{ $today->format('D, M j') }}</span>
                    </div>
                    <div class="card-body pb-2">
                        @if ($day['holiday'] && ! $day['shift'])
                            <div class="off-day-banner"><i class="ti ti-confetti"></i> {{ $day['holiday'] }} — enjoy the day off.</div>
                        @elseif ($day['shift'])
                            @php $style = ShiftTime::style($day['shift']); @endphp
                            <div class="d-flex align-items-center gap-2">
                                <span class="attention-icon tone-{{ $style['tone'] }}"><i class="ti {{ $style['icon'] }}"></i></span>
                                <div>
                                    <div class="fw-semibold">{{ $day['shift']->name }} <span class="list-subtext">{{ $day['shift']->code }}</span></div>
                                    <div class="list-subtext">{{ ShiftTime::format($day['shift']->start_time) }} – {{ ShiftTime::format($day['shift']->end_time) }} · {{ Kit::days(ShiftTime::netHours($day['shift'])) }} h work</div>
                                </div>
                            </div>
                        @else
                            <div class="list-subtext"><i class="ti ti-calendar-off"></i> No shift rostered today.</div>
                        @endif
                        <div class="attention-tags mt-2">
                            @if ($day['record'])
                                <span class="tag tone-{{ Attendance::meta($day['record']->status)['tone'] }}">{{ Attendance::label($day['record']->status) }}</span>
                                @if ($day['record']->check_in_time)<span class="tag tag-outline tone-slate"><i class="ti ti-login"></i> {{ Attendance::formatTime($day['record']->check_in_time) }}</span>@endif
                                @if ($day['record']->check_out_time)<span class="tag tag-outline tone-slate"><i class="ti ti-logout"></i> {{ Attendance::formatTime($day['record']->check_out_time) }}</span>@endif
                                @if ($day['worked'] !== null)<span class="tag tone-teal">Worked {{ Kit::days($day['worked']) }} h</span>@endif
                                @if ($day['late'])<span class="tag tone-amber">Late +{{ $day['late'] }} min</span>@endif
                            @elseif (! $day['holiday'])
                                <span class="tag tag-outline tone-slate">Not checked in yet</span>
                            @endif
                        </div>
                        @if ($day['missed_punch_out'])
                            <a href="{{ route('admin.my-requests.index', ['type' => 'regularization']) }}" class="highlight-chip tone-amber mt-2"><i class="ti ti-alert-triangle"></i> You missed a punch-out yesterday · fix it</a>
                        @endif
                    </div>
                    <div class="side-list-subhead">Next 6 days</div>
                    @foreach ($day['next'] as $n)
                        <div class="side-list-item py-2">
                            <span class="when-block tone-{{ $n['holiday'] ? 'pink' : ($n['shift'] ? ShiftTime::style($n['shift'])['tone'] : 'slate') }}"><span class="when-block-month">{{ $n['date']->format('D') }}</span><span class="when-block-day">{{ $n['date']->format('j') }}</span></span>
                            <span class="flex-grow-1 font-13">
                                @if ($n['holiday'])
                                    <i class="ti ti-confetti tone-text-pink"></i> {{ $n['holiday'] }}
                                @elseif ($n['shift'])
                                    <strong>{{ $n['shift']->code }}</strong> {{ ShiftTime::format($n['shift']->start_time) }}–{{ ShiftTime::format($n['shift']->end_time) }}
                                @else
                                    <span class="text-muted">{{ $n['off'] ? 'Weekly off' : 'No shift' }}</span>
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- To-dos --}}
            <div class="col-xl-7">
                <div class="card side-list tone-rose h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-list-check"></i></span>
                        <span class="fw-semibold">Your to-dos</span>
                        @if ($data['todos']->isNotEmpty())<span class="side-list-count">{{ $data['todos']->count() }}</span>@endif
                    </div>
                    @forelse ($data['todos'] as $todo)
                        <a href="{{ route($todo['route']) }}" class="side-list-item">
                            <span class="attention-icon tone-{{ $todo['tone'] }}"><i class="ti {{ $todo['icon'] }}"></i></span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold font-13 text-truncate">{{ $todo['title'] }}</div>
                                @isset($todo['progress'])
                                    <span class="mini-progress-bar is-toned tone-{{ $todo['tone'] }} d-block mt-1"><span style="width: {{ $todo['progress'] }}%"></span></span>
                                @endisset
                            </div>
                            @if ($todo['date'])
                                @php $d = (int) $today->diffInDays($todo['date'], false); @endphp
                                <span class="countdown tone-{{ $d < 0 ? 'danger' : ($d <= 7 ? 'warning' : 'lime') }}">{{ $d < 0 ? 'Overdue '.abs($d).'d' : ($d ? 'in '.$d.'d' : 'Today') }}</span>
                            @endif
                        </a>
                    @empty
                        <div class="side-list-empty"><i class="ti ti-circle-check"></i> You're all caught up.</div>
                    @endforelse
                    @if ($profile['missing'])
                        <a href="{{ route('admin.my-profile.index') }}" class="side-list-foot"><i class="ti ti-user"></i> Help us know you: add your {{ implode(', ', array_slice($profile['missing'], 0, 3)) }}{{ count($profile['missing']) > 3 ? '…' : '' }}</a>
                    @endif
                </div>
            </div>

            {{-- My leave --}}
            <div class="col-xl-8">
                <div class="card side-list tone-violet h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-umbrella"></i></span>
                        <span class="fw-semibold">My leave · {{ $today->year }}</span>
                        <a href="{{ route('admin.my-leave.index') }}" class="ms-auto btn btn-sm btn-light"><i class="ti ti-calendar-plus"></i> Apply leave</a>
                    </div>
                    <div class="card-body">
                        @forelse ($leave['balances'] as $b)
                            @php
                                $style = LeaveTypeStyle::for($b['type']?->name, $b['type']?->code);
                                $total = max(0.0001, $b['entitled']);
                                $free = max(0, $b['left'] - $b['pending']);
                            @endphp
                            <div class="type-usage">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="tag tone-{{ $style['tone'] }}"><i class="ti {{ $style['icon'] }}"></i>{{ $b['type']?->name }}</span>
                                    <span class="ms-auto font-13"><strong class="{{ $b['left'] <= 2 ? 'text-warning' : '' }}">{{ Kit::days($b['left']) }}</strong> left{{ $b['pending'] ? ' · '.Kit::days($b['pending']).' pending' : '' }} <span class="text-muted">of {{ Kit::days($b['entitled']) }}</span></span>
                                </div>
                                <div class="stack-bar">
                                    @if ($b['used'])<span class="tone-slate" style="flex: {{ $b['used'] }}" title="Used {{ Kit::days($b['used']) }}"></span>@endif
                                    @if ($b['pending'])<span class="tone-amber" style="flex: {{ min($b['pending'], $b['left']) }}" title="Pending {{ Kit::days($b['pending']) }}"></span>@endif
                                    @if ($free)<span class="tone-{{ $style['tone'] }}" style="flex: {{ $free }}" title="Available {{ Kit::days($free) }}"></span>@endif
                                </div>
                            </div>
                        @empty
                            <div class="side-list-empty is-neutral px-0"><i class="ti ti-umbrella"></i> No leave balance for {{ $today->year }} yet. HR allocates balances at the start of each year.</div>
                        @endforelse
                    </div>
                    @if ($leave['upcoming']->isNotEmpty())
                        <div class="side-list-subhead">Upcoming & pending</div>
                        @foreach ($leave['upcoming'] as $r)
                            @php $style = LeaveTypeStyle::for($r->leaveType?->name, $r->leaveType?->code); @endphp
                            <div class="side-list-item">
                                <span class="when-block tone-{{ $style['tone'] }}"><span class="when-block-month">{{ $r->start_date->format('M') }}</span><span class="when-block-day">{{ $r->start_date->format('j') }}</span></span>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold font-13">{{ $r->leaveType?->name }} · {{ Kit::days((float) $r->days) }} {{ Str::plural('day', (float) $r->days) }}</div>
                                    <div class="list-subtext">{{ $r->start_date->format('M j') }}{{ $r->start_date->isSameDay($r->end_date) ? '' : ' – '.$r->end_date->format('M j') }}</div>
                                </div>
                                <span class="tag tone-{{ $r->status === 'approved' ? 'success' : 'amber' }}">{{ ucfirst($r->status) }}</span>
                                @if ($r->status === 'pending')
                                    <form method="POST" action="{{ route('admin.my-leave.cancel', $r) }}" onsubmit="return confirm('Cancel this leave request?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon-danger" title="Cancel request"><i class="ti ti-x"></i></button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card side-list tone-pink h-100">
                    <div class="side-list-head"><span class="side-list-icon"><i class="ti ti-confetti"></i></span><span class="fw-semibold">Upcoming holidays</span></div>
                    @forelse ($data['holidays'] as $i => $holiday)
                        @if ($i === 0)
                            <div class="card-body">
                                <div class="holiday-card is-static holiday-card-compact">
                                    <div class="holiday-card-month">{{ $holiday['date']->format('M Y') }}</div>
                                    <div class="holiday-card-day">{{ $holiday['date']->format('j') }}</div>
                                    <div class="holiday-card-name">{{ $holiday['name'] }}</div>
                                    <div class="holiday-card-when">{{ $holiday['date']->format('l') }}{{ $holiday['long_weekend'] ? ' · long weekend' : '' }}</div>
                                </div>
                            </div>
                        @else
                            <div class="side-list-item">
                                <span class="when-block tone-pink"><span class="when-block-month">{{ $holiday['date']->format('M') }}</span><span class="when-block-day">{{ $holiday['date']->format('j') }}</span></span>
                                <span class="flex-grow-1 fw-semibold font-13">{{ $holiday['name'] }}</span>
                                <span class="list-subtext">{{ $holiday['date']->format('D') }}</span>
                            </div>
                        @endif
                    @empty
                        <div class="side-list-empty is-neutral"><i class="ti ti-confetti"></i> No holidays coming up.</div>
                    @endforelse
                </div>
            </div>

            {{-- My attendance --}}
            <div class="col-xl-8">
                <div class="card side-list tone-indigo h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-calendar-stats"></i></span>
                        <span class="fw-semibold">My attendance · {{ $today->format('F') }}</span>
                        <a href="{{ route('admin.my-attendance.index', ['month' => $today->format('Y-m')]) }}" class="ms-auto font-12 text-decoration-none">Details <i class="ti ti-arrow-right"></i></a>
                    </div>
                    <div class="card-body d-flex gap-3 align-items-start flex-wrap">
                        <div class="text-center">
                            <div class="progress-ring progress-ring-sm tone-success" style="--value: {{ $month['rate'] ?? 0 }}"><span>{{ $month['rate'] !== null ? round($month['rate']) : '—' }}<small>{{ $month['rate'] !== null ? '%' : '' }}</small></span></div>
                            <div class="list-subtext mt-1">Attendance</div>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="heatmap-strip">
                                @foreach ($month['days'] as $d)
                                    @php
                                        $status = $d['record']?->status;
                                        $meta = Attendance::meta($status);
                                    @endphp
                                    @if ($status)
                                        <span class="heat-cell tone-{{ $meta['tone'] }} {{ $d['date']->isToday() ? 'is-today' : '' }}" title="{{ $d['date']->format('D, M j') }}: {{ Attendance::label($status) }}">{{ $meta['letter'] }}</span>
                                    @elseif ($d['date']->isFuture())
                                        <span class="heat-cell is-future {{ $d['holiday'] ? 'tone-pink' : '' }}" title="{{ $d['date']->format('D, M j') }}{{ $d['holiday'] ? ': '.$d['holiday'] : '' }}">{{ $d['date']->format('j') }}</span>
                                    @else
                                        <span class="heat-cell is-empty {{ $d['date']->isToday() ? 'is-today' : '' }}" title="{{ $d['date']->format('D, M j') }}: {{ $d['holiday'] ?? ($d['off'] ? 'weekly off' : 'not marked') }}"></span>
                                    @endif
                                @endforeach
                            </div>
                            @if ($month['counts']->isEmpty())
                                <div class="list-subtext mt-2">Nothing marked yet this month.</div>
                            @else
                                <div class="stack-legend mt-2">
                                    @foreach ($month['counts'] as $status => $n)
                                        <span><i class="legend-dot tone-{{ Attendance::meta($status)['tone'] }}"></i>{{ Attendance::label($status) }} <strong>{{ $n }}</strong></span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pay & requests --}}
            <div class="col-xl-4">
                <div class="card side-list tone-teal h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-file-invoice"></i></span>
                        <span class="fw-semibold">Pay & requests</span>
                    </div>
                    <div class="card-body pb-2">
                        @if ($pay['payslip'])
                            @php $run = $pay['payslip']->payrollRun; @endphp
                            <a href="{{ route('admin.my-payslips.show', $pay['payslip']) }}" class="leave-summary is-static tone-teal d-block text-decoration-none">
                                <div class="leave-summary-label">{{ $run ? Carbon\Carbon::create($run->year, $run->month, 1)->format('F Y') : 'Latest' }} payslip</div>
                                <div class="leave-summary-days">{{ Kit::money((float) $pay['payslip']->net_pay) }}<small> net</small></div>
                                <div class="leave-summary-dates">Gross {{ Kit::money((float) $pay['payslip']->gross_earnings) }} · deductions {{ Kit::money((float) $pay['payslip']->total_deductions) }}</div>
                            </a>
                        @else
                            <div class="side-list-empty is-neutral px-0"><i class="ti ti-file-invoice"></i> Your payslips will appear here after your first payroll run.</div>
                        @endif
                        @if ($pay['ot']['approved'] || $pay['ot']['pending'])
                            <div class="list-subtext mt-2"><i class="ti ti-alarm"></i> Overtime this month: {{ Kit::days($pay['ot']['approved']) }} h approved · {{ Kit::days($pay['ot']['pending']) }} h pending</div>
                        @endif
                    </div>
                    @if ($pay['requests']->isNotEmpty())
                        <div class="side-list-subhead">Recent requests</div>
                        @foreach ($pay['requests'] as $req)
                            <a href="{{ route($req['route'], $req['params']) }}" class="side-list-item py-2">
                                <i class="ti {{ $req['icon'] }} text-muted"></i>
                                <span class="flex-grow-1 font-13">{{ $req['label'] }} <span class="list-subtext">· {{ $req['at']?->diffForHumans() }}</span></span>
                                <span class="tag tone-{{ ['approved' => 'success', 'rejected' => 'danger', 'pending' => 'amber', 'cancelled' => 'slate', 'reimbursed' => 'teal'][$req['status']] ?? 'slate' }}">{{ ucfirst(str_replace('_', ' ', $req['status'])) }}</span>
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- My team --}}
            <div class="col-12">
                <div class="card side-list tone-sky">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-users"></i></span>
                        <span class="fw-semibold">My team</span>
                    </div>
                    @if (! $team['manager'] && $team['teammates']->isEmpty())
                        <div class="side-list-empty is-neutral"><i class="ti ti-hierarchy"></i> No reporting manager set. Ask HR to update your profile.</div>
                    @else
                        <div class="row g-0">
                            <div class="col-lg-4 border-end">
                                @if ($team['manager'])
                                    <div class="side-list-subhead border-0">Manager</div>
                                    <div class="side-list-item">
                                        <x-avatar :person="$team['manager']" />
                                        <div class="min-w-0"><div class="fw-semibold font-13">{{ $team['manager']->name }}</div><div class="list-subtext">{{ $team['manager']->designation }}</div></div>
                                    </div>
                                @endif
                                @if ($team['teammates']->isNotEmpty())
                                    <div class="side-list-subhead">Teammates</div>
                                    <div class="px-3 py-2 face-stack">@foreach ($team['teammates'] as $mate)<x-avatar :person="$mate" />@endforeach</div>
                                @endif
                            </div>
                            <div class="col-lg-4 border-end">
                                <div class="side-list-subhead border-0">Away this week</div>
                                @forelse ($team['away'] as $a)
                                    <div class="side-list-item py-2">
                                        <x-avatar :person="$a->employee" />
                                        <span class="flex-grow-1 font-13 fw-semibold text-truncate">{{ $a->employee?->name }}</span>
                                        <span class="list-subtext">{{ $a->start_date->format('M j') }}{{ $a->start_date->isSameDay($a->end_date) ? '' : '–'.$a->end_date->format('j') }}</span>
                                    </div>
                                @empty
                                    <div class="side-list-empty is-neutral"><i class="ti ti-mood-smile"></i> Everyone's around this week.</div>
                                @endforelse
                            </div>
                            <div class="col-lg-4">
                                <div class="side-list-subhead border-0">Celebrations & new faces</div>
                                @foreach ($team['celebrations'] as $c)
                                    <div class="side-list-item py-2">
                                        <i class="ti {{ $c['kind'] === 'birthday' ? 'ti-gift tone-text-pink' : 'ti-award tone-text-amber' }}"></i>
                                        <span class="flex-grow-1 font-13 text-truncate">{{ $c['employee']->name }} · {{ $c['kind'] === 'birthday' ? 'birthday' : $c['years'].' yr' }}</span>
                                        <span class="list-subtext">{{ $c['date']->isToday() ? 'Today' : $c['date']->format('M j') }}</span>
                                    </div>
                                @endforeach
                                @foreach ($team['new_faces'] as $n)
                                    <div class="side-list-item py-2">
                                        <i class="ti ti-user-plus tone-text-teal"></i>
                                        <span class="flex-grow-1 font-13 text-truncate">{{ $n->name }} joined</span>
                                        <span class="list-subtext">{{ $n->joining_date->format('M j') }}</span>
                                    </div>
                                @endforeach
                                @if ($team['celebrations']->isEmpty() && $team['new_faces']->isEmpty())
                                    <div class="side-list-empty is-neutral"><i class="ti ti-calendar-event"></i> Nothing to celebrate in the next 14 days.</div>
                                @endif
                            </div>
                        </div>
                    @endif
                    @if ($team['assets'])
                        <a href="{{ route('admin.my-assets.index') }}" class="side-list-foot"><i class="ti ti-package"></i> {{ $team['assets'] }} {{ Str::plural('asset', $team['assets']) }} with you</a>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endsection
