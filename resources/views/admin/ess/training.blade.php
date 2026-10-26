@extends('layouts.admin')

@section('title', 'My Training')

@php
    use App\Http\Controllers\Admin\Ess\MyTrainingController as Training;
    use App\Models\Course;
    use Illuminate\Support\Str;

    $pageUrl = fn (array $overrides = []) => route('admin.my-training.index', array_filter(
        array_merge($state, $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $tabs = [
        '' => ['label' => 'All training', 'tone' => 'indigo', 'count' => $counts['all']],
        'todo' => ['label' => 'To do', 'tone' => 'amber', 'count' => $counts['todo']],
        'completed' => ['label' => 'Completed', 'tone' => 'success', 'count' => $counts['completed']],
        'closed' => ['label' => 'Dropped or cancelled', 'tone' => 'slate', 'count' => $counts['closed']],
    ];
    $stageMeta = [
        'upcoming' => ['sky', 'ti-calendar-event', 'Upcoming'],
        'now' => ['amber', 'ti-player-play', 'Ready to start'],
        'in_progress' => ['indigo', 'ti-clock', 'In progress'],
        'overdue' => ['rose', 'ti-alert-triangle', 'Session over, not closed'],
        'completed' => ['success', 'ti-circle-check', 'Completed'],
        'dropped' => ['slate', 'ti-x', 'Dropped'],
        'cancelled' => ['slate', 'ti-calendar-off', 'Session cancelled'],
    ];
    $modeMeta = ['online' => ['sky', 'ti-world'], 'offline' => ['teal', 'ti-building'], 'hybrid' => ['violet', 'ti-users']];
    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
    $next = $kpis['next'];
    $nextTitle = $next?->trainingSession?->course?->title;
@endphp

@section('content')
    <x-page-title title="My Training" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="kpi-strip">
        <x-kpi-card label="To do" :value="$kpis['todo']" icon="ti-school" :tone="$kpis['overdue'] ? 'rose' : ($kpis['todo'] ? 'amber' : 'success')"
                    :hint="$kpis['todo'] ? collect([$kpis['running'] ? $kpis['running'].' running now' : null, $kpis['overdue'] ? $kpis['overdue'].' not closed' : null, $next ? 'next: '.Str::limit((string) $nextTitle, 18).' in '.$next->days_to_start.' '.Str::plural('day', $next->days_to_start) : null])->filter()->implode(' · ') ?: 'Assigned to you' : 'Nothing is waiting for you'" :href="$kpis['todo'] ? $pageUrl(['tab' => 'todo']) : null" />
        <x-kpi-card label="Completed" :value="$kpis['completed']" icon="ti-circle-check" tone="success" :hint="$kpis['completed'] ? $kpis['completed_year'].' in '.now()->year : 'Nothing finished yet'" :href="$kpis['completed'] ? $pageUrl(['tab' => 'completed']) : null" />
        <x-kpi-card label="Hours learned" :value="$num($kpis['hours']).' h'" icon="ti-history" tone="indigo" :hint="$kpis['attempts'] ? $kpis['passed'].' of '.$kpis['attempts'].' '.Str::plural('assessment', $kpis['attempts']).' passed' : 'From completed courses'" />
        <x-kpi-card label="Certificates" :value="$kpis['certs']" icon="ti-file-certificate" :tone="$kpis['certs_expired'] ? 'rose' : ($kpis['certs_soon'] ? 'amber' : 'teal')" :hint="collect([$kpis['certs_soon'] ? $kpis['certs_soon'].' expiring soon' : null, $kpis['certs_expired'] ? $kpis['certs_expired'].' expired' : null])->filter()->implode(' · ') ?: ($kpis['certs'] ? 'All valid' : 'None on record')" />
    </div>

    <div class="card list-view">
        <div class="list-toolbar">
            <nav class="list-tabs" aria-label="Training status">
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
                        <th>Course</th>
                        <th>When</th>
                        <th>Where</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $e)
                        @php
                            $session = $e->trainingSession;
                            $course = $session?->course;
                            $stage = $stageMeta[$e->stage];
                            $mode = $modeMeta[$course?->delivery_mode] ?? ['slate', 'ti-school'];
                            $title = $course?->title ?? 'Removed course';
                            $sameDay = $session?->start_date && (! $session->end_date || $session->end_date->isSameDay($session->start_date));
                        @endphp
                        <tr class="{{ in_array($e->stage, ['now', 'overdue'], true) ? 'is-pending-row' : '' }}">
                            <td>
                                <div class="list-user">
                                    <span class="list-avatar list-avatar-square tone-{{ $mode[0] }}"><i class="ti {{ $mode[1] }}"></i></span>
                                    <div class="list-user-text">
                                        <div class="list-user-name" @if ($course?->description) title="{{ Str::limit($course->description, 300) }}" @endif>{{ $title }}</div>
                                        <div class="list-user-meta">{{ collect([$course?->category, Course::DELIVERY_MODES[$course?->delivery_mode] ?? null, (float) $course?->duration_hours ? $num($course->duration_hours).' h' : null])->filter()->implode(' · ') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                @if ($session?->start_date)
                                    {{ $session->start_date->format('D, M j') }}{{ $sameDay ? ', '.$session->start_date->format('Y') : ' – '.$session->end_date->format('D, M j, Y') }}
                                    <div class="payslip-parts">
                                        @if ($e->stage === 'upcoming')
                                            starts in {{ $e->days_to_start }} {{ Str::plural('day', $e->days_to_start) }}
                                        @elseif (in_array($e->stage, ['now', 'in_progress'], true))
                                            {{ $e->days_to_start === 0 ? 'starts today' : 'started '.abs($e->days_to_start).' '.Str::plural('day', abs($e->days_to_start)).' ago' }}
                                        @elseif ($e->completed_date)
                                            completed {{ $e->completed_date->format('M j, Y') }}
                                        @else
                                            assigned {{ $e->assigned_date?->format('M j, Y') }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">No date</span>
                                @endif
                            </td>
                            <td>
                                {{ $session?->location ?: ($course?->delivery_mode === 'online' ? 'Online' : '—') }}
                                @if ($session?->trainer)
                                    <div class="payslip-parts">with {{ $session->trainer->name }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $stage[0] }}"><i class="ti {{ $stage[1] }}"></i>{{ $stage[2] }}</span>
                                @if ($e->stage === 'overdue')
                                    <div class="payslip-parts">waiting for your trainer or HR to close it</div>
                                @elseif ($e->stage === 'cancelled')
                                    <div class="payslip-parts">nothing for you to do</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($e->can_start)
                                        <form action="{{ route('admin.my-training.start', $e) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti ti-player-play"></i> I've started</button>
                                        </form>
                                    @endif
                                    @if ($e->can_withdraw)
                                        <form action="{{ route('admin.my-training.withdraw', $e) }}" method="POST" class="d-inline" onsubmit="return confirm(@js('Withdraw from '.$title.'? Your seat goes to someone else and HR is told. Only HR can put you back.'))">
                                            @csrf
                                            <button type="submit" class="btn btn-sm review-btn is-reject"><i class="ti ti-x"></i> Can't attend</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-school font-24 d-block mb-2"></i>
                                @if ($state['tab'] !== '')
                                    <div class="fw-semibold text-body mb-1">{{ $state['tab'] === 'todo' ? 'Nothing is waiting for you' : 'Nothing here' }}</div>
                                    <span class="font-13"><a href="{{ route('admin.my-training.index') }}">Show all training</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">No training assigned to you yet</div>
                                    <span class="font-13">Courses appear here when HR or your manager enrols you in a session.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($results->isNotEmpty() || $certifications->isNotEmpty())
        <div class="row g-3">
            @if ($results->isNotEmpty())
                <div class="{{ $certifications->isNotEmpty() ? 'col-lg-6' : 'col-12' }}">
                    <div class="card form-section tone-indigo mb-0 h-100">
                        <div class="form-section-head form-section-head-compact">
                            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-list-check"></i></span>
                            <h5 class="form-section-title">Assessment results</h5>
                            <span class="count-pill ms-auto">{{ $results->where('status', 'pass')->count() }} / {{ $results->count() }} passed</span>
                        </div>
                        <div class="form-section-body">
                            @foreach ($results as $result)
                                @php
                                    $assessment = $result->assessment;
                                    $max = (float) ($assessment?->max_score ?: 0);
                                    $percent = $max > 0 ? min(100, round((float) $result->score / $max * 100)) : 0;
                                    $passed = $result->status === 'pass';
                                @endphp
                                <div class="py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                    <div class="d-flex justify-content-between gap-2 font-13">
                                        <span>
                                            <span class="fw-semibold">{{ $assessment?->title ?? 'Removed assessment' }}</span>
                                            <span class="text-muted">{{ collect([$assessment?->course?->title, $result->attempted_date?->format('M j, Y')])->filter()->implode(' · ') }}</span>
                                        </span>
                                        <span class="tag tone-{{ $passed ? 'success' : 'rose' }}"><i class="ti {{ $passed ? 'ti-circle-check' : 'ti-circle-x' }}"></i>{{ $passed ? 'Passed' : 'Not passed' }}</span>
                                    </div>
                                    <div class="mini-progress tone-{{ $passed ? 'success' : 'rose' }}">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ $percent }}%"></span></div>
                                        <span class="mini-progress-label">{{ $num($result->score) }}{{ $max > 0 ? ' / '.$num($max) : '' }}</span>
                                    </div>
                                    @if ($assessment && (float) $assessment->passing_score > 0)
                                        <div class="font-12 text-muted">{{ $num($assessment->passing_score) }} needed to pass</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            @if ($certifications->isNotEmpty())
                <div class="{{ $results->isNotEmpty() ? 'col-lg-6' : 'col-12' }}">
                    <div class="card form-section tone-teal mb-0 h-100">
                        <div class="form-section-head form-section-head-compact">
                            <span class="form-section-icon form-section-icon-sm"><i class="ti ti-file-certificate"></i></span>
                            <h5 class="form-section-title">My certificates</h5>
                            <span class="count-pill ms-auto">{{ $certifications->count() }}</span>
                        </div>
                        <div class="form-section-body">
                            @foreach ($certifications as $cert)
                                <div class="d-flex justify-content-between align-items-start gap-2 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                    <div class="font-13">
                                        <div class="fw-semibold">{{ $cert->name }}</div>
                                        <div class="text-muted">{{ collect([$cert->issuing_body, $cert->credential_id ? 'ID '.$cert->credential_id : null, $cert->issue_date ? 'issued '.$cert->issue_date->format('M j, Y') : null])->filter()->implode(' · ') }}</div>
                                    </div>
                                    <div class="text-end text-nowrap">
                                        @if ($cert->state === 'expired')
                                            <span class="tag tone-rose"><i class="ti ti-alert-triangle"></i>Expired{{ $cert->expiry_date ? ' '.$cert->expiry_date->format('M j, Y') : '' }}</span>
                                        @elseif ($cert->state === 'soon')
                                            <span class="tag tone-amber"><i class="ti ti-clock"></i>{{ $cert->days_left === 0 ? 'Expires today' : 'Expires in '.$cert->days_left.' '.Str::plural('day', $cert->days_left) }}</span>
                                        @else
                                            <span class="tag tone-success"><i class="ti ti-circle-check"></i>Valid</span>
                                            <div class="font-12 text-muted">{{ $cert->expiry_date ? 'until '.$cert->expiry_date->format('M j, Y') : 'does not expire' }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            <div class="font-12 text-muted mt-3"><i class="ti ti-info-circle"></i> Renewed a certificate? Give HR the new one so they can update your record.</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
@endsection
