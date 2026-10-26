<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TrainingEnrollment;
use App\Models\TrainingSession;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TrainingEnrollmentController extends Controller
{
    protected const TABS = ['assigned', 'in_progress', 'completed', 'dropped', 'attention'];

    /** Someone still expected to attend. These are the people who take up a seat's worth of follow-up. */
    protected const OPEN = ['assigned', 'in_progress'];

    /** Sessions people can still be put into. */
    protected const BOOKABLE = ['scheduled', 'ongoing'];

    /** Employees who have left; they can't be sent on training. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TrainingEnrollment::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'session_id' => $id('session_id'),
            'department_id' => $id('department_id'),
            'employee_id' => $id('employee_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['session_id'], fn ($q, $sessionId) => $q->where('training_session_id', $sessionId))
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w
                    ->whereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like)))
                    ->orWhereHas('trainingSession.course', fn ($c) => $c->where('title', 'like', $like)));
            });
        $tabbed = fn (Builder $q, string $tab) => match ($tab) {
            'attention' => $this->needsClosing($q),
            '' => $q,
            default => $q->where('status', $tab),
        };

        $enrollments = $tabbed($filtered(), $state['tab'])
            ->with(['employee.department:id,name', 'trainingSession.course:id,title,delivery_mode,duration_hours', 'trainingSession.trainer:id,name'])
            ->orderByDesc('assigned_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'training_session_id', 'employee_id', 'status']);
        $open = $all->whereIn('status', self::OPEN);
        $counted = $all->where('status', '!=', 'dropped')->count();
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'branch_id', 'status']);
        $listedSessions = TrainingSession::query()->where('company_id', Company::current()->id)->whereIn('id', $all->pluck('training_session_id')->unique())
            ->with('course:id,title')->orderByDesc('start_date')->get(['id', 'course_id', 'start_date', 'end_date', 'status']);
        $canCreate = $request->user()->can('create', TrainingEnrollment::class);

        return view('admin.hrm.learning.training-enrollments.index', [
            'enrollments' => $enrollments,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'sessionOptions' => $listedSessions->mapWithKeys(fn (TrainingSession $s) => [$s->id => ($s->course?->title ?? 'Removed course').' · '.$this->dates($s)]),
            'counts' => ['all' => $filtered()->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($filtered(), $tab)->count()])->all(),
            'kpis' => [
                'open' => $open->count(),
                'open_people' => $open->pluck('employee_id')->unique()->count(),
                'in_progress' => $all->where('status', 'in_progress')->count(),
                'completed' => $all->where('status', 'completed')->count(),
                'rate' => $counted > 0 ? (int) round($all->where('status', 'completed')->count() / $counted * 100) : null,
                'dropped' => $all->where('status', 'dropped')->count(),
                'attention' => $this->needsClosing($this->scoped())->count(),
            ],
            'focus' => $this->focus($state['session_id']),
            'canCreate' => $canCreate,
            'form' => $canCreate ? $this->formData($people->whereNotIn('status', self::INACTIVE)->values()) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TrainingEnrollment::class);

        // Several people can be assigned at once; the old single employee_id still works.
        if (! $request->has('employee_ids') && $request->filled('employee_id')) {
            $request->merge(['employee_ids' => [$request->input('employee_id')]]);
        }

        $data = $request->validate([
            'training_session_id' => ['required', 'integer'],
            'employee_ids' => ['required', 'array', 'min:1', 'max:500'],
            'employee_ids.*' => ['integer', 'distinct'],
        ], [
            'training_session_id.required' => 'Choose the session to assign people to.',
            'employee_ids.required' => 'Pick at least one employee.',
        ]);

        $session = $this->sessions()->with('course:id,title')->whereKey($data['training_session_id'])->first();

        if (! $session) {
            throw ValidationException::withMessages(['training_session_id' => 'Choose a session from the list.']);
        }
        if (! in_array($session->status, self::BOOKABLE, true)) {
            throw ValidationException::withMessages(['training_session_id' => 'That session is '.strtolower(TrainingSession::STATUSES[$session->status] ?? $session->status).', so nobody can be assigned to it.']);
        }

        $employees = $this->employeeQuery()->whereNotIn('status', self::INACTIVE)->whereIn('id', $data['employee_ids'])->orderBy('name')->get(['id', 'name']);

        if ($employees->count() !== count($data['employee_ids'])) {
            throw ValidationException::withMessages(['employee_ids' => 'One or more of the selected employees can\'t be assigned (not found, or no longer with the company).']);
        }

        $companyId = Company::current()->id;
        $title = $session->course?->title ?? 'the session';

        // The session row is locked so two people assigning at the same moment can't overfill it.
        $fresh = DB::transaction(function () use ($session, $employees, $companyId, $title) {
            $locked = TrainingSession::query()->whereKey($session->id)->lockForUpdate()->first();
            $existing = TrainingEnrollment::query()->where('training_session_id', $session->id)->get()->keyBy('employee_id');
            $taken = $existing->where('status', '!=', 'dropped')->count();
            $fresh = $employees->reject(fn (Employee $e) => $existing->has($e->id) && $existing[$e->id]->status !== 'dropped')->values();

            if ($fresh->isEmpty()) {
                throw ValidationException::withMessages(['employee_ids' => ($employees->count() === 1 ? $employees->first()->name.' is' : 'Everyone selected is').' already assigned to "'.$title.'".']);
            }

            if ($locked->capacity !== null && $taken + $fresh->count() > $locked->capacity) {
                $left = max(0, $locked->capacity - $taken);

                throw ValidationException::withMessages(['employee_ids' => '"'.$title.'" has '.($left === 0 ? 'no seats left' : 'only '.$left.' '.($left === 1 ? 'seat' : 'seats').' left').' ('.$taken.' of '.$locked->capacity.' taken), and you picked '.$fresh->count().'. Pick fewer people or raise the seats on the Training Calendar.']);
            }

            foreach ($fresh as $employee) {
                // Someone who dropped out earlier gets their old row back; one person can only be in a session once.
                if ($existing->has($employee->id)) {
                    $existing[$employee->id]->update(['status' => 'assigned', 'assigned_date' => today()->toDateString(), 'completed_date' => null]);
                } else {
                    TrainingEnrollment::create(['company_id' => $companyId, 'training_session_id' => $session->id, 'employee_id' => $employee->id, 'status' => 'assigned', 'assigned_date' => today()->toDateString()]);
                }
            }

            return $fresh;
        });

        $skipped = $employees->count() - $fresh->count();
        $message = ($fresh->count() === 1 ? $fresh->first()->name.' assigned' : $fresh->count().' people assigned').' to "'.$title.'" ('.$this->dates($session).').';

        return redirect()->route('admin.training-enrollments.index')->with('status', $message.($skipped ? ' Skipped '.$skipped.' who '.($skipped === 1 ? 'was' : 'were').' already assigned.' : ''));
    }

    public function update(Request $request, TrainingEnrollment $enrollment): RedirectResponse
    {
        $this->ensureOwn($enrollment);
        $this->authorize('update', $enrollment);

        $data = $request->validate(['status' => ['required', Rule::in(array_keys(TrainingEnrollment::STATUSES))]]);
        $to = $data['status'];

        return DB::transaction(function () use ($enrollment, $to) {
            $row = TrainingEnrollment::query()->whereKey($enrollment->id)->lockForUpdate()->first();
            $from = $row->status;

            if ($from === $to) {
                return back()->with('status', 'Nothing to change; it was already '.strtolower(TrainingEnrollment::STATUSES[$to]).'.');
            }

            $session = TrainingSession::query()->whereKey($row->training_session_id)->lockForUpdate()->first();
            $name = $row->employee?->name ?? 'This employee';

            if ($session) {
                if (in_array($to, ['in_progress', 'completed'], true) && $session->start_date->gt(today())) {
                    return back()->withErrors(['status' => 'The session doesn\'t start until '.$session->start_date->format('M j, Y').', so '.$name.' can\'t be marked '.strtolower(TrainingEnrollment::STATUSES[$to]).' yet.']);
                }
                if ($to !== 'dropped' && $session->status === 'cancelled') {
                    return back()->withErrors(['status' => 'That session was cancelled. Restore it on the Training Calendar first, or mark '.$name.' as dropped.']);
                }
                if (in_array($to, self::OPEN, true) && $session->status === 'completed') {
                    return back()->withErrors(['status' => 'That session has finished, so '.$name.' can only be marked completed or dropped.']);
                }
                // Coming back from "dropped" takes a seat again.
                if ($from === 'dropped' && $session->capacity !== null) {
                    $taken = TrainingEnrollment::query()->where('training_session_id', $session->id)->where('status', '!=', 'dropped')->count();

                    if ($taken >= $session->capacity) {
                        return back()->withErrors(['status' => 'The session is full ('.$taken.' of '.$session->capacity.' seats), so '.$name.' can\'t be put back on it.']);
                    }
                }
            }

            $row->update([
                'status' => $to,
                // A training that finished in the past is dated to its last day, not to the day HR got round to closing it.
                'completed_date' => $to === 'completed' ? ($session && $session->end_date->lt(today()) ? $session->end_date : today())->toDateString() : null,
            ]);

            return back()->with('status', $name.' marked '.strtolower(TrainingEnrollment::STATUSES[$to]).'.');
        });
    }

    /** Close a session for everyone on it: all who were assigned or in progress are marked completed. */
    public function completeSession(TrainingSession $trainingSession): RedirectResponse
    {
        abort_unless($this->sessions()->whereKey($trainingSession->id)->exists(), 404);
        $this->authorize('update', new TrainingEnrollment);

        if ($trainingSession->status === 'cancelled') {
            return back()->withErrors(['status' => 'That session was cancelled, so nobody can be marked completed.']);
        }
        if ($trainingSession->start_date->gt(today())) {
            return back()->withErrors(['status' => 'The session doesn\'t start until '.$trainingSession->start_date->format('M j, Y').'.']);
        }

        $date = ($trainingSession->end_date->lt(today()) ? $trainingSession->end_date : today())->toDateString();
        $done = $this->scoped()->where('training_session_id', $trainingSession->id)->whereIn('status', self::OPEN)
            ->update(['status' => 'completed', 'completed_date' => $date]);

        return back()->with('status', $done ? $done.' '.($done === 1 ? 'person' : 'people').' marked completed. Mark anyone who didn\'t finish as dropped.' : 'Nobody on that session was still open.');
    }

    public function destroy(TrainingEnrollment $enrollment): RedirectResponse
    {
        $this->ensureOwn($enrollment);
        $this->authorize('delete', $enrollment);

        if ($enrollment->status === 'completed') {
            return back()->withErrors(['enrollment' => 'A completed training is part of '.($enrollment->employee?->name ?? 'the employee').'\'s record, so it can\'t be removed.']);
        }

        $enrollment->delete();

        return back()->with('status', 'Assignment removed.');
    }

    /** Sessions people can be put into, with the seats left and who is already on each. */
    protected function formData($people): array
    {
        $sessions = $this->sessions()->whereIn('status', self::BOOKABLE)->with(['course:id,title', 'trainer:id,name'])->orderBy('start_date')->orderBy('id')->get();
        // Seats are counted across the whole company; a branch login must not overfill a shared session.
        $onSession = TrainingEnrollment::query()->whereIn('training_session_id', $sessions->pluck('id'))->where('status', '!=', 'dropped')
            ->get(['training_session_id', 'employee_id'])->groupBy('training_session_id');
        $visible = $people->pluck('id')->flip();

        return [
            'people' => $people,
            'sessions' => $sessions->map(function (TrainingSession $s) use ($onSession, $visible) {
                $ids = ($onSession[$s->id] ?? collect())->pluck('employee_id');

                return [
                    'id' => $s->id,
                    'title' => $s->course?->title ?? 'Removed course',
                    'dates' => $this->dates($s),
                    'trainer' => $s->trainer?->name,
                    'capacity' => $s->capacity,
                    'taken' => $ids->count(),
                    'ended' => $s->end_date->lt(today()),
                    'enrolled' => $ids->filter(fn ($employeeId) => $visible->has($employeeId))->values()->all(),
                ];
            })->values(),
        ];
    }

    /** When the list is narrowed to one session: what it is and whether it can be closed for everyone. */
    protected function focus(?int $sessionId): ?array
    {
        $session = $sessionId ? $this->sessions()->with('course:id,title')->whereKey($sessionId)->first() : null;

        if (! $session) {
            return null;
        }

        $open = $this->scoped()->where('training_session_id', $session->id)->whereIn('status', self::OPEN)->count();

        return [
            'session' => $session,
            'title' => $session->course?->title ?? 'Removed course',
            'dates' => $this->dates($session),
            'open' => $open,
            'can_complete' => $open > 0 && $session->status !== 'cancelled' && $session->start_date->lte(today()),
        ];
    }

    /** Still "assigned" or "in progress" although the session is over, closed or cancelled. */
    protected function needsClosing(Builder $query): Builder
    {
        return $query->whereIn('training_enrollments.status', self::OPEN)
            ->whereHas('trainingSession', fn ($s) => $s->where(fn ($w) => $w->whereIn('status', ['completed', 'cancelled'])->orWhereDate('end_date', '<', today())));
    }

    /** "Oct 12, 2026" or "Oct 12 – 14, 2026" */
    protected function dates(TrainingSession $session): string
    {
        return $session->start_date->isSameDay($session->end_date)
            ? $session->start_date->format('M j, Y')
            : $session->start_date->format('M j').' – '.$session->end_date->format($session->start_date->isSameMonth($session->end_date) ? 'j, Y' : 'M j, Y');
    }

    /** 404 (not 403) for another company's or branch's assignment, so the URL doesn't confirm it exists. */
    protected function ensureOwn(TrainingEnrollment $enrollment): void
    {
        abort_unless($this->scoped()->whereKey($enrollment->id)->exists(), 404);
    }

    protected function scoped(): Builder
    {
        return TrainingEnrollment::query()
            ->where('training_enrollments.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    /** Sessions this login may use: its own company and, when tied to a branch, that branch's plus company-wide ones. */
    protected function sessions(): Builder
    {
        return TrainingSession::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')));
    }

    protected function employeeQuery(): Builder
    {
        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()));
    }
}
