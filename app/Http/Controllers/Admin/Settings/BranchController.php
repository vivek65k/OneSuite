<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Concerns\ExportsCsv;
use App\Http\Concerns\PersistsTableState;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BranchController extends Controller
{
    use ExportsCsv, PersistsTableState;

    protected const SORTABLE = ['name', 'code', 'city', 'is_default', 'is_active', 'created_at'];

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Branch::class);

        $state = $this->resolveTableState($request, 'branches', [
            'sort' => 'name', 'direction' => 'asc', 'per_page' => 25,
        ]);

        if ($state instanceof RedirectResponse) {
            return $state;
        }

        $sort = in_array($state['sort'] ?? 'name', self::SORTABLE, true) ? $state['sort'] : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $this->clampPerPage($state['per_page'] ?? 25);

        $branches = $this->filteredQuery($state)
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.settings.organization.branches.index', [
            'branches' => $branches,
            'state' => $state,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Branch::class);

        $state = $request->query();
        $requestedSort = $state['sort'] ?? 'name';
        $sort = in_array($requestedSort, self::SORTABLE, true) ? $requestedSort : 'name';
        $direction = ($state['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $branches = $this->filteredQuery($state)->orderBy($sort, $direction)->lazy(500);

        return $this->streamCsv(
            'branches-'.now()->format('Y-m-d-His').'.csv',
            ['Name', 'Code', 'Phone', 'Email', 'City', 'Default', 'Active'],
            (function () use ($branches) {
                foreach ($branches as $branch) {
                    yield [
                        $branch->name,
                        $branch->code,
                        $branch->phone,
                        $branch->email,
                        $branch->city,
                        $branch->is_default ? 'Yes' : 'No',
                        $branch->is_active ? 'Yes' : 'No',
                    ];
                }
            })()
        );
    }

    protected function filteredQuery(array $state): Builder
    {
        return Branch::query()
            ->where('company_id', Company::current()->id)
            ->when($state['search'] ?? null, fn ($q, $search) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            }))
            ->when(($state['is_active'] ?? '') !== '', fn ($q) => $q->where('is_active', $state['is_active']));
    }

    public function create(): View
    {
        $this->authorize('create', Branch::class);

        return view('admin.settings.organization.branches.create', ['branch' => new Branch()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Branch::class);

        $data = $this->validated($request);
        $data['company_id'] = Company::current()->id;

        Branch::create($data);

        return redirect()->route('admin.branches.index')->with('status', 'Branch created.');
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('update', $branch);

        return view('admin.settings.organization.branches.edit', ['branch' => $branch]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $branch);

        $branch->update($this->validated($request));

        return redirect()->route('admin.branches.index')->with('status', 'Branch updated.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $branch->delete();

        return redirect()->route('admin.branches.index')->with('status', 'Branch deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
