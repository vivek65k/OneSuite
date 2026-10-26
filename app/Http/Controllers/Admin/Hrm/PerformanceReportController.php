<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeKpi;
use App\Models\Feedback360;
use App\Models\Goal;
use App\Models\Increment;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PerformanceReportController extends Controller
{
    use ExportsCsv;

    protected const SORTS = ['name', 'rating', 'goals', 'kpi'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Appraisal::class);

        $cycles = AppraisalCycle::query()->where('company_id', Company::current()->id)->orderByDesc('period_start')->get();
        $cycle = $this->cycle($request, $cycles);
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $departmentId = ctype_digit($text('department_id')) ? (int) $text('department_id') : null;
        $sort = in_array($text('sort'), self::SORTS, true) ? $text('sort') : 'name';

        $all = $cycle ? $this->rows($cycle) : collect();
        $rows = $this->sorted($departmentId ? $all->where('department_id', $departmentId)->values() : $all, $sort);
        $rated = $rows->whereNotNull('rating');
        $ranked = $rated->sortByDesc('rating')->values();

        return view('admin.hrm.performance.reports', [
            'cycle' => $cycle,
            'cycles' => $cycles,
            'departmentId' => $departmentId,
            'sort' => $sort,
            'rows' => $rows,
            'departments' => $all->filter(fn ($r) => $r['department_id'])->pluck('department', 'department_id')->sort(),
            'kpis' => [
                'total' => $rows->count(),
                'completed' => $rows->where('completed', true)->count(),
                'rating' => $rated->isNotEmpty() ? round((float) $rated->avg('rating'), 1) : null,
                'rated' => $rated->count(),
                'goal_progress' => $rows->whereNotNull('goal_progress')->isNotEmpty() ? round((float) $rows->whereNotNull('goal_progress')->avg('goal_progress')) : null,
                'goals' => $rows->sum('goals_total'),
                'goals_done' => $rows->sum('goals_completed'),
                'kpi' => $rows->whereNotNull('kpi_achievement')->isNotEmpty() ? round((float) $rows->whereNotNull('kpi_achievement')->avg('kpi_achievement'), 1) : null,
                'kpis' => $rows->sum('kpis_total'),
            ],
            // How many people landed on each rating from 1 to 5 (rounded), for the spread chart.
            'distribution' => collect(range(5, 1))->mapWithKeys(fn (int $star) => [$star => $rated->filter(fn ($r) => (int) round($r['rating']) === $star)->count()]),
            // "Top rated" only means something for people rated 3 or better.
            'top' => $ranked->filter(fn ($r) => $r['rating'] >= 3)->take(3)->values(),
            // Lowest rated or furthest behind on goals: the people to talk to first.
            'attention' => $rows->filter(fn ($r) => ($r['rating'] !== null && $r['rating'] <= 2) || ($r['goal_progress'] !== null && $r['goal_progress'] < 40) || ($r['kpi_achievement'] !== null && $r['kpi_achievement'] < 60))
                ->sortBy(fn ($r) => $r['rating'] ?? 9)->take(4)->values(),
            'byDepartment' => $all->groupBy(fn ($r) => $r['department'] ?? 'No department')->map(function (Collection $group, string $name) {
                $withRating = $group->whereNotNull('rating');

                return [
                    'name' => $name,
                    'id' => $group->first()['department_id'],
                    'people' => $group->count(),
                    'completed' => $group->where('completed', true)->count(),
                    'rating' => $withRating->isNotEmpty() ? round((float) $withRating->avg('rating'), 1) : null,
                    'goal_progress' => $group->whereNotNull('goal_progress')->isNotEmpty() ? round((float) $group->whereNotNull('goal_progress')->avg('goal_progress')) : null,
                    'kpi' => $group->whereNotNull('kpi_achievement')->isNotEmpty() ? round((float) $group->whereNotNull('kpi_achievement')->avg('kpi_achievement'), 1) : null,
                ];
            })->sortByDesc(fn ($d) => $d['rating'] ?? -1)->values(),
            // People with goals or KPIs in this cycle but no appraisal: they are missing from the report.
            'notStarted' => $cycle ? $this->withoutAppraisal($cycle, $all->pluck('employee_id')) : collect(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Appraisal::class);

        $cycles = AppraisalCycle::query()->where('company_id', Company::current()->id)->orderByDesc('period_start')->get();
        $cycle = $this->cycle($request, $cycles);
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $departmentId = ctype_digit($text('department_id')) ? (int) $text('department_id') : null;
        $rows = $cycle ? $this->rows($cycle) : collect();
        $rows = $this->sorted($departmentId ? $rows->where('department_id', $departmentId)->values() : $rows, in_array($text('sort'), self::SORTS, true) ? $text('sort') : 'name');
        // A cell starting with = + - @ would run as a formula when the file is opened in a spreadsheet.
        $safe = fn (?string $value) => preg_match('/^[=+\-@\t\r]/', (string) $value) ? "'".$value : (string) $value;
        $num = fn ($n, int $decimals = 1) => $n === null ? '' : number_format((float) $n, $decimals, '.', '');

        return $this->streamCsv(
            'performance-report'.($cycle ? '-'.Str::slug($cycle->name) : '').'.csv',
            ['Employee Code', 'Employee', 'Department', 'Self Rating', 'Manager Rating', 'Overall Rating', '360 Feedback', 'Status', 'Goals Completed', 'Goals Total', 'Avg Goal Progress %', 'KPIs', 'Avg KPI Achievement %', 'Increment Given'],
            (function () use ($rows, $safe, $num) {
                foreach ($rows as $row) {
                    yield [
                        $safe($row['code']), $safe($row['name']), $safe($row['department']),
                        $row['self'] ?? '', $row['manager'] ?? '', $num($row['overall'], 2), $num($row['feedback']),
                        $row['completed'] ? 'Completed' : 'In progress', $row['goals_completed'], $row['goals_total'], $num($row['goal_progress']),
                        $row['kpis_total'], $num($row['kpi_achievement']), $row['increment'] ? 'Yes' : 'No',
                    ];
                }
            })()
        );
    }

    /** The cycle being reported: the one asked for when it belongs to this company, else the most recent. */
    protected function cycle(Request $request, Collection $cycles): ?AppraisalCycle
    {
        foreach (['cycle_id', 'appraisal_cycle_id'] as $key) {
            $value = $request->query($key);

            if (is_scalar($value) && ctype_digit((string) $value) && ($found = $cycles->firstWhere('id', (int) $value))) {
                return $found;
            }
        }

        // Prefer one that has started; a cycle created for next year shouldn't be the default.
        return $cycles->first(fn (AppraisalCycle $c) => $c->period_start && $c->period_start->lte(today())) ?? $cycles->first();
    }

    /** One line per appraisal in the cycle that this login may see, with everything recorded against it. */
    protected function rows(AppraisalCycle $cycle): Collection
    {
        $appraisals = $this->appraisals($cycle)->with('employee.department:id,name')->get()->filter(fn (Appraisal $a) => $a->employee)->values();
        $employeeIds = $appraisals->pluck('employee_id');

        $goals = Goal::query()->where('appraisal_cycle_id', $cycle->id)->whereIn('employee_id', $employeeIds)->get(['employee_id', 'status', 'progress_percent'])->groupBy('employee_id');
        $kpis = EmployeeKpi::query()->where('appraisal_cycle_id', $cycle->id)->whereIn('employee_id', $employeeIds)->get()->groupBy('employee_id');
        $feedback = Feedback360::query()->whereIn('appraisal_id', $appraisals->pluck('id'))->where('relationship', '!=', 'self')
            ->selectRaw('appraisal_id, AVG(rating) as rating, COUNT(*) as entries')->groupBy('appraisal_id')->get()->keyBy('appraisal_id');
        $increments = Increment::query()->whereIn('appraisal_id', $appraisals->pluck('id'))->pluck('increment_percent', 'appraisal_id');

        return $appraisals->map(function (Appraisal $appraisal) use ($goals, $kpis, $feedback, $increments) {
            $own = $goals->get($appraisal->employee_id, collect());
            $achievements = $kpis->get($appraisal->employee_id, collect())->map(fn (EmployeeKpi $k) => $k->achievementPercent())->filter(fn ($v) => $v !== null);
            $fb = $feedback->get($appraisal->id);

            return [
                'appraisal_id' => $appraisal->id,
                'employee_id' => $appraisal->employee_id,
                'employee' => $appraisal->employee,
                'name' => $appraisal->employee->name,
                'code' => $appraisal->employee->employee_code,
                'department_id' => $appraisal->employee->department_id,
                'department' => $appraisal->employee->department?->name,
                'self' => $appraisal->self_review_rating,
                'manager' => $appraisal->manager_review_rating,
                'overall' => $appraisal->overall_rating !== null ? (float) $appraisal->overall_rating : null,
                // The figure people are ranked on: the final rating when there is one, else the best available so far.
                'rating' => $appraisal->overall_rating !== null ? (float) $appraisal->overall_rating : ($appraisal->manager_review_rating ?? $appraisal->self_review_rating),
                'completed' => $appraisal->status === 'completed',
                'goals_total' => $own->count(),
                'goals_completed' => $own->where('status', 'completed')->count(),
                'goal_progress' => $own->isNotEmpty() ? round((float) $own->avg('progress_percent'), 1) : null,
                'kpis_total' => $kpis->get($appraisal->employee_id, collect())->count(),
                'kpi_achievement' => $achievements->isNotEmpty() ? round((float) $achievements->avg(), 1) : null,
                'feedback' => $fb ? round((float) $fb->rating, 1) : null,
                'feedback_entries' => $fb ? (int) $fb->entries : 0,
                'increment' => $increments->has($appraisal->id) ? (float) $increments[$appraisal->id] : null,
            ];
        });
    }

    protected function sorted(Collection $rows, string $sort): Collection
    {
        // Missing figures always go last, whichever way the column sorts.
        return (match ($sort) {
            'rating' => $rows->sortBy([fn ($a, $b) => ($b['rating'] ?? -1) <=> ($a['rating'] ?? -1), fn ($a, $b) => strcasecmp($a['name'], $b['name'])]),
            'goals' => $rows->sortBy([fn ($a, $b) => ($b['goal_progress'] ?? -1) <=> ($a['goal_progress'] ?? -1), fn ($a, $b) => strcasecmp($a['name'], $b['name'])]),
            'kpi' => $rows->sortBy([fn ($a, $b) => ($b['kpi_achievement'] ?? -1) <=> ($a['kpi_achievement'] ?? -1), fn ($a, $b) => strcasecmp($a['name'], $b['name'])]),
            default => $rows->sortBy(fn ($r) => mb_strtolower($r['name'])),
        })->values();
    }

    protected function appraisals(AppraisalCycle $cycle): Builder
    {
        return Appraisal::query()
            ->where('company_id', Company::current()->id)
            ->where('appraisal_cycle_id', $cycle->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function withoutAppraisal(AppraisalCycle $cycle, Collection $appraised): Collection
    {
        $ids = Goal::query()->where('appraisal_cycle_id', $cycle->id)->pluck('employee_id')
            ->merge(EmployeeKpi::query()->where('appraisal_cycle_id', $cycle->id)->pluck('employee_id'))
            ->unique()->diff($appraised);

        return Employee::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->whereIn('id', $ids)->orderBy('name')->pluck('name');
    }
}
