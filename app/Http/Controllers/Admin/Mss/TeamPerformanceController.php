<?php

namespace App\Http\Controllers\Admin\Mss;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Employee;
use App\Models\EmployeeKpi;
use App\Models\Goal;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeamPerformanceController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['todo', 'waiting', 'reviewed', 'completed'];

    public function index(Request $request): View
    {
        $manager = $this->currentEmployee();
        $teamIds = $this->teamEmployeeIds();

        // Anything that isn't a plain value (e.g. ?cycle[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';

        // The team is the manager's own reporting line, read by id: it doesn't depend on what their role may browse.
        $people = Employee::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('id', $teamIds)->with('department:id,name')->orderBy('name')->get()
            ->each(fn (Employee $e) => $e->setAttribute('is_direct', (int) $e->manager_id === (int) $manager->id))->keyBy('id');
        $mine = fn (string $model) => $model::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('employee_id', $people->keys());

        // Only cycles the team actually has appraisals in; the active one by default, else the latest.
        $cycles = AppraisalCycle::query()->where('company_id', $manager->company_id)->whereIn('id', $mine(Appraisal::class)->select('appraisal_cycle_id'))->orderByDesc('period_end')->get();
        $default = $cycles->firstWhere('status', 'active') ?? $cycles->first();
        $cycle = (ctype_digit($text('cycle')) ? $cycles->firstWhere('id', (int) $text('cycle')) : null) ?? $default;
        $focus = ctype_digit($text('employee_id')) ? $people->get((int) $text('employee_id')) : null;
        $state = [
            'cycle' => $cycle && $cycle->id !== $default?->id ? $cycle->id : null,
            'employee_id' => $focus?->id,
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
        ];

        // Goals and KPIs of the same cycle: what the rating should be judged against.
        $goals = $cycle ? $mine(Goal::class)->where('appraisal_cycle_id', $cycle->id)->get()->groupBy('employee_id') : collect();
        $kpis = $cycle ? $mine(EmployeeKpi::class)->where('appraisal_cycle_id', $cycle->id)->get()->groupBy('employee_id') : collect();
        $evidence = function (int $employeeId) use ($goals, $kpis) {
            $g = $goals->get($employeeId, collect());
            $weights = (float) $g->sum('weight');
            $scored = $kpis->get($employeeId, collect())->filter(fn (EmployeeKpi $k) => $k->achievementPercent() !== null);

            return [
                'goals' => $g->count(),
                'goals_done' => $g->where('status', 'completed')->count(),
                'goal_progress' => $g->isEmpty() ? null : (int) round($weights > 0 ? $g->sum(fn (Goal $x) => (float) $x->weight * (int) $x->progress_percent) / $weights : $g->avg('progress_percent')),
                'kpis' => $kpis->get($employeeId, collect())->count(),
                'kpi_achievement' => $scored->isNotEmpty() ? (int) round($scored->avg(fn (EmployeeKpi $k) => min(150, (float) $k->achievementPercent()))) : null,
            ];
        };

        $all = $cycle
            ? $mine(Appraisal::class)->where('appraisal_cycle_id', $cycle->id)->with('managerReviewedBy:id,name')->withCount('feedback360s')->withAvg('feedback360s', 'rating')->get()
                ->map(function (Appraisal $a) use ($people, $cycle, $evidence) {
                    $a->setRelation('employee', $people[$a->employee_id]);
                    $a->setAttribute('stage', match (true) {
                        $a->status === 'completed' => 'completed',
                        $a->manager_review_rating !== null => 'reviewed',
                        $a->self_review_rating !== null => 'todo',
                        default => 'waiting',
                    });
                    // Once signed off, or once the cycle is closed, the review is part of the record.
                    $a->setAttribute('can_review', $a->status !== 'completed' && $cycle->status !== 'closed');
                    $a->setAttribute('evidence', $evidence($a->employee_id));

                    return $a;
                })
                // What needs the manager first, then by name.
                ->sortBy(fn (Appraisal $a) => [array_search($a->stage, ['todo', 'waiting', 'reviewed', 'completed'], true), $a->employee->name])->values()
            : collect();
        $filtered = $all->when($focus, fn (Collection $c) => $c->where('employee_id', $focus->id))->values();
        $tabbed = fn (Collection $rows, string $tab) => $tab === '' ? $rows : $rows->where('stage', $tab);
        $rated = $all->whereNotNull('manager_review_rating');
        $both = $rated->whereNotNull('self_review_rating');
        $completed = $all->where('stage', 'completed')->whereNotNull('overall_rating');

        return view('admin.mss.performance', [
            'state' => $state,
            'cycle' => $cycle,
            'cycles' => $cycles,
            'hasTeam' => $people->isNotEmpty(),
            'people' => $people->only($all->pluck('employee_id')->all())->values(),
            'focus' => $focus,
            'appraisals' => $tabbed($filtered, $state['tab'])->values(),
            'counts' => ['all' => $filtered->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($filtered, $tab)->count()])->all(),
            'summary' => [
                'total' => $all->count(),
                'todo' => $all->where('stage', 'todo')->count(),
                'waiting' => $all->where('stage', 'waiting')->count(),
                'reviewed' => $rated->count(),
                'average' => $rated->isNotEmpty() ? round((float) $rated->avg('manager_review_rating'), 1) : null,
                // How far apart people and their manager see things: positive means they rate themselves higher.
                'gap' => $both->isNotEmpty() ? round((float) $both->avg(fn (Appraisal $a) => $a->self_review_rating - $a->manager_review_rating), 1) : null,
                'completed' => $all->where('stage', 'completed')->count(),
                'overall' => $completed->isNotEmpty() ? round((float) $completed->avg('overall_rating'), 1) : null,
                'without' => $people->reject(fn (Employee $e) => in_array($e->status, ['terminated', 'inactive'], true))->count() - $all->pluck('employee_id')->unique()->count(),
            ],
        ]);
    }

    /** Write the manager's review, or correct it until the appraisal is signed off. */
    public function submitManagerReview(Request $request, int $appraisal): RedirectResponse
    {
        $manager = $this->currentEmployee();
        // 404 (not 403) for an appraisal outside the team, so the id doesn't confirm it exists.
        $record = Appraisal::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('employee_id', $this->teamEmployeeIds())->whereKey($appraisal)->firstOrFail();
        $employee = Employee::withoutGlobalScopes()->with('user')->find($record->employee_id);
        $name = $employee?->name ?? 'this employee';

        $data = $request->validate([
            'manager_review_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'manager_review_comments' => ['nullable', 'string', 'max:2000'],
        ], [
            'manager_review_rating.*' => 'Pick a rating from 1 to 5.',
            'manager_review_comments.max' => 'Keep your comments under 2,000 characters.',
        ]);
        $comments = filled($data['manager_review_comments'] ?? null) ? trim($data['manager_review_comments']) : null;

        // A low or top rating with nothing behind it is no use to the person or to HR.
        if ($comments === null && in_array((int) $data['manager_review_rating'], [1, 2, 5], true)) {
            throw ValidationException::withMessages(['manager_review_comments' => 'A rating of '.$data['manager_review_rating'].' needs a comment saying why.']);
        }

        $first = DB::transaction(function () use ($record, $data, $comments, $name) {
            $row = Appraisal::withoutGlobalScopes()->whereKey($record->id)->lockForUpdate()->first();

            // Once the appraisal is signed off its rating is final; changing the review would leave the two out of step.
            if ($row->status === 'completed') {
                throw ValidationException::withMessages(['review' => $name.'\'s appraisal is already completed, so the review can\'t be changed. Ask HR to reopen it first.']);
            }
            if ($row->appraisalCycle()->value('status') === 'closed') {
                throw ValidationException::withMessages(['review' => 'This appraisal cycle is closed. Ask HR if '.$name.'\'s review still needs to be recorded.']);
            }

            $first = $row->manager_review_rating === null;
            $row->forceFill([
                'manager_review_rating' => (int) $data['manager_review_rating'],
                'manager_review_comments' => $comments,
                'manager_reviewed_by_user_id' => Auth::id(),
                // Keep the date it was first written; a later correction doesn't make it look freshly submitted.
                'manager_review_submitted_at' => $row->manager_review_submitted_at ?? now(),
            ])->save();

            return $first;
        });

        if ($first) {
            // The rating itself stays private until HR completes the appraisal; the employee only learns it has been done.
            Notifier::notifyUser($employee?->user, 'Your manager has reviewed your appraisal', 'Your review is with HR now. You will see the result once the appraisal is completed.', route('admin.my-performance.index'), 'ti ti-star');
        }

        return back()->with('status', 'Review '.($first ? 'submitted' : 'updated').' for '.$name.': '.$data['manager_review_rating'].' of 5.'.($first ? ' HR completes the appraisal next.' : ''));
    }
}
