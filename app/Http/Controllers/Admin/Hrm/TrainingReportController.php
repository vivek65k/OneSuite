<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\AssessmentResult;
use App\Models\Company;
use App\Models\Course;
use App\Models\TrainingEnrollment;
use App\Models\TrainingSession;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainingReportController extends Controller
{
    protected const SORTS = ['name', 'enrolled', 'completion', 'hours', 'pass'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TrainingEnrollment::class);

        $state = $this->state($request);
        $all = $this->rows($state['year']);
        $rows = $this->sorted($state['category'] !== '' ? $all->where('category', $state['category'])->values() : $all, $state['sort']);
        $active = $rows->filter(fn ($r) => $r['sessions'] > 0 || $r['taken'] > 0);

        return view('admin.hrm.learning.reports', [
            'state' => $state,
            'years' => $this->years(),
            'categories' => $all->pluck('category')->filter()->unique()->sort()->values(),
            'rows' => $rows,
            'totals' => $this->totals($rows),
            // Enrolments per category, for the spread chart.
            'byCategory' => $rows->groupBy(fn ($r) => $r['category'] ?: 'No category')->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'courses' => $group->count(),
                'enrolled' => $group->sum('enrolled'),
                'completed' => $group->sum('completed'),
            ])->sortByDesc('enrolled')->values(),
            'top' => $active->where('enrolled', '>', 0)->sortByDesc('enrolled')->take(3)->values(),
            // Courses where things are not going well: few finish, few pass, or sessions were left open.
            'attention' => $active->filter(fn ($r) => $r['overdue'] > 0 || ($r['completion'] !== null && $r['closed'] > 0 && $r['completion'] < 50) || ($r['pass'] !== null && $r['pass'] < 50))
                ->sortBy(fn ($r) => $r['completion'] ?? 100)->take(4)->values(),
            'neverRun' => $rows->filter(fn ($r) => $r['course']->is_active && $r['sessions'] === 0)->count(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', TrainingEnrollment::class);

        $state = $this->state($request);
        $all = $this->rows($state['year']);
        $rows = $this->sorted($state['category'] !== '' ? $all->where('category', $state['category'])->values() : $all, $state['sort']);
        // A cell starting with = + - @ would run as a formula when the file is opened in a spreadsheet.
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;

        return response()->streamDownload(function () use ($rows, $safe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Course', 'Category', 'Delivery', 'Course hours', 'Sessions', 'Cancelled sessions', 'Enrolled', 'Completed', 'In progress', 'Not started', 'Dropped', 'Completion Rate %', 'Training hours delivered', 'Assessments Taken', 'Assessments Passed', 'Pass Rate %', 'Average score %']);

            foreach ($rows as $r) {
                fputcsv($out, array_map($safe, [
                    $r['course']->title, $r['category'], Course::DELIVERY_MODES[$r['course']->delivery_mode] ?? $r['course']->delivery_mode, $r['course']->duration_hours,
                    $r['sessions'], $r['cancelled'], $r['enrolled'], $r['completed'], $r['in_progress'], $r['enrolled'] - $r['completed'] - $r['in_progress'], $r['dropped'],
                    $r['completion'] ?? '', $r['hours'], $r['taken'], $r['passed'], $r['pass'] ?? '', $r['average'] ?? '',
                ]));
            }

            fclose($out);
        }, 'training-report'.($state['year'] ? '-'.$state['year'] : '').'.csv', ['Content-Type' => 'text/csv']);
    }

    protected function state(Request $request): array
    {
        // Anything that isn't a plain value (e.g. ?year[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $year = ctype_digit($text('year')) && (int) $text('year') >= 2000 && (int) $text('year') <= 2100 ? (int) $text('year') : null;

        return [
            'year' => $year,
            'category' => trim(mb_substr($text('category'), 0, 120)),
            'sort' => in_array($text('sort'), self::SORTS, true) ? $text('sort') : 'name',
        ];
    }

    /** Years that have a session or an assessment result, newest first. */
    protected function years(): Collection
    {
        $sessions = $this->sessions()->pluck('start_date')->map(fn ($d) => $d?->year);
        $results = $this->results()->pluck('attempted_date')->map(fn ($d) => $d?->year);

        return $sessions->merge($results)->filter()->unique()->sortDesc()->values();
    }

    /** One line per course, counting only what this login is allowed to see. */
    protected function rows(?int $year): Collection
    {
        $courses = Course::query()
            ->where('company_id', Company::current()->id)
            // The Courses page shows a branch its own courses and the company-wide ones; the report must match it.
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')))
            ->with('assessments:id,course_id,max_score')
            ->orderBy('title')->orderBy('id')
            ->get();

        $sessions = $this->sessions()->whereIn('course_id', $courses->pluck('id'))
            ->when($year, fn ($q) => $q->whereYear('start_date', $year))
            ->get(['id', 'course_id', 'start_date', 'end_date', 'status'])->groupBy('course_id');
        $enrollments = $this->enrollments()->whereIn('training_session_id', $sessions->flatten()->pluck('id'))
            ->get(['id', 'training_session_id', 'employee_id', 'status'])->groupBy('training_session_id');
        $results = $this->results()->whereIn('assessment_id', $courses->flatMap->assessments->pluck('id'))
            ->when($year, fn ($q) => $q->whereYear('attempted_date', $year))
            ->get(['id', 'assessment_id', 'employee_id', 'score', 'status'])->groupBy('assessment_id');

        return $courses->map(function (Course $course) use ($sessions, $enrollments, $results) {
            $mine = $sessions->get($course->id, collect());
            // A cancelled session never took place, so neither it nor the people on it count.
            $held = $mine->where('status', '!=', 'cancelled');
            $over = $held->filter(fn (TrainingSession $s) => $s->status === 'completed' || $s->end_date->lt(today()));
            $people = $held->flatMap(fn (TrainingSession $s) => $enrollments->get($s->id, collect()));
            // Dropped people are shown on their own and don't pull the completion rate down.
            $counted = $people->where('status', '!=', 'dropped');
            $completed = $counted->where('status', 'completed');
            $overdue = $over->flatMap(fn (TrainingSession $s) => $enrollments->get($s->id, collect()))->whereIn('status', ['assigned', 'in_progress']);
            $scores = $course->assessments->flatMap(fn ($a) => $results->get($a->id, collect())->map(fn (AssessmentResult $r) => ['status' => $r->status, 'pct' => (float) $a->max_score > 0 ? (float) $r->score / (float) $a->max_score * 100 : null]));

            return [
                'course' => $course,
                'category' => filled($course->category) ? trim($course->category) : null,
                'sessions' => $held->count(),
                'cancelled' => $mine->count() - $held->count(),
                'closed' => $over->count(),
                'enrolled' => $counted->count(),
                'completed' => $completed->count(),
                'in_progress' => $counted->where('status', 'in_progress')->count(),
                'dropped' => $people->count() - $counted->count(),
                'overdue' => $overdue->count(),
                'completion' => $counted->isNotEmpty() ? (int) round($completed->count() / $counted->count() * 100) : null,
                'hours' => round($completed->count() * (float) ($course->duration_hours ?? 0), 2),
                'learners' => $completed->pluck('employee_id')->unique()->values(),
                'assessments' => $course->assessments->count(),
                'taken' => $scores->count(),
                'passed' => $scores->where('status', 'pass')->count(),
                'pass' => $scores->isNotEmpty() ? (int) round($scores->where('status', 'pass')->count() / $scores->count() * 100) : null,
                'average' => $scores->whereNotNull('pct')->isNotEmpty() ? (int) round($scores->whereNotNull('pct')->avg('pct')) : null,
            ];
        });
    }

    protected function totals(Collection $rows): array
    {
        $enrolled = $rows->sum('enrolled');
        $taken = $rows->sum('taken');

        return [
            'courses' => $rows->count(),
            'run' => $rows->where('sessions', '>', 0)->count(),
            'sessions' => $rows->sum('sessions'),
            'cancelled' => $rows->sum('cancelled'),
            'enrolled' => $enrolled,
            'completed' => $rows->sum('completed'),
            'in_progress' => $rows->sum('in_progress'),
            'dropped' => $rows->sum('dropped'),
            'overdue' => $rows->sum('overdue'),
            'completion' => $enrolled > 0 ? (int) round($rows->sum('completed') / $enrolled * 100) : null,
            'hours' => (float) $rows->sum('hours'),
            // One person who finished three courses is one person trained.
            'people' => $rows->flatMap(fn ($r) => $r['learners'])->unique()->count(),
            'taken' => $taken,
            'passed' => $rows->sum('passed'),
            'pass' => $taken > 0 ? (int) round($rows->sum('passed') / $taken * 100) : null,
        ];
    }

    protected function sorted(Collection $rows, string $sort): Collection
    {
        if ($sort === 'name') {
            return $rows->values();
        }

        // Biggest first; courses with nothing to measure go last.
        return $rows->sort(function ($a, $b) use ($sort) {
            [$x, $y] = [$a[$sort], $b[$sort]];

            if ($x === null || $y === null) {
                return ($x === null) <=> ($y === null);
            }

            return ($y <=> $x) ?: strcasecmp($a['course']->title, $b['course']->title);
        })->values();
    }

    protected function sessions()
    {
        return TrainingSession::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')));
    }

    /** A branch login counts only its own branch's people, also on sessions shared across the company. */
    protected function enrollments()
    {
        return TrainingEnrollment::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    protected function results()
    {
        return AssessmentResult::query()
            ->where('assessment_results.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }
}
