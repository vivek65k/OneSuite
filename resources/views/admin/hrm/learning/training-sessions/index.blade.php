@extends('layouts.admin')

@section('title', 'Training Calendar')

@php
    use App\Models\Course;
    use App\Models\TrainingSession;
    use Illuminate\Support\Str;

    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $pageUrl = fn (array $overrides = []) => route('admin.training-sessions.index', array_filter(
        array_merge(['month' => $month, 'status' => $status], $overrides),
        fn ($value) => $value !== null && $value !== ''
    ));
    $statusMeta = [
        'scheduled' => ['indigo', 'ti-calendar-event'],
        'ongoing' => ['amber', 'ti-player-play'],
        'completed' => ['success', 'ti-circle-check'],
        'cancelled' => ['slate', 'ti-ban'],
    ];
    $modeIcon = ['online' => 'ti-device-laptop', 'offline' => 'ti-building', 'hybrid' => 'ti-switch-horizontal'];
    $tabs = ['' => ['label' => 'All sessions', 'tone' => 'indigo', 'count' => $counts['all']]];
    foreach (TrainingSession::STATUSES as $value => $label) {
        $tabs[$value] = ['label' => $label, 'tone' => $statusMeta[$value][0] ?? 'slate', 'count' => $counts[$value] ?? 0];
    }
    $dates = fn ($s) => $s->start_date->isSameDay($s->end_date)
        ? $s->start_date->format('M j, Y')
        : $s->start_date->format('M j').' – '.$s->end_date->format($s->start_date->isSameMonth($s->end_date) ? 'j, Y' : 'M j, Y');
    $isThisMonth = $start->isSameMonth(today());
    $fillPct = $kpis['capacity'] > 0 ? min(100, round($kpis['seats'] / $kpis['capacity'] * 100)) : null;
    $oldCourse = (string) old('course_id', '');
    $oldTrainer = (string) old('trainer_id', '');
@endphp

