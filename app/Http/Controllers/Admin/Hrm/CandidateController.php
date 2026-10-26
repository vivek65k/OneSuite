<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosition;
use App\Support\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['name', 'stage', 'applied_date', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Candidate::class);

        $state = $this->resolveTableState($request, 'candidates', [
            'sort' => 'applied_date', 'direction' => 'desc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'applied_date', self::SORTABLE, true) ? $state['sort'] : 'applied_date';
        $direction = ($state['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $candidates = $this->filteredQuery($state)
            ->with('jobPosition')
            ->withCount('interviews')
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        // Stage-tab counts ignore the stage filter itself (so every tab shows
        // what clicking it would return) but respect the rest.
        $stageCounts = $this->filteredQuery(array_diff_key($state, ['stage' => true]))
            ->toBase()
            ->selectRaw('stage, COUNT(*) as aggregate')
            ->groupBy('stage')
            ->pluck('aggregate', 'stage')
            ->map(fn ($count) => (int) $count);

        // Headline KPIs are company/branch-wide, unaffected by list filters.
        $kpis = $this->filteredQuery([])
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN stage NOT IN ('hired', 'rejected') THEN 1 ELSE 0 END) as in_pipeline")
            ->selectRaw("SUM(CASE WHEN stage = 'interview' THEN 1 ELSE 0 END) as interviewing")
            ->selectRaw("SUM(CASE WHEN stage = 'hired' THEN 1 ELSE 0 END) as hired")
            ->selectRaw('SUM(CASE WHEN applied_date >= ? THEN 1 ELSE 0 END) as this_month', [now()->startOfMonth()->toDateString()])
            ->first();

        return view('admin.hrm.recruitment.candidates.index', [
            'candidates' => $candidates,
            'stageCounts' => $stageCounts->put('all', (int) $stageCounts->sum()),
            'kpis' => array_map('intval', (array) $kpis),
            'sources' => $this->sourceOptions(),
            'jobPositions' => $this->jobPositionOptions(),
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function pipeline(Request $request): View
    {
        $this->authorize('viewAny', Candidate::class);

        $candidates = $this->filteredQuery($request->query())
            ->with('jobPosition')
            ->withCount('interviews')
            ->orderByDesc('applied_date')
            ->get()
            ->groupBy('stage');

        return view('admin.hrm.recruitment.candidates.pipeline', [
            'grouped' => $candidates,
            'state' => $request->query(),
            'jobPositions' => $this->jobPositionOptions(),
            'sources' => $this->sourceOptions(),
        ]);
    }

    /**
     * Move a candidate to another stage from the pipeline board (drag-and-drop
     * via fetch, or the card's "Move to" menu as a plain form post).
     */
    public function updateStage(Request $request, Candidate $candidate): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $candidate);

        // CandidatePolicy only checks the permission, not ownership - make
        // sure this candidate belongs to the current company (and branch).
        abort_unless((int) $candidate->company_id === (int) Company::current()->id, 404);
        abort_if(BranchContext::isRestricted() && (int) $candidate->branch_id !== (int) BranchContext::currentBranchId(), 404);

        $data = $request->validate(['stage' => ['required', Rule::in(Candidate::STAGES)]]);
        $candidate->update(['stage' => $data['stage']]);

        $message = "{$candidate->name} moved to ".Str::headline($data['stage']).'.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'stage' => $candidate->stage])
            : back()->with('status', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Candidate::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'applied_date';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'applied_date';
        $direction = ($state['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $candidates = $this->filteredQuery($state)->with('jobPosition')->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'candidates-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Email', 'Phone', 'Applied For', 'Source', 'Stage', 'Applied Date'],
            (function () use ($candidates) {
                foreach ($candidates as $candidate) {
                    yield [
                        $candidate->name, $candidate->email, $candidate->phone,
                        $candidate->jobPosition?->title, $candidate->source, $candidate->stage,
                        optional($candidate->applied_date)->format('Y-m-d'),
                    ];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return Candidate::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($state['stage'] ?? null, fn ($q, $stage) => $q->where('stage', $stage))
            ->when($state['job_position_id'] ?? null, fn ($q, $jobPositionId) => $q->where('job_position_id', $jobPositionId))
            ->when($state['source'] ?? null, fn ($q, $source) => $q->where('source', $source))
            ->when($state['applied_from'] ?? null, fn ($q, $date) => $q->whereDate('applied_date', '>=', $date))
            ->when($state['applied_until'] ?? null, fn ($q, $date) => $q->whereDate('applied_date', '<=', $date));
    }

    /** Sources already used on candidates, for the filter and form suggestions. */
    protected function sourceOptions()
    {
        return Candidate::query()
            ->where('company_id', Company::current()->id)
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');
    }

    public function create(): View
    {
        $this->authorize('create', Candidate::class);

        return view('admin.hrm.recruitment.candidates.create', [
            'candidate' => new Candidate(),
            'jobPositions' => $this->jobPositionOptions(),
            'sources' => $this->sourceOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Candidate::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;
        $data['branch_id'] = BranchContext::currentBranchId();

        if ($request->hasFile('resume')) {
            $data['resume_path'] = $request->file('resume')->store('candidate-resumes', 'public');
        }

        Candidate::create($data);

        return redirect()->route('admin.candidates.index')->with('status', 'Candidate added.');
    }

    public function edit(Candidate $candidate): View
    {
        $this->authorize('update', $candidate);

        return view('admin.hrm.recruitment.candidates.edit', [
            'candidate' => $candidate,
            'jobPositions' => $this->jobPositionOptions(),
            'sources' => $this->sourceOptions(),
        ]);
    }

    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorize('update', $candidate);

        $data = $this->validated($request, $candidate);

        if ($request->hasFile('resume')) {
            $data['resume_path'] = $request->file('resume')->store('candidate-resumes', 'public');
        }

        $candidate->update($data);

        return redirect()->route('admin.candidates.index')->with('status', 'Candidate updated.');
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        $this->authorize('delete', $candidate);

        $candidate->delete();

        return redirect()->route('admin.candidates.index')->with('status', 'Candidate deleted.');
    }

    protected function jobPositionOptions()
    {
        return JobPosition::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    protected function validated(Request $request, ?Candidate $candidate = null): array
    {
        $companyId = Company::current()->id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_position_id' => ['nullable', Rule::exists('job_positions', 'id')->where('company_id', $companyId)],
            'source' => ['nullable', 'string', 'max:255'],
            'stage' => ['required', Rule::in(Candidate::STAGES)],
            'applied_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'resume' => ['nullable', 'file', 'max:10240'],
        ]);

        unset($data['resume']);

        return $data;
    }
}
