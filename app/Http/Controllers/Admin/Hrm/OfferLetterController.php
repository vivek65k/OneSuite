<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosition;
use App\Models\OfferLetter;
use App\Support\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfferLetterController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['status', 'offer_date', 'salary_offered', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', OfferLetter::class);

        $state = $this->resolveTableState($request, 'offer-letters', [
            'sort' => 'offer_date', 'direction' => 'desc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'offer_date', self::SORTABLE, true) ? $state['sort'] : 'offer_date';
        $direction = ($state['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $offers = $this->filteredQuery($state)
            ->with(['candidate', 'jobPosition'])
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
        $all = $this->filteredQuery([])->toBase();
        $accepted = (clone $all)->where('status', 'accepted')->count();
        $declined = (clone $all)->where('status', 'rejected')->count();
        $kpis = [
            'sent' => (clone $all)->where('status', 'sent')->count(),
            'accepted' => $accepted,
            'acceptance_rate' => ($accepted + $declined) ? round($accepted / ($accepted + $declined) * 100) : null,
            'joining_soon' => (clone $all)->where('status', 'accepted')->whereBetween('joining_date', [now()->toDateString(), now()->addDays(30)->toDateString()])->count(),
            'drafts' => (clone $all)->where('status', 'draft')->count(),
        ];

        return view('admin.hrm.recruitment.offer-letters.index', [
            'offers' => $offers,
            'statusCounts' => $statusCounts->put('all', (int) $statusCounts->sum()),
            'kpis' => $kpis,
            'jobPositions' => $this->jobPositionOptions(),
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', OfferLetter::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'offer_date';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'offer_date';
        $direction = ($state['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $offers = $this->filteredQuery($state)->with(['candidate', 'jobPosition'])->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'offer-letters-'.now()->format('Y-m-d-His').'.csv',
            ['Candidate', 'Position', 'Salary Offered', 'Status', 'Offer Date', 'Joining Date'],
            (function () use ($offers) {
                foreach ($offers as $offer) {
                    yield [
                        $offer->candidate?->name, $offer->jobPosition?->title,
                        $offer->salary_offered.' '.$offer->currency, $offer->status,
                        optional($offer->offer_date)->format('Y-m-d'), optional($offer->joining_date)->format('Y-m-d'),
                    ];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return OfferLetter::query()
            ->whereHas('candidate', fn ($q) => $q->where('company_id', Company::current()->id)
                ->when(BranchContext::isRestricted(), fn ($q2) => $q2->where('branch_id', BranchContext::currentBranchId())))
            ->when($state['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($state['candidate_id'] ?? null, fn ($q, $candidateId) => $q->where('candidate_id', $candidateId))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->whereHas('candidate', fn ($q2) => $q2->where(function ($q3) use ($search) {
                $q3->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            })))
            ->when($state['job_position_id'] ?? null, fn ($q, $jobId) => $q->where('job_position_id', $jobId))
            ->when($state['offer_from'] ?? null, fn ($q, $date) => $q->whereDate('offer_date', '>=', $date))
            ->when($state['offer_until'] ?? null, fn ($q, $date) => $q->whereDate('offer_date', '<=', $date))
            ->when($state['joining'] ?? null, fn ($q, $window) => match ($window) {
                'next_30' => $q->whereBetween('joining_date', [now()->toDateString(), now()->addDays(30)->toDateString()]),
                'overdue' => $q->where('status', 'accepted')->whereDate('joining_date', '<', now()->toDateString()),
                default => $q,
            });
    }

    /**
     * Quick status change from the list's row menu (e.g. "Mark accepted").
     */
    public function updateStatus(Request $request, OfferLetter $offerLetter): RedirectResponse
    {
        $this->authorize('update', $offerLetter);

        // The offer's policy only checks the permission - confirm the offer's
        // candidate belongs to the current company (and branch).
        $candidate = $offerLetter->candidate;
        abort_unless($candidate && (int) $candidate->company_id === (int) Company::current()->id, 404);
        abort_if(BranchContext::isRestricted() && (int) $candidate->branch_id !== (int) BranchContext::currentBranchId(), 404);

        $data = $request->validate(['status' => ['required', Rule::in(OfferLetter::STATUSES)]]);
        $offerLetter->update(['status' => $data['status']]);

        return back()->with('status', "Offer for {$candidate->name} marked as ".Str::headline($data['status']).'.');
    }

    /**
     * Applied-for position / stage / email per candidate id, for the form's
     * live candidate card and position prefill.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function candidateDetails(): array
    {
        return Candidate::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->with('jobPosition:id,title,min_salary,max_salary')
            ->get(['id', 'job_position_id', 'stage', 'email'])
            ->mapWithKeys(fn ($candidate) => [$candidate->id => [
                'job_position_id' => $candidate->job_position_id,
                'job' => $candidate->jobPosition?->title,
                'min_salary' => $candidate->jobPosition?->min_salary,
                'max_salary' => $candidate->jobPosition?->max_salary,
                'stage' => $candidate->stage,
                'email' => $candidate->email,
            ]])
            ->all();
    }

    public function create(): View
    {
        $this->authorize('create', OfferLetter::class);

        // ?candidate_id= preselects the candidate, only if it's one this user
        // may pick; the company's currency is the default.
        $offer = new OfferLetter(['currency' => Company::current()->currency]);
        $candidates = $this->candidateOptions();
        if ($candidates->has((int) request('candidate_id'))) {
            $offer->candidate_id = (int) request('candidate_id');
        }

        return view('admin.hrm.recruitment.offer-letters.create', [
            'offer' => $offer,
            'candidates' => $candidates,
            'jobPositions' => $this->jobPositionOptions(),
            'candidateDetails' => $this->candidateDetails(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', OfferLetter::class);

        OfferLetter::create($this->validated($request));

        return redirect()->route('admin.offer-letters.index')->with('status', 'Offer created.');
    }

    public function edit(OfferLetter $offerLetter): View
    {
        $this->authorize('update', $offerLetter);

        return view('admin.hrm.recruitment.offer-letters.edit', [
            'offer' => $offerLetter,
            'candidates' => $this->candidateOptions(),
            'jobPositions' => $this->jobPositionOptions(),
            'candidateDetails' => $this->candidateDetails(),
        ]);
    }

    public function update(Request $request, OfferLetter $offerLetter): RedirectResponse
    {
        $this->authorize('update', $offerLetter);

        $offerLetter->update($this->validated($request));

        return redirect()->route('admin.offer-letters.index')->with('status', 'Offer updated.');
    }

    public function destroy(OfferLetter $offerLetter): RedirectResponse
    {
        $this->authorize('delete', $offerLetter);

        $offerLetter->delete();

        return redirect()->route('admin.offer-letters.index')->with('status', 'Offer removed.');
    }

    protected function candidateOptions()
    {
        return Candidate::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function jobPositionOptions()
    {
        return JobPosition::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    protected function validated(Request $request): array
    {
        $companyId = Company::current()->id;

        return $request->validate([
            'candidate_id' => [
                'required',
                Rule::exists('candidates', 'id')->where('company_id', $companyId)
                    ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId())),
            ],
            'job_position_id' => ['nullable', Rule::exists('job_positions', 'id')->where('company_id', $companyId)],
            'salary_offered' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['required', Rule::in(OfferLetter::STATUSES)],
            'offer_date' => ['nullable', 'date'],
            'joining_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
