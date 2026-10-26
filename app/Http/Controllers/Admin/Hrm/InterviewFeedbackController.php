<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Interview;
use App\Models\InterviewFeedback;
use App\Support\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InterviewFeedbackController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['interviewer_name', 'recommendation', 'rating', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', InterviewFeedback::class);

        $state = $this->resolveTableState($request, 'interview-feedback', [
            'sort' => 'created_at', 'direction' => 'desc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'created_at', self::SORTABLE, true) ? $state['sort'] : 'created_at';
        $direction = ($state['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $feedback = $this->filteredQuery($state)
            ->with('interview.candidate.jobPosition')
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        // Recommendation-tab counts ignore that filter itself (so every tab
        // shows what clicking it would return) but respect the rest.
        $recommendationCounts = $this->filteredQuery(array_diff_key($state, ['recommendation' => true]))
            ->toBase()
            ->selectRaw('recommendation, COUNT(*) as aggregate')
            ->groupBy('recommendation')
            ->pluck('aggregate', 'recommendation')
            ->map(fn ($count) => (int) $count);

        // Headline KPIs are company/branch-wide, unaffected by list filters.
        $kpiBase = $this->filteredQuery([])->toBase();
        $kpis = [
            'total' => (clone $kpiBase)->count(),
            'hire' => (clone $kpiBase)->where('recommendation', 'hire')->count(),
            'avg_rating' => (clone $kpiBase)->whereNotNull('rating')->avg('rating'),
            'rated' => (clone $kpiBase)->whereNotNull('rating')->count(),
        ];

        // Completed interviews nobody has written feedback for yet.
        $awaiting = $this->interviewScope()
            ->where('status', 'completed')
            ->doesntHave('feedback')
            ->with('candidate')
            ->orderByDesc('scheduled_at');

        return view('admin.hrm.recruitment.interview-feedback.index', [
            'feedbackEntries' => $feedback,
            'recommendationCounts' => $recommendationCounts->put('all', (int) $recommendationCounts->sum()),
            'kpis' => $kpis + ['awaiting' => (clone $awaiting)->count()],
            'awaitingInterviews' => $awaiting->limit(4)->get(),
            'interviewers' => $this->filteredQuery([])->distinct()->orderBy('interviewer_name')->pluck('interviewer_name'),
            'candidates' => Candidate::query()
                ->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->whereHas('interviews.feedback')
                ->orderBy('name')
                ->pluck('name', 'id'),
            'interviews' => $this->interviewOptions(),
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', InterviewFeedback::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'created_at';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'created_at';
        $direction = ($state['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $feedback = $this->filteredQuery($state)->with('interview.candidate')->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'interview-feedback-'.now()->format('Y-m-d-His').'.csv',
            ['Candidate', 'Round', 'Interviewer', 'Rating', 'Recommendation', 'Comments'],
            (function () use ($feedback) {
                foreach ($feedback as $entry) {
                    yield [
                        $entry->interview?->candidate?->name, $entry->interview?->round,
                        $entry->interviewer_name, $entry->rating, $entry->recommendation, $entry->comments,
                    ];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return InterviewFeedback::query()
            ->whereHas('interview.candidate', fn ($q) => $q->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q2) => $q2->where('branch_id', BranchContext::currentBranchId())))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('interviewer_name', 'like', "%{$search}%")
                    ->orWhere('comments', 'like', "%{$search}%")
                    ->orWhereHas('interview.candidate', fn ($q3) => $q3->where('name', 'like', "%{$search}%"));
            }))
            ->when($state['recommendation'] ?? null, fn ($q, $recommendation) => $q->where('recommendation', $recommendation))
            ->when($state['interview_id'] ?? null, fn ($q, $interviewId) => $q->where('interview_id', $interviewId))
            ->when($state['candidate_id'] ?? null, fn ($q, $candidateId) => $q->whereHas('interview', fn ($q2) => $q2->where('candidate_id', $candidateId)))
            ->when($state['interviewer'] ?? null, fn ($q, $name) => $q->where('interviewer_name', $name))
            ->when($state['min_rating'] ?? null, fn ($q, $rating) => $q->where('rating', '>=', (int) $rating));
    }

    /** Interviews belonging to the current company (and branch). */
    protected function interviewScope(): Builder
    {
        return Interview::query()
            ->whereHas('candidate', fn ($q) => $q->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q2) => $q2->where('branch_id', BranchContext::currentBranchId())));
    }

    /**
     * Candidate / round / date / interviewer per interview id, for the form's
     * live interview card and interviewer prefill.
     *
     * @return array<int, array<string, ?string>>
     */
    protected function interviewDetails(): array
    {
        return $this->interviewScope()
            ->with('candidate.jobPosition:id,title')
            ->get()
            ->mapWithKeys(fn (Interview $interview) => [$interview->id => [
                'candidate' => $interview->candidate?->name,
                'job' => $interview->candidate?->jobPosition?->title,
                'round' => $interview->round,
                'when' => $interview->scheduled_at?->format('D, M j, Y · g:i A'),
                'status' => $interview->status,
                'interviewer' => $interview->interviewer_name,
            ]])
            ->all();
    }

    public function create(): View
    {
        $this->authorize('create', InterviewFeedback::class);

        // ?interview_id= preselects the interview (the "Add feedback" nudges),
        // only if it's one this user may pick.
        $feedbackEntry = new InterviewFeedback();
        $interviews = $this->interviewOptions();
        if ($interviews->has((int) request('interview_id'))) {
            $feedbackEntry->interview_id = (int) request('interview_id');
        }

        return view('admin.hrm.recruitment.interview-feedback.create', [
            'feedbackEntry' => $feedbackEntry,
            'interviews' => $interviews,
            'interviewDetails' => $this->interviewDetails(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', InterviewFeedback::class);

        InterviewFeedback::create($this->validated($request));

        return redirect()->route('admin.interview-feedback.index')->with('status', 'Feedback recorded.');
    }

    public function edit(InterviewFeedback $interviewFeedback): View
    {
        $this->authorize('update', $interviewFeedback);

        return view('admin.hrm.recruitment.interview-feedback.edit', [
            'feedbackEntry' => $interviewFeedback,
            'interviews' => $this->interviewOptions(),
            'interviewDetails' => $this->interviewDetails(),
        ]);
    }

    public function update(Request $request, InterviewFeedback $interviewFeedback): RedirectResponse
    {
        $this->authorize('update', $interviewFeedback);

        $interviewFeedback->update($this->validated($request));

        return redirect()->route('admin.interview-feedback.index')->with('status', 'Feedback updated.');
    }

    public function destroy(InterviewFeedback $interviewFeedback): RedirectResponse
    {
        $this->authorize('delete', $interviewFeedback);

        $interviewFeedback->delete();

        return redirect()->route('admin.interview-feedback.index')->with('status', 'Feedback removed.');
    }

    protected function interviewOptions()
    {
        return Interview::query()
            ->whereHas('candidate', fn ($q) => $q->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q2) => $q2->where('branch_id', BranchContext::currentBranchId())))
            ->with('candidate')
            ->orderByDesc('scheduled_at')
            ->get()
            ->mapWithKeys(fn (Interview $interview) => [
                $interview->id => "{$interview->candidate?->name} - {$interview->round} ({$interview->scheduled_at->format('M j, Y')})",
            ]);
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'interview_id' => [
                'required',
                Rule::exists('interviews', 'id')->where(
                    fn ($q) => $q->whereIn('candidate_id', Candidate::query()
                        ->where('company_id', Company::current()->id)
                        ->when(BranchContext::isRestricted(), fn ($q2) => $q2->where('branch_id', BranchContext::currentBranchId()))
                        ->pluck('id'))
                ),
            ],
            'interviewer_name' => ['required', 'string', 'max:255'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'recommendation' => ['required', Rule::in(InterviewFeedback::RECOMMENDATIONS)],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        return $data;
    }
}
