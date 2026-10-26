<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ResumeBankEntry;
use App\Support\BranchContext;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResumeBankController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['name', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', ResumeBankEntry::class);

        $state = $this->resolveTableState($request, 'resume-bank', [
            'sort' => 'created_at', 'direction' => 'desc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'created_at', self::SORTABLE, true) ? $state['sort'] : 'created_at';
        $direction = ($state['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $entries = $this->filteredQuery($state)
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.hrm.recruitment.resume-bank.index', [
            'entries' => $entries,
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', ResumeBankEntry::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'created_at';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'created_at';
        $direction = ($state['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $entries = $this->filteredQuery($state)->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'resume-bank-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Email', 'Phone', 'Skills', 'Source'],
            (function () use ($entries) {
                foreach ($entries as $entry) {
                    yield [$entry->name, $entry->email, $entry->phone, $entry->skills, $entry->source];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return ResumeBankEntry::query()
            ->where('company_id', Company::current()->id)
            ->when(BranchContext::isRestricted(), fn ($q) => $q->where('branch_id', BranchContext::currentBranchId()))
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('skills', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }));
    }

    public function create(): View
    {
        $this->authorize('create', ResumeBankEntry::class);

        return view('admin.hrm.recruitment.resume-bank.create', ['entry' => new ResumeBankEntry()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ResumeBankEntry::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;
        $data['branch_id'] = BranchContext::currentBranchId();

        if ($request->hasFile('resume')) {
            $data['resume_path'] = $request->file('resume')->store('resume-bank', 'public');
        }

        ResumeBankEntry::create($data);

        return redirect()->route('admin.resume-bank.index')->with('status', 'Resume added.');
    }

    public function edit(ResumeBankEntry $resumeBankEntry): View
    {
        $this->authorize('update', $resumeBankEntry);

        return view('admin.hrm.recruitment.resume-bank.edit', ['entry' => $resumeBankEntry]);
    }

    public function update(Request $request, ResumeBankEntry $resumeBankEntry): RedirectResponse
    {
        $this->authorize('update', $resumeBankEntry);

        $data = $this->validated($request);

        if ($request->hasFile('resume')) {
            $data['resume_path'] = $request->file('resume')->store('resume-bank', 'public');
        }

        $resumeBankEntry->update($data);

        return redirect()->route('admin.resume-bank.index')->with('status', 'Resume updated.');
    }

    public function destroy(ResumeBankEntry $resumeBankEntry): RedirectResponse
    {
        $this->authorize('delete', $resumeBankEntry);

        $resumeBankEntry->delete();

        return redirect()->route('admin.resume-bank.index')->with('status', 'Resume removed.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'skills' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'resume' => ['nullable', 'file', 'max:10240'],
        ]);

        unset($data['resume']);

        return $data;
    }
}