@section('content')
    <x-page-title title="Training Calendar" />

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ti ti-circle-check font-18"></i> {{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2"><i class="ti ti-alert-circle font-18"></i><div>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div></div>
    @endif

    <div class="record-header">
        <span class="list-avatar list-avatar-square record-avatar tone-indigo" aria-hidden="true"><i class="ti ti-calendar-event font-20"></i></span>
        <div class="record-header-text">
            <div class="record-header-title">{{ $start->format('F Y') }}</div>
            <div class="record-header-meta">
                <span>{{ $kpis['sessions'] }} {{ Str::plural('session', $kpis['sessions']) }}{{ $kpis['cancelled'] ? ' · '.$kpis['cancelled'].' cancelled' : '' }}</span>
                @if ($isThisMonth)
                    <span class="status-dot status-dot-success">This month</span>
                @endif
            </div>
        </div>
        <div class="record-header-actions">
            <form method="GET" action="{{ route('admin.training-sessions.index') }}" class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.training-sessions.index', ['month' => $start->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-light btn-sm px-2" title="Previous month"><i class="ti ti-chevron-left font-14"></i></a>
                <input type="month" name="month" class="form-control form-control-sm list-inline-select" value="{{ $month }}" onchange="this.form.submit()" aria-label="Month">
                <a href="{{ route('admin.training-sessions.index', ['month' => $start->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-light btn-sm px-2" title="Next month"><i class="ti ti-chevron-right font-14"></i></a>
                @unless ($isThisMonth)
                    <a href="{{ route('admin.training-sessions.index') }}" class="btn btn-light btn-sm px-3">Today</a>
                @endunless
            </form>
        </div>
    </div>

    <div class="kpi-strip">
        <x-kpi-card label="Sessions this month" :value="$kpis['sessions']" icon="ti-calendar-event" tone="indigo" :hint="$kpis['sessions'] ? ($counts['completed'] ?? 0).' completed · '.($counts['scheduled'] ?? 0).' still to run' : 'Nothing scheduled'" />
        <x-kpi-card label="Seats filled" :value="$kpis['capacity'] > 0 ? $kpis['seats'].' / '.$kpis['capacity'] : ($kpis['seats'] ?: '—')" icon="ti-users" tone="teal" :hint="$fillPct !== null ? $fillPct.'% of the seats on offer' : ($kpis['seats'] ? 'People assigned, no seat limits set' : 'Nobody assigned yet')" />
        <x-kpi-card label="Training hours" :value="$kpis['hours'] > 0 ? $num($kpis['hours']) : '—'" icon="ti-clock" tone="amber" :hint="$kpis['hours'] > 0 ? 'Course time across these sessions' : 'No course durations set'" />
        <x-kpi-card label="Needs attention" :value="$kpis['no_trainer'] + $kpis['stale']" icon="ti-alert-triangle" :tone="($kpis['no_trainer'] + $kpis['stale']) ? 'rose' : 'success'"
                    :hint="($kpis['no_trainer'] + $kpis['stale']) ? collect([$kpis['no_trainer'] ? $kpis['no_trainer'].' without a trainer' : null, $kpis['stale'] ? $kpis['stale'].' ended but not closed' : null])->filter()->implode(' · ') : 'Every session has a trainer and is up to date'" />
    </div>

    @if ($canCreate)
        <div class="row g-3 mb-3">
            <div class="col-xl-7">
                <form method="POST" action="{{ route('admin.training-sessions.store') }}" class="card form-section tone-indigo mb-0 h-100" id="session-form">
                    @csrf
                    <div class="form-section-head">
                        <span class="form-section-icon"><i class="ti ti-calendar-plus"></i></span>
                        <div class="flex-grow-1">
                            <h5 class="form-section-title">Schedule a session</h5>
                            <p class="form-section-sub">Pick a course, the days it runs and who teaches it. People are assigned to it afterwards.</p>
                        </div>
                    </div>
                    <div class="form-section-body">
                        @if ($courses->isEmpty())
                            <div class="run-note tone-amber"><i class="ti ti-alert-triangle"></i><span>There are no active courses to schedule. @can('viewAny', Course::class)<a href="{{ route('admin.courses.index') }}">Add a course</a> first.@endcan</span></div>
                        @else
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="session-course">Course</label>
                                    <select name="course_id" id="session-course" class="form-select @error('course_id') is-invalid @enderror" required>
                                        <option value="" data-mode="" data-hours="">Select course</option>
                                        @foreach ($courses as $course)
                                            <option value="{{ $course->id }}" data-mode="{{ $course->delivery_mode }}" data-hours="{{ $course->duration_hours !== null ? $num($course->duration_hours) : '' }}" data-category="{{ $course->category }}" @selected($oldCourse === (string) $course->id)>{{ $course->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="session-trainer">Trainer <span class="text-muted fw-normal">(optional)</span></label>
                                    <select name="trainer_id" id="session-trainer" class="form-select @error('trainer_id') is-invalid @enderror">
                                        <option value="">Decide later</option>
                                        @foreach ($trainers as $trainer)
                                            <option value="{{ $trainer->id }}" @selected($oldTrainer === (string) $trainer->id)>{{ $trainer->name }}{{ $trainer->specialization ? ' — '.$trainer->specialization : '' }}{{ $trainer->is_internal ? '' : ' (external)' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="session-start">First day</label>
                                    <input type="date" name="start_date" id="session-start" value="{{ old('start_date') }}" class="form-control @error('start_date') is-invalid @enderror" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="session-end">Last day</label>
                                    <input type="date" name="end_date" id="session-end" value="{{ old('end_date') }}" class="form-control @error('end_date') is-invalid @enderror" required>
                                    <div class="quick-chips">
                                        @foreach ([1 => '1 day', 2 => '2 days', 3 => '3 days', 5 => '5 days'] as $days => $label)
                                            <button type="button" class="quick-chip" data-days="{{ $days }}">{{ $label }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label" for="session-location"><span id="session-location-label">Where</span> <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="text" name="location" id="session-location" value="{{ old('location') }}" maxlength="255" class="form-control" placeholder="Room or meeting link">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="session-capacity">Seats <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="number" min="1" max="100000" name="capacity" id="session-capacity" value="{{ old('capacity') }}" class="form-control @error('capacity') is-invalid @enderror" placeholder="No limit">
                                    <div class="quick-chips">
                                        @foreach ([10, 20, 30] as $seats)
                                            <button type="button" class="quick-chip" data-seats="{{ $seats }}">{{ $seats }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-de-primary text-light px-4"><i class="ti ti-check font-14 me-1"></i> Schedule session</button>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
            <div class="col-xl-5">
                <div class="card side-list tone-teal mb-0 h-100">
                    <div class="side-list-head">
                        <span class="side-list-icon"><i class="ti ti-eye"></i></span>
                        <span class="fw-semibold">What you are scheduling</span>
                    </div>
                    <div class="card-body">
                        <div class="rev-compare">
                            <div>
                                <span class="rev-compare-label">Course</span>
                                <span class="rev-compare-value promo-title" id="sum-course">Pick a course</span>
                                <span class="rev-compare-meta" id="sum-course-meta"></span>
                            </div>
                            <i class="ti ti-arrow-right"></i>
                            <div>
                                <span class="rev-compare-label">Runs</span>
                                <span class="rev-compare-value promo-title" id="sum-days">—</span>
                                <span class="rev-compare-meta" id="sum-days-meta"></span>
                            </div>
                        </div>
                        <div class="run-note tone-amber mt-3" id="sum-warn" hidden><i class="ti ti-alert-triangle"></i><span></span></div>
                        <div class="run-note tone-indigo mt-2"><i class="ti ti-info-circle"></i><span>A trainer can't run two sessions on the same day, and a session can last at most 90 days.</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Month grid --}}
    <div class="card mb-3">
        <div class="cal-grid" role="grid" aria-label="{{ $start->format('F Y') }}">
            @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dow)
                <div class="cal-dow {{ in_array($dow, ['Sat', 'Sun'], true) ? 'is-weekend' : '' }}" role="columnheader">{{ $dow }}</div>
            @endforeach
            @foreach ($weeks as $week)
                @foreach ($week as $day)
                    @php
                        $inMonth = $day->month === $start->month;
                        $onDay = $inMonth ? $sessions->filter(fn ($s) => $s->start_date->lte($day) && $s->end_date->gte($day))->values() : collect();
                    @endphp
                    <div class="cal-day {{ $inMonth ? '' : 'is-outside' }} {{ $day->isWeekend() ? 'is-weekend' : '' }} {{ $day->isToday() ? 'is-today' : '' }}" role="gridcell">
                        <div class="cal-day-head">
                            <span class="cal-day-num">{{ $day->format('j') }}</span>
                            @if ($onDay->count() > 1)
                                <span class="cal-day-count" title="{{ $onDay->count() }} sessions">{{ $onDay->count() }}</span>
                            @endif
                        </div>
                        @foreach ($onDay->take(3) as $s)
                            <a href="#session-{{ $s->id }}" class="cal-event tone-{{ $statusMeta[$s->status][0] ?? 'slate' }} {{ $s->status === 'cancelled' ? 'is-cancelled' : '' }}" title="{{ $s->course?->title }} · {{ TrainingSession::STATUSES[$s->status] ?? $s->status }}{{ $s->trainer ? ' · '.$s->trainer->name : '' }}">{{ $s->course?->title ?? 'Removed course' }}</a>
                        @endforeach
                        @if ($onDay->count() > 3)
                            <span class="cal-more">+{{ $onDay->count() - 3 }} more</span>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- Sessions --}}
    <div class="card list-view">
        <div class="list-toolbar">
            <nav class="list-tabs" aria-label="Session status">
                @foreach ($tabs as $value => $tab)
                    <a href="{{ $pageUrl(['status' => $value ?: null]) }}" class="list-tab tone-{{ $tab['tone'] }} {{ $status === $value ? 'active' : '' }}">
                        {{ $tab['label'] }}
                        <span class="list-tab-count">{{ $tab['count'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
        <div class="table-responsive table-responsive-sticky">
            <table class="table table-hover list-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>When</th>
                        <th>Course</th>
                        <th>Trainer</th>
                        <th>Where</th>
                        <th style="min-width: 150px">Seats</th>
                        <th>Status</th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($listed as $s)
                        @php
                            $people = (int) ($enrolled[$s->id] ?? 0);
                            $meta = $statusMeta[$s->status] ?? ['slate', 'ti-circle'];
                            $days = (int) $s->start_date->diffInDays($s->end_date) + 1;
                            $ended = $s->end_date->lt(today());
                            $started = $s->start_date->lte(today());
                            $stale = in_array($s->status, ['scheduled', 'ongoing'], true) && $ended;
                            $full = $s->capacity && $people >= $s->capacity;
                        @endphp
                        <tr id="session-{{ $s->id }}" class="{{ $stale ? 'is-pending-row' : '' }}">
                            <td class="text-nowrap">
                                <div class="date-range-cell">
                                    <span class="when-block tone-{{ $meta[0] }}"><span class="when-block-month">{{ $s->start_date->format('M') }}</span><span class="when-block-day">{{ $s->start_date->format('j') }}</span></span>
                                    <div>
                                        <div class="fw-semibold">{{ $dates($s) }}</div>
                                        <div class="payslip-parts">{{ $days }} {{ Str::plural('day', $days) }}{{ $s->start_date->isToday() ? ' · starts today' : ($s->start_date->isFuture() ? ' · in '.(int) today()->diffInDays($s->start_date).' days' : '') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $s->course?->title ?? 'Removed course' }}</div>
                                @if ($s->course)
                                    <div class="payslip-parts"><i class="ti {{ $modeIcon[$s->course->delivery_mode] ?? 'ti-book' }}"></i> {{ Course::DELIVERY_MODES[$s->course->delivery_mode] ?? $s->course->delivery_mode }}{{ $s->course->duration_hours !== null ? ' · '.$num($s->course->duration_hours).' h' : '' }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($s->trainer)
                                    {{ $s->trainer->name }}
                                @elseif (in_array($s->status, ['scheduled', 'ongoing'], true))
                                    <span class="tag tone-rose"><i class="ti ti-alert-triangle"></i>No trainer</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="rev-notes" title="{{ $s->location }}">{{ $s->location ? Str::limit($s->location, 40) : '—' }}</td>
                            <td>
                                @if ($s->capacity)
                                    <div class="mini-progress tone-{{ $full ? 'rose' : 'teal' }}">
                                        <div class="mini-progress-bar is-toned"><span style="width: {{ min(100, round($people / $s->capacity * 100)) }}%"></span></div>
                                        <span class="mini-progress-label">{{ $people }}/{{ $s->capacity }}</span>
                                    </div>
                                    <div class="payslip-parts">{{ $full ? 'Full' : ($s->capacity - $people).' '.Str::plural('seat', $s->capacity - $people).' left' }}</div>
                                @else
                                    <div>{{ $people }} assigned</div>
                                    <div class="payslip-parts">No seat limit</div>
                                @endif
                            </td>
                            <td>
                                <span class="tag tone-{{ $meta[0] }}"><i class="ti {{ $meta[1] }}"></i>{{ TrainingSession::STATUSES[$s->status] ?? $s->status }}</span>
                                @if ($stale)
                                    <div class="payslip-parts">Ended {{ $s->end_date->format('M j') }}, not closed</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="list-row-actions">
                                    @if ($people || in_array($s->status, ['scheduled', 'ongoing'], true))
                                        @can('viewAny', \App\Models\TrainingEnrollment::class)
                                            <a href="{{ route('admin.training-enrollments.index', ['session_id' => $s->id]) }}" class="btn-icon" title="{{ $people ? 'See or assign people' : 'Assign people' }}"><i class="ti ti-users font-16"></i></a>
                                        @endcan
                                    @endif
                                    @can('update', $s)
                                        @php
                                            // The one obvious next step for a session, as a labelled button.
                                            $next = match (true) {
                                                $s->status === 'scheduled' && $ended => ['completed', 'Mark completed', 'ti-circle-check'],
                                                $s->status === 'scheduled' && $started => ['ongoing', 'Start', 'ti-player-play'],
                                                $s->status === 'ongoing' => ['completed', 'Mark completed', 'ti-circle-check'],
                                                $s->status === 'cancelled' => ['scheduled', 'Restore', 'ti-rotate'],
                                                default => null,
                                            };
                                        @endphp
                                        @if ($next)
                                            <form method="POST" action="{{ route('admin.training-sessions.update', $s) }}" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="{{ $next[0] }}">
                                                <button type="submit" class="btn btn-sm review-btn is-approve"><i class="ti {{ $next[2] }}"></i> {{ $next[1] }}</button>
                                            </form>
                                        @endif
                                        @if (in_array($s->status, ['scheduled', 'ongoing'], true))
                                            <form method="POST" action="{{ route('admin.training-sessions.update', $s) }}" class="d-inline" onsubmit="return confirm('Cancel this session?{{ $people ? ' '.$people.' assigned '.Str::plural('person', $people).' will see it as cancelled.' : '' }}');">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="btn-icon" title="Cancel session"><i class="ti ti-ban font-16"></i></button>
                                            </form>
                                        @endif
                                        <button type="button" class="btn-icon btn-icon-accent" title="Change session" data-bs-toggle="modal" data-bs-target="#session-modal"
                                                data-action="{{ route('admin.training-sessions.update', $s) }}" data-course="{{ $s->course_id }}" data-course-title="{{ $s->course?->title }}" data-trainer="{{ $s->trainer_id }}"
                                                data-start="{{ $s->start_date->toDateString() }}" data-end="{{ $s->end_date->toDateString() }}" data-location="{{ $s->location }}" data-capacity="{{ $s->capacity }}" data-people="{{ $people }}"><i class="ti ti-pencil font-16"></i></button>
                                    @endcan
                                    @unless ($people)
                                        @can('delete', $s)
                                            <form action="{{ route('admin.training-sessions.destroy', $s) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this session? Nobody is assigned to it.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Delete session"><i class="ti ti-trash font-16"></i></button>
                                            </form>
                                        @endcan
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-calendar-event font-24 d-block mb-2"></i>
                                @if ($counts['all'])
                                    <div class="fw-semibold text-body mb-1">No {{ strtolower(TrainingSession::STATUSES[$status] ?? '') }} sessions in {{ $start->format('F') }}</div>
                                    <span class="font-13"><a href="{{ $pageUrl(['status' => null]) }}">Show all sessions</a></span>
                                @else
                                    <div class="fw-semibold text-body mb-1">Nothing scheduled in {{ $start->format('F Y') }}</div>
                                    <span class="font-13">{{ $canCreate ? 'Schedule a session with the form above.' : 'Sessions scheduled by HR will appear here.' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="session-modal" tabindex="-1" aria-labelledby="session-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="#" class="modal-content" id="session-edit">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="session-modal-title">Change session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="edit-course">Course</label>
                            <select name="course_id" id="edit-course" class="form-select" required>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit-trainer">Trainer</label>
                            <select name="trainer_id" id="edit-trainer" class="form-select">
                                <option value="">Decide later</option>
                                @foreach ($trainers as $trainer)
                                    <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-start">First day</label>
                            <input type="date" name="start_date" id="edit-start" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="edit-end">Last day</label>
                            <input type="date" name="end_date" id="edit-end" class="form-control" required>
                        </div>
                        <div class="col-8">
                            <label class="form-label" for="edit-location">Where</label>
                            <input type="text" name="location" id="edit-location" maxlength="255" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label" for="edit-capacity">Seats</label>
                            <input type="number" min="1" max="100000" name="capacity" id="edit-capacity" class="form-control" placeholder="No limit">
                        </div>
                    </div>
                    <div class="font-12 text-muted mt-2" id="edit-note"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-de-primary text-light"><i class="ti ti-check font-14 me-1"></i> Save session</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var el = function (id) { return document.getElementById(id); };
            var bookings = @json($bookings->values());
            var modes = @json(Course::DELIVERY_MODES);
            var addDays = function (iso, n) { var d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + n); return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); };
            var span = function (a, b) { return Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000) + 1; };
            // Who else has this trainer on any of these days (ignoring the session being edited).
            var clash = function (trainer, from, to, ignore) {
                if (!trainer || !from || !to) return null;
                return bookings.filter(function (b) { return String(b.trainer) === String(trainer) && String(b.id) !== String(ignore) && b.from <= to && b.to >= from; })[0] || null;
            };

            // Edit dialog
            var modal = el('session-modal');
            if (modal) {
                var editing = null, people = 0;
                var note = function () {
                    var c = clash(el('edit-trainer').value, el('edit-start').value, el('edit-end').value, editing);
                    var cap = parseInt(el('edit-capacity').value, 10);
                    el('edit-note').textContent = c ? 'This trainer is already running "' + c.course + '" from ' + c.from + ' to ' + c.to + '. Saving will be refused.'
                        : (cap && cap < people ? people + ' people are already assigned, so the seats can\'t go below that.' : (people ? people + (people === 1 ? ' person is' : ' people are') + ' assigned to this session.' : ''));
                };
                modal.addEventListener('show.bs.modal', function (event) {
                    var b = event.relatedTarget;
                    if (!b) return;
                    editing = b.dataset.action.split('/').pop(); people = parseInt(b.dataset.people, 10) || 0;
                    el('session-edit').action = b.dataset.action;
                    var course = el('edit-course');
                    // A session may use a course that has since been switched off; keep it selectable for this session.
                    if (![].some.call(course.options, function (o) { return o.value === b.dataset.course; })) {
                        var o = document.createElement('option'); o.value = b.dataset.course; o.textContent = (b.dataset.courseTitle || 'Course') + ' (switched off)'; o.dataset.temp = '1'; course.appendChild(o);
                    }
                    course.value = b.dataset.course;
                    el('edit-trainer').value = b.dataset.trainer || '';
                    el('edit-start').value = b.dataset.start; el('edit-end').value = b.dataset.end;
                    el('edit-location').value = b.dataset.location || ''; el('edit-capacity').value = b.dataset.capacity || '';
                    note();
                });
                modal.addEventListener('hidden.bs.modal', function () {
                    [].slice.call(el('edit-course').querySelectorAll('[data-temp]')).forEach(function (o) { o.remove(); });
                });
                ['edit-trainer', 'edit-start', 'edit-end', 'edit-capacity'].forEach(function (id) { el(id).addEventListener('input', note); el(id).addEventListener('change', note); });
            }

            // Schedule form
            var course = el('session-course');
            if (!course) return;
            var trainer = el('session-trainer'), start = el('session-start'), end = el('session-end');

            function render() {
                var opt = course.options[course.selectedIndex], warn = '';
                var picked = !!course.value, mode = opt ? opt.dataset.mode : '';
                el('sum-course').textContent = picked ? opt.textContent : 'Pick a course';
                el('sum-course-meta').textContent = picked ? [modes[mode], opt.dataset.hours ? opt.dataset.hours + ' h of content' : null, opt.dataset.category || null].filter(Boolean).join(' · ') : '';
                el('session-location-label').textContent = mode === 'online' ? 'Meeting link' : (mode === 'offline' ? 'Room or venue' : 'Where');
                el('session-location').placeholder = mode === 'online' ? 'e.g. https://meet…' : (mode === 'offline' ? 'e.g. Training room 2' : 'Room or meeting link');

                var ok = start.value && end.value && end.value >= start.value;
                var days = ok ? span(start.value, end.value) : null;
                el('sum-days').textContent = ok ? days + (days === 1 ? ' day' : ' days') : '—';
                el('sum-days-meta').textContent = ok ? (days === 1 ? start.value : start.value + ' to ' + end.value) : 'Pick the days';

                var c = clash(trainer.value, start.value, end.value, null);
                if (start.value && end.value && end.value < start.value) warn = 'The last day is before the first day.';
                else if (days && days > 90) warn = 'That is ' + days + ' days. A session can last at most 90; split it into several.';
                else if (c) warn = 'This trainer is already running "' + c.course + '" from ' + c.from + ' to ' + c.to + '. Saving will be refused.';
                else if (picked && !trainer.value) warn = 'No trainer yet. You can add one later; the session will show as needing attention.';
                el('sum-warn').hidden = !warn;
                el('sum-warn').querySelector('span').textContent = warn;
            }

            start.addEventListener('change', function () { if (!end.value || end.value < start.value) end.value = start.value; render(); });
            [course, trainer, end].forEach(function (input) { input.addEventListener('change', render); });
            document.querySelectorAll('#session-form [data-days]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    if (!start.value) { start.focus(); return; }
                    end.value = addDays(start.value, parseInt(chip.dataset.days, 10) - 1);
                    render();
                });
            });
            document.querySelectorAll('#session-form [data-seats]').forEach(function (chip) {
                chip.addEventListener('click', function () { el('session-capacity').value = chip.dataset.seats; });
            });
            render();
        })();
    </script>
@endpush
