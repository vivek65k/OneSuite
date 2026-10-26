<?php

namespace App\Http\Controllers\Admin\Mss;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Employee;
use App\Models\Goal;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeamGoalController extends Controller
{
    use ResolvesCurrentEmployee;

    protected const TABS = ['not_started', 'in_progress', 'completed', 'overdue'];

    /** People who have left can't be given new goals. */
    protected const INACTIVE = ['terminated', 'inactive'];

    /** A target date further out than this is almost certainly a typo. */
    protected const YEARS_AHEAD = 5;

    public function index(Request $request): View
    {
        $manager = $this->currentEmployee();
        [$people, $cycles] = $this->team($manager);

        // Anything that isn't a plain value (e.g. ?cycle[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $default = $cycles->firstWhere('status', 'active') ?? $cycles->first();
        // "none" shows the goals set outside any appraisal cycle.
        $cycleKey = $text('cycle') === 'none' ? 'none' : (ctype_digit($text('cycle')) && $cycles->firstWhere('id', (int) $text('cycle')) ? (int) $text('cycle') : ($default?->id ?? 'none'));
        $cycle = is_int($cycleKey) ? $cycles->firstWhere('id', $cycleKey) : null;
        $focus = ctype_digit($text('employee_id')) ? $people->get((int) $text('employee_id')) : null;
        $state = [
            'cycle' => $cycleKey === ($default?->id ?? 'none') ? null : $cycleKey,
            'employee_id' => $focus?->id,
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
        ];

        $every = Goal::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('employee_id', $people->keys())->get();
        $locks = $this->locks($manager, $every);
        $all = $every->filter(fn (Goal $g) => $cycle ? (int) $g->appraisal_cycle_id === $cycle->id : $g->appraisal_cycle_id === null)
            ->map(function (Goal $g) use ($people, $locks) {
                $days = $g->target_date ? (int) today()->diffInDays($g->target_date, false) : null;
                $g->setRelation('employee', $people[$g->employee_id]);
                $g->setAttribute('days_left', $days);
                $g->setAttribute('overdue', $g->status !== 'completed' && $days !== null && $days < 0);
                $g->setAttribute('lock', $locks[$g->id] ?? null);

                return $g;
            })
            // Overdue first, then the nearest deadline; finished goals last.
            ->sortBy(fn (Goal $g) => [$g->status === 'completed' ? 2 : ($g->overdue ? 0 : 1), $g->target_date?->timestamp ?? PHP_INT_MAX, $g->employee->name])->values();

        $filtered = $all->when($focus, fn (Collection $c) => $c->where('employee_id', $focus->id))->values();
        $tabbed = fn (Collection $rows, string $tab) => match ($tab) {
            'overdue' => $rows->where('overdue', true),
            '' => $rows,
            default => $rows->where('status', $tab),
        };
        $active = $people->reject(fn (Employee $e) => in_array($e->status, self::INACTIVE, true));
        // Per person in this cycle: how much of the 100% is given out, and how far along they are.
        $perPerson = $active->map(function (Employee $e) use ($all) {
            $mine = $all->where('employee_id', $e->id);
            $weights = (float) $mine->sum('weight');

            return [
                'employee' => $e,
                'goals' => $mine->count(),
                'done' => $mine->where('status', 'completed')->count(),
                'overdue' => $mine->where('overdue', true)->count(),
                'weight' => round($weights, 2),
                'progress' => $mine->isEmpty() ? null : (int) round($weights > 0 ? $mine->sum(fn (Goal $g) => (float) $g->weight * (int) $g->progress_percent) / $weights : $mine->avg('progress_percent')),
            ];
        })->values();

        return view('admin.mss.goals', [
            'state' => $state,
            'cycle' => $cycle,
            'cycleKey' => $cycleKey,
            'cycles' => $cycles,
            'hasUncycled' => $every->contains(fn (Goal $g) => $g->appraisal_cycle_id === null),
            'hasTeam' => $people->isNotEmpty(),
            'people' => $active->values(),
            'focus' => $focus,
            'goals' => $tabbed($filtered, $state['tab'])->values(),
            'counts' => ['all' => $filtered->count()] + collect(self::TABS)->mapWithKeys(fn ($tab) => [$tab => $tabbed($filtered, $tab)->count()])->all(),
            'perPerson' => $perPerson,
            'canAdd' => $active->isNotEmpty() && (! $cycle || $cycle->status !== 'closed'),
            'openCycles' => $cycles->where('status', '!=', 'closed')->values(),
            // What the form needs to show the weight still free for each person in each cycle.
            'weightsUsed' => $every->groupBy(fn (Goal $g) => $g->employee_id.':'.($g->appraisal_cycle_id ?? 'none'))->map(fn ($rows) => round((float) $rows->sum('weight'), 2)),
            'maxDate' => today()->addYears(self::YEARS_AHEAD)->toDateString(),
            'kpis' => [
                'goals' => $all->count(),
                'done' => $all->where('status', 'completed')->count(),
                'progress' => $all->isEmpty() ? null : (int) round($all->avg('progress_percent')),
                'overdue' => $all->where('overdue', true)->count(),
                'due_soon' => $all->filter(fn (Goal $g) => $g->status !== 'completed' && $g->days_left !== null && $g->days_left >= 0 && $g->days_left <= 14)->count(),
                'without' => $perPerson->where('goals', 0)->pluck('employee'),
                'unweighted' => $perPerson->filter(fn ($p) => $p['goals'] > 0 && abs($p['weight'] - 100) > 0.01 && $p['weight'] > 0)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $manager = $this->currentEmployee();
        [$people] = $this->team($manager);

        $personId = $request->input('employee_id');
        $person = is_scalar($personId) && ctype_digit((string) $personId) ? $people->get((int) $personId) : null;
        if (! $person) {
            throw ValidationException::withMessages(['employee_id' => 'Choose someone from your team.']);
        }
        if (in_array($person->status, self::INACTIVE, true)) {
            throw ValidationException::withMessages(['employee_id' => $person->name.' is no longer with the company, so they can\'t be given a goal.']);
        }

        $goal = DB::transaction(function () use ($request, $manager, $person) {
            // One at a time per person, so two quick saves can't both pass the weight check.
            DB::table('employees')->where('id', $person->id)->lockForUpdate()->first();
            $values = $this->validated($request, $manager, $person);

            $goal = (new Goal)->forceFill($values + ['company_id' => $manager->company_id, 'employee_id' => $person->id, 'status' => 'not_started', 'progress_percent' => 0]);
            $goal->save();

            return $goal;
        });

        Notifier::notifyUser(
            Employee::withoutGlobalScopes()->with('user')->find($person->id)?->user,
            'New goal set for you',
            $manager->name.' set you a goal: "'.$goal->title.'"'.($goal->target_date ? ', due '.Carbon::parse($goal->target_date)->format('M j, Y') : '').'.',
            route('admin.my-performance.index'),
            'ti ti-flag',
        );

        return back()->with('status', 'Goal set for '.$person->name.': "'.$goal->title.'".');
    }

    /** Two kinds of save land here: a quick progress update, and a full edit of the goal's details. */
    public function update(Request $request, int $goal): RedirectResponse
    {
        $manager = $this->currentEmployee();
        [$people] = $this->team($manager);
        $record = $this->owned($manager, $people, $goal);
        $person = $people[$record->employee_id];

        $message = DB::transaction(function () use ($request, $manager, $record, $person) {
            DB::table('employees')->where('id', $person->id)->lockForUpdate()->first();
            $row = Goal::withoutGlobalScopes()->whereKey($record->id)->lockForUpdate()->first();
            $this->guardLock($manager, $row);

            if ($request->has('title')) {
                $row->forceFill($this->validated($request, $manager, $person, $row))->save();

                return 'Goal updated: "'.$row->title.'".';
            }

            $data = $request->validate(['progress_percent' => ['required', 'integer', 'min:0', 'max:100']], ['progress_percent.*' => 'Progress is a whole number from 0 to 100.']);
            $progress = (int) $data['progress_percent'];
            // Status and progress tell the same story: 100% is completed, anything above zero has started.
            $row->forceFill(['progress_percent' => $progress, 'status' => $progress === 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'not_started')])->save();

            return $progress === 100 ? '"'.$row->title.'" marked as completed for '.$person->name.'.' : '"'.$row->title.'" is now at '.$progress.'% for '.$person->name.'.';
        });

        return back()->with('status', $message);
    }

    public function destroy(int $goal): RedirectResponse
    {
        $manager = $this->currentEmployee();
        [$people] = $this->team($manager);
        $record = $this->owned($manager, $people, $goal);
        $this->guardLock($manager, $record);

        $record->delete();

        return back()->with('status', '"'.$record->title.'" removed from '.$people[$record->employee_id]->name.'\'s goals.');
    }

    /**
     * The people below the signed-in manager, and the company's appraisal cycles.
     * The team is read by id from the reporting line: it doesn't depend on what the manager's role may browse.
     *
     * @return array{0: Collection<int, Employee>, 1: Collection<int, AppraisalCycle>}
     */
    protected function team(Employee $manager): array
    {
        $people = Employee::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('id', $this->teamEmployeeIds())
            ->with('department:id,name')->orderBy('name')->get()
            ->each(fn (Employee $e) => $e->setAttribute('is_direct', (int) $e->manager_id === (int) $manager->id))->keyBy('id');

        return [$people, AppraisalCycle::query()->where('company_id', $manager->company_id)->orderByDesc('period_end')->get()];
    }

    /** 404 (not 403) for a goal outside the team, so the id doesn't confirm it exists. */
    protected function owned(Employee $manager, Collection $people, int $id): Goal
    {
        return Goal::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('employee_id', $people->keys())->whereKey($id)->firstOrFail();
    }

    /**
     * Why each goal can no longer be changed, if it can't: its cycle is closed, or the
     * person's appraisal for that cycle is completed and the goal was part of what it was judged on.
     *
     * @return array<int, string> goal id => reason
     */
    protected function locks(Employee $manager, Collection $goals): array
    {
        $cycleIds = $goals->pluck('appraisal_cycle_id')->filter()->unique();
        if ($cycleIds->isEmpty()) {
            return [];
        }
        $closed = AppraisalCycle::query()->where('company_id', $manager->company_id)->whereIn('id', $cycleIds)->where('status', 'closed')->pluck('id')->flip();
        $signedOff = Appraisal::withoutGlobalScopes()->where('company_id', $manager->company_id)->whereIn('appraisal_cycle_id', $cycleIds)
            ->whereIn('employee_id', $goals->pluck('employee_id')->unique())->where('status', 'completed')->get(['employee_id', 'appraisal_cycle_id'])
            ->mapWithKeys(fn (Appraisal $a) => [$a->employee_id.':'.$a->appraisal_cycle_id => true]);

        return $goals->filter(fn (Goal $g) => $g->appraisal_cycle_id)->mapWithKeys(fn (Goal $g) => [$g->id => match (true) {
            $closed->has($g->appraisal_cycle_id) => 'The appraisal cycle is closed',
            $signedOff->has($g->employee_id.':'.$g->appraisal_cycle_id) => 'Their appraisal for this cycle is completed',
            default => null,
        }])->filter()->all();
    }

    protected function guardLock(Employee $manager, Goal $goal): void
    {
        if ($reason = $this->locks($manager, collect([$goal]))[$goal->id] ?? null) {
            throw ValidationException::withMessages(['goal' => $reason.', so "'.$goal->title.'" can no longer be changed.']);
        }
    }

    protected function validated(Request $request, Employee $manager, Employee $person, ?Goal $existing = null): array
    {
        $data = $request->validate([
            'appraisal_cycle_id' => ['nullable'],
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'target_date' => ['nullable', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:'.today()->addYears(self::YEARS_AHEAD)->toDateString()],
        ], [
            'title.*' => 'Give the goal a short title (3 to 255 characters).',
            'weight.*' => 'The weight is a percentage from 0 to 100.',
            'target_date.before_or_equal' => 'The target date can be at most five years ahead.',
        ]);

        $cycleId = null;
        if (filled($data['appraisal_cycle_id'] ?? null)) {
            $cycle = is_scalar($data['appraisal_cycle_id']) && ctype_digit((string) $data['appraisal_cycle_id'])
                ? AppraisalCycle::query()->where('company_id', $manager->company_id)->whereKey((int) $data['appraisal_cycle_id'])->first() : null;
            if (! $cycle) {
                throw ValidationException::withMessages(['appraisal_cycle_id' => 'Choose an appraisal cycle from the list.']);
            }
            // A goal can stay in the cycle it is already in, but nothing new goes into a closed one.
            if ($cycle->status === 'closed' && (int) $existing?->appraisal_cycle_id !== $cycle->id) {
                throw ValidationException::withMessages(['appraisal_cycle_id' => '"'.$cycle->name.'" is closed, so goals can\'t be added to it.']);
            }
            $cycleId = $cycle->id;
        }
        $clean = fn (string $text) => trim(preg_replace('/[ \t]+/', ' ', strip_tags($text)));
        $title = $clean($data['title']);
        if (mb_strlen($title) < 3) {
            throw ValidationException::withMessages(['title' => 'Give the goal a short title (3 to 255 characters).']);
        }
        $weight = filled($data['weight'] ?? null) ? round((float) $data['weight'], 2) : null;
        $siblings = Goal::withoutGlobalScopes()->where('employee_id', $person->id)
            ->when($cycleId, fn ($q) => $q->where('appraisal_cycle_id', $cycleId), fn ($q) => $q->whereNull('appraisal_cycle_id'))
            ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->get(['id', 'title', 'weight']);

        // The same goal twice only muddles the weights and the progress.
        if ($siblings->contains(fn (Goal $g) => mb_strtolower(trim($g->title)) === mb_strtolower($title))) {
            throw ValidationException::withMessages(['title' => $person->name.' already has a goal called "'.$title.'"'.($cycleId ? ' in this cycle.' : '.')]);
        }
        // One person's goal weights in one cycle can't add up to more than 100%.
        $used = (float) $siblings->sum('weight');
        if ($weight && $used + $weight > 100.001) {
            $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
            throw ValidationException::withMessages(['weight' => $person->name.'\'s other goals '.($cycleId ? 'in this cycle' : 'outside a cycle').' already carry '.$fmt($used).'% of the weight, so at most '.$fmt(max(0, 100 - $used)).'% is left.']);
        }

        return [
            'appraisal_cycle_id' => $cycleId,
            'title' => $title,
            'description' => filled($data['description'] ?? null) ? trim(strip_tags($data['description'])) : null,
            'weight' => $weight,
            'target_date' => filled($data['target_date'] ?? null) ? Carbon::parse($data['target_date'])->toDateString() : null,
        ];
    }
}
