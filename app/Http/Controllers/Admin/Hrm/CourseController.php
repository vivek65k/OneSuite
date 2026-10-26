<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Company;
use App\Models\Course;
use App\Models\TrainingEnrollment;
use App\Models\TrainingSession;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourseController extends Controller
{
    protected const TABS = ['active', 'inactive'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Course::class);

        // Anything that isn't a plain value (e.g. ?search[]=x) is ignored rather than crashing the page.
        $text = fn (string $key) => is_scalar($request->query($key)) ? (string) $request->query($key) : '';
        $state = [
            'tab' => in_array($text('tab'), self::TABS, true) ? $text('tab') : '',
            'category' => trim(mb_substr($text('category'), 0, 120)),
            'mode' => array_key_exists($text('mode'), Course::DELIVERY_MODES) ? $text('mode') : '',
            'search' => trim(mb_substr($text('search'), 0, 80)),
        ];

        $filtered = fn () => $this->scoped()
            ->when($state['category'] !== '', fn ($q) => $q->where('category', $state['category']))
            ->when($state['mode'], fn ($q, $mode) => $q->where('delivery_mode', $mode))
            ->when($state['search'] !== '', function ($q) use ($state) {
                $like = '%'.addcslashes($state['search'], '%_\\').'%';
                $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('category', 'like', $like));
            });

        $courses = $filtered()
            ->when($state['tab'], fn ($q, $tab) => $q->where('is_active', $tab === 'active'))
            ->orderByDesc('is_active')->orderBy('title')->orderBy('id')
            ->paginate(24)
            ->withQueryString();

        $all = $this->scoped()->get(['id', 'category', 'duration_hours', 'is_active']);
        $stats = $this->stats($all->pluck('id'));
        $pageStats = $stats->only($courses->pluck('id')->all());

        return view('admin.hrm.learning.courses.index', [
            'courses' => $courses,
            'state' => $state,
            'stats' => $pageStats,
            'categories' => $all->pluck('category')->filter()->map(fn ($c) => trim($c))->unique()->sort()->values(),
            'counts' => [
                'all' => $filtered()->count(),
                'active' => $filtered()->where('is_active', true)->count(),
                'inactive' => $filtered()->where('is_active', false)->count(),
            ],
            'kpis' => [
                'active' => $all->where('is_active', true)->count(),
                'inactive' => $all->where('is_active', false)->count(),
                'hours' => (float) $all->where('is_active', true)->sum(fn (Course $c) => (float) $c->duration_hours),
                'upcoming' => $stats->sum('upcoming'),
                'learners' => $stats->sum('enrolled'),
                'completed' => $stats->sum('completed'),
                'never_run' => $all->where('is_active', true)->filter(fn (Course $c) => ($stats[$c->id]['sessions'] ?? 0) === 0)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Course::class);

        return view('admin.hrm.learning.courses.create', ['course' => new Course(['delivery_mode' => 'online', 'is_active' => true]), 'categories' => $this->categories()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Course::class);

        $data = $this->validated($request);

        Course::create($data + ['company_id' => Company::current()->id, 'branch_id' => BranchContext::currentBranchId()]);

        return redirect()->route('admin.courses.index')->with('status', 'Course "'.$data['title'].'" added. Schedule a session for it on the Training Calendar.');
    }

    public function edit(Course $course): View
    {
        $this->ensureOwn($course);
        $this->authorize('update', $course);

        return view('admin.hrm.learning.courses.edit', ['course' => $course, 'categories' => $this->categories(), 'usage' => $this->stats(collect([$course->id]))->get($course->id) ?? ['sessions' => 0, 'enrolled' => 0, 'completed' => 0]]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->ensureOwn($course);
        $this->authorize('update', $course);

        $course->update($this->validated($request, $course));

        return redirect()->route('admin.courses.index')->with('status', 'Course updated.');
    }

    /** Switch a course off (or back on) without touching its sessions or history. */
    public function toggle(Course $course): RedirectResponse
    {
        $this->ensureOwn($course);
        $this->authorize('update', $course);

        $course->update(['is_active' => ! $course->is_active]);

        return back()->with('status', '"'.$course->title.'" is now '.($course->is_active ? 'active' : 'switched off. Its past sessions and records are kept, but it should no longer be scheduled').'.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->ensureOwn($course);
        $this->authorize('delete', $course);

        // Deleting a course would also delete its sessions, everyone's enrolment history and its assessments.
        $sessions = TrainingSession::query()->where('course_id', $course->id)->count();
        $assessments = Assessment::query()->where('course_id', $course->id)->count();

        if ($sessions || $assessments) {
            $what = collect([$sessions ? $sessions.' training '.($sessions === 1 ? 'session' : 'sessions') : null, $assessments ? $assessments.' '.($assessments === 1 ? 'assessment' : 'assessments') : null])->filter()->implode(' and ');

            return back()->withErrors(['course' => '"'.$course->title.'" has '.$what.', so it can\'t be deleted. Switch it off instead; the records are kept.']);
        }

        $course->delete();

        return redirect()->route('admin.courses.index')->with('status', 'Course removed.');
    }

    protected function validated(Request $request, ?Course $course = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:120'],
            // The column is decimal(5,2): anything from 1000 up would not fit.
            'duration_hours' => ['nullable', 'numeric', 'min:0.25', 'max:999'],
            'delivery_mode' => ['required', Rule::in(array_keys(Course::DELIVERY_MODES))],
        ], [
            'duration_hours.min' => 'The duration must be at least a quarter of an hour, or left empty.',
            'duration_hours.max' => 'The duration can be at most 999 hours.',
        ]);

        $title = trim($data['title']);

        if ($this->scoped()->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])->when($course, fn ($q) => $q->whereKeyNot($course->id))->exists()) {
            throw ValidationException::withMessages(['title' => 'A course called "'.$title.'" already exists.']);
        }

        return [
            'title' => $title,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'category' => filled($data['category'] ?? null) ? trim($data['category']) : null,
            'duration_hours' => filled($data['duration_hours'] ?? null) ? round((float) $data['duration_hours'], 2) : null,
            'delivery_mode' => $data['delivery_mode'],
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /** Per course: sessions held and coming up, the next date, and how many people were enrolled and finished. */
    protected function stats(Collection $courseIds): Collection
    {
        $sessions = TrainingSession::query()->whereIn('course_id', $courseIds)->get(['id', 'course_id', 'start_date', 'status']);
        $enrollments = TrainingEnrollment::query()->whereIn('training_session_id', $sessions->pluck('id'))
            ->selectRaw("training_session_id, COUNT(*) as enrolled, SUM(status = 'completed') as completed")
            ->groupBy('training_session_id')->get()->keyBy('training_session_id');

        return $sessions->groupBy('course_id')->map(function (Collection $group) use ($enrollments) {
            $upcoming = $group->filter(fn (TrainingSession $s) => $s->status !== 'cancelled' && $s->start_date && $s->start_date->gte(today()));

            return [
                'sessions' => $group->where('status', '!=', 'cancelled')->count(),
                'upcoming' => $upcoming->count(),
                'next' => $upcoming->min('start_date'),
                'enrolled' => (int) $group->sum(fn (TrainingSession $s) => (int) ($enrollments[$s->id]->enrolled ?? 0)),
                'completed' => (int) $group->sum(fn (TrainingSession $s) => (int) ($enrollments[$s->id]->completed ?? 0)),
            ];
        });
    }

    protected function categories(): Collection
    {
        return $this->scoped()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->map(fn ($c) => trim($c))->filter()->unique()->values();
    }

    /** 404 (not 403) for another company's or branch's course, so the URL doesn't confirm it exists. */
    protected function ensureOwn(Course $course): void
    {
        abort_unless($this->scoped()->whereKey($course->id)->exists(), 404);
    }

    /** Courses this login may see: its own company and, when tied to a branch, that branch's plus company-wide ones. */
    protected function scoped(): Builder
    {
        return Course::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where(fn ($w) => $w->where('branch_id', BranchContext::currentBranchId())->orWhereNull('branch_id')));
    }
}
