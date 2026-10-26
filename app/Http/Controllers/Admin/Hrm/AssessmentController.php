<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Company;
use App\Models\Course;
use App\Models\Department;
use App\Models\Employee;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    protected const TABS = ['pass', 'fail'];

    /** The score columns are decimal(6,2). */
    protected const MAX_SCORE = 9999.99;

    /** A result can be dated at most this many years back. */
    protected const YEARS_BACK = 5;

    /** Employees who have left; no new results are recorded for them. */
    protected const INACTIVE = ['terminated', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Assessment::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $id = fn (string $key) => ctype_digit($text($key)) ? (int) $text($key) : null;
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'assessment_id' => $id('assessment_id'),
            'department_id' => $id('department_id'),
            'employee_id' => $id('employee_id'),
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $assessments = $this->assessments()->with('course:id,title,is_active')->orderBy('title')->orderBy('id')->get();
        $visible = $assessments->pluck('id');

        $filtered = fn () => $this->scoped()->whereIn('assessment_id', $visible)
            ->when($state['assessment_id'], fn ($q, $assessmentId) => $q->where('assessment_id', $assessmentId))
            ->when($state['employee_id'], fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            ->when($state['department_id'], fn ($q, $departmentId) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $departmentId)))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w
                    ->whereHas('employee', fn ($e) => $e->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('employee_code', 'like', $like)))
                    ->orWhereHas('assessment', fn ($a) => $a->where('title', 'like', $like)->orWhereHas('course', fn ($c) => $c->where('title', 'like', $like))));
            });

        $results = $filtered()
            ->when($state['tab'], fn ($q, $tab) => $q->where('status', $tab))
            ->with(['employee.department:id,name', 'assessment.course:id,title'])
            ->orderByDesc('attempted_date')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $all = $this->scoped()->whereIn('assessment_id', $visible)->get(['id', 'assessment_id', 'employee_id', 'score', 'status']);
        $byAssessment = $all->groupBy('assessment_id');
        $maxOf = $assessments->pluck('max_score', 'id');
        // Scores are out of different totals, so the average is of percentages.
        $percents = $all->map(fn (AssessmentResult $r) => (float) ($maxOf[$r->assessment_id] ?? 0) > 0 ? (float) $r->score / (float) $maxOf[$r->assessment_id] * 100 : null)->filter(fn ($p) => $p !== null);
        $people = $this->employeeQuery()->with('department:id,name')->orderBy('name')->get(['id', 'name', 'employee_code', 'avatar', 'department_id', 'branch_id', 'status']);

        return view('admin.hrm.learning.assessments.index', [
            'assessments' => $assessments,
            'stats' => $byAssessment->map(fn ($group) => [
                'taken' => $group->count(),
                'passed' => $group->where('status', 'pass')->count(),
                'best' => (float) $group->max(fn (AssessmentResult $r) => (float) $r->score),
            ]),
            // What each person already scored, so the score sheet can show it and warn before overwriting.
            'existing' => $byAssessment->map(fn ($group) => $group->mapWithKeys(fn (AssessmentResult $r) => [$r->employee_id => (float) $r->score])),
            'results' => $results,
            'state' => $state,
            'employees' => $people->pluck('name', 'id'),
            'sheetPeople' => $people->whereNotIn('status', self::INACTIVE)->values(),
            'departments' => Department::query()->where('company_id', Company::current()->id)
                ->whereIn('id', $people->pluck('department_id')->filter()->unique())->orderBy('name')->pluck('name', 'id'),
            'courses' => $this->courses()->where('is_active', true)->orderBy('title')->pluck('title', 'id'),
            'counts' => [
                'all' => $filtered()->count(),
                'pass' => $filtered()->where('status', 'pass')->count(),
                'fail' => $filtered()->where('status', 'fail')->count(),
            ],
            'kpis' => [
                'assessments' => $assessments->count(),
                'unused' => $assessments->filter(fn (Assessment $a) => ! $byAssessment->has($a->id))->count(),
                'results' => $all->count(),
                'people' => $all->pluck('employee_id')->unique()->count(),
                'passed' => $all->where('status', 'pass')->count(),
                'failed' => $all->where('status', 'fail')->count(),
                'average' => $percents->isNotEmpty() ? round($percents->avg(), 1) : null,
            ],
            'canCreate' => $request->user()->can('create', Assessment::class),
            'canScore' => $request->user()->can('create', AssessmentResult::class),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Assessment::class);

        $courseId = is_scalar($request->input('course_id')) ? $request->input('course_id') : null;
        $course = $this->courses()->whereKey($courseId)->first();

        if (! $course) {
            throw ValidationException::withMessages(['course_id' => 'Choose a course from the list.']);
        }
        if (! $course->is_active) {
            throw ValidationException::withMessages(['course_id' => '"'.$course->title.'" is switched off. Switch it on under Courses before adding an assessment to it.']);
        }

        $data = $this->validated($request, $course->id);

        Assessment::create($data + ['company_id' => Company::current()->id, 'course_id' => $course->id]);

        return redirect()->route('admin.assessments.index')->with('status', '"'.$data['title'].'" added to '.$course->title.'. Record scores for it with the score sheet.');
    }

    public function update(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->ensureOwn($assessment);
        $this->authorize('update', $assessment);

        $data = $this->validated($request, $assessment->course_id, $assessment);
        $results = AssessmentResult::withoutGlobalScopes()->where('assessment_id', $assessment->id);
        $best = (float) (clone $results)->max('score');

        if ($data['max_score'] < $best) {
            throw ValidationException::withMessages(['max_score' => 'Someone already scored '.$this->num($best).' on this assessment, so the maximum can\'t be lower than that.']);
        }

        // A new pass mark changes who passed, so every recorded result is graded again.
        $regraded = DB::transaction(function () use ($assessment, $data, $results) {
            $assessment->update($data);

            return (clone $results)->where('status', 'fail')->where('score', '>=', $data['passing_score'])->update(['status' => 'pass'])
                + (clone $results)->where('status', 'pass')->where('score', '<', $data['passing_score'])->update(['status' => 'fail']);
        });

        return back()->with('status', 'Assessment updated.'.($regraded ? ' '.$regraded.' '.($regraded === 1 ? 'result was' : 'results were').' re-graded against the new pass mark.' : ''));
    }

    public function destroy(Assessment $assessment): RedirectResponse
    {
        $this->ensureOwn($assessment);
        $this->authorize('delete', $assessment);

        // Deleting an assessment would silently delete everyone's score on it.
        $taken = AssessmentResult::withoutGlobalScopes()->where('assessment_id', $assessment->id)->count();

        if ($taken) {
            return back()->withErrors(['assessment' => '"'.$assessment->title.'" has '.$taken.' recorded '.($taken === 1 ? 'result' : 'results').', so it can\'t be deleted. Remove the results first if it really was entered by mistake.']);
        }

        $assessment->delete();

        return back()->with('status', 'Assessment removed.');
    }

    public function storeResult(Request $request): RedirectResponse
    {
        $this->authorize('create', AssessmentResult::class);

        // The score sheet sends scores[employee_id]; the old single employee_id + score still works.
        if (! $request->has('scores') && is_scalar($request->input('employee_id')) && $request->filled('employee_id')) {
            $request->merge(['scores' => [(string) $request->input('employee_id') => $request->input('score')]]);
        }

        $request->validate([
            'assessment_id' => ['required'],
            'scores' => ['required', 'array', 'max:500'],
            'attempted_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.today()->subYears(self::YEARS_BACK)->toDateString()],
        ], [
            'assessment_id.required' => 'Choose the assessment the scores are for.',
            'scores.required' => 'Enter at least one score.',
            'attempted_date.before_or_equal' => 'The date can\'t be in the future.',
            'attempted_date.after_or_equal' => 'The date can be at most '.self::YEARS_BACK.' years back.',
        ]);

        $assessmentId = is_scalar($request->input('assessment_id')) ? $request->input('assessment_id') : null;
        $assessment = $this->assessments()->whereKey($assessmentId)->first();

        if (! $assessment) {
            throw ValidationException::withMessages(['assessment_id' => 'Choose an assessment from the list.']);
        }

        // Rows left empty on the sheet are simply people who didn't take it.
        $scores = collect($request->input('scores'))->filter(fn ($score) => is_scalar($score) && trim((string) $score) !== '');

        if ($scores->isEmpty()) {
            throw ValidationException::withMessages(['scores' => 'Enter at least one score.']);
        }

        $max = (float) $assessment->max_score;

        foreach ($scores as $employeeId => $score) {
            if (! ctype_digit((string) $employeeId) || ! is_numeric($score) || (float) $score < 0 || (float) $score > $max) {
                throw ValidationException::withMessages(['scores' => 'Every score must be a number between 0 and '.$this->num($max).' (the maximum for "'.$assessment->title.'").']);
            }
        }

        $employees = $this->employeeQuery()->whereNotIn('status', self::INACTIVE)->whereIn('id', $scores->keys())->get(['id', 'name']);

        if ($employees->count() !== $scores->count()) {
            throw ValidationException::withMessages(['scores' => 'One or more of the employees can\'t be scored (not found, or no longer with the company).']);
        }

        $date = Carbon::parse($request->input('attempted_date'))->toDateString();
        $companyId = Company::current()->id;
        $replaced = 0;
        $passed = 0;

        DB::transaction(function () use ($assessment, $employees, $scores, $date, $companyId, &$replaced, &$passed) {
            // One result per person per assessment: a retake replaces the earlier score.
            $existing = AssessmentResult::withoutGlobalScopes()->where('assessment_id', $assessment->id)->whereIn('employee_id', $employees->pluck('id'))->lockForUpdate()->get()->keyBy('employee_id');

            foreach ($employees as $employee) {
                $score = round((float) $scores[$employee->id], 2);
                $status = $score >= (float) $assessment->passing_score ? 'pass' : 'fail';
                $passed += $status === 'pass' ? 1 : 0;

                if ($row = $existing->get($employee->id)) {
                    $row->update(['score' => $score, 'status' => $status, 'attempted_date' => $date]);
                    $replaced++;
                } else {
                    AssessmentResult::create(['company_id' => $companyId, 'assessment_id' => $assessment->id, 'employee_id' => $employee->id, 'score' => $score, 'status' => $status, 'attempted_date' => $date]);
                }
            }
        });

        $count = $employees->count();
        $message = $count === 1
            ? $employees->first()->name.' scored '.$this->num((float) $scores->first()).' / '.$this->num($max).' on "'.$assessment->title.'": '.($passed ? 'pass' : 'fail').'.'
            : $count.' scores recorded for "'.$assessment->title.'": '.$passed.' passed, '.($count - $passed).' failed.';

        return redirect()->route('admin.assessments.index')->with('status', $message.($replaced ? ' '.$replaced.' earlier '.($replaced === 1 ? 'score was' : 'scores were').' replaced.' : ''));
    }

    public function destroyResult(AssessmentResult $result): RedirectResponse
    {
        abort_unless($this->scoped()->whereKey($result->id)->exists(), 404);
        $this->authorize('delete', $result);

        $result->delete();

        return back()->with('status', 'Result removed.');
    }

    protected function validated(Request $request, int $courseId, ?Assessment $assessment = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:'.self::MAX_SCORE],
            'passing_score' => ['required', 'numeric', 'gt:0', 'lte:max_score'],
        ], [
            'max_score.max' => 'The maximum score can be at most '.number_format(self::MAX_SCORE, 2).'.',
            'passing_score.gt' => 'The pass mark must be more than zero, otherwise everyone passes.',
            'passing_score.lte' => 'The pass mark can\'t be higher than the maximum score.',
        ]);

        $title = trim($data['title']);

        if (Assessment::query()->where('company_id', Company::current()->id)->where('course_id', $courseId)->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
            ->when($assessment, fn ($q) => $q->whereKeyNot($assessment->id))->exists()) {
            throw ValidationException::withMessages(['title' => 'This course already has an assessment called "'.$title.'".']);
        }

        return ['title' => $title, 'max_score' => round((float) $data['max_score'], 2), 'passing_score' => round((float) $data['passing_score'], 2)];
    }

    protected function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    /** 404 (not 403) for another company's or branch's assessment, so the URL doesn't confirm it exists. */
    protected function ensureOwn(Assessment $assessment): void
    {
        abort_unless($this->assessments()->whereKey($assessment->id)->exists(), 404);
    }

    /** Results this login may see: its own company and, when tied to a branch, that branch's employees. */
    protected function scoped(): Builder
    {
        return AssessmentResult::query()
            ->where('assessment_results.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', BranchContext::currentBranchId())));
    }

    /** An assessment follows its course: a branch login sees its branch's courses and the company-wide ones. */
    protected function assessments(): Builder
    {
        return Assessment::query()
            ->where('assessments.company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->whereHas('course', fn ($c) => $c->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id'))));
    }

    protected function courses(): Builder
    {
        return Course::query()
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
