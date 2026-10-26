<?php

namespace App\Http\Controllers\Admin\Ess;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\AssessmentResult;
use App\Models\Certification;
use App\Models\Employee;
use App\Models\TrainingEnrollment;
use App\Support\Notifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MyTrainingController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['todo', 'completed', 'closed'];

    /** Certificates running out within this many days are flagged. */
    public const SOON_DAYS = 60;

    public function index(Request $request): View
    {
        $employee = $this->currentEmployee();

        // Anything that isn't a plain value (e.g. ?tab[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = ['tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : ''];

        $all = $this->mine($employee)->with(['trainingSession.course', 'trainingSession.trainer:id,name'])->get()
            ->map(function (TrainingEnrollment $e) {
                $session = $e->trainingSession;
                $cancelled = $session?->status === 'cancelled';
                $open = in_array($e->status, ['assigned', 'in_progress'], true);
                $start = $session?->start_date;
                $end = $session?->end_date ?? $start;
                // Where it stands for the person, which is not always the stored status.
                $e->setAttribute('stage', match (true) {
                    $e->status === 'completed' => 'completed',
                    $e->status === 'dropped' => 'dropped',
                    $cancelled || ! $session => 'cancelled',
                    $e->status === 'in_progress' => 'in_progress',
                    $start && $start->gt(today()) => 'upcoming',
                    $end && $end->lt(today()) => 'overdue',
                    default => 'now',
                });
                $e->setAttribute('days_to_start', $start ? (int) today()->diffInDays($start, false) : null);
                // The session has to have begun, and still be on, before it can be started.
                $e->setAttribute('can_start', $e->status === 'assigned' && ! $cancelled && $session && $start && $start->lte(today()) && $end->gte(today()) && $session->status !== 'completed');
                // Stepping out is for before it begins; afterwards it is the trainer's or HR's call.
                $e->setAttribute('can_withdraw', $open && ! $cancelled && $session && $start && $start->gt(today()));

                return $e;
            })
            // What is next comes first; finished ones newest first.
            ->sortBy(fn (TrainingEnrollment $e) => [
                in_array($e->stage, ['in_progress', 'now', 'overdue', 'upcoming'], true) ? 0 : 1,
                in_array($e->stage, ['in_progress', 'now', 'overdue', 'upcoming'], true)
                    ? ($e->trainingSession?->start_date?->timestamp ?? PHP_INT_MAX)
                    : -1 * (($e->completed_date ?? $e->trainingSession?->start_date)?->timestamp ?? 0),
            ])->values();
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'todo' => $rows->whereIn('stage', ['in_progress', 'now', 'overdue', 'upcoming']),
            'completed' => $rows->where('stage', 'completed'),
            'closed' => $rows->whereIn('stage', ['dropped', 'cancelled']),
            default => $rows,
        };
        $todo = $tabbed($all, 'todo');
        $completed = $tabbed($all, 'completed');
        $hours = fn (Collection $rows) => (float) $rows->sum(fn (TrainingEnrollment $e) => (float) $e->trainingSession?->course?->duration_hours);

        $certifications = Certification::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id)
            ->orderByRaw('expiry_date is null')->orderBy('expiry_date')->get()->map(function (Certification $c) {
                $days = $c->expiry_date ? (int) today()->diffInDays($c->expiry_date, false) : null;
                // Judged by the date, so a certificate nobody has marked expired yet still shows as expired.
                $c->setAttribute('days_left', $days);
                $c->setAttribute('state', $c->status === 'expired' || ($days !== null && $days < 0) ? 'expired' : ($days !== null && $days <= self::SOON_DAYS ? 'soon' : 'valid'));

                return $c;
            });
        $results = AssessmentResult::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id)
            ->with('assessment.course:id,title')->orderByDesc('attempted_date')->orderByDesc('id')->get();
        $next = $todo->first(fn (TrainingEnrollment $e) => $e->stage === 'upcoming');

        return view('admin.ess.training', [
            'state' => $state,
            'enrollments' => $tabbed($all, $state['tab'])->values(),
            'counts' => ['all' => $all->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($all, $tab)->count()])->all(),
            'kpis' => [
                'todo' => $todo->count(),
                'running' => $todo->whereIn('stage', ['in_progress', 'now'])->count(),
                'overdue' => $todo->where('stage', 'overdue')->count(),
                'next' => $next,
                'completed' => $completed->count(),
                'completed_year' => $completed->filter(fn (TrainingEnrollment $e) => $e->completed_date?->year === now()->year)->count(),
                'hours' => $hours($completed),
                'certs' => $certifications->where('state', '!=', 'expired')->count(),
                'certs_soon' => $certifications->where('state', 'soon')->count(),
                'certs_expired' => $certifications->where('state', 'expired')->count(),
                'passed' => $results->where('status', 'pass')->count(),
                'attempts' => $results->count(),
            ],
            'certifications' => $certifications,
            'results' => $results,
        ]);
    }

    /** "I've started": moves an assigned training to in progress once its session has begun. */
    public function start(int $enrollment): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $record = $this->owned($employee, $enrollment);
        $title = $record->trainingSession?->course?->title ?? 'the training';

        DB::transaction(function () use ($record, $title) {
            $row = TrainingEnrollment::withoutGlobalScopes()->whereKey($record->id)->lockForUpdate()->first();
            $session = $row->trainingSession()->first();

            if ($row->status !== 'assigned') {
                throw ValidationException::withMessages(['training' => 'This training is already '.str_replace('_', ' ', $row->status).'.']);
            }
            if (! $session || $session->status === 'cancelled') {
                throw ValidationException::withMessages(['training' => 'The session for '.$title.' was cancelled.']);
            }
            if ($session->status === 'completed') {
                throw ValidationException::withMessages(['training' => 'The session for '.$title.' is already closed. Ask HR if you attended it.']);
            }
            if ($session->start_date->gt(today())) {
                throw ValidationException::withMessages(['training' => $title.' doesn\'t start until '.$session->start_date->format('M j, Y').'.']);
            }
            // A session that is over is closed by the trainer or HR, who know whether the person attended.
            if (($session->end_date ?? $session->start_date)->lt(today())) {
                throw ValidationException::withMessages(['training' => $title.' ended on '.($session->end_date ?? $session->start_date)->format('M j, Y').'. Ask your trainer or HR to record your attendance.']);
            }
            $row->forceFill(['status' => 'in_progress'])->save();
        });

        return back()->with('status', 'Marked as started: '.$title.'. Your trainer or HR marks it completed.');
    }

    /** Step out of a training that hasn't begun yet. Frees the seat and tells the people who run training. */
    public function withdraw(int $enrollment): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $record = $this->owned($employee, $enrollment);
        $session = $record->trainingSession;
        $title = $session?->course?->title ?? 'the training';

        DB::transaction(function () use ($record, $title) {
            $row = TrainingEnrollment::withoutGlobalScopes()->whereKey($record->id)->lockForUpdate()->first();
            $session = $row->trainingSession()->first();

            if (! in_array($row->status, ['assigned', 'in_progress'], true)) {
                throw ValidationException::withMessages(['training' => 'This training is already '.str_replace('_', ' ', $row->status).'.']);
            }
            if (! $session || $session->start_date->lte(today())) {
                throw ValidationException::withMessages(['training' => $title.' has already started. Ask HR if you can\'t continue.']);
            }
            $row->forceFill(['status' => 'dropped'])->save();
        });

        Notifier::notifyUsersWithPermission(
            'update_training_enrollment',
            'Training seat given up',
            $employee->name.' withdrew from '.$title.($session?->start_date ? ' starting '.$session->start_date->format('M j, Y') : '').'.',
            route('admin.training-enrollments.index'),
            'ti ti-school',
            $employee->branch_id,
            employeeId: $employee->id,
        );

        return back()->with('status', 'You withdrew from '.$title.'. HR has been told and your seat is free for someone else.');
    }

    /** This employee's own enrollments, whatever their role may browse elsewhere. */
    protected function mine(Employee $employee): Builder
    {
        return TrainingEnrollment::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id);
    }

    /** 404 (not 403) for someone else's enrollment, so the id doesn't confirm it exists. */
    protected function owned(Employee $employee, int $id): TrainingEnrollment
    {
        return $this->mine($employee)->whereKey($id)->with('trainingSession.course:id,title')->firstOrFail();
    }
}
