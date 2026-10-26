<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Course;
use App\Models\Trainer;
use App\Models\TrainingEnrollment;
use App\Models\TrainingSession;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainingSessionController extends Controller
{
    /** A single session can't run longer than this many days. */
    protected const MAX_DAYS = 90;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TrainingSession::class);

        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        // A bad ?month= used to crash the page; anything that isn't YYYY-MM now falls back to this month.
        $start = preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $text('month'), $m) && (int) $m[1] >= 2000 && (int) $m[1] <= 2100
            ? Carbon::create((int) $m[1], (int) $m[2], 1)
            : today()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $status = array_key_exists($text('status'), TrainingSession::STATUSES) ? $text('status') : '';

        $sessions = $this->scoped()
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->with(['course:id,title,delivery_mode,duration_hours,category', 'trainer:id,name'])
            ->orderBy('start_date')->orderBy('id')
            ->get();
        $enrolled = TrainingEnrollment::query()->whereIn('training_session_id', $sessions->pluck('id'))->where('status', '!=', 'dropped')
            ->selectRaw('training_session_id, COUNT(*) as total')->groupBy('training_session_id')->pluck('total', 'training_session_id');
        $live = $sessions->where('status', '!=', 'cancelled');
        // Still "scheduled" or "ongoing" although the last day has passed: someone forgot to close it.
        $stale = $live->filter(fn (TrainingSession $s) => in_array($s->status, ['scheduled', 'ongoing'], true) && $s->end_date->lt(today()));
        $courses = $this->courses()->where('is_active', true)->orderBy('title')->get(['id', 'title', 'delivery_mode', 'duration_hours', 'category']);
        $trainers = $this->trainers()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'specialization', 'is_internal']);

        return view('admin.hrm.learning.training-sessions.index', [
            'start' => $start,
            'month' => $start->format('Y-m'),
            'status' => $status,
            'sessions' => $sessions,
            'listed' => $status ? $sessions->where('status', $status)->values() : $sessions,
            'enrolled' => $enrolled,
            'weeks' => $this->weeks($start),
            'counts' => ['all' => $sessions->count()] + collect(TrainingSession::STATUSES)->map(fn ($label, $key) => $sessions->where('status', $key)->count())->all(),
            'kpis' => [
                'sessions' => $live->count(),
                'cancelled' => $sessions->where('status', 'cancelled')->count(),
                'seats' => (int) $live->sum(fn (TrainingSession $s) => (int) ($enrolled[$s->id] ?? 0)),
                'capacity' => (int) $live->whereNotNull('capacity')->sum('capacity'),
                'hours' => (float) $live->sum(fn (TrainingSession $s) => (float) ($s->course?->duration_hours ?? 0)),
                'no_trainer' => $live->whereNull('trainer_id')->where('status', '!=', 'completed')->count(),
                'stale' => $stale->count(),
            ],
            'courses' => $courses,
            'trainers' => $trainers,
            // Every booking of each trainer from last month on, so the form can warn about a clash before saving.
            'bookings' => $this->scoped()->whereNotNull('trainer_id')->where('status', '!=', 'cancelled')->whereDate('end_date', '>=', today()->subMonth())
                ->with('course:id,title')->get(['id', 'trainer_id', 'course_id', 'start_date', 'end_date'])
                ->map(fn (TrainingSession $s) => ['id' => $s->id, 'trainer' => $s->trainer_id, 'from' => $s->start_date->toDateString(), 'to' => $s->end_date->toDateString(), 'course' => $s->course?->title]),
            'canCreate' => $request->user()->can('create', TrainingSession::class),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TrainingSession::class);

        $data = $this->validated($request);

        $session = TrainingSession::create($data + ['company_id' => Company::current()->id, 'branch_id' => BranchContext::currentBranchId(), 'status' => 'scheduled']);

        return redirect()->route('admin.training-sessions.index', ['month' => $session->start_date->format('Y-m')])
            ->with('status', '"'.$session->course->title.'" scheduled for '.$this->dates($session).'. Assign people to it under Training Assignment.');
    }

    public function update(Request $request, TrainingSession $trainingSession): RedirectResponse
    {
        $this->ensureOwn($trainingSession);
        $this->authorize('update', $trainingSession);

        // The status buttons send only a status; the edit dialog sends the details.
        if ($request->has('status') && ! $request->has('course_id')) {
            $data = $request->validate(['status' => ['required', Rule::in(array_keys(TrainingSession::STATUSES))]]);

            if (in_array($data['status'], ['ongoing', 'completed'], true) && $trainingSession->start_date->gt(today())) {
                return back()->withErrors(['status' => 'This session doesn\'t start until '.$trainingSession->start_date->format('M j, Y').', so it can\'t be marked '.strtolower(TrainingSession::STATUSES[$data['status']]).' yet.']);
            }

            // While it was cancelled its trainer was free to be booked elsewhere; check before bringing it back.
            if ($trainingSession->status === 'cancelled' && $data['status'] !== 'cancelled' && $trainingSession->trainer_id) {
                $clash = TrainingSession::query()->where('company_id', Company::current()->id)->where('trainer_id', $trainingSession->trainer_id)
                    ->where('status', '!=', 'cancelled')->whereKeyNot($trainingSession->id)
                    ->whereDate('start_date', '<=', $trainingSession->end_date)->whereDate('end_date', '>=', $trainingSession->start_date)
                    ->with('course:id,title')->first();

                if ($clash) {
                    return back()->withErrors(['status' => ($trainingSession->trainer?->name ?? 'The trainer').' has since been booked for "'.($clash->course?->title ?? 'another session').'" on '.$this->dates($clash).'. Change the trainer or the dates of this session first, then restore it.']);
                }
            }

            $trainingSession->update($data);

            return back()->with('status', 'Session marked '.strtolower(TrainingSession::STATUSES[$data['status']]).'.');
        }

        $trainingSession->update($this->validated($request, $trainingSession));

        return back()->with('status', 'Training session updated.');
    }

    public function destroy(TrainingSession $trainingSession): RedirectResponse
    {
        $this->ensureOwn($trainingSession);
        $this->authorize('delete', $trainingSession);

        // Deleting a session would also delete everyone's enrolment in it, including completed training on their record.
        $people = TrainingEnrollment::query()->where('training_session_id', $trainingSession->id)->count();

        if ($people) {
            return back()->withErrors(['session' => $people.' '.($people === 1 ? 'person is' : 'people are').' assigned to this session, so it can\'t be deleted. Cancel it instead; their record is kept.']);
        }

        $trainingSession->delete();

        return back()->with('status', 'Training session removed.');
    }

    protected function validated(Request $request, ?TrainingSession $session = null): array
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer'],
            'trainer_id' => ['nullable', 'integer'],
            'start_date' => ['required', 'date', 'after_or_equal:'.today()->subYears(2)->toDateString(), 'before_or_equal:'.today()->addYears(2)->toDateString()],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ], [
            'end_date.after_or_equal' => 'The last day can\'t be before the first day.',
            'start_date.before_or_equal' => 'A session can be scheduled at most two years ahead.',
            'start_date.after_or_equal' => 'A session can be dated at most two years back.',
        ]);

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();

        if ($start->diffInDays($end) + 1 > self::MAX_DAYS) {
            throw ValidationException::withMessages(['end_date' => 'A session can run for at most '.self::MAX_DAYS.' days. Split a longer programme into several sessions.']);
        }

        // The course must be one this login can see, and still on offer (unless the session already uses it).
        $course = $this->courses()->whereKey($data['course_id'])->first();

        if (! $course) {
            throw ValidationException::withMessages(['course_id' => 'Choose a course from the list.']);
        }
        if (! $course->is_active && (int) $session?->course_id !== (int) $course->id) {
            throw ValidationException::withMessages(['course_id' => '"'.$course->title.'" is switched off. Switch it on under Courses before scheduling it.']);
        }

        $trainer = null;

        if (filled($data['trainer_id'] ?? null)) {
            $trainer = $this->trainers()->whereKey($data['trainer_id'])->first();

            if (! $trainer) {
                throw ValidationException::withMessages(['trainer_id' => 'Choose a trainer from the list.']);
            }
            if (! $trainer->is_active && (int) $session?->trainer_id !== (int) $trainer->id) {
                throw ValidationException::withMessages(['trainer_id' => $trainer->name.' is no longer an active trainer.']);
            }

            // One trainer can't be in two sessions on the same day.
            $clash = TrainingSession::query()->where('company_id', Company::current()->id)->where('trainer_id', $trainer->id)->where('status', '!=', 'cancelled')
                ->when($session, fn ($q) => $q->whereKeyNot($session->id))
                ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)
                ->with('course:id,title')->first();

            if ($clash) {
                throw ValidationException::withMessages(['trainer_id' => $trainer->name.' is already running "'.($clash->course?->title ?? 'another session').'" on '.$this->dates($clash).'. Pick another trainer or other dates.']);
            }
        }

        $capacity = filled($data['capacity'] ?? null) ? (int) $data['capacity'] : null;

        if ($session && $capacity !== null) {
            $people = TrainingEnrollment::query()->where('training_session_id', $session->id)->where('status', '!=', 'dropped')->count();

            if ($capacity < $people) {
                throw ValidationException::withMessages(['capacity' => $people.' people are already assigned to this session, so it can\'t be limited to '.$capacity.' seats.']);
            }
        }

        return [
            'course_id' => $course->id,
            'trainer_id' => $trainer?->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'location' => filled($data['location'] ?? null) ? trim($data['location']) : null,
            'capacity' => $capacity,
        ];
    }

    /** "Oct 12, 2026" or "Oct 12 – 14, 2026" */
    protected function dates(TrainingSession $session): string
    {
        return $session->start_date->isSameDay($session->end_date)
            ? $session->start_date->format('M j, Y')
            : $session->start_date->format('M j').' – '.$session->end_date->format($session->start_date->isSameMonth($session->end_date) ? 'j, Y' : 'M j, Y');
    }

    /** The weeks (Monday first) that cover a month, each a list of seven days. */
    protected function weeks(Carbon $start): array
    {
        $day = $start->copy()->startOfWeek(Carbon::MONDAY);
        $last = $start->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $weeks = [];

        while ($day->lte($last)) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $week[] = $day->copy();
                $day->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    /** 404 (not 403) for another company's or branch's session, so the URL doesn't confirm it exists. */
    protected function ensureOwn(TrainingSession $session): void
    {
        abort_unless($this->scoped()->whereKey($session->id)->exists(), 404);
    }

    /** Sessions this login may see: its own company and, when tied to a branch, that branch's plus company-wide ones. */
    protected function scoped(): Builder
    {
        return TrainingSession::query()
            ->where('training_sessions.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')));
    }

    protected function courses(): Builder
    {
        return Course::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')));
    }

    /** External trainers are shared; internal ones follow their employee's branch. */
    protected function trainers(): Builder
    {
        return Trainer::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->whereNull('employee_id')
                ->orWhereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId()))));
    }
}
