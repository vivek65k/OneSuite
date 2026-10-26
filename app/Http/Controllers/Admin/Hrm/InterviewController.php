<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Interview;
use App\Models\JobPosition;
use App\Support\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InterviewController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['round', 'scheduled_at', 'status', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Interview::class);

        $state = $this->resolveTableState($request, 'interviews', [
            'sort' => 'scheduled_at', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'scheduled_at', self::SORTABLE, true) ? $state['sort'] : 'scheduled_at';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $interviews = $this->filteredQuery($state)
            ->with('candidate.jobPosition')
            ->withCount('feedback')
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        // Status-tab counts ignore the status filter itself (so every tab
        // shows what clicking it would return) but respect the rest.
        $statusCounts = $this->filteredQuery(array_diff_key($state, ['status' => true]))
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count);

        // Headline KPIs are company/branch-wide, unaffected by list filters.
        $active = ['scheduled', 'rescheduled'];
        $kpis = [
            'today' => $this->filteredQuery([])->whereIn('status', $active)->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'week' => $this->filteredQuery([])->whereIn('status', $active)->whereBetween('scheduled_at', [now(), now()->endOfWeek()])->count(),
            'upcoming' => $this->filteredQuery([])->whereIn('status', $active)->where('scheduled_at', '>=', now())->count(),
            'completed' => $this->filteredQuery([])->where('status', 'completed')->count(),
            'awaiting_feedback' => $this->filteredQuery([])->where('status', 'completed')->doesntHave('feedback')->count(),
        ];

        return view('admin.hrm.recruitment.interviews.index', [
            'interviews' => $interviews,
            'statusCounts' => $statusCounts->put('all', (int) $statusCounts->sum()),
            'kpis' => $kpis,
            'interviewers' => $this->interviewerOptions(),
            'jobPositions' => JobPosition::query()
                ->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
                ->orderBy('title')
                ->pluck('title', 'id'),
            'candidates' => $this->candidateOptions(),
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Interview::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'scheduled_at';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'scheduled_at';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $interviews = $this->filteredQuery($state)->with('candidate')->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'interviews-'.now()->format('Y-m-d-His').'.csv',
            ['Candidate', 'Round', 'Scheduled At', 'Interviewer', 'Location', 'Status'],
            (function () use ($interviews) {
                foreach ($interviews as $interview) {
                    yield [
                        $interview->candidate?->name, $interview->round,
                        optional($interview->scheduled_at)->format('Y-m-d H:i'),
                        $interview->interviewer_name, $interview->location, $interview->status,
                    ];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return Interview::query()
            ->whereHas('candidate', fn ($q) => $q->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q2) => $q2->where('branch_id', BranchContext::currentBranchId())))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('round', 'like', "%{$search}%")
                    ->orWhere('interviewer_name', 'like', "%{$search}%")
                    ->orWhereHas('candidate', fn ($q3) => $q3->where('name', 'like', "%{$search}%"));
            }))
            ->when($state['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($state['candidate_id'] ?? null, fn ($q, $candidateId) => $q->where('candidate_id', $candidateId))
            ->when($state['job_position_id'] ?? null, fn ($q, $jobId) => $q->whereHas('candidate', fn ($q2) => $q2->where('job_position_id', $jobId)))
            ->when($state['interviewer'] ?? null, fn ($q, $name) => $q->where('interviewer_name', $name))
            ->when($state['date_from'] ?? null, fn ($q, $date) => $q->whereDate('scheduled_at', '>=', $date))
            ->when($state['date_until'] ?? null, fn ($q, $date) => $q->whereDate('scheduled_at', '<=', $date))
            ->when($state['when'] ?? null, fn ($q, $when) => match ($when) {
                'upcoming' => $q->where('scheduled_at', '>=', now()),
                'today' => $q->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()]),
                'week' => $q->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'past' => $q->where('scheduled_at', '<', now()),
                default => $q,
            });
    }

    /** Interviewer names already used, for the filter and form suggestions. */
    protected function interviewerOptions()
    {
        return $this->filteredQuery([])
            ->whereNotNull('interviewer_name')
            ->where('interviewer_name', '!=', '')
            ->distinct()
            ->orderBy('interviewer_name')
            ->pluck('interviewer_name');
    }

    public function create(): View
    {
        $this->authorize('create', Interview::class);

        // ?candidate_id= preselects the candidate (e.g. "Schedule interview"
        // from a candidate), only if it's one this user may pick.
        $interview = new Interview();
        $candidates = $this->candidateOptions();
        if ($candidates->has((int) request('candidate_id'))) {
            $interview->candidate_id = (int) request('candidate_id');
        }

        return view('admin.hrm.recruitment.interviews.create', [
            'interview' => $interview,
            'candidates' => $candidates,
            'candidateDetails' => $this->candidateDetails(),
            'interviewers' => $this->interviewerOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Interview::class);

        Interview::create($this->validated($request));

        return redirect()->route('admin.interviews.index')->with('status', 'Interview scheduled.');
    }

    public function edit(Interview $interview): View
    {
        $this->authorize('update', $interview);

        return view('admin.hrm.recruitment.interviews.edit', [
            'interview' => $interview,
            'candidates' => $this->candidateOptions(),
            'candidateDetails' => $this->candidateDetails(),
            'interviewers' => $this->interviewerOptions(),
        ]);
    }

    public function update(Request $request, Interview $interview): RedirectResponse
    {
        $this->authorize('update', $interview);

        $interview->update($this->validated($request));

        return redirect()->route('admin.interviews.index')->with('status', 'Interview updated.');
    }

    public function destroy(Interview $interview): RedirectResponse
    {
        $this->authorize('delete', $interview);

        $interview->delete();

        return redirect()->route('admin.interviews.index')->with('status', 'Interview removed.');
    }

    protected function candidateOptions()
    {
        return Candidate::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Stage / applied-for per candidate id, for the form's live candidate card.
     *
     * @return array<int, array{job: ?string, stage: string, email: ?string}>
     */
    protected function candidateDetails(): array
    {
        return Candidate::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->with('jobPosition:id,title')
            ->get(['id', 'job_position_id', 'stage', 'email'])
            ->mapWithKeys(fn ($candidate) => [$candidate->id => [
                'job' => $candidate->jobPosition?->title,
                'stage' => $candidate->stage,
                'email' => $candidate->email,
            ]])
            ->all();
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'candidate_id' => [
                'required',
                Rule::exists('candidates', 'id')->where('company_id', Company::current()->id)
                    ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId())),
            ],
            'round' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date'],
            'interviewer_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Interview::STATUSES)],
        ]);
    }
}
