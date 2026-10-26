<?php

namespace App\Http\Controllers\Admin\Ess;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Employee;
use App\Models\EmployeeKpi;
use App\Models\Goal;
use App\Models\Increment;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MyPerformanceController extends Controller
{
    use ResolvesCurrentEmployee;

    public function index(Request $request): View
    {
        $employee = $this->currentEmployee();

        $appraisals = Appraisal::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id)
            ->with(['appraisalCycle', 'managerReviewedBy:id,name'])->withCount('feedback360s')->withAvg('feedback360s', 'rating')->get()
            ->sortByDesc(fn (Appraisal $a) => $a->appraisalCycle?->period_end?->timestamp ?? $a->created_at?->timestamp ?? 0)->values()
            ->map(function (Appraisal $a) {
                $released = $a->status === 'completed';
                $cycleOpen = $a->appraisalCycle?->status !== 'closed';
                // Where it stands for the employee.
                $a->setAttribute('stage', match (true) {
                    $released => 'completed',
                    $a->self_review_rating === null && ! $cycleOpen => 'missed',
                    $a->self_review_rating === null => 'self_due',
                    $a->manager_review_rating === null => 'with_manager',
                    default => 'finalising',
                });
                // The manager's view is shared once the appraisal is signed off, not while it is still being written.
                $a->setAttribute('released', $released);
                // Your own review can be written or corrected until your manager has reviewed it.
                $a->setAttribute('can_review', ! $released && $cycleOpen && $a->manager_review_rating === null);

                return $a;
            });

        // Which cycle the goals and KPIs on screen belong to: the one asked for, else the active one, else the latest.
        $cycles = AppraisalCycle::query()->where('company_id', $employee->company_id)->orderByDesc('period_end')->get(['id', 'name', 'status', 'period_start', 'period_end']);
        $text = is_scalar($request->query('cycle')) ? (string) $request->query('cycle') : '';
        $cycle = (ctype_digit($text) ? $cycles->firstWhere('id', (int) $text) : null) ?? $cycles->firstWhere('status', 'active') ?? $cycles->first();
        $default = $cycles->firstWhere('status', 'active') ?? $cycles->first();

        $goals = Goal::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id)
            // Goals set outside any cycle always show.
            ->where(fn ($q) => $q->whereNull('appraisal_cycle_id')->when($cycle, fn ($w) => $w->orWhere('appraisal_cycle_id', $cycle->id)))
            ->orderByRaw("status = 'completed'")->orderByRaw('target_date is null')->orderBy('target_date')->orderBy('id')->get()
            ->map(function (Goal $g) use ($cycles) {
                $days = $g->target_date ? (int) today()->diffInDays($g->target_date, false) : null;
                $g->setAttribute('days_left', $days);
                $g->setAttribute('overdue', $g->status !== 'completed' && $days !== null && $days < 0);
                $g->setAttribute('locked', $g->appraisal_cycle_id !== null && $cycles->firstWhere('id', $g->appraisal_cycle_id)?->status === 'closed');

                return $g;
            });
        $kpis = EmployeeKpi::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id)
            ->when($cycle, fn ($q) => $q->where(fn ($w) => $w->whereNull('appraisal_cycle_id')->orWhere('appraisal_cycle_id', $cycle->id)))
            ->with('kpi:id,name,unit,description')->orderBy('id')->get();

        $latest = $appraisals->firstWhere('stage', 'completed');
        $weights = (float) $goals->sum('weight');
        $scored = $kpis->filter(fn (EmployeeKpi $k) => $k->achievementPercent() !== null);

        return view('admin.ess.performance', [
            'appraisals' => $appraisals,
            'increments' => Increment::withoutGlobalScopes()->where('employee_id', $employee->id)->whereIn('appraisal_id', $appraisals->where('released', true)->pluck('id'))->get()->keyBy('appraisal_id'),
            'cycles' => $cycles,
            'cycle' => $cycle,
            'isDefaultCycle' => $cycle?->id === $default?->id,
            'goals' => $goals,
            'kpis' => $kpis,
            'summary' => [
                'latest' => $latest,
                'self_due' => $appraisals->where('stage', 'self_due')->count(),
                'in_review' => $appraisals->whereIn('stage', ['with_manager', 'finalising'])->count(),
                'goal_count' => $goals->count(),
                'goal_done' => $goals->where('status', 'completed')->count(),
                'goal_overdue' => $goals->where('overdue', true)->count(),
                // Weighted by each goal's share when weights are set; a plain average otherwise.
                'goal_progress' => $goals->isEmpty() ? null : (int) round($weights > 0 ? $goals->sum(fn (Goal $g) => (float) $g->weight * (int) $g->progress_percent) / $weights : $goals->avg('progress_percent')),
                'kpi_count' => $kpis->count(),
                'kpi_scored' => $scored->count(),
                'kpi_achievement' => $scored->isNotEmpty() ? (int) round($scored->avg(fn (EmployeeKpi $k) => min(150, (float) $k->achievementPercent()))) : null,
                'kpi_on_target' => $scored->filter(fn (EmployeeKpi $k) => $k->achievementPercent() >= 100)->count(),
            ],
        ]);
    }

    /** Write your self review, or correct it while your manager hasn't reviewed yet. */
    public function submitSelfReview(Request $request, int $appraisal): RedirectResponse
    {
        $employee = $this->currentEmployee();
        // 404 (not 403) for someone else's appraisal, so the id doesn't confirm it exists.
        $record = $this->ownAppraisal($employee, $appraisal);

        $data = $request->validate([
            'self_review_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'self_review_comments' => ['nullable', 'string', 'max:2000'],
        ], [
            'self_review_rating.*' => 'Pick a rating from 1 to 5.',
            'self_review_comments.max' => 'Keep your comments under 2,000 characters.',
        ]);

        $first = DB::transaction(function () use ($record, $data) {
            $row = Appraisal::withoutGlobalScopes()->whereKey($record->id)->lockForUpdate()->first();

            if ($row->status === 'completed') {
                throw ValidationException::withMessages(['review' => 'This appraisal is completed, so your self review can no longer be changed.']);
            }
            if ($row->appraisalCycle()->value('status') === 'closed') {
                throw ValidationException::withMessages(['review' => 'This appraisal cycle is closed. Ask HR if your self review still needs to be recorded.']);
            }
            // Once the manager has responded to it, changing it would rewrite what they responded to.
            if ($row->manager_review_rating !== null) {
                throw ValidationException::withMessages(['review' => 'Your manager has already reviewed this appraisal, so your self review can no longer be changed.']);
            }

            $first = $row->self_review_rating === null;
            $row->forceFill([
                'self_review_rating' => (int) $data['self_review_rating'],
                'self_review_comments' => filled($data['self_review_comments'] ?? null) ? trim($data['self_review_comments']) : null,
                // Keep the date it was first handed in; a later correction doesn't make it look freshly submitted.
                'self_review_submitted_at' => $row->self_review_submitted_at ?? now(),
            ])->save();

            return $first;
        });

        if ($first) {
            Notifier::notifyUser(
                Employee::withoutGlobalScopes()->whereKey($employee->manager_id)->first()?->user,
                'Self review submitted',
                $employee->name.' submitted their self review'.($record->appraisalCycle ? ' for '.$record->appraisalCycle->name : '').'. It is ready for your review.',
                route('admin.manager-reviews.index'),
                'ti ti-star',
            );
        }

        return back()->with('status', $first ? 'Your self review was submitted. Your manager reviews it next.' : 'Your self review was updated.');
    }

    /** Move one of your own goals forward. */
    public function updateGoal(Request $request, int $goal): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $record = Goal::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id)->whereKey($goal)->firstOrFail();

        $data = $request->validate([
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ], ['progress_percent.*' => 'Progress is a whole number from 0 to 100.']);

        if ($record->appraisal_cycle_id && AppraisalCycle::query()->whereKey($record->appraisal_cycle_id)->value('status') === 'closed') {
            throw ValidationException::withMessages(['goal' => 'The appraisal cycle for "'.$record->title.'" is closed, so its progress can no longer be changed.']);
        }

        // Once the appraisal for that cycle is signed off, the goals it was judged on are part of the record (the same rule as Team Goals).
        if ($record->appraisal_cycle_id && Appraisal::withoutGlobalScopes()->where('employee_id', $employee->id)->where('appraisal_cycle_id', $record->appraisal_cycle_id)->where('status', 'completed')->exists()) {
            throw ValidationException::withMessages(['goal' => 'Your appraisal for this cycle is completed, so the progress of "'.$record->title.'" can no longer be changed.']);
        }

        $progress = (int) $data['progress_percent'];
        // Status and progress tell the same story: 100% is completed, anything above zero has started.
        $record->forceFill([
            'progress_percent' => $progress,
            'status' => $progress === 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'not_started'),
        ])->save();

        return back()->with('status', $progress === 100 ? '"'.$record->title.'" marked as completed.' : '"'.$record->title.'" is now at '.$progress.'%.');
    }

    protected function ownAppraisal(Employee $employee, int $id): Appraisal
    {
        return Appraisal::withoutGlobalScopes()->where('employee_id', $employee->id)->where('company_id', $employee->company_id)
            ->whereKey($id)->with('appraisalCycle:id,name,status')->firstOrFail();
    }
}
